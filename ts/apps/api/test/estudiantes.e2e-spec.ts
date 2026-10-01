import { createHash, randomBytes } from 'node:crypto';
import type { INestApplication } from '@nestjs/common';
import { Test } from '@nestjs/testing';
import { createPool, Pool, ResultSetHeader, RowDataPacket } from 'mysql2/promise';
import request from 'supertest';

// Debe fijarse ANTES de importar AppModule: ConfigModule lee process.env al crear el módulo.
const DATABASE_URL = process.env.TEST_DATABASE_URL ?? 'mysql://root@127.0.0.1:3306/sge_bench';
process.env.DATABASE_URL = DATABASE_URL;
// Esta suite cambia datos y exige efecto inmediato: sin caché de autenticación (la caché tiene su propia suite).
process.env.AUTH_CACHE_TTL_SECONDS = '0';

import { AppModule } from '../src/app.module';

const USER_MODEL = 'App\\Models\\User';
const sha256 = (s: string) => createHash('sha256').update(s).digest('hex');

/**
 * E2E contra MySQL real (sge_bench por defecto: tenant 1 con ~4.950 estudiantes).
 * Crea un tenant temporal con sus propios usuarios/estudiantes/tokens y lo borra todo al terminar.
 * No toca los datos existentes.
 */
