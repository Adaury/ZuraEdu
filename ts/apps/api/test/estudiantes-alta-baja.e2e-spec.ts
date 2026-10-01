import type { INestApplication } from '@nestjs/common';
import { Test } from '@nestjs/testing';
import { RowDataPacket } from 'mysql2/promise';
import request from 'supertest';
import { DATABASE_URL, Fixtures, TenantCreado } from './helpers/fixtures';

// Debe fijarse ANTES de importar AppModule: ConfigModule lee process.env al crear el módulo.
process.env.DATABASE_URL = DATABASE_URL;
process.env.AUTH_CACHE_TTL_SECONDS = '0'; // efecto inmediato de los cambios de datos

import { AppModule } from '../src/app.module';

/**
 * POST y DELETE de /api/v1/estudiantes contra MySQL real, con la auditoría de Laravel
 * (`estudiante.creado` / `estudiante.eliminado`). Cada prueba crea sus propios colegios, usuarios y tokens.
 */
describe('API · alta y borrado de estudiantes (e2e, MySQL real)', () => {
  const fx = new Fixtures();
  let app: INestApplication;

  let colegioA: TenantCreado;
  let colegioB: TenantCreado;
  let colegioC: TenantCreado; // numeración automática determinista (sin estudiantes previos)
  let adminA: { userId: number; bearer: string };
  const token = { adminB: '', adminC: '', soloLectura: '', alumno: '' };
  const anio = new Date().getUTCFullYear();

  const valido = (extra: Record<string, unknown> = {}) => ({
    nombres: 'Ana',
    apellidos: 'Perez',
    fechaNacimiento: '2012-05-01',
    sexo: 'F',
    estado: 'activo',
    ...extra,
  });

  const post = (cuerpo: unknown, tok?: string) => {
    let r = request(app.getHttpServer()).post('/api/v1/estudiantes').send(cuerpo as object);
    if (tok) r = r.set('Authorization', `Bearer ${tok}`);
    return r;
  };
  const borrar = (id: number | string, tok?: string) => {
    let r = request(app.getHttpServer()).delete(`/api/v1/estudiantes/${id}`);
    if (tok) r = r.set('Authorization', `Bearer ${tok}`);
    return r;
  };
  const listar = (tok: string, query = '') =>
    request(app.getHttpServer()).get(`/api/v1/estudiantes?perPage=100${query}`).set('Authorization', `Bearer ${tok}`);

  const fila = async (id: number) => {
    const [r] = await fx.pool.query<RowDataPacket[]>('select * from estudiantes where id = ?', [id]);
    return r[0];
  };
  const auditoria = async (modeloId: number, accion?: string) => {
    const [r] = await fx.pool.query<RowDataPacket[]>(
      "select * from activity_logs where modelo = 'App\\\\Models\\\\Estudiante' and modelo_id = ? order by id",
      [modeloId],
    );
    return accion ? r.filter((l) => l.accion === accion) : r;
  };
  const totalEn = async (tenantId: number) => {
    const [r] = await fx.pool.query<RowDataPacket[]>('select count(*) as n from estudiantes where tenant_id = ?', [tenantId]);
    return Number(r[0].n);
  };

  beforeAll(async () => {
    colegioA = await fx.crearTenant('a');
    colegioB = await fx.crearTenant('b');
    colegioC = await fx.crearTenant('c');

    const idAdminA = await fx.crearUsuario(colegioA.id, 'adminA', 'Administrador');
    adminA = { userId: idAdminA, bearer: (await fx.crearToken(idAdminA)).bearer };
    token.adminB = (await fx.crearToken(await fx.crearUsuario(colegioB.id, 'adminB', 'Administrador'))).bearer;
    token.adminC = (await fx.crearToken(await fx.crearUsuario(colegioC.id, 'adminC', 'Administrador'))).bearer;
    token.soloLectura = (await fx.crearToken(await fx.crearUsuario(colegioA.id, 'lector', 'Caja / Finanzas'))).bearer;
    token.alumno = (await fx.crearToken(await fx.crearUsuario(colegioA.id, 'alumno', 'Estudiante'))).bearer;

    const moduleRef = await Test.createTestingModule({ imports: [AppModule] }).compile();
    app = moduleRef.createNestApplication();
    await app.listen(0);
  }, 60000);

  afterAll(async () => {
    await app?.close();
    await fx.limpiar();
  });

  describe('POST /api/v1/estudiantes', () => {
    it('crea con los campos mínimos: 201, colegio del TOKEN, matrícula automática, nacionalidad por defecto y auditoría de Laravel', async () => {
      const res = await post(valido({ cedula: '00100000001' }), adminA.bearer).expect(201);

      expect(res.body).toMatchObject({ nombres: 'Ana', apellidos: 'Perez', sexo: 'F', estado: 'activo', cedula: '00100000001' });
      expect(res.body.numeroMatricula).toMatch(new RegExp(`^${anio}-\\d{5}$`));
      const id = res.body.id as number;

      const f = await fila(id);
      expect(f).toMatchObject({ tenant_id: colegioA.id, nacionalidad: 'Dominicana', estado: 'activo', deleted_at: null });
      expect(f.created_at).toBeTruthy();
      expect(f.updated_at).toBeTruthy();

      const logs = await auditoria(id);
      expect(logs).toHaveLength(1);
      expect(logs[0]).toMatchObject({
        accion: 'estudiante.creado',
        tenant_id: colegioA.id,
        user_id: adminA.userId, // sale del token
        modelo_id: id,
        descripcion: `Estudiante creado: Perez, Ana (Matr: ${res.body.numeroMatricula})`,
      });
      expect(logs[0].modelo).toBe(['App', 'Models', 'Estudiante'].join('\\'));
    });

    it('guarda todos los campos enviados y los opcionales vacíos como null', async () => {
      const res = await post(
        valido({
          numeroMatricula: `M-${anio}-X1`,
          nacionalidad: 'Haitiana',
          telefono: '8095551234',
          email: 'ana@example.com',
          sector: '   ',
          tutorNombre: 'Maria',
          tutorParentesco: 'Madre',
          notasMedicas: 'Alergia',
        }),
        adminA.bearer,
      ).expect(201);

      expect(res.body.numeroMatricula).toBe(`M-${anio}-X1`);
      expect(await fila(res.body.id)).toMatchObject({
        nacionalidad: 'Haitiana',
        telefono: '8095551234',
        email: 'ana@example.com',
        sector: null,
        tutor_nombre: 'Maria',
        tutor_parentesco: 'Madre',
        notas_medicas: 'Alergia',
      });
    });

    describe('número de matrícula automático (AAAA-NNNNN)', () => {
      it('es secuencial por colegio y NO reutiliza el número de un estudiante borrado (Laravel sí colisionaría)', async () => {
        const a = (await post(valido({ nombres: 'Uno' }), token.adminC).expect(201)).body;
        const b = (await post(valido({ nombres: 'Dos' }), token.adminC).expect(201)).body;
        const c = (await post(valido({ nombres: 'Tres' }), token.adminC).expect(201)).body;
        expect([a.numeroMatricula, b.numeroMatricula, c.numeroMatricula]).toEqual([`${anio}-00001`, `${anio}-00002`, `${anio}-00003`]);

        await borrar(b.id, token.adminC).expect(204);

        // Laravel: count(activos del año) + 1 = 3 → ya existe (el de "Tres") → error 500. Aquí: máximo + 1.
        const d = (await post(valido({ nombres: 'Cuatro' }), token.adminC).expect(201)).body;
        expect(d.numeroMatricula).toBe(`${anio}-00004`);
      });

      it('cada colegio lleva su propia secuencia', async () => {
        const enB = (await post(valido(), token.adminB).expect(201)).body;
        expect(enB.numeroMatricula).toBe(`${anio}-00001`); // B empieza en 1 aunque C ya vaya por el 4
      });

      it('12 altas simultáneas (más que las conexiones del pool): todas 201, números distintos y CONSECUTIVOS sin huecos', async () => {
        const N = 12;
        const respuestas = await Promise.all(
          Array.from({ length: N }, (_, i) => post(valido({ nombres: `Par${i}` }), adminA.bearer)),
        );
        // Se compara estado + mensaje para que un fallo diga POR QUÉ (409 por colisión interna, 500 por bloqueo...).
        expect(respuestas.map((r) => [r.status, r.status === 201 ? 'ok' : r.body?.message])).toEqual(
          Array.from({ length: N }, () => [201, 'ok']),
        );

        const sufijos = respuestas
          .map((r) => r.body.numeroMatricula as string)
          .filter((n) => n.startsWith(`${anio}-`))
          .map((n) => Number(n.slice(`${anio}-`.length)))
          .sort((a, b) => a - b);
        expect(sufijos).toHaveLength(N);
        expect(new Set(sufijos).size).toBe(N);
        // La asignación está serializada por colegio: cada número es el anterior + 1 (sin saltos por reintentos).
        sufijos.slice(1).forEach((s, i) => expect(s).toBe(sufijos[i] + 1));
      });
    });

    describe('aislamiento entre colegios', () => {
      it('el estudiante creado pertenece al colegio del token y el otro colegio no lo ve', async () => {
        const res = await post(valido({ nombres: 'SoloDeA', apellidos: 'Aislado' }), adminA.bearer).expect(201);

        expect((await fila(res.body.id)).tenant_id).toBe(colegioA.id);
        const vistoPorB = await listar(token.adminB, '&q=Aislado').expect(200);
        expect(vistoPorB.body.meta.total).toBe(0);
        const vistoPorA = await listar(adminA.bearer, '&q=Aislado').expect(200);
        expect(vistoPorA.body.meta.total).toBe(1);
      });

      it('un tenant_id en el cuerpo se RECHAZA y no se crea nada', async () => {
        const antes = await totalEn(colegioB.id);
        await post(valido({ tenant_id: colegioB.id }), adminA.bearer).expect(400);
        await post(valido({ tenantId: colegioB.id }), adminA.bearer).expect(400);
        expect(await totalEn(colegioB.id)).toBe(antes);
      });

      it('la misma cédula en OTRO colegio es válida (la unicidad es por colegio)', async () => {
        await post(valido({ cedula: '00200000001' }), adminA.bearer).expect(201);
        await post(valido({ cedula: '00200000001' }), token.adminB).expect(201);
      });
    });

    describe('integridad', () => {
      it('cédula repetida en el colegio → 409 y no queda nada (ni estudiante ni auditoría)', async () => {
        await post(valido({ cedula: '00300000001' }), adminA.bearer).expect(201);
        const antes = await totalEn(colegioA.id);
        const [logsAntes] = await fx.pool.query<RowDataPacket[]>("select count(*) as n from activity_logs where tenant_id = ? and accion = 'estudiante.creado'", [colegioA.id]);

        const res = await post(valido({ cedula: '00300000001', nombres: 'Repetida' }), adminA.bearer).expect(409);

        expect(res.body.message).toMatch(/cédula/i);
        expect(await totalEn(colegioA.id)).toBe(antes);
        const [logsDespues] = await fx.pool.query<RowDataPacket[]>("select count(*) as n from activity_logs where tenant_id = ? and accion = 'estudiante.creado'", [colegioA.id]);
        expect(Number(logsDespues[0].n)).toBe(Number(logsAntes[0].n));
      });

      it('número de matrícula dado por el cliente y repetido → 409 con su mensaje (no se reintenta)', async () => {
        await post(valido({ numeroMatricula: 'DUP-1' }), adminA.bearer).expect(201);
        const res = await post(valido({ numeroMatricula: 'DUP-1', nombres: 'Otro' }), adminA.bearer).expect(409);
        expect(res.body.message).toMatch(/matrícula/i);
      });

      it('un número de matrícula de un estudiante BORRADO tampoco se puede reutilizar (la restricción única los cuenta)', async () => {
        const creado = (await post(valido({ numeroMatricula: 'BORRADO-1' }), adminA.bearer).expect(201)).body;
        await borrar(creado.id, adminA.bearer).expect(204);
        await post(valido({ numeroMatricula: 'BORRADO-1', nombres: 'Reuso' }), adminA.bearer).expect(409);
      });
    });

    describe('validación', () => {
      it.each(['nombres', 'apellidos', 'fechaNacimiento', 'sexo', 'estado'])('falta el obligatorio "%s" → 400 que lo nombra', async (campo) => {
        const cuerpo = valido() as Record<string, unknown>;
        delete cuerpo[campo];
        const res = await post(cuerpo, adminA.bearer).expect(400);
        expect(res.body.errors.map((e: { campo: string }) => e.campo)).toContain(campo);
      });

      it.each([
        ['fecha futura', { fechaNacimiento: '2999-01-01' }],
        ['sexo inválido', { sexo: 'X' }],
        ['estado inválido', { estado: 'borrado' }],
        ['correo inválido', { email: 'no-es-correo' }],
        ['nombre demasiado corto', { nombres: 'A' }],
        ['tutorParentesco de 51', { tutorParentesco: 'a'.repeat(51) }],
        ['nacionalidad null', { nacionalidad: null }],
        ['numeroMatricula vacío', { numeroMatricula: '' }],
      ])('%s → 400', async (_n, extra) => {
        await post(valido(extra), adminA.bearer).expect(400);
      });

      it('grupo_id y foto (fuera de alcance) se rechazan como campos desconocidos y no se crea nada', async () => {
        const antes = await totalEn(colegioA.id);
        await post(valido({ grupo_id: 1 }), adminA.bearer).expect(400);
        await post(valido({ foto: 'x.png' }), adminA.bearer).expect(400);
        expect(await totalEn(colegioA.id)).toBe(antes);
      });

      it('cuerpo vacío → 400', async () => {
        await post({}, adminA.bearer).expect(400);
      });
    });

    describe('permisos', () => {
      it('sin token → 401; solo lectura → 403; alumno → 403; y no se crea nada', async () => {
        const antes = await totalEn(colegioA.id);
        await post(valido()).expect(401);
        await post(valido(), token.soloLectura).expect(403);
        await post(valido(), token.alumno).expect(403);
        expect(await totalEn(colegioA.id)).toBe(antes);
      });
    });
  });

  describe('DELETE /api/v1/estudiantes/:id', () => {
    const crear = async (extra: Record<string, unknown> = {}, tok = adminA.bearer) =>
      (await post(valido(extra), tok).expect(201)).body as { id: number; numeroMatricula: string };

    it('borrado lógico: 204, deleted_at y updated_at marcados, ya no aparece y se audita como Laravel', async () => {
      const { id } = await crear({ nombres: 'Borrame', apellidos: 'Pronto' });
      await fx.pool.query("update estudiantes set updated_at = '2020-01-01 00:00:00' where id = ?", [id]);

      const res = await borrar(id, adminA.bearer).expect(204);
      expect(res.text).toBe('');

      const f = await fila(id);
      expect(f.deleted_at).toBeTruthy();
      expect(String(f.updated_at)).not.toContain('2020-01-01'); // Eloquent también toca updated_at
      expect((await listar(adminA.bearer, '&q=Borrame').expect(200)).body.meta.total).toBe(0);

      const logs = await auditoria(id);
      expect(logs.map((l) => l.accion)).toEqual(['estudiante.creado', 'estudiante.eliminado']); // sin "actualizado": Laravel no lo dispara
      expect(logs[1]).toMatchObject({
        tenant_id: colegioA.id,
        user_id: adminA.userId,
        descripcion: 'Estudiante eliminado: Pronto, Borrame',
      });
    });

    it('la fila se CONSERVA (borrado lógico): no se elimina físicamente', async () => {
      const { id } = await crear();
      await borrar(id, adminA.bearer).expect(204);
      expect(await fila(id)).toBeDefined();
    });

    it('borrar dos veces → el segundo es 404 y no duplica la auditoría', async () => {
      const { id } = await crear();
      await borrar(id, adminA.bearer).expect(204);
      await borrar(id, adminA.bearer).expect(404);
      expect(await auditoria(id, 'estudiante.eliminado')).toHaveLength(1);
    });

    it('un estudiante borrado ya no se puede editar (404)', async () => {
      const { id } = await crear();
      await borrar(id, adminA.bearer).expect(204);
      await request(app.getHttpServer())
        .patch(`/api/v1/estudiantes/${id}`)
        .set('Authorization', `Bearer ${adminA.bearer}`)
        .send({ nombres: 'Revivido' })
        .expect(404);
    });

    it('un admin NO puede borrar un estudiante de otro colegio (404) y nada cambia ni se audita', async () => {
      const { id } = await crear({ nombres: 'DeB' }, token.adminB);

      await borrar(id, adminA.bearer).expect(404);

      expect((await fila(id)).deleted_at).toBeNull();
      expect(await auditoria(id, 'estudiante.eliminado')).toHaveLength(0);
    });

    it('id inexistente → 404; id inválido → 400', async () => {
      await borrar(999999999, adminA.bearer).expect(404);
      for (const malo of ['abc', '0', '-3', '1.5']) await borrar(encodeURIComponent(malo), adminA.bearer).expect(400);
    });

    it('permisos: sin token 401, solo lectura 403, alumno 403, y el estudiante sigue ahí', async () => {
      const { id } = await crear();
      await borrar(id).expect(401);
      await borrar(id, token.soloLectura).expect(403);
      await borrar(id, token.alumno).expect(403);
      expect((await fila(id)).deleted_at).toBeNull();
    });

    it('dos borrados simultáneos: uno gana (204), el otro 404, y la auditoría queda una sola vez', async () => {
      const { id } = await crear();
      const [r1, r2] = await Promise.all([borrar(id, adminA.bearer), borrar(id, adminA.bearer)]);
      expect([r1.status, r2.status].sort()).toEqual([204, 404]);
      expect(await auditoria(id, 'estudiante.eliminado')).toHaveLength(1);
    });
  });
});
