import type { INestApplication } from '@nestjs/common';
import { Test } from '@nestjs/testing';
import request from 'supertest';
import { DATABASE_URL, Fixtures, TenantCreado } from './helpers/fixtures';

// Debe fijarse ANTES de importar AppModule: ConfigModule lee process.env al crear el módulo.
process.env.DATABASE_URL = DATABASE_URL;
process.env.AUTH_CACHE_TTL_SECONDS = '0';

import { AppModule } from '../src/app.module';
import { errorSchema, estudianteDtoSchema, estudiantesPageSchema, healthSchema } from '../src/openapi/respuestas';

async function crearApp(openapiHabilitado: boolean): Promise<INestApplication> {
  process.env.OPENAPI_ENABLED = String(openapiHabilitado);
  const moduleRef = await Test.createTestingModule({ imports: [AppModule] }).compile();
  const app = moduleRef.createNestApplication();
  await app.listen(0);
  return app;
}

/**
 * El contrato OpenAPI frente a la API REAL: las respuestas que devuelve el servidor tienen que cumplir los esquemas
 * del documento, y cada código de estado que se observa tiene que estar declarado en la operación.
 */
describe('API · contrato OpenAPI vs respuestas reales (e2e, MySQL real)', () => {
  const fx = new Fixtures();
  let app: INestApplication;
  let sinOpenapi: INestApplication;
  let colegio: TenantCreado;
  let otroColegio: TenantCreado;
  let admin = '';
  let lector = ''; // ve estudiantes pero no los gestiona
  let idA: number;
  let idB: number;
  let doc: Record<string, any>; // eslint-disable-line @typescript-eslint/no-explicit-any
  /** Estados HTTP observados por operación: se comprueba al final que todos estaban declarados. */
  const observados: Array<{ metodo: string; ruta: string; estado: number }> = [];

  const llamar = async (metodo: 'get' | 'patch', ruta: string, plantilla: string, tok?: string, cuerpo?: object) => {
    let r = request(app.getHttpServer())[metodo](ruta);
    if (tok) r = r.set('Authorization', `Bearer ${tok}`);
    if (cuerpo) r = r.send(cuerpo);
    const res = await r;
    observados.push({ metodo, ruta: plantilla, estado: res.status });
    return res;
  };

  beforeAll(async () => {
    colegio = await fx.crearTenant('c');
    otroColegio = await fx.crearTenant('o');
    idA = await fx.crearEstudiante(colegio.id, 'Ana', 'Perez', 1, '00100000001');
    await fx.crearEstudiante(colegio.id, 'Luis', 'Gomez', 2, '00100000002');
    idB = await fx.crearEstudiante(otroColegio.id, 'Zoe', 'Ajena', 3);
    admin = (await fx.crearToken(await fx.crearUsuario(colegio.id, 'admin', 'Administrador'))).bearer;
    lector = (await fx.crearToken(await fx.crearUsuario(colegio.id, 'lector', 'Caja / Finanzas'))).bearer;

    app = await crearApp(true);
    sinOpenapi = await crearApp(false);
    process.env.OPENAPI_ENABLED = 'true';
    doc = (await request(app.getHttpServer()).get('/openapi.json')).body;
  }, 60000);

  afterAll(async () => {
    await Promise.all([app?.close(), sinOpenapi?.close()]);
    await fx.limpiar();
  });

  describe('GET /openapi.json', () => {
    it('es público (sin token) y devuelve el documento OpenAPI 3.1', async () => {
      const res = await request(app.getHttpServer()).get('/openapi.json').expect(200);
      expect(res.body.openapi).toBe('3.1.0');
      expect(Object.keys(res.body.paths)).toEqual(
        expect.arrayContaining(['/health', '/api/v1/estudiantes', '/api/v1/estudiantes/{id}']),
      );
    });

    it('no contiene datos de ningún colegio', async () => {
      const texto = JSON.stringify(doc);
      expect(texto).not.toContain(colegio.dominio);
      expect(texto).not.toContain('Perez');
      expect(texto).not.toContain('00100000001');
    });

    it('con OPENAPI_ENABLED=false responde 404', async () => {
      await request(sinOpenapi.getHttpServer()).get('/openapi.json').expect(404);
    });
  });

  describe('las respuestas reales cumplen los esquemas del contrato', () => {
    it('GET /health', async () => {
      const res = await llamar('get', '/health', '/health');
      expect(res.status).toBe(200);
      expect(healthSchema.safeParse(res.body).success).toBe(true);
    });

    it('GET /api/v1/estudiantes → página de estudiantes', async () => {
      const res = await llamar('get', '/api/v1/estudiantes?perPage=10', '/api/v1/estudiantes', admin);
      expect(res.status).toBe(200);
      const parsed = estudiantesPageSchema.safeParse(res.body);
      expect(parsed.success).toBe(true);
      expect(res.body.data.length).toBe(2);
    });

    it('PATCH /api/v1/estudiantes/:id → estudiante', async () => {
      const res = await llamar('patch', `/api/v1/estudiantes/${idA}`, '/api/v1/estudiantes/{id}', admin, { nombres: 'Anita' });
      expect(res.status).toBe(200);
      expect(estudianteDtoSchema.safeParse(res.body).success).toBe(true);
      expect(res.body.nombres).toBe('Anita');
    });
  });

  describe('los errores reales tienen la forma documentada y su estado está declarado', () => {
    const casos: Array<[string, 'get' | 'patch', () => string, string, () => string | undefined, object | undefined, number]> = [
      ['GET sin token → 401', 'get', () => '/api/v1/estudiantes', '/api/v1/estudiantes', () => undefined, undefined, 401],
      ['GET parámetros inválidos → 400', 'get', () => '/api/v1/estudiantes?perPage=999', '/api/v1/estudiantes', () => admin, undefined, 400],
      ['PATCH sin permiso → 403', 'patch', () => `/api/v1/estudiantes/${idA}`, '/api/v1/estudiantes/{id}', () => lector, { nombres: 'Xx' }, 403],
      ['PATCH cuerpo inválido → 400', 'patch', () => `/api/v1/estudiantes/${idA}`, '/api/v1/estudiantes/{id}', () => admin, { estado: 'x' }, 400],
      ['PATCH campo desconocido → 400', 'patch', () => `/api/v1/estudiantes/${idA}`, '/api/v1/estudiantes/{id}', () => admin, { tenant_id: 1 }, 400],
      ['PATCH de otro colegio → 404', 'patch', () => `/api/v1/estudiantes/${idB}`, '/api/v1/estudiantes/{id}', () => admin, { nombres: 'Xx' }, 404],
      ['PATCH cédula repetida → 409', 'patch', () => `/api/v1/estudiantes/${idA}`, '/api/v1/estudiantes/{id}', () => admin, { cedula: '00100000002' }, 409],
    ];

    it.each(casos)('%s', async (_nombre, metodo, ruta, plantilla, tok, cuerpo, esperado) => {
      const res = await llamar(metodo, ruta(), plantilla, tok(), cuerpo);
      expect(res.status).toBe(esperado);
      expect(errorSchema.safeParse(res.body).success).toBe(true);
    });
  });

  it('cada estado HTTP observado en las pruebas está declarado en la operación del contrato', () => {
    expect(observados.length).toBeGreaterThanOrEqual(10);
    for (const { metodo, ruta, estado } of observados) {
      const declarados = Object.keys(doc.paths[ruta][metodo].responses);
      expect({ ruta, metodo, estado, declarado: declarados.includes(String(estado)) }).toEqual({
        ruta,
        metodo,
        estado,
        declarado: true,
      });
    }
  });
});
