import type { INestApplication } from '@nestjs/common';
import { Test } from '@nestjs/testing';
import request from 'supertest';
import { DATABASE_URL, Fixtures, TenantCreado } from './helpers/fixtures';

// Debe fijarse ANTES de importar AppModule: ConfigModule lee process.env al crear el módulo.
process.env.DATABASE_URL = DATABASE_URL;

import { AppModule } from '../src/app.module';
import { estudianteDtoSchema } from '../src/openapi/respuestas';

describe('API · GET /api/v1/estudiantes/:id (e2e, MySQL real)', () => {
  const fx = new Fixtures();
  let colegioA: TenantCreado;
  let colegioB: TenantCreado;
  let app: INestApplication;
  const token: Record<string, string> = {};
  let propio = 0;
  let ajeno = 0;
  let borrado = 0;

  const get = (id: string | number, bearer?: string) => {
    let r = request(app.getHttpServer()).get(`/api/v1/estudiantes/${id}`);
    if (bearer) r = r.set('Authorization', `Bearer ${bearer}`);
    return r;
  };

  beforeAll(async () => {
    colegioA = await fx.crearTenant('goa');
    colegioB = await fx.crearTenant('gob');
    propio = await fx.crearEstudiante(colegioA.id, 'Rosa', 'Propia', 1, '001-0000001-1');
    borrado = await fx.crearEstudiante(colegioA.id, 'Luis', 'Borrado', 2);
    ajeno = await fx.crearEstudiante(colegioB.id, 'Ana', 'Ajena', 3);
    await fx.pool.query('update estudiantes set deleted_at = now() where id = ?', [borrado]);
    token.admin = (await fx.crearToken(await fx.crearUsuario(colegioA.id, 'goadmin', 'Administrador'))).bearer;
    token.sinPermiso = (await fx.crearToken(await fx.crearUsuario(colegioA.id, 'gosin', 'Estudiante'))).bearer;
    app = (await Test.createTestingModule({ imports: [AppModule] }).compile()).createNestApplication();
    await app.listen(0);
  }, 60000);

  afterAll(async () => {
    await app?.close();
    await fx.limpiar();
  });

  it('200 con la forma del contrato para un estudiante del propio colegio', async () => {
    const r = await get(propio, token.admin).expect(200);
    expect(estudianteDtoSchema.safeParse(r.body).success).toBe(true);
    expect(r.body).toMatchObject({ id: propio, nombres: 'Rosa', apellidos: 'Propia', cedula: '001-0000001-1' });
  });

  it('404 para un estudiante de OTRO colegio (no se distingue de uno inexistente)', async () => {
    const r = await get(ajeno, token.admin).expect(404);
    const inexistente = await get(999_999_999, token.admin).expect(404);
    expect(r.body.message).toBe(inexistente.body.message);
  });

  it('404 para un estudiante borrado lógicamente', async () => {
    await get(borrado, token.admin).expect(404);
  });

  it.each(['abc', '0', '-5', '1.5', '1e3'])('400 con un id inválido (%s)', async (id) => {
    await get(id, token.admin).expect(400);
  });

  it('401 sin token y 403 sin el permiso ver-estudiantes', async () => {
    await get(propio).expect(401);
    await get(propio, token.sinPermiso).expect(403);
  });
});
