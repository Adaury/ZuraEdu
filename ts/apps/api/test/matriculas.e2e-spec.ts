import { createServer, Server } from 'node:http';
import type { AddressInfo } from 'node:net';
import type { INestApplication } from '@nestjs/common';
import { Test } from '@nestjs/testing';
import { RowDataPacket } from 'mysql2/promise';
import request from 'supertest';
import { DATABASE_URL, Fixtures, TenantCreado } from './helpers/fixtures';
import { ClienteSocket } from './helpers/socket-client';

// Debe fijarse ANTES de importar AppModule: ConfigModule lee process.env al compilar el módulo.
const RV = {
  id: process.env.TEST_REVERB_APP_ID,
  key: process.env.TEST_REVERB_APP_KEY,
  secret: process.env.TEST_REVERB_APP_SECRET,
  host: process.env.TEST_REVERB_HOST ?? '127.0.0.1',
  port: process.env.TEST_REVERB_PORT ?? '8080',
};
const hayReverb = Boolean(RV.id && RV.key && RV.secret);
process.env.DATABASE_URL = DATABASE_URL;
delete process.env.REDIS_HOST; // esta prueba no necesita Redis (las cachés de Laravel se borran en mejor esfuerzo)
if (hayReverb) {
  process.env.REVERB_APP_ID = RV.id;
  process.env.REVERB_APP_KEY = RV.key;
  process.env.REVERB_APP_SECRET = RV.secret;
  process.env.REVERB_HOST = RV.host;
  process.env.REVERB_PORT = RV.port;
}

import { AppModule } from '../src/app.module';
import { NotificacionesService } from '../src/notificaciones/notificaciones.service';
import { matriculaDtoSchema } from '../src/openapi/respuestas';
import { ReverbPublisher } from '../src/realtime/reverb.publisher';

interface Fila extends RowDataPacket {
  [k: string]: unknown;
}

