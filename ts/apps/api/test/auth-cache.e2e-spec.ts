import type { INestApplication } from '@nestjs/common';
import { Test } from '@nestjs/testing';
import request from 'supertest';
import { DATABASE_URL, dormir, Fixtures, TenantCreado } from './helpers/fixtures';

// Debe fijarse ANTES de importar AppModule: ConfigModule lee process.env al compilar el módulo.
process.env.DATABASE_URL = DATABASE_URL;

import { AppModule } from '../src/app.module';
import { QUERY_COUNTER, QueryCounter } from '../src/db/query-counter';

async function crearApp(ttlSegundos: number): Promise<INestApplication> {
  process.env.AUTH_CACHE_TTL_SECONDS = String(ttlSegundos);
  const moduleRef = await Test.createTestingModule({ imports: [AppModule] }).compile();
  const app = moduleRef.createNestApplication();
  await app.listen(0); // un solo servidor escuchando: supertest no abre uno por petición
  return app;
}

/**
 * Caché de autenticación (sesión + permisos) contra MySQL real.
 * Verifica el ahorro de consultas Y el compromiso de seguridad: un cambio hecho en Laravel
 * (revocar token, quitar permiso, desactivar usuario) se nota como máximo al vencer el TTL.
 */
describe('API · caché de autenticación (e2e, MySQL real)', () => {
  const fx = new Fixtures();
  let colegio: TenantCreado; // colegio temporal propio (no depende de datos previos)
  let corta: INestApplication; // TTL 1 s
  let sinCache: INestApplication; // TTL 0
  let larga: INestApplication; // TTL 30 s

  const get = (app: INestApplication, bearer: string) =>
    request(app.getHttpServer()).get('/api/v1/estudiantes?perPage=5').set('Authorization', `Bearer ${bearer}`);
  const consultas = (app: INestApplication) => app.get<QueryCounter>(QUERY_COUNTER).total;

  beforeAll(async () => {
    colegio = await fx.crearTenant('cache');
    corta = await crearApp(1);
    sinCache = await crearApp(0);
    larga = await crearApp(30);
  }, 60000);

  afterAll(async () => {
    await Promise.all([corta?.close(), sinCache?.close(), larga?.close()]);
    await fx.limpiar();
  });

  describe('ahorro de consultas', () => {
    it('la 2ª petición hace solo las 2 consultas de datos (count + select)', async () => {
      const t = await fx.crearToken(await fx.crearUsuario(colegio.id, 'ahorro', 'Administrador'));

      const a0 = consultas(corta);
      await get(corta, t.bearer).expect(200);
      const primera = consultas(corta) - a0;

      const b0 = consultas(corta);
      await get(corta, t.bearer).expect(200);
      const segunda = consultas(corta) - b0;

      expect(segunda).toBe(2);
      expect(primera).toBeGreaterThanOrEqual(segunda + 5); // token, usuario, tenant, roles, permisos...
    });

    it('con TTL 0 cada petición vuelve a validar todo (comportamiento original)', async () => {
      const t = await fx.crearToken(await fx.crearUsuario(colegio.id, 'sincache', 'Administrador'));
      await get(sinCache, t.bearer).expect(200); // calienta solo la resolución de host

      const a0 = consultas(sinCache);
      await get(sinCache, t.bearer).expect(200);
      expect(consultas(sinCache) - a0).toBeGreaterThanOrEqual(7);
    });

    it('single-flight: 10 peticiones concurrentes en frío cargan la sesión UNA vez', async () => {
      const t = await fx.crearToken(await fx.crearUsuario(colegio.id, 'concurrente', 'Administrador'));

      const a0 = consultas(corta);
      const respuestas = await Promise.all(Array.from({ length: 10 }, () => get(corta, t.bearer)));
      respuestas.forEach((r) => expect(r.status).toBe(200));

      // Sin single-flight serían ~10 × 7 = 70. Con él: ~5 de auth + 10 × 2 de datos.
      expect(consultas(corta) - a0).toBeLessThanOrEqual(30);
    });
  });

  describe('seguridad: la revocación se nota como máximo al vencer el TTL', () => {
    it('un token revocado sigue valiendo dentro del TTL y se rechaza al vencer', async () => {
      const t = await fx.crearToken(await fx.crearUsuario(colegio.id, 'revocado', 'Administrador'));
      await get(corta, t.bearer).expect(200);

      await fx.pool.query('delete from personal_access_tokens where id = ?', [t.id]); // "logout" en Laravel
      await get(corta, t.bearer).expect(200); // compromiso documentado: aún dentro del TTL

      await dormir(1200);
      await get(corta, t.bearer).expect(401);
    });

    it('con TTL 0 la revocación es inmediata', async () => {
      const t = await fx.crearToken(await fx.crearUsuario(colegio.id, 'revocado0', 'Administrador'));
      await get(sinCache, t.bearer).expect(200);
      await fx.pool.query('delete from personal_access_tokens where id = ?', [t.id]);
      await get(sinCache, t.bearer).expect(401);
    });

    it('un permiso quitado se nota al vencer el TTL', async () => {
      const userId = await fx.crearUsuario(colegio.id, 'sinrol', 'Administrador');
      const t = await fx.crearToken(userId);
      await get(corta, t.bearer).expect(200);

      await fx.pool.query('delete from model_has_roles where model_id = ?', [userId]); // se le quita el rol
      await get(corta, t.bearer).expect(200); // dentro del TTL

      await dormir(1200);
      await get(corta, t.bearer).expect(403);
    });

    it('un usuario desactivado se nota al vencer el TTL', async () => {
      const userId = await fx.crearUsuario(colegio.id, 'desactivado', 'Administrador');
      const t = await fx.crearToken(userId);
      await get(corta, t.bearer).expect(200);

      await fx.pool.query('update users set activo = 0 where id = ?', [userId]);
      await get(corta, t.bearer).expect(200);

      await dormir(1200);
      await get(corta, t.bearer).expect(401);
    });

    it('la entrada cacheada nunca sobrevive al expires_at del token', async () => {
      // TTL 30 s, pero el token vence en 2 s: la entrada debe vencer con el token, no a los 30 s.
      const t = await fx.crearToken(await fx.crearUsuario(colegio.id, 'expira', 'Administrador'), 2);
      await get(larga, t.bearer).expect(200);

      await dormir(2400);
      await get(larga, t.bearer).expect(401);
    });
  });

  describe('seguridad: la clave de la caché', () => {
    it('un secreto equivocado con el id de un token cacheado NO acierta la caché', async () => {
      const t = await fx.crearToken(await fx.crearUsuario(colegio.id, 'secreto', 'Administrador'));
      await get(corta, t.bearer).expect(200); // queda cacheado

      await get(corta, `${t.id}|${'0'.repeat(40)}`).expect(401);
      await get(corta, `${t.id}|${t.plano}x`).expect(401); // casi correcto
      await get(corta, t.bearer).expect(200); // el verdadero sigue funcionando
    });

    it('los rechazos nunca se cachean: un token inválido repetido vuelve a la base cada vez', async () => {
      const a0 = consultas(corta);
      await get(corta, '999999|abc').expect(401);
      await get(corta, '999999|abc').expect(401);
      expect(consultas(corta) - a0).toBe(2); // 1 consulta por intento, ninguna evitada
    });

    it('dos usuarios no comparten permisos: el sin-permiso sigue recibiendo 403 aunque el admin esté cacheado', async () => {
      const admin = await fx.crearToken(await fx.crearUsuario(colegio.id, 'admin-c', 'Administrador'));
      const alumno = await fx.crearToken(await fx.crearUsuario(colegio.id, 'alumno-c', 'Estudiante'));

      await get(corta, admin.bearer).expect(200);
      await get(corta, alumno.bearer).expect(403);
      await get(corta, admin.bearer).expect(200);
      await get(corta, alumno.bearer).expect(403);
    });
  });
});
