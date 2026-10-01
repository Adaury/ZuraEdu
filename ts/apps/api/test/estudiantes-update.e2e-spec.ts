import type { INestApplication } from '@nestjs/common';
import { Test } from '@nestjs/testing';
import { RowDataPacket } from 'mysql2/promise';
import request from 'supertest';
import { NOMBRES_COLUMNAS_EDITABLES } from '../src/estudiantes/estudiante-columnas';
import { DATABASE_URL, Fixtures, TenantCreado } from './helpers/fixtures';

// Debe fijarse ANTES de importar AppModule: ConfigModule lee process.env al crear el módulo.
process.env.DATABASE_URL = DATABASE_URL;
process.env.AUTH_CACHE_TTL_SECONDS = '0'; // efecto inmediato de los cambios de datos

import { AppModule } from '../src/app.module';

/**
 * PATCH /api/v1/estudiantes/:id contra MySQL real: edición parcial con auditoría idéntica a la de Laravel.
 * Cada prueba crea sus propios colegios, estudiantes, usuarios y tokens, y los borra al terminar.
 */
describe('API · editar estudiante (e2e, MySQL real)', () => {
  const fx = new Fixtures();
  let app: INestApplication;

  let colegioA: TenantCreado;
  let colegioB: TenantCreado;
  let a1: number; // estudiante del colegio A, con cédula
  let a2: number; // otro estudiante de A, con otra cédula
  let b1: number; // estudiante del colegio B
  let adminA: { userId: number; bearer: string };
  const token = { adminB: '', soloLectura: '', alumno: '' };

  const CEDULA_A1 = '00100000001';
  const CEDULA_A2 = '00100000002';
  const CEDULA_B1 = '00100000003';

  const patch = (id: number | string, cuerpo: unknown, tok?: string) => {
    let r = request(app.getHttpServer()).patch(`/api/v1/estudiantes/${id}`).send(cuerpo as object);
    if (tok) r = r.set('Authorization', `Bearer ${tok}`);
    return r;
  };

  const fila = async (id: number) => {
    const [r] = await fx.pool.query<RowDataPacket[]>('select * from estudiantes where id = ?', [id]);
    return r[0];
  };

  /** Registros de auditoría del estudiante, en orden de escritura. `accion` filtra por tipo. */
  const auditoria = async (modeloId: number, accion?: string) => {
    const [r] = await fx.pool.query<RowDataPacket[]>(
      "select * from activity_logs where modelo = 'App\\\\Models\\\\Estudiante' and modelo_id = ? order by id",
      [modeloId],
    );
    return accion ? r.filter((l) => l.accion === accion) : r;
  };

  beforeAll(async () => {
    colegioA = await fx.crearTenant('a');
    colegioB = await fx.crearTenant('b');

    a1 = await fx.crearEstudiante(colegioA.id, 'Ana', 'Perez', 1, CEDULA_A1);
    a2 = await fx.crearEstudiante(colegioA.id, 'Luis', 'Gomez', 2, CEDULA_A2);
    b1 = await fx.crearEstudiante(colegioB.id, 'Zoe', 'Aislada', 3, CEDULA_B1);

    const idAdminA = await fx.crearUsuario(colegioA.id, 'adminA', 'Administrador');
    adminA = { userId: idAdminA, bearer: (await fx.crearToken(idAdminA)).bearer };
    token.adminB = (await fx.crearToken(await fx.crearUsuario(colegioB.id, 'adminB', 'Administrador'))).bearer;
    // Caja / Finanzas puede VER estudiantes pero no gestionarlos (lo verifica el propio Laravel con una prueba).
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

  describe('edición correcta y auditoría', () => {
    it('cambia el estado, devuelve el estudiante actualizado y audita con el formato de Laravel', async () => {
      const res = await patch(a1, { estado: 'inactivo' }, adminA.bearer).expect(200);

      expect(res.body).toMatchObject({ id: a1, estado: 'inactivo', nombres: 'Ana', apellidos: 'Perez', cedula: CEDULA_A1 });
      expect((await fila(a1)).estado).toBe('inactivo');

      const [log, ...resto] = await auditoria(a1, 'estudiante.editado');
      expect(resto).toHaveLength(0);
      expect(log).toMatchObject({
        tenant_id: colegioA.id,
        user_id: adminA.userId, // sale del token, no del cuerpo
        accion: 'estudiante.editado',
        modelo: 'App\\Models\\Estudiante',
        modelo_id: a1,
        descripcion: `Estudiante #${a1}: estado: activo → inactivo`,
      });
      expect(log.ip).toBeTruthy();
      expect(log.created_at).toBeTruthy();
    });

    it('escribe los DOS registros que genera Laravel (el del observer primero) con su formato', async () => {
      const id = await fx.crearEstudiante(colegioA.id, 'Marta', 'Diaz', 30, '002');
      await patch(id, { estado: 'inactivo', nombres: 'Martina' }, adminA.bearer).expect(200);

      const logs = await auditoria(id);
      expect(logs.map((l) => l.accion)).toEqual(['estudiante.actualizado', 'estudiante.editado']);
      // EstudianteObserver::updated(): valores NUEVOS de apellidos/nombres y las columnas cambiadas + updated_at
      expect(logs[0].descripcion).toBe('Estudiante actualizado: Diaz, Martina | Campos: nombres, estado, updated_at');
      expect(logs[1].descripcion).toBe(`Estudiante #${id}: nombres: Marta → Martina | estado: activo → inactivo`);
      for (const l of logs) {
        expect(l).toMatchObject({ tenant_id: colegioA.id, user_id: adminA.userId, modelo_id: id });
        // Se arma con join para no depender de escribir barras invertidas a mano (Estudiante::class de PHP).
        expect(l.modelo).toBe(['App', 'Models', 'Estudiante'].join('\\'));
      }
    });

    it('el registro del observer lista solo la columna que cambió más updated_at', async () => {
      const id = await fx.crearEstudiante(colegioA.id, 'Solo', 'Estado', 31);
      await patch(id, { estado: 'egresado' }, adminA.bearer).expect(200);
      expect((await auditoria(id, 'estudiante.actualizado'))[0].descripcion).toBe(
        'Estudiante actualizado: Estado, Solo | Campos: estado, updated_at',
      );
    });

    it('varios campos: orden fijo de la descripción, "—" para nulos y fecha como la escribe Laravel', async () => {
      const id = await fx.crearEstudiante(colegioA.id, 'Marta', 'Diaz', 10); // sin cédula ni fecha de nacimiento
      await patch(id, { estado: 'egresado', nombres: 'Martina', cedula: '00100000099', fechaNacimiento: '2012-05-01' }, adminA.bearer).expect(200);

      const [log] = await auditoria(id, 'estudiante.editado');
      expect(log.descripcion).toBe(
        `Estudiante #${id}: cedula: — → 00100000099 | nombres: Marta → Martina | fecha_nacimiento: — → 2012-05-01 00:00:00 | estado: activo → egresado`,
      );
    });

    it('quitar la cédula (null) se audita como "—"', async () => {
      const id = await fx.crearEstudiante(colegioA.id, 'Pedro', 'Ruiz', 11, '00100000077');
      await patch(id, { cedula: null }, adminA.bearer).expect(200);

      expect((await fila(id)).cedula).toBeNull();
      expect((await auditoria(id, 'estudiante.editado'))[0].descripcion).toBe(`Estudiante #${id}: cedula: 00100000077 → —`);
    });

    it('si no cambia ningún campo responde 200 y NO escribe ni audita (igual que Laravel)', async () => {
      const id = await fx.crearEstudiante(colegioA.id, 'Sin', 'Cambios', 12, '00100000055');
      const antes = await fila(id);

      const res = await patch(id, { nombres: 'Sin', estado: 'activo' }, adminA.bearer).expect(200);

      expect(res.body.nombres).toBe('Sin');
      expect(await auditoria(id)).toHaveLength(0);
      expect((await fila(id)).updated_at).toEqual(antes.updated_at);
    });

    it('actualiza updated_at cuando sí hay cambios', async () => {
      const id = await fx.crearEstudiante(colegioA.id, 'Con', 'Cambio', 13);
      await fx.pool.query("update estudiantes set updated_at = '2020-01-01 00:00:00' where id = ?", [id]);

      await patch(id, { nombres: 'Cambiado' }, adminA.bearer).expect(200);

      expect(String((await fila(id)).updated_at)).not.toContain('2020-01-01');
    });
  });

  describe('aislamiento multi-tenant', () => {
    it('un admin NO puede editar un estudiante de otro colegio (404) y nada cambia ni se audita', async () => {
      await patch(b1, { estado: 'inactivo', nombres: 'Hackeado' }, adminA.bearer).expect(404);

      const intacto = await fila(b1);
      expect(intacto).toMatchObject({ estado: 'activo', nombres: 'Zoe', tenant_id: colegioB.id });
      expect(await auditoria(b1)).toHaveLength(0);
    });

    it('el admin de B sí puede editar a SU estudiante', async () => {
      await patch(b1, { nombres: 'Zoe Maria' }, token.adminB).expect(200);
      expect((await fila(b1)).nombres).toBe('Zoe Maria');
      expect((await auditoria(b1))[0].tenant_id).toBe(colegioB.id);
    });

    it('un estudiante borrado lógicamente no se puede editar (404)', async () => {
      const id = await fx.crearEstudiante(colegioA.id, 'Borrado', 'Logico', 14);
      await fx.pool.query('update estudiantes set deleted_at = utc_timestamp() where id = ?', [id]);

      await patch(id, { nombres: 'Revivido' }, adminA.bearer).expect(404);
      expect((await fila(id)).nombres).toBe('Borrado');
    });

    it('un id inexistente → 404', async () => {
      await patch(999999999, { nombres: 'Nadie' }, adminA.bearer).expect(404);
    });

    it('la misma cédula en OTRO colegio es válida (la unicidad es por colegio)', async () => {
      // CEDULA_B1 pertenece a un estudiante del colegio B; en A no hay conflicto.
      const id = await fx.crearEstudiante(colegioA.id, 'Otra', 'Cedula', 15);
      await patch(id, { cedula: CEDULA_B1 }, adminA.bearer).expect(200);
      expect((await fila(id)).cedula).toBe(CEDULA_B1);
    });
  });

  describe('permisos', () => {
    it('sin token → 401 y no cambia nada', async () => {
      await patch(a2, { nombres: 'Anonimo' }).expect(401);
      expect((await fila(a2)).nombres).toBe('Luis');
    });

    it('un rol que solo VE estudiantes (sin gestionar-estudiantes) → 403 y no cambia nada', async () => {
      await patch(a2, { nombres: 'Lector' }, token.soloLectura).expect(403);
      expect((await fila(a2)).nombres).toBe('Luis');
      expect(await auditoria(a2)).toHaveLength(0);
    });

    it('un alumno → 403', async () => {
      await patch(a2, { nombres: 'Alumno' }, token.alumno).expect(403);
      expect((await fila(a2)).nombres).toBe('Luis');
    });
  });

  describe('validación', () => {
    it('estado fuera del ENUM → 400', async () => {
      const res = await patch(a2, { estado: 'borrado' }, adminA.bearer).expect(400);
      expect(res.body.errors[0].campo).toBe('estado');
      expect((await fila(a2)).estado).toBe('activo');
    });

    it('un campo desconocido como tenant_id se RECHAZA (no se ignora) y el tenant no cambia', async () => {
      await patch(a2, { nombres: 'Mover', tenant_id: colegioB.id }, adminA.bearer).expect(400);
      const f = await fila(a2);
      expect(f.tenant_id).toBe(colegioA.id);
      expect(f.nombres).toBe('Luis');
    });

    it('cuerpo vacío → 400', async () => {
      await patch(a2, {}, adminA.bearer).expect(400);
    });

    it.each(['abc', '0', '-5', '1.5', '1 or 1=1'])('id inválido %p → 400', async (id) => {
      await patch(encodeURIComponent(id), { nombres: 'Xx' }, adminA.bearer).expect(400);
    });

    it('fecha de nacimiento futura → 400', async () => {
      await patch(a2, { fechaNacimiento: '2999-01-01' }, adminA.bearer).expect(400);
    });

    it('nombre demasiado corto → 400', async () => {
      await patch(a2, { nombres: 'A' }, adminA.bearer).expect(400);
    });
  });

  describe('integridad', () => {
    it('cédula repetida dentro del colegio → 409, sin cambios parciales y sin auditoría', async () => {
      // a1 ya tiene CEDULA_A1; intentar darle a a2 esa misma cédula y de paso cambiar el nombre.
      const res = await patch(a2, { cedula: CEDULA_A1, nombres: 'ParcialNo' }, adminA.bearer).expect(409);
      expect(res.body.message).toMatch(/cédula/i);

      const f = await fila(a2);
      expect(f.cedula).toBe(CEDULA_A2); // la transacción se deshizo: tampoco cambió el nombre
      expect(f.nombres).toBe('Luis');
      expect(await auditoria(a2)).toHaveLength(0);
    });

    it('dos ediciones simultáneas de campos distintos se conservan las dos (la fila se bloquea)', async () => {
      const id = await fx.crearEstudiante(colegioA.id, 'Base', 'Inicial', 20);
      await Promise.all([
        patch(id, { nombres: 'NombreUno' }, adminA.bearer).expect(200),
        patch(id, { apellidos: 'ApellidoDos' }, adminA.bearer).expect(200),
      ]);

      const f = await fila(id);
      expect(f.nombres).toBe('NombreUno');
      expect(f.apellidos).toBe('ApellidoDos'); // ninguna edición pisó a la otra
      expect(await auditoria(id, 'estudiante.editado')).toHaveLength(2);
      expect(await auditoria(id, 'estudiante.actualizado')).toHaveLength(2); // y el del observer, uno por edición
    });

    it('dos ediciones simultáneas del MISMO campo dejan una cadena de auditoría coherente (sin "antes" repetido)', async () => {
      const id = await fx.crearEstudiante(colegioA.id, 'Carrera', 'Estado', 21);
      await Promise.all([
        patch(id, { estado: 'inactivo' }, adminA.bearer).expect(200),
        patch(id, { estado: 'egresado' }, adminA.bearer).expect(200),
      ]);

      const [primero, segundo] = (await auditoria(id, 'estudiante.editado')).map((l) => l.descripcion as string);
      const final = (await fila(id)).estado as string;

      // Sin bloqueo, ambos registros dirían "activo → ..." y uno perdería su "antes" real.
      expect(primero).toMatch(/estado: activo → (inactivo|egresado)$/);
      const estadoTrasPrimero = primero.split('→ ')[1];
      expect(segundo).toBe(`Estudiante #${id}: estado: ${estadoTrasPrimero} → ${final}`);
      expect(final).not.toBe(estadoTrasPrimero);
    });
  });

  describe('edición completa de los demás campos', () => {
    it('un campo NO sensible (teléfono) escribe SOLO el registro del observer, no el "editado"', async () => {
      const id = await fx.crearEstudiante(colegioA.id, 'Rosa', 'Mota', 40);
      await patch(id, { telefono: '8095551234' }, adminA.bearer).expect(200);

      expect((await fila(id)).telefono).toBe('8095551234');
      const logs = await auditoria(id);
      expect(logs.map((l) => l.accion)).toEqual(['estudiante.actualizado']); // Laravel no escribe "editado" sin cambios sensibles
      expect(logs[0].descripcion).toBe('Estudiante actualizado: Mota, Rosa | Campos: telefono, updated_at');
    });

    it('mezcla de campos: el observer los lista en el orden de la tabla y el "editado" solo detalla los sensibles', async () => {
      const id = await fx.crearEstudiante(colegioA.id, 'Mix', 'Campos', 41);
      await patch(
        id,
        { notasMedicas: 'Alergia', estado: 'inactivo', telefono: '809', numeroMatricula: 'NUEVA-41', sexo: 'M' },
        adminA.bearer,
      ).expect(200);

      const logs = await auditoria(id);
      expect(logs.map((l) => l.accion)).toEqual(['estudiante.actualizado', 'estudiante.editado']);
      expect(logs[0].descripcion).toBe(
        'Estudiante actualizado: Campos, Mix | Campos: numero_matricula, sexo, telefono, estado, notas_medicas, updated_at',
      );
      expect(logs[1].descripcion).toBe(`Estudiante #${id}: estado: activo → inactivo`); // numero_matricula/sexo/telefono no son sensibles
      expect(await fila(id)).toMatchObject({ numero_matricula: 'NUEVA-41', sexo: 'M', telefono: '809', estado: 'inactivo', notas_medicas: 'Alergia' });
    });

    it('solo se modifican las columnas enviadas: el resto queda intacto', async () => {
      const id = await fx.crearEstudiante(colegioA.id, 'Resto', 'Intacto', 42, '009');
      await fx.pool.query("update estudiantes set direccion = 'Calle 5', sector = 'Norte', tutor_nombre = 'Madre X' where id = ?", [id]);

      await patch(id, { telefono: '555' }, adminA.bearer).expect(200);

      expect(await fila(id)).toMatchObject({ direccion: 'Calle 5', sector: 'Norte', tutor_nombre: 'Madre X', cedula: '009', nombres: 'Resto' });
    });

    it('vaciar un campo (null o cadena vacía) lo guarda como null y lo audita como cambio', async () => {
      const id = await fx.crearEstudiante(colegioA.id, 'Vacia', 'Campo', 43);
      await fx.pool.query("update estudiantes set email = 'v@example.com', sector = 'Sur' where id = ?", [id]);

      await patch(id, { email: null, sector: '' }, adminA.bearer).expect(200);

      const f = await fila(id);
      expect(f.email).toBeNull();
      expect(f.sector).toBeNull();
      expect((await auditoria(id, 'estudiante.actualizado'))[0].descripcion).toBe(
        'Estudiante actualizado: Campo, Vacia | Campos: email, sector, updated_at',
      );
    });

    it('el orden de columnas editables es el orden REAL de la tabla estudiantes (lo que usa Eloquent)', async () => {
      const [cols] = await fx.pool.query<RowDataPacket[]>(
        "select column_name as c from information_schema.columns where table_schema = database() and table_name = 'estudiantes' order by ordinal_position",
      );
      const enTabla = cols.map((r) => r.c as string).filter((c) => (NOMBRES_COLUMNAS_EDITABLES as readonly string[]).includes(c));
      expect([...NOMBRES_COLUMNAS_EDITABLES]).toEqual(enTabla);
    });

    it('número de matrícula repetido en el colegio → 409 con su propio mensaje y sin cambios', async () => {
      const a = await fx.crearEstudiante(colegioA.id, 'Dup', 'Uno', 44);
      const b = await fx.crearEstudiante(colegioA.id, 'Dup', 'Dos', 45);
      const matriculaDeA = (await fila(a)).numero_matricula as string;

      const res = await patch(b, { numeroMatricula: matriculaDeA, telefono: '111' }, adminA.bearer).expect(409);

      expect(res.body.message).toMatch(/matrícula/i);
      const f = await fila(b);
      expect(f.telefono).toBeNull(); // se deshizo todo, también el teléfono
      expect(await auditoria(b)).toHaveLength(0);
    });

    it('el mismo número de matrícula en OTRO colegio es válido (la unicidad es por colegio)', async () => {
      const enB = (await fila(b1)).numero_matricula as string;
      const id = await fx.crearEstudiante(colegioA.id, 'Mismo', 'Numero', 46);
      await patch(id, { numeroMatricula: enB }, adminA.bearer).expect(200);
      expect((await fila(id)).numero_matricula).toBe(enB);
    });

    it('valores demasiado largos para la COLUMNA dan 400 (no el 500 de base de datos que daría PHP)', async () => {
      const id = await fx.crearEstudiante(colegioA.id, 'Largo', 'Valor', 47);
      await patch(id, { tutorParentesco: 'a'.repeat(51) }, adminA.bearer).expect(400);
      await patch(id, { tutorTrabajo: 'a'.repeat(101) }, adminA.bearer).expect(400);
      expect((await fila(id)).tutor_parentesco).toBeNull();
    });

    it('nacionalidad no admite null (columna NOT NULL) y un correo inválido da 400', async () => {
      const id = await fx.crearEstudiante(colegioA.id, 'Pais', 'Correo', 48);
      await patch(id, { nacionalidad: null }, adminA.bearer).expect(400);
      await patch(id, { email: 'no-es-correo' }, adminA.bearer).expect(400);
    });

    it('tutor_email (regla de Laravel sin columna real) y foto se rechazan como campos desconocidos', async () => {
      await patch(a2, { tutorEmail: 'x@y.com' }, adminA.bearer).expect(400);
      await patch(a2, { foto: 'x.png' }, adminA.bearer).expect(400);
    });

    it('la edición de campos no sensibles respeta el aislamiento entre colegios (404 y nada cambia)', async () => {
      await patch(b1, { telefono: '000', numeroMatricula: 'HACK' }, adminA.bearer).expect(404);
      const f = await fila(b1);
      expect(f.telefono).toBeNull();
      expect(f.numero_matricula).not.toBe('HACK');
    });
  });
});
