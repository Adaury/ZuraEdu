import { Controller, Get, HttpCode, Inject } from '@nestjs/common';
import type { HealthResponse } from '@zuraedu/shared';
import { sql } from 'kysely';
import { Public } from '../auth/decorators';
import { Database, SYSTEM_DB } from '../db/db.module';

/**
 * Misma forma que el /health de Laravel ({status, checks, version}) para reutilizar el monitoreo.
 * Público y sin tenant: solo verifica la base de datos (Redis se agrega cuando haya colas en TS).
 */
@Controller('health')
export class HealthController {
  constructor(@Inject(SYSTEM_DB) private readonly db: Database) {}

  @Public()
  @Get()
  @HttpCode(200)
  async check(): Promise<HealthResponse> {
    let database = 'ok';
    try {
      await sql`select 1`.execute(this.db);
    } catch {
      database = 'error';
    }
    return {
      status: database === 'ok' ? 'ok' : 'degraded',
      checks: { database },
      version: '0.1',
      runtime: 'node',
    };
  }
}
