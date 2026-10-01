import { Global, Inject, Module, OnApplicationShutdown } from '@nestjs/common';
import { Kysely, MysqlDialect, sql } from 'kysely';
import { ClsService } from 'nestjs-cls';
import { createPool, Pool } from 'mysql2';
import { ENV, Env } from '../config/env';
import type { DB as Schema } from './db';
import { TenantScopePlugin } from './tenant-scope.plugin';
import type { TenantStore } from '../tenancy/tenant.store';

export type Database = Kysely<Schema>;

/** Conexión con filtro de tenant AUTOMÁTICO y fail-closed. Es la que usan los módulos de negocio. */
export const DB = Symbol('DB');
/** Conexión SIN filtro de tenant. Solo para autenticación, resolución de tenant y plataforma. */
export const SYSTEM_DB = Symbol('SYSTEM_DB');
/** Tablas que tienen columna tenant_id (se leen de information_schema al arrancar). */
export const TENANT_TABLES = Symbol('TENANT_TABLES');
const MYSQL_POOL = Symbol('MYSQL_POOL');

export async function loadTenantTables(db: Database): Promise<ReadonlySet<string>> {
  const { rows } = await sql<{ tabla: string }>`
    select table_name as tabla
    from information_schema.columns
    where table_schema = database() and column_name = 'tenant_id'
  `.execute(db);
  return new Set(rows.map((r) => r.tabla));
}

class DbLifecycle implements OnApplicationShutdown {
  constructor(@Inject(SYSTEM_DB) private readonly system: Database) {}

  async onApplicationShutdown(): Promise<void> {
    // Las dos conexiones comparten el mismo pool: basta con cerrar una.
    await this.system.destroy();
  }
}

@Global()
@Module({
  providers: [
    {
      provide: MYSQL_POOL,
      inject: [ENV],
      useFactory: (env: Env): Pool =>
        createPool({
          uri: env.DATABASE_URL,
          connectionLimit: 10,
          // Fechas como texto ('2026-09-30'), igual que PHP: evita desfases de zona horaria.
          dateStrings: true,
          supportBigNumbers: true,
          bigNumberStrings: false,
        }),
    },
    {
      provide: SYSTEM_DB,
      inject: [MYSQL_POOL],
      useFactory: (pool: Pool): Database => new Kysely<Schema>({ dialect: new MysqlDialect({ pool }) }),
    },
    {
      provide: TENANT_TABLES,
      inject: [SYSTEM_DB],
      useFactory: (system: Database) => loadTenantTables(system),
    },
    {
      provide: DB,
      inject: [MYSQL_POOL, TENANT_TABLES, ClsService],
      useFactory: (pool: Pool, tablas: ReadonlySet<string>, cls: ClsService<TenantStore>): Database =>
        new Kysely<Schema>({
          dialect: new MysqlDialect({ pool }),
          plugins: [new TenantScopePlugin(tablas, () => cls.get('tenantId'))],
        }),
    },
    DbLifecycle,
  ],
  exports: [DB, SYSTEM_DB, TENANT_TABLES],
})
export class DbModule {}
