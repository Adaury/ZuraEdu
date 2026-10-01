import type { INestApplication } from '@nestjs/common';
import { Test } from '@nestjs/testing';
import { RowDataPacket } from 'mysql2/promise';
import request from 'supertest';
import { DATABASE_URL, Fixtures, TenantCreado } from './helpers/fixtures';

// Debe fijarse ANTES de importar AppModule: ConfigModule lee process.env al crear el módulo.
process.env.DATABASE_URL = DATABASE_URL;
// Esta suite cambia datos y exige efecto inmediato: sin caché de autenticación (la caché tiene su propia suite).
process.env.AUTH_CACHE_TTL_SECONDS = '0';

import { AppModule } from '../src/app.module';

/**
 * E2E contra MySQL real. Crea DOS colegios temporales con sus propios usuarios, estudiantes y tokens y los
 * borra al terminar: no depende de datos previos (corre igual en sge_bench y en una base recién migrada en CI).
 */
describe('API · estudiantes (e2e, MySQL real)', () => {
  const fx = new Fixtures();
  let app: INestApplication;

  let colegioA: TenantCreado; // 40 estudiantes: 30 "Juan" y 10 "Maria"
  let colegioB: TenantCreado; // 2 estudiantes
  const token = { admin1: '', admin2: '', sinPermiso: '', inactivo: '' };
  const TOTAL_A = 40;

  beforeAll(async () => {
    colegioA = await fx.crearTenant('a');
    colegioB = await fx.crearTenant('b');

    for (let i = 0; i < TOTAL_A; i++) {
      await fx.crearEstudiante(colegioA.id, i < 30 ? 'Juan' : 'Maria', `Gomez${String(i).padStart(2, '0')}`, i);
    }
    await fx.crearEstudiante(colegioB.id, 'Zoe', 'Aislada0', 0);
    await fx.crearEstudiante(colegioB.id, 'Yara', 'Aislada1', 1);

    token.admin1 = (await fx.crearToken(await fx.crearUsuario(colegioA.id, 'admin1', 'Administrador'))).bearer;
    token.admin2 = (await fx.crearToken(await fx.crearUsuario(colegioB.id, 'admin2', 'Administrador'))).bearer;
    token.sinPermiso = (await fx.crearToken(await fx.crearUsuario(colegioA.id, 'sinpermiso', 'Estudiante'))).bearer;
    token.inactivo = (await fx.crearToken(await fx.crearUsuario(colegioA.id, 'inactivo', 'Administrador', 0))).bearer;

    const moduleRef = await Test.createTestingModule({ imports: [AppModule] }).compile();
    app = moduleRef.createNestApplication();
    await app.listen(0); // un solo servidor escuchando: supertest no abre uno por petición
  }, 60000);

  afterAll(async () => {
    await app?.close();
    await fx.limpiar();
  });

  const get = (path: string, tok?: string, host?: string) => {
    let r = request(app.getHttpServer()).get(path);
    if (tok) r = r.set('Authorization', `Bearer ${tok}`);
    if (host) r = r.set('Host', host);
    return r;
  };

  describe('público', () => {
    it('GET /health responde sin token con la misma forma que Laravel', async () => {
      const res = await get('/health').expect(200);
      expect(res.body).toMatchObject({ status: 'ok', checks: { database: 'ok' }, runtime: 'node' });
    });
  });

  describe('autenticación (tokens de Sanctum)', () => {
    it('sin token → 401', async () => {
      await get('/api/v1/estudiantes').expect(401);
    });

    it('token con formato inválido → 401', async () => {
      await get('/api/v1/estudiantes', 'basura').expect(401);
      await get('/api/v1/estudiantes', '0|nada').expect(401);
    });

    it('token con id correcto pero secreto equivocado → 401', async () => {
      const [id] = token.admin1.split('|');
      await get('/api/v1/estudiantes', `${id}|${'0'.repeat(40)}`).expect(401);
    });

    it('usuario inactivo → 401', async () => {
      await get('/api/v1/estudiantes', token.inactivo).expect(401);
    });
  });

  describe('permisos (Spatie)', () => {
    it('usuario sin ver-estudiantes → 403', async () => {
      const [r] = await fx.pool.query<RowDataPacket[]>(
        `select 1 from role_has_permissions rhp join permissions p on p.id = rhp.permission_id
         join roles r on r.id = rhp.role_id where r.name = 'Estudiante' and p.name = 'ver-estudiantes'`,
      );
      expect(r).toHaveLength(0); // precondición: el rol Estudiante no tiene este permiso
      await get('/api/v1/estudiantes', token.sinPermiso).expect(403);
    });

    it('Administrador sí puede', async () => {
      await get('/api/v1/estudiantes', token.admin1).expect(200);
    });
  });

  describe('aislamiento multi-tenant', () => {
    it('el admin del colegio A ve el total real de SU colegio (paridad con SQL directo)', async () => {
      const [cnt] = await fx.pool.query<RowDataPacket[]>(
        'select count(*) as n from estudiantes where tenant_id = ? and deleted_at is null',
        [colegioA.id],
      );
      expect(Number(cnt[0].n)).toBe(TOTAL_A);

      const res = await get('/api/v1/estudiantes?perPage=5', token.admin1).expect(200);
      expect(res.body.meta.total).toBe(TOTAL_A);
      expect(res.body.data).toHaveLength(5);
    });

    it('el admin del colegio B solo ve SUS estudiantes, nunca los del colegio A', async () => {
      const res = await get('/api/v1/estudiantes', token.admin2).expect(200);
      expect(res.body.meta.total).toBe(2);
      expect(res.body.data.map((e: { nombres: string }) => e.nombres).sort()).toEqual(['Yara', 'Zoe']);
    });

    it('la búsqueda tampoco cruza tenants (buscar un nombre del otro colegio no devuelve nada)', async () => {
      const res = await get('/api/v1/estudiantes?q=Aislada', token.admin1).expect(200);
      expect(res.body.meta.total).toBe(0);
    });

    it('un token del colegio A usado con el dominio del colegio B → 403', async () => {
      await get('/api/v1/estudiantes', token.admin1, `${colegioB.dominio}.zuraedu.com`).expect(403);
    });

    it('el mismo token con el dominio de SU colegio sí funciona', async () => {
      await get('/api/v1/estudiantes', token.admin2, `${colegioB.dominio}.zuraedu.com`).expect(200);
    });

    it('un tenant suspendido → 403', async () => {
      await fx.pool.query("update tenants set estado = 'suspendido' where id = ?", [colegioB.id]);
      try {
        await get('/api/v1/estudiantes', token.admin2).expect(403);
      } finally {
        await fx.pool.query("update tenants set estado = 'activo' where id = ?", [colegioB.id]);
      }
    });
  });

  describe('validación y búsqueda', () => {
    it('perPage por encima del máximo → 400', async () => {
      const res = await get('/api/v1/estudiantes?perPage=101', token.admin1).expect(400);
      expect(res.body.errors[0].campo).toBe('perPage');
    });

    it('estado fuera del ENUM → 400', async () => {
      await get('/api/v1/estudiantes?estado=borrado', token.admin1).expect(400);
    });

    it('el comodín % se busca literal (no devuelve todo)', async () => {
      const res = await get('/api/v1/estudiantes?q=%25', token.admin1).expect(200);
      expect(res.body.meta.total).toBe(0);
    });

    it('la búsqueda filtra y pagina', async () => {
      const res = await get('/api/v1/estudiantes?q=Juan&perPage=10', token.admin1).expect(200);
      expect(res.body.meta.total).toBe(30); // 30 de los 40
      expect(res.body.meta.lastPage).toBe(3);
      expect(res.body.data).toHaveLength(10);
      for (const e of res.body.data) expect(e.nombres).toBe('Juan');
    });

    it('ordena por apellidos y pagina sin repetir ni saltar filas', async () => {
      const vistos: string[] = [];
      for (const page of [1, 2, 3, 4]) {
        const res = await get(`/api/v1/estudiantes?perPage=10&page=${page}`, token.admin1).expect(200);
        vistos.push(...res.body.data.map((e: { apellidos: string }) => e.apellidos));
      }
      expect(vistos).toHaveLength(TOTAL_A);
      expect(new Set(vistos).size).toBe(TOTAL_A); // ninguna repetida
      expect(vistos).toEqual([...vistos].sort()); // orden ascendente por apellidos
    });

    it('el contrato JSON coincide con el DTO compartido', async () => {
      const res = await get('/api/v1/estudiantes?perPage=1', token.admin1).expect(200);
      expect(Object.keys(res.body.data[0]).sort()).toEqual(
        ['apellidos', 'cedula', 'estado', 'id', 'nombres', 'numeroMatricula', 'sexo'].sort(),
      );
    });
  });
});
