import { Global, Inject, Logger, Module, OnApplicationShutdown } from '@nestjs/common';
import Redis from 'ioredis';
import { ENV, Env } from '../config/env';
import { LaravelCache } from './laravel-cache';
import { REDIS } from './redis.token';

class RedisLifecycle implements OnApplicationShutdown {
  constructor(@Inject(REDIS) private readonly redis: Redis | null) {}

  async onApplicationShutdown(): Promise<void> {
    if (!this.redis) return;
    try {
      await this.redis.quit();
    } catch {
      this.redis.disconnect(); // si no estaba conectado, quit() falla: se corta sin más
    }
  }
}

@Global()
@Module({
  providers: [
    {
      provide: REDIS,
      inject: [ENV],
      useFactory: (env: Env): Redis | null => {
        if (env.REDIS_HOST === undefined) return null;

        const logger = new Logger('Redis');
        const redis = new Redis({
          host: env.REDIS_HOST,
          port: env.REDIS_PORT,
          username: env.REDIS_USERNAME,
          password: env.REDIS_PASSWORD,
          db: env.REDIS_CACHE_DB, // la base de la conexión `cache` de Laravel
          lazyConnect: true,
          connectTimeout: 2000,
          // Falla rápido: un Redis caído no debe colgar /health ni las escrituras (la invalidación es "mejor esfuerzo").
          commandTimeout: 2000,
          maxRetriesPerRequest: 1,
          retryStrategy: (intentos) => Math.min(intentos * 200, 2000), // reintenta en segundo plano
        });
        // Sin este listener, ioredis registra cada error de conexión como "Unhandled error event".
        let avisado = false;
        redis.on('error', (e) => {
          if (!avisado) logger.warn(`Redis no responde (${env.REDIS_HOST}:${env.REDIS_PORT}): ${e.message}`);
          avisado = true;
        });
        redis.on('ready', () => {
          avisado = false;
        });
        void redis.connect().catch(() => undefined); // no bloquea el arranque; reintenta solo
        return redis;
      },
    },
    LaravelCache,
    RedisLifecycle,
  ],
  exports: [REDIS, LaravelCache],
})
export class RedisModule {}
