import { Controller, Get, HttpCode, Inject } from '@nestjs/common';
import type { HealthResponse } from '@zuraedu/shared';
import type Redis from 'ioredis';
import { sql } from 'kysely';
import { Public } from '../auth/decorators';
import { Database, SYSTEM_DB } from '../db/db.module';
import { REDIS } from '../redis/redis.token';

/**
 * Misma forma que el /health de Laravel ({status, checks, version}) para reutilizar el monitoreo.
 * Público y sin tenant: verifica la base de datos y, si hay REDIS_HOST, Redis ('omitido' si no está configurado;
 * si está configurado y no responde, el estado pasa a 'degraded').
 */
@Controller('health')
export class HealthController {
  constructor(
    @Inject(SYSTEM_DB) private readonly db: Database,
    @Inject(REDIS) private readonly redis: Redis | null,
  ) {}

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
    let redis = 'omitido';
    if (this.redis !== null) {
      try {
        redis = (await this.redis.ping()) === 'PONG' ? 'ok' : 'error';
      } catch {
        redis = 'error';
      }
    }
    return {
      status: database === 'ok' && redis !== 'error' ? 'ok' : 'degraded',
      checks: { database, redis },
      version: '0.1',
      runtime: 'node',
    };
  }
}
