import type { INestApplication } from '@nestjs/common';
import { Test } from '@nestjs/testing';
import Redis from 'ioredis';
import request from 'supertest';
import { DATABASE_URL, Fixtures, TenantCreado } from './helpers/fixtures';

// Debe fijarse ANTES de importar AppModule: ConfigModule lee process.env al compilar el módulo.
const REDIS_HOST = process.env.TEST_REDIS_HOST ?? '127.0.0.1';
const REDIS_PORT = process.env.TEST_REDIS_PORT ?? '6379';
process.env.DATABASE_URL = DATABASE_URL;

import { AppModule } from '../src/app.module';
import { RELOJ } from '../src/auth/auth-cache';

async function crearApp(opciones: { limite: number; redis: boolean; redisPuerto?: string; reloj?: () => number }): Promise<INestApplication> {
  process.env.RATE_LIMIT_PER_MINUTE = String(opciones.limite);
  if (opciones.redis) {
    process.env.REDIS_HOST = REDIS_HOST;
    process.env.REDIS_PORT = opciones.redisPuerto ?? REDIS_PORT;
    process.env.REDIS_CACHE_DB = '15'; // nunca la 1: ahí vive la caché real de Laravel
    process.env.REDIS_PREFIX = 'tsprobe_database_';
    process.env.CACHE_PREFIX = 'tsprobe_cache_';
  } else {
    delete process.env.REDIS_HOST;
  }
  const b = Test.createTestingModule({ imports: [AppModule] });
  if (opciones.reloj) b.overrideProvider(RELOJ).useValue(opciones.reloj);
  const app = (await b.compile()).createNestApplication();
  await app.listen(0);
  return app;
}

describe('API · limitación de peticiones (e2e)', () => {
  const fx = new Fixtures();
  let colegioA: TenantCreado;
  let colegioB: TenantCreado;
  const token: Record<string, string> = {};
  const apps: INestApplication[] = [];
  let sonda: Redis;

  const lista = (app: INestApplication, bearer: string) =>
    request(app.getHttpServer()).get('/api/v1/estudiantes?perPage=1').set('Authorization', `Bearer ${bearer}`);
  const nueva = async (o: Parameters<typeof crearApp>[0]) => {
    const a = await crearApp(o);
    apps.push(a);
    return a;
  };

  beforeAll(async () => {
    colegioA = await fx.crearTenant('rla');
    colegioB = await fx.crearTenant('rlb');
    await fx.crearEstudiante(colegioA.id, 'Rita', 'Limite', 0);
    token.a1 = (await fx.crearToken(await fx.crearUsuario(colegioA.id, 'rl1', 'Administrador'))).bearer;
    token.a2 = (await fx.crearToken(await fx.crearUsuario(colegioA.id, 'rl2', 'Administrador'))).bearer;
    token.sinPermiso = (await fx.crearToken(await fx.crearUsuario(colegioA.id, 'rl3', 'Estudiante'))).bearer;
    token.b1 = (await fx.crearToken(await fx.crearUsuario(colegioB.id, 'rl4', 'Administrador'))).bearer;
    sonda = new Redis({ host: REDIS_HOST, port: Number(REDIS_PORT), db: 15, lazyConnect: true, maxRetriesPerRequest: 1 });
    await sonda.connect();
  }, 60000);

  afterAll(async () => {
    await Promise.all(apps.map((a) => a.close()));
    const claves = await sonda.keys('tsapi:ratelimit:*');
    if (claves.length) await sonda.del(...claves);
    sonda.disconnect();
    await fx.limpiar();
  });

  describe('en memoria (sin Redis)', () => {
    let app: INestApplication;
    let ahora = Date.now();
    beforeAll(async () => {
      app = await nueva({ limite: 3, redis: false, reloj: () => ahora });
    });

    it('rechaza con 429 a la petición que supera el límite, con Retry-After y cabeceras X-RateLimit', async () => {
      const r1 = await lista(app, token.a1).expect(200);
      expect(r1.headers['x-ratelimit-limit']).toBe('3');
      expect(r1.headers['x-ratelimit-remaining']).toBe('2');
      await lista(app, token.a1).expect(200);
      await lista(app, token.a1).expect(200);
      const r4 = await lista(app, token.a1).expect(429);
      expect(r4.headers['retry-after']).toBeDefined();
      expect(Number(r4.headers['retry-after'])).toBeGreaterThanOrEqual(1);
      expect(r4.headers['x-ratelimit-remaining']).toBe('0');
      expect(r4.body.statusCode).toBe(429);
    });

    it('el cupo es por usuario: otro usuario del mismo colegio y uno de otro colegio no se ven afectados', async () => {
      await lista(app, token.a2).expect(200);
      await lista(app, token.b1).expect(200);
    });

    it('los rechazos por permiso (403) también cuentan, como en Laravel', async () => {
      for (let i = 0; i < 3; i++) await lista(app, token.sinPermiso).expect(403);
      await lista(app, token.sinPermiso).expect(429);
    });

    it('/health es público y nunca se limita', async () => {
      for (let i = 0; i < 10; i++) await request(app.getHttpServer()).get('/health').expect(200);
    });

    it('un token inválido responde 401 (no 429): no hay usuario al que contar', async () => {
      for (let i = 0; i < 6; i++) await lista(app, '999999|noexiste').expect(401);
    });

    it('al vencer la ventana el usuario vuelve a poder pedir', async () => {
      await lista(app, token.a1).expect(429); // sigue bloqueado dentro del minuto
      ahora += 61_000;
      await lista(app, token.a1).expect(200);
    });
  });

  it('RATE_LIMIT_PER_MINUTE=0 desactiva el límite', async () => {
    const app = await nueva({ limite: 0, redis: false });
    for (let i = 0; i < 8; i++) await lista(app, token.a1).expect(200);
  });

  describe('con Redis: el contador se comparte entre instancias de la API', () => {
    it('dos instancias suman sobre el mismo cupo (límite 4: 2 + 2 permitidas, la quinta falla en cualquiera)', async () => {
      const x = await nueva({ limite: 4, redis: true });
      const y = await nueva({ limite: 4, redis: true });
      await lista(x, token.a1).expect(200);
      await lista(y, token.a1).expect(200);
      await lista(x, token.a1).expect(200);
      await lista(y, token.a1).expect(200);
      await lista(x, token.a1).expect(429);
      await lista(y, token.a1).expect(429);
      // la clave física está en el espacio de esta API, no en el de Laravel
      expect((await sonda.keys('tsapi:ratelimit:u:*')).length).toBeGreaterThan(0);
    });

    it('si Redis no responde, sigue limitando con la memoria del proceso (no tumba la API)', async () => {
      const app = await nueva({ limite: 2, redis: true, redisPuerto: '1' }); // puerto 1: nadie escucha
      await lista(app, token.a2).expect(200);
      await lista(app, token.a2).expect(200);
      await lista(app, token.a2).expect(429);
    }, 30000);
  });
});
