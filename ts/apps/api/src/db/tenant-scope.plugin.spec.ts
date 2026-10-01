import { DummyDriver, Kysely, MysqlAdapter, MysqlIntrospector, MysqlQueryCompiler, sql } from 'kysely';
import type { DB } from './db';
import {
  TenantContextMissingError,
  TenantInsertWithoutTenantError,
  TenantScopePlugin,
} from './tenant-scope.plugin';

// Tablas reales: estudiantes, matriculas, grupos tienen tenant_id; tenants y roles no.
const TABLAS_TENANT = new Set(['estudiantes', 'matriculas', 'grupos', 'asistencias']);

function crearDb(getTenantId: () => number | undefined): Kysely<DB> {
  return new Kysely<DB>({
    dialect: {
      createAdapter: () => new MysqlAdapter(),
      createDriver: () => new DummyDriver(),
      createIntrospector: (db) => new MysqlIntrospector(db),
      createQueryCompiler: () => new MysqlQueryCompiler(),
    },
    plugins: [new TenantScopePlugin(TABLAS_TENANT, getTenantId)],
  });
}

describe('TenantScopePlugin', () => {
  let tenant: number | undefined;
  let db: Kysely<DB>;

  beforeEach(() => {
    tenant = 7;
    db = crearDb(() => tenant);
  });

  describe('SELECT', () => {
    it('agrega tenant_id a una tabla de tenant', () => {
      const { sql: q, parameters } = db.selectFrom('estudiantes').select('id').compile();
      expect(q).toBe('select `id` from `estudiantes` where `estudiantes`.`tenant_id` = ?');
      expect(parameters).toEqual([7]);
    });

    it('combina con filtros existentes con AND (no los reemplaza)', () => {
      const { sql: q, parameters } = db
        .selectFrom('estudiantes')
        .select('id')
        .where('estado', '=', 'activo')
        .compile();
      expect(q).toContain('`estado` = ?');
      expect(q).toContain('`estudiantes`.`tenant_id` = ?');
      expect(q).toMatch(/where .* and .*/);
      expect(parameters).toContain(7);
      expect(parameters).toContain('activo');
    });

    it('no toca tablas sin tenant_id', () => {
      const { sql: q, parameters } = db.selectFrom('tenants').select('id').compile();
      expect(q).toBe('select `id` from `tenants`');
      expect(parameters).toEqual([]);
    });

    it('usa el alias de la tabla', () => {
      const { sql: q } = db.selectFrom('estudiantes as e').select('e.id').compile();
      expect(q).toContain('`e`.`tenant_id` = ?');
      expect(q).not.toContain('`estudiantes`.`tenant_id`');
    });

    it('lee el tenant en cada consulta (no lo congela)', () => {
      tenant = 1;
      expect(db.selectFrom('estudiantes').select('id').compile().parameters).toEqual([1]);
      tenant = 2;
      expect(db.selectFrom('estudiantes').select('id').compile().parameters).toEqual([2]);
    });

    it('filtra el lado tenant de un JOIN en el ON (conserva el LEFT JOIN)', () => {
      const { sql: q, parameters } = db
        .selectFrom('estudiantes as e')
        .leftJoin('matriculas as m', 'm.estudiante_id', 'e.id')
        .select(['e.id', 'm.id as matriculaId'])
        .compile();
      expect(q).toMatch(/left join `matriculas` as `m` on .*`m`\.`tenant_id` = \?/);
      expect(q).toContain('`e`.`tenant_id` = ?');
      expect(parameters).toEqual([7, 7].length === parameters.length ? parameters : [7, 7]);
      expect(parameters.filter((p) => p === 7)).toHaveLength(2);
    });

    it('no agrega filtro de tenant al JOIN con una tabla sin tenant_id', () => {
      const { sql: q } = db
        .selectFrom('estudiantes as e')
        .innerJoin('tenants as t', 't.id', 'e.tenant_id')
        .select('e.id')
        .compile();
      expect(q).not.toContain('`t`.`tenant_id`');
    });

    it('filtra también las subconsultas', () => {
      const { sql: q, parameters } = db
        .selectFrom('estudiantes')
        .select('id')
        .where('id', 'in', db.selectFrom('matriculas').select('estudiante_id'))
        .compile();
      expect(q).toContain('`matriculas`.`tenant_id` = ?');
      expect(q).toContain('`estudiantes`.`tenant_id` = ?');
      expect(parameters.filter((p) => p === 7)).toHaveLength(2);
    });
  });

  describe('UPDATE / DELETE', () => {
    it('UPDATE agrega tenant_id al WHERE', () => {
      const { sql: q, parameters } = db
        .updateTable('estudiantes')
        .set({ estado: 'inactivo' })
        .where('id', '=', 5)
        .compile();
      expect(q).toContain('`estudiantes`.`tenant_id` = ?');
      expect(parameters).toContain(7);
    });

    it('DELETE agrega tenant_id al WHERE', () => {
      const { sql: q, parameters } = db.deleteFrom('estudiantes').where('id', '=', 5).compile();
      expect(q).toContain('`estudiantes`.`tenant_id` = ?');
      expect(parameters).toContain(7);
    });
  });

  describe('INSERT', () => {
    it('rechaza un INSERT en tabla de tenant sin tenant_id', () => {
      const q = db.insertInto('grupos').values({ school_year_id: 1, grado_id: 1, seccion_id: 1 });
      expect(() => q.compile()).toThrow(TenantInsertWithoutTenantError);
    });

    it('acepta un INSERT que trae tenant_id', () => {
      const q = db.insertInto('grupos').values({ school_year_id: 1, grado_id: 1, seccion_id: 1, tenant_id: 7 });
      expect(() => q.compile()).not.toThrow();
    });

    it('no exige tenant_id en tablas sin esa columna', () => {
      const q = db.insertInto('roles').values({ name: 'x', guard_name: 'web' });
      expect(() => q.compile()).not.toThrow();
    });
  });

  describe('fail closed (sin tenant en contexto)', () => {
    it('lanza si se consulta una tabla de tenant sin contexto', () => {
      tenant = undefined;
      expect(() => db.selectFrom('estudiantes').select('id').compile()).toThrow(TenantContextMissingError);
    });

    it('lanza en UPDATE y DELETE sin contexto', () => {
      tenant = undefined;
      expect(() => db.updateTable('estudiantes').set({ estado: 'inactivo' }).compile()).toThrow(TenantContextMissingError);
      expect(() => db.deleteFrom('estudiantes').compile()).toThrow(TenantContextMissingError);
    });

    it('permite consultar tablas SIN tenant_id sin contexto (autenticación, roles)', () => {
      tenant = undefined;
      expect(() => db.selectFrom('tenants').select('id').compile()).not.toThrow();
      expect(() => db.selectFrom('roles').select('id').compile()).not.toThrow();
    });

    it('el SQL crudo no se toca (por eso SYSTEM_DB y el SQL crudo deben usarse con cuidado)', () => {
      tenant = undefined;
      expect(() => sql`select 1`.compile(db)).not.toThrow();
    });
  });
});
