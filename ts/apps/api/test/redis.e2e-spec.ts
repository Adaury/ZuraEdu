import { spawnSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { resolve } from 'node:path';
import type { INestApplication } from '@nestjs/common';
import { Test } from '@nestjs/testing';
import Redis from 'ioredis';
import request from 'supertest';
import { DATABASE_URL } from './helpers/fixtures';

// Debe fijarse ANTES de importar AppModule: ConfigModule lee process.env al compilar el módulo.
const REDIS_HOST = process.env.TEST_REDIS_HOST ?? '127.0.0.1';
const REDIS_PORT = process.env.TEST_REDIS_PORT ?? '6379';
const DB_PRUEBAS = '15'; // nunca la 1: ahí vive la caché real de Laravel
const REDIS_PREFIX = 'tsprobe_database_';
const CACHE_PREFIX = 'tsprobe_cache_';
process.env.DATABASE_URL = DATABASE_URL;

import { AppModule } from '../src/app.module';
import { LaravelCache } from '../src/redis/laravel-cache';

async function crearApp(redisPort: string | undefined): Promise<INestApplication> {
  if (redisPort === undefined) {
    delete process.env.REDIS_HOST;
  } else {
    process.env.REDIS_HOST = REDIS_HOST;
    process.env.REDIS_PORT = redisPort;
  }
  process.env.REDIS_CACHE_DB = DB_PRUEBAS;
  process.env.REDIS_PREFIX = REDIS_PREFIX;
  process.env.CACHE_PREFIX = CACHE_PREFIX;
  const moduleRef = await Test.createTestingModule({ imports: [AppModule] }).compile();
  const app = moduleRef.createNestApplication();
  await app.listen(0);
  return app;
}

describe('API · Redis (e2e, Redis real, base 15)', () => {
  const sufijo = Math.random().toString(36).slice(2, 10);
  let sonda: Redis; // cliente independiente de la API: crea y comprueba claves físicas directamente
  let app: INestApplication;
  const creadas: string[] = [];
  const fisica = (logica: string) => `${REDIS_PREFIX}${CACHE_PREFIX}${logica}`;
  const sembrar = async (logica: string) => {
    await sonda.set(fisica(logica), 's:1:"x";', 'EX', 120);
    creadas.push(fisica(logica));
  };

  beforeAll(async () => {
    sonda = new Redis({ host: REDIS_HOST, port: Number(REDIS_PORT), db: Number(DB_PRUEBAS), lazyConnect: true, maxRetriesPerRequest: 1 });
    await sonda.connect(); // si Redis no está, falla aquí con un mensaje claro
    app = await crearApp(REDIS_PORT);
  }, 60000);

  afterAll(async () => {
    if (creadas.length > 0) await sonda?.del(...creadas);
    await app?.close();
    sonda?.disconnect();
  });

  describe('/health', () => {
    it('con Redis configurado y vivo: checks.redis = ok, status ok', async () => {
      const r = await request(app.getHttpServer()).get('/health').expect(200);
      expect(r.body.checks).toEqual({ database: 'ok', redis: 'ok' });
      expect(r.body.status).toBe('ok');
    });

    it('sin REDIS_HOST: checks.redis = omitido y el estado sigue ok', async () => {
      const sinRedis = await crearApp(undefined);
      try {
        const r = await request(sinRedis.getHttpServer()).get('/health').expect(200);
        expect(r.body.checks.redis).toBe('omitido');
        expect(r.body.status).toBe('ok');
      } finally {
        await sinRedis.close();
      }
    });

    it('con Redis configurado pero caído: status degraded, checks.redis = error, y responde rápido (no se cuelga)', async () => {
      const caido = await crearApp('1'); // puerto 1: nadie escucha
      try {
        const t0 = Date.now();
        const r = await request(caido.getHttpServer()).get('/health').expect(200);
        expect(r.body.checks.redis).toBe('error');
        expect(r.body.checks.database).toBe('ok');
        expect(r.body.status).toBe('degraded');
        expect(Date.now() - t0).toBeLessThan(5000);
      } finally {
        await caido.close();
      }
    }, 20000);
  });

  describe('LaravelCache contra Redis real', () => {
    it('olvidar borra la clave con los DOS prefijos y deja intactas las demás', async () => {
      const cache = app.get(LaravelCache);
      await sembrar(`t1_${sufijo}_a`);
      await sembrar(`t1_${sufijo}_b`);
      expect(await cache.olvidar(`t1_${sufijo}_a`)).toBe(1);
      expect(await sonda.exists(fisica(`t1_${sufijo}_a`))).toBe(0);
      expect(await sonda.exists(fisica(`t1_${sufijo}_b`))).toBe(1);
      expect(await cache.olvidar(`t1_${sufijo}_a`)).toBe(0); // ya no existe
    });

    it('invalidar con la clave lógica "a secas" (sin prefijos) no toca la clave real: por eso se añaden los prefijos', async () => {
      await sembrar(`t1_${sufijo}_c`);
      await sonda.set(`t1_${sufijo}_c`, 'x', 'EX', 60);
      creadas.push(`t1_${sufijo}_c`);
      await app.get(LaravelCache).olvidar(`t1_${sufijo}_c`);
      expect(await sonda.exists(fisica(`t1_${sufijo}_c`))).toBe(0);
      expect(await sonda.exists(`t1_${sufijo}_c`)).toBe(1); // la clave sin prefijos no es de Laravel
    });

    it('olvidarPorPrefijo borra solo las claves del prefijo (incluso con más de un lote de SCAN)', async () => {
      const cache = app.get(LaravelCache);
      for (let i = 0; i < 450; i++) await sembrar(`t2_${sufijo}_dash_${i}`);
      await sembrar(`t2_${sufijo}_otra`);
      expect(await cache.olvidarPorPrefijo(`t2_${sufijo}_dash_`)).toBe(450);
      expect(await sonda.exists(fisica(`t2_${sufijo}_dash_0`))).toBe(0);
      expect(await sonda.exists(fisica(`t2_${sufijo}_otra`))).toBe(1);
    }, 30000);

    it('los metacaracteres glob del prefijo se toman literales (un "*" no borra todo)', async () => {
      await sembrar(`t3_${sufijo}_x1`);
      expect(await app.get(LaravelCache).olvidarPorPrefijo(`t3_${sufijo}_*`)).toBe(0);
      expect(await sonda.exists(fisica(`t3_${sufijo}_x1`))).toBe(1);
    });
  });

  // Prueba cruzada con el Laravel REAL: la clave que escribe PHP debe ser la que borra TypeScript.
  // Se salta (con aviso) si no hay PHP + vendor; en el CI no está PHP en el job ts, así que allí no corre.
  describe('prueba cruzada con Laravel real', () => {
    const raiz = resolve(__dirname, '../../../..');
    const php = process.env.PHP_BIN ?? 'php';
    const hayLaravel =
      existsSync(resolve(raiz, 'vendor/autoload.php')) && spawnSync(php, ['-v'], { encoding: 'utf8' }).status === 0;
    const prueba = hayLaravel ? it : it.skip;
    if (!hayLaravel) console.warn('Prueba cruzada con Laravel OMITIDA: no hay PHP (PHP_BIN) o falta vendor/.');

    prueba('una clave escrita con Cache::put de Laravel es borrada por LaravelCache.olvidar', async () => {
      const clave = `t9_cruzada_${sufijo}`;
      const entorno = {
        ...process.env,
        APP_ENV: 'testing',
        CACHE_STORE: 'redis',
        CACHE_DRIVER: 'redis',
        REDIS_CLIENT: 'predis',
        REDIS_HOST,
        REDIS_PORT,
        REDIS_PASSWORD: 'null',
        REDIS_CACHE_DB: DB_PRUEBAS,
        REDIS_PREFIX,
        CACHE_PREFIX,
      };
      const ejecutar = (codigo: string) =>
        spawnSync(php, ['artisan', 'tinker', `--execute=${codigo}`], { cwd: raiz, env: entorno, encoding: 'utf8' });

      const escribir = ejecutar(`Cache::put('${clave}', 'valor', 120); echo 'OK';`);
      expect(escribir.stdout).toContain('OK');
      creadas.push(fisica(clave));

      expect(await app.get(LaravelCache).existe(clave)).toBe(true); // TS ve la clave que escribió PHP
      expect(await app.get(LaravelCache).olvidar(clave)).toBe(1);

      const leer = ejecutar(`echo Cache::has('${clave}') ? 'SIGUE' : 'BORRADA';`);
      expect(leer.stdout).toContain('BORRADA'); // y PHP ya no la ve
    }, 60000);
  });
});