describe('API · estudiantes (e2e, MySQL real)', () => {
  let app: INestApplication;
  let pool: Pool;
  const sufijo = randomBytes(4).toString('hex');

  // ids creados (para limpieza)
  const creados = { tenantId: 0, userIds: [] as number[], tokenIds: [] as number[], estudianteIds: [] as number[] };

  // token plano por escenario
  const token: Record<'admin1' | 'admin2' | 'sinPermiso' | 'inactivo', string> = {
    admin1: '',
    admin2: '',
    sinPermiso: '',
    inactivo: '',
  };
  let dominioTenant2 = '';
  let totalTenant1 = 0;

  async function rolId(nombre: string): Promise<number> {
    const [rows] = await pool.query<RowDataPacket[]>('select id from roles where name = ?', [nombre]);
    if (!rows[0]) throw new Error(`Rol "${nombre}" no existe en la base de pruebas`);
    return Number(rows[0].id);
  }

  async function crearUsuario(tenantId: number, etiqueta: string, rol: string, activo = 1): Promise<number> {
    const [r] = await pool.query<ResultSetHeader>(
      'insert into users (name, email, password, tenant_id, activo) values (?, ?, ?, ?, ?)',
      [`e2e ${etiqueta}`, `e2e-${etiqueta}-${sufijo}@example.test`, 'x', tenantId, activo],
    );
    const id = r.insertId;
    creados.userIds.push(id);
    await pool.query('insert into model_has_roles (role_id, model_type, model_id) values (?, ?, ?)', [
      await rolId(rol),
      USER_MODEL,
      id,
    ]);
    return id;
  }

  async function crearToken(userId: number): Promise<string> {
    const plano = randomBytes(20).toString('hex');
    const [r] = await pool.query<ResultSetHeader>(
      'insert into personal_access_tokens (tokenable_type, tokenable_id, name, token, abilities) values (?, ?, ?, ?, ?)',
      [USER_MODEL, userId, 'e2e', sha256(plano), '["*"]'],
    );
    creados.tokenIds.push(r.insertId);
    return `${r.insertId}|${plano}`; // formato de Sanctum
  }

  beforeAll(async () => {
    pool = createPool(DATABASE_URL);

    // Tenant 1 existente (demo) → administrador temporal + total real de estudiantes (paridad con SQL directo)
    const [t1] = await pool.query<RowDataPacket[]>('select id from tenants where id = 1');
    if (!t1[0]) throw new Error('La base de pruebas debe tener el tenant 1 (usa sge_bench)');
    const [cnt] = await pool.query<RowDataPacket[]>(
      'select count(*) as n from estudiantes where tenant_id = 1 and deleted_at is null',
    );
    totalTenant1 = Number(cnt[0].n);

    // Tenant 2 temporal con 2 estudiantes propios
    dominioTenant2 = `e2e${sufijo}`;
    const [t2] = await pool.query<ResultSetHeader>(
      "insert into tenants (nombre_institucion, dominio, estado) values (?, ?, 'activo')",
      [`E2E ${sufijo}`, dominioTenant2],
    );
    creados.tenantId = t2.insertId;
    for (const [i, nombre] of ['Zoe', 'Yara'].entries()) {
      const [e] = await pool.query<ResultSetHeader>(
        "insert into estudiantes (tenant_id, numero_matricula, nombres, apellidos, sexo, estado) values (?, ?, ?, ?, 'F', 'activo')",
        [creados.tenantId, `E2E-${sufijo}-${i}`, nombre, `Aislada${i}`],
      );
      creados.estudianteIds.push(e.insertId);
    }

    token.admin1 = await crearToken(await crearUsuario(1, 'admin1', 'Administrador'));
    token.admin2 = await crearToken(await crearUsuario(creados.tenantId, 'admin2', 'Administrador'));
    token.sinPermiso = await crearToken(await crearUsuario(1, 'sinpermiso', 'Estudiante'));
    token.inactivo = await crearToken(await crearUsuario(1, 'inactivo', 'Administrador', 0));

    const moduleRef = await Test.createTestingModule({ imports: [AppModule] }).compile();
    app = moduleRef.createNestApplication();
    await app.init();
  }, 60000);

  afterAll(async () => {
    await app?.close();
    if (pool) {
      const ids = creados.userIds;
      if (creados.estudianteIds.length) await pool.query('delete from estudiantes where id in (?)', [creados.estudianteIds]);
      if (creados.tokenIds.length) await pool.query('delete from personal_access_tokens where id in (?)', [creados.tokenIds]);
      if (ids.length) {
        await pool.query('delete from model_has_roles where model_type = ? and model_id in (?)', [USER_MODEL, ids]);
        await pool.query('delete from users where id in (?)', [ids]);
      }
      if (creados.tenantId) await pool.query('delete from tenants where id = ?', [creados.tenantId]);
      await pool.end();
    }
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
      const [r] = await pool.query<RowDataPacket[]>(
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
    it('el admin del tenant 1 ve el total real del tenant 1 (paridad con SQL directo)', async () => {
      const res = await get('/api/v1/estudiantes?perPage=5', token.admin1).expect(200);
      expect(res.body.meta.total).toBe(totalTenant1);
      expect(res.body.data).toHaveLength(5);
    });

    it('el admin del tenant 2 solo ve SUS estudiantes, nunca los del tenant 1', async () => {
      const res = await get('/api/v1/estudiantes', token.admin2).expect(200);
      expect(res.body.meta.total).toBe(2);
      expect(res.body.data.map((e: { nombres: string }) => e.nombres).sort()).toEqual(['Yara', 'Zoe']);
    });

    it('la búsqueda tampoco cruza tenants (buscar un nombre del otro colegio no devuelve nada)', async () => {
      const res = await get('/api/v1/estudiantes?q=Aislada', token.admin1).expect(200);
      expect(res.body.meta.total).toBe(0);
    });

    it('un token del tenant 1 usado con el dominio del tenant 2 → 403', async () => {
      await get('/api/v1/estudiantes', token.admin1, `${dominioTenant2}.zuraedu.com`).expect(403);
    });

    it('el mismo token con el dominio de SU tenant sí funciona', async () => {
      await get('/api/v1/estudiantes', token.admin2, `${dominioTenant2}.zuraedu.com`).expect(200);
    });

    it('un tenant suspendido → 403', async () => {
      await pool.query("update tenants set estado = 'suspendido' where id = ?", [creados.tenantId]);
      try {
        await get('/api/v1/estudiantes', token.admin2).expect(403);
      } finally {
        await pool.query("update tenants set estado = 'activo' where id = ?", [creados.tenantId]);
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
      expect(res.body.meta.total).toBeGreaterThan(0);
      expect(res.body.meta.total).toBeLessThan(totalTenant1);
      expect(res.body.meta.lastPage).toBe(Math.ceil(res.body.meta.total / 10));
      for (const e of res.body.data) expect(e.nombres + e.apellidos + e.numeroMatricula + (e.cedula ?? '')).toMatch(/juan/i);
    });

    it('el contrato JSON coincide con el DTO compartido', async () => {
      const res = await get('/api/v1/estudiantes?perPage=1', token.admin1).expect(200);
      expect(Object.keys(res.body.data[0]).sort()).toEqual(
        ['apellidos', 'cedula', 'estado', 'id', 'nombres', 'numeroMatricula', 'sexo'].sort(),
      );
    });
  });
});