describe('API · POST /api/v1/matriculas (e2e: MySQL, Expo falso y Reverb real)', () => {
  const fx = new Fixtures();
  let colegioA: TenantCreado;
  let colegioB: TenantCreado;
  let app: INestApplication;
  let expo: Server;
  let expoRecibido: Array<Array<{ to: string; title: string; body: string }>> = [];
  const token: Record<string, string> = {};
  let contador = 0;

  const post = (cuerpo: unknown, bearer?: string) => {
    let r = request(app.getHttpServer()).post('/api/v1/matriculas');
    if (bearer) r = r.set('Authorization', `Bearer ${bearer}`);
    return r.send(cuerpo as object);
  };
  const drenar = async () => {
    await app.get(NotificacionesService).drenar();
    await app.get(ReverbPublisher).drenar();
  };
  const q = async (sql: string, args: unknown[] = []): Promise<Fila[]> => (await fx.pool.query<Fila[]>(sql, args))[0];

  async function anio(tenantId: number): Promise<number> {
    contador++;
    const [r] = await fx.pool.query<import('mysql2/promise').ResultSetHeader>(
      "insert into school_years (tenant_id, nombre, fecha_inicio, fecha_fin, activo, created_at, updated_at) values (?, ?, '2026-08-01', '2027-06-30', 1, now(), now())",
      [tenantId, `AE-${fx.sufijo}-${contador}`],
    );
    return r.insertId;
  }
  async function grupo(tenantId: number, anioId: number, capacidad: number): Promise<{ id: number; nombre: string }> {
    contador++;
    const [g] = await fx.pool.query<import('mysql2/promise').ResultSetHeader>(
      "insert into grados (tenant_id, nombre, nivel, orden, ciclo, activo, created_at, updated_at) values (?, ?, ?, 1, 'primer_ciclo', 1, now(), now())",
      [tenantId, `Primero${contador}`, 100 + contador],
    );
    const [s] = await fx.pool.query<import('mysql2/promise').ResultSetHeader>(
      'insert into secciones (tenant_id, nombre, orden, created_at, updated_at) values (?, ?, 1, now(), now())',
      [tenantId, `S${contador}`],
    );
    const [r] = await fx.pool.query<import('mysql2/promise').ResultSetHeader>(
      'insert into grupos (tenant_id, school_year_id, grado_id, seccion_id, capacidad, activo, created_at, updated_at) values (?, ?, ?, ?, ?, 1, now(), now())',
      [tenantId, anioId, g.insertId, s.insertId, capacidad],
    );
    return { id: r.insertId, nombre: `Primero${contador} S${contador}` };
  }
  const estudiante = (tenantId: number) => fx.crearEstudiante(tenantId, 'Mateo', `Prueba${++contador}`, contador);
  const cuerpo = (anioId: number, estId: number, grupoId: number, extra: object = {}) => ({
    schoolYearId: anioId,
    estudianteId: estId,
    grupoId,
    fechaMatricula: '2026-08-15',
    ...extra,
  });

  beforeAll(async () => {
    expo = createServer((req, res) => {
      let c = '';
      req.on('data', (d) => (c += d));
      req.on('end', () => {
        expoRecibido.push(JSON.parse(c));
        res.end('{}');
      });
    });
    await new Promise<void>((ok) => expo.listen(0, '127.0.0.1', ok));
    process.env.EXPO_PUSH_URL = `http://127.0.0.1:${(expo.address() as AddressInfo).port}/push`;

    colegioA = await fx.crearTenant('mta');
    colegioB = await fx.crearTenant('mtb');
    token.admin = (await fx.crearToken(await fx.crearUsuario(colegioA.id, 'madmin', 'Administrador'))).bearer;
    token.adminB = (await fx.crearToken(await fx.crearUsuario(colegioB.id, 'madminb', 'Administrador'))).bearer;
    token.estudiante = (await fx.crearToken(await fx.crearUsuario(colegioA.id, 'mest', 'Estudiante'))).bearer;

    app = (await Test.createTestingModule({ imports: [AppModule] }).compile()).createNestApplication();
    await app.listen(0);
  }, 60000);

  afterEach(() => {
    expoRecibido = [];
  });
  afterAll(async () => {
    await drenar();
    await app?.close();
    await new Promise<void>((ok) => expo.close(() => ok()));
    await fx.limpiar();
  });

  describe('camino feliz', () => {
    it('201 con la forma del contrato; la fila queda activa con numero_orden 1 y la auditoría matricula.creada', async () => {
      const a = await anio(colegioA.id);
      const g = await grupo(colegioA.id, a, 30);
      const e = await estudiante(colegioA.id);

      const r = await post(cuerpo(a, e, g.id, { observaciones: '  Traslado interno  ' }), token.admin).expect(201);
      expect(matriculaDtoSchema.safeParse(r.body).success).toBe(true);
      expect(r.body).toMatchObject({ schoolYearId: a, estudianteId: e, grupoId: g.id, fechaMatricula: '2026-08-15', numeroOrden: 1, estado: 'activa', observaciones: 'Traslado interno' });

      const [m] = await q('select * from matriculas where id = ?', [r.body.id]);
      expect(m).toMatchObject({ tenant_id: colegioA.id, estado: 'activa', numero_orden: 1, estudiante_id: e, grupo_id: g.id });

      const [log] = await q("select * from activity_logs where accion = 'matricula.creada' and modelo_id = ?", [r.body.id]);
      expect(log.modelo).toBe('App\\Models\\Matricula');
      expect(log.tenant_id).toBe(colegioA.id);
      expect(String(log.descripcion)).toContain(`en ${g.nombre} (orden 1`);
    });

    it('una cadena vacía en observaciones se guarda como NULL', async () => {
      const a = await anio(colegioA.id);
      const g = await grupo(colegioA.id, a, 5);
      const r = await post(cuerpo(a, await estudiante(colegioA.id), g.id, { observaciones: '' }), token.admin).expect(201);
      expect(r.body.observaciones).toBeNull();
    });

    it('numero_orden cuenta TODAS las matrículas del grupo (también retiradas), pero el cupo solo las activas', async () => {
      const a = await anio(colegioA.id);
      const g = await grupo(colegioA.id, a, 2); // capacidad 2
      const viejo = await estudiante(colegioA.id);
      await fx.pool.query(
        "insert into matriculas (tenant_id, school_year_id, estudiante_id, grupo_id, fecha_matricula, numero_orden, estado, created_at, updated_at) values (?, ?, ?, ?, '2026-08-01', 1, 'retirada', now(), now())",
        [colegioA.id, a, viejo, g.id],
      );
      const r1 = await post(cuerpo(a, await estudiante(colegioA.id), g.id), token.admin).expect(201);
      const r2 = await post(cuerpo(a, await estudiante(colegioA.id), g.id), token.admin).expect(201);
      expect([r1.body.numeroOrden, r2.body.numeroOrden]).toEqual([2, 3]); // la retirada ocupa el 1
      await post(cuerpo(a, await estudiante(colegioA.id), g.id), token.admin).expect(409); // 2 activas = lleno
    });
  });

  describe('cupo, duplicados y concurrencia', () => {
    it('409 sin cupo, con el mismo mensaje que Laravel', async () => {
      const a = await anio(colegioA.id);
      const g = await grupo(colegioA.id, a, 1);
      await post(cuerpo(a, await estudiante(colegioA.id), g.id), token.admin).expect(201);
      const r = await post(cuerpo(a, await estudiante(colegioA.id), g.id), token.admin).expect(409);
      expect(r.body.message).toBe(`El grupo ${g.nombre} no tiene cupo suficiente: 1/1 ocupados, quedan 0 disponible(s).`);
    });

    it('409 si el estudiante ya está matriculado ese año (aunque sea en otro grupo)', async () => {
      const a = await anio(colegioA.id);
      const g1 = await grupo(colegioA.id, a, 5);
      const g2 = await grupo(colegioA.id, a, 5);
      const e = await estudiante(colegioA.id);
      await post(cuerpo(a, e, g1.id), token.admin).expect(201);
      const r = await post(cuerpo(a, e, g2.id), token.admin).expect(409);
      expect(r.body.message).toBe('Este estudiante ya está matriculado en este año escolar.');
    });

    it('CONCURRENCIA: 8 matrículas simultáneas a un grupo de 3 cupos → exactamente 3 entran, órdenes 1..3 sin repetir', async () => {
      const a = await anio(colegioA.id);
      const g = await grupo(colegioA.id, a, 3);
      const ests = await Promise.all(Array.from({ length: 8 }, () => estudiante(colegioA.id)));

      const rs = await Promise.all(ests.map((e) => post(cuerpo(a, e, g.id), token.admin)));
      const ok = rs.filter((r) => r.status === 201);
      expect(ok).toHaveLength(3);
      expect(rs.filter((r) => r.status === 409)).toHaveLength(5);
      expect(ok.map((r) => r.body.numeroOrden).sort()).toEqual([1, 2, 3]);
      const [{ n }] = await q('select count(*) n from matriculas where grupo_id = ?', [g.id]);
      expect(Number(n)).toBe(3);
    });

    it('CONCURRENCIA: el mismo estudiante pedido 5 veces a la vez se matricula UNA sola vez', async () => {
      const a = await anio(colegioA.id);
      const g = await grupo(colegioA.id, a, 30);
      const e = await estudiante(colegioA.id);
      const rs = await Promise.all(Array.from({ length: 5 }, () => post(cuerpo(a, e, g.id), token.admin)));
      expect(rs.filter((r) => r.status === 201)).toHaveLength(1);
      expect(rs.filter((r) => r.status === 409)).toHaveLength(4);
      const [{ n }] = await q('select count(*) n from matriculas where estudiante_id = ? and school_year_id = ?', [e, a]);
      expect(Number(n)).toBe(1);
    });
  });

  describe('referencias: el cliente manda ids, la BD decide (aislamiento entre colegios)', () => {
    it('año escolar, estudiante y grupo de OTRO colegio → 422 y no se crea nada', async () => {
      const aA = await anio(colegioA.id);
      const gA = await grupo(colegioA.id, aA, 5);
      const eA = await estudiante(colegioA.id);
      const aB = await anio(colegioB.id);
      const gB = await grupo(colegioB.id, aB, 5);
      const eB = await estudiante(colegioB.id);

      const r1 = await post(cuerpo(aB, eA, gA.id), token.admin).expect(422);
      expect(r1.body.errors[0].campo).toBe('schoolYearId');
      const r2 = await post(cuerpo(aA, eB, gA.id), token.admin).expect(422);
      expect(r2.body.errors[0].campo).toBe('estudianteId');
      const r3 = await post(cuerpo(aA, eA, gB.id), token.admin).expect(422);
      expect(r3.body.errors[0].campo).toBe('grupoId');

      const [{ n }] = await q('select count(*) n from matriculas where tenant_id in (?, ?) and estudiante_id in (?, ?)', [colegioA.id, colegioB.id, eA, eB]);
      expect(Number(n)).toBe(0);
    });

    it('un estudiante borrado lógicamente no se puede matricular (Laravel sí lo dejaba pasar: exists no filtra deleted_at)', async () => {
      const a = await anio(colegioA.id);
      const g = await grupo(colegioA.id, a, 5);
      const e = await estudiante(colegioA.id);
      await fx.pool.query('update estudiantes set deleted_at = now() where id = ?', [e]);
      const r = await post(cuerpo(a, e, g.id), token.admin).expect(422);
      expect(r.body.errors[0].campo).toBe('estudianteId');
    });

    it('un grupo de OTRO año escolar → 422 (la matrícula quedaría incoherente)', async () => {
      const a1 = await anio(colegioA.id);
      const a2 = await anio(colegioA.id);
      const gDelOtroAnio = await grupo(colegioA.id, a2, 5);
      const r = await post(cuerpo(a1, await estudiante(colegioA.id), gDelOtroAnio.id), token.admin).expect(422);
      expect(r.body.errors[0].mensaje).toMatch(/no pertenece al año escolar/);
    });

    it('ids inexistentes → 422', async () => {
      const a = await anio(colegioA.id);
      await post(cuerpo(a, 999_999_999, 999_999_999), token.admin).expect(422);
    });
  });

  describe('validación del cuerpo (400) y permisos', () => {
    it.each([
      ['falta schoolYearId', { estudianteId: 1, grupoId: 1, fechaMatricula: '2026-08-15' }],
      ['falta fechaMatricula', { schoolYearId: 1, estudianteId: 1, grupoId: 1 }],
      ['fecha imposible', { schoolYearId: 1, estudianteId: 1, grupoId: 1, fechaMatricula: '2026-02-31' }],
      ['fecha con otro formato', { schoolYearId: 1, estudianteId: 1, grupoId: 1, fechaMatricula: '15/08/2026' }],
      ['id como texto', { schoolYearId: '1', estudianteId: 1, grupoId: 1, fechaMatricula: '2026-08-15' }],
      ['id negativo', { schoolYearId: 1, estudianteId: -3, grupoId: 1, fechaMatricula: '2026-08-15' }],
      ['campo desconocido tenantId', { schoolYearId: 1, estudianteId: 1, grupoId: 1, fechaMatricula: '2026-08-15', tenantId: 99 }],
      ['campo desconocido estado', { schoolYearId: 1, estudianteId: 1, grupoId: 1, fechaMatricula: '2026-08-15', estado: 'promovida' }],
      ['campo desconocido numeroOrden', { schoolYearId: 1, estudianteId: 1, grupoId: 1, fechaMatricula: '2026-08-15', numeroOrden: 1 }],
      ['campo en snake_case', { school_year_id: 1, estudianteId: 1, grupoId: 1, fechaMatricula: '2026-08-15' }],
    ])('400: %s', async (_nombre, c) => {
      const r = await post(c, token.admin).expect(400);
      expect(Array.isArray(r.body.errors)).toBe(true);
    });

    it('sin token → 401; sin el permiso gestionar-matriculas → 403', async () => {
      await post({}).expect(401);
      await post(cuerpo(1, 1, 1), token.estudiante).expect(403);
    });
  });

  describe('efectos posteriores: notificaciones y tiempo real', () => {
    it('notifica «Matrícula confirmada» al estudiante y a su representante (fila + push), con el texto de Laravel', async () => {
      const a = await anio(colegioA.id);
      const g = await grupo(colegioA.id, a, 5);
      const e = await estudiante(colegioA.id);
      const userEst = await fx.crearUsuario(colegioA.id, 'mnest', 'Estudiante');
      const userRep = await fx.crearUsuario(colegioA.id, 'mnrep', 'Representante');
      await fx.pool.query('update estudiantes set user_id = ? where id = ?', [userEst, e]);
      const [rep] = await fx.pool.query<import('mysql2/promise').ResultSetHeader>(
        "insert into representantes (tenant_id, user_id, nombres, apellidos, created_at, updated_at) values (?, ?, 'Rosa', 'Madre', now(), now())",
        [colegioA.id, userRep],
      );
      await fx.pool.query("insert into estudiante_representante (estudiante_id, representante_id, parentesco, es_principal, created_at, updated_at) values (?, ?, 'madre', 1, now(), now())", [e, rep.insertId]);
      await fx.pool.query("insert into device_tokens (user_id, token, platform, created_at, updated_at) values (?, 'ExponentPushToken[rep]', 'ios', now(), now())", [userRep]);

      await post(cuerpo(a, e, g.id), token.admin).expect(201);
      await drenar();

      const filas = await q("select user_id, tipo, titulo, mensaje from notificaciones where tenant_id = ? and tipo = 'general' and titulo like '%Matrícula confirmada' order by user_id", [colegioA.id]);
      expect(filas.map((f) => f.user_id)).toEqual([userEst, userRep].sort((x, y) => x - y));
      for (const f of filas) {
        expect(f.titulo).toBe('✅ Matrícula confirmada');
        expect(String(f.mensaje)).toMatch(new RegExp(`^Prueba\\d+, Mateo ha sido matriculado/a en ${g.nombre} para el año escolar en curso\\.$`));
      }
      expect(expoRecibido.flat().map((m) => m.to)).toEqual(['ExponentPushToken[rep]']);
    });

    it('un estudiante sin usuario ni representantes se matricula igual, sin notificaciones', async () => {
      const a = await anio(colegioA.id);
      const g = await grupo(colegioA.id, a, 5);
      const antes = await q('select count(*) n from notificaciones where tenant_id = ?', [colegioA.id]);
      await post(cuerpo(a, await estudiante(colegioA.id), g.id), token.admin).expect(201);
      await drenar();
      const despues = await q('select count(*) n from notificaciones where tenant_id = ?', [colegioA.id]);
      expect(Number(despues[0].n)).toBe(Number(antes[0].n));
    });

    (hayReverb ? it : it.skip)('emite dashboard.updated {tipo: nueva_matricula, datos: {grupo_id}} en private-tenant.{id}', async () => {
      const a = await anio(colegioA.id);
      const g = await grupo(colegioA.id, a, 5);
      const c = new ClienteSocket({ host: RV.host, port: RV.port, key: RV.key!, secret: RV.secret! });
      try {
        await c.conectarYSuscribir(`private-tenant.${colegioA.id}`);
        await post(cuerpo(a, await estudiante(colegioA.id), g.id), token.admin).expect(201);
        const m = await c.esperar((x) => x.event === 'dashboard.updated', 5000);
        expect(m.channel).toBe(`private-tenant.${colegioA.id}`);
        expect(JSON.parse(m.data!)).toEqual({ tipo: 'nueva_matricula', datos: { grupo_id: g.id } });
      } finally {
        c.cerrar();
      }
    });

    (hayReverb ? it : it.skip)('aislamiento: la matrícula de un colegio NO emite al canal de otro colegio', async () => {
      const a = await anio(colegioA.id);
      const g = await grupo(colegioA.id, a, 5);
      const c = new ClienteSocket({ host: RV.host, port: RV.port, key: RV.key!, secret: RV.secret! });
      try {
        await c.conectarYSuscribir(`private-tenant.${colegioB.id}`);
        await post(cuerpo(a, await estudiante(colegioA.id), g.id), token.admin).expect(201);
        await drenar();
        await new Promise((r) => setTimeout(r, 500));
        expect(c.recibidos.some((x) => x.event === 'dashboard.updated')).toBe(false);
      } finally {
        c.cerrar();
      }
    });
  });
});
