import { actualizarEstudianteSchema, idEstudianteSchema } from './estudiantes.update';

const ok = (o: unknown) => actualizarEstudianteSchema.safeParse(o);

describe('actualizarEstudianteSchema', () => {
  it('acepta una edición parcial de un solo campo', () => {
    expect(ok({ estado: 'inactivo' })).toMatchObject({ success: true, data: { estado: 'inactivo' } });
  });

  it('acepta todos los campos válidos a la vez', () => {
    expect(
      ok({ cedula: '00100000001', nombres: 'Ana', apellidos: 'Perez', fechaNacimiento: '2012-05-01', estado: 'activo' }).success,
    ).toBe(true);
  });

  it('rechaza un cuerpo vacío (tiene que cambiar algo)', () => {
    expect(ok({}).success).toBe(false);
  });

  describe('protección contra asignación masiva (strict)', () => {
    it.each(['tenant_id', 'tenantId', 'id', 'deleted_at', 'numero_matricula', 'numeroMatricula', 'user_id', 'sexo'])(
      'rechaza el campo no permitido "%s" en vez de ignorarlo',
      (campo) => {
        expect(ok({ estado: 'activo', [campo]: 1 }).success).toBe(false);
      },
    );
  });

  describe('estado', () => {
    it.each(['activo', 'inactivo', 'egresado', 'transferido'])('acepta %s', (estado) => {
      expect(ok({ estado }).success).toBe(true);
    });

    it.each(['borrado', 'ACTIVO', '', 'activa'])('rechaza %p', (estado) => {
      expect(ok({ estado }).success).toBe(false);
    });
  });

  describe('nombres y apellidos (2–100, como UpdateEstudianteRequest)', () => {
    it('recorta espacios', () => {
      expect(ok({ nombres: '  Ana  ' })).toMatchObject({ data: { nombres: 'Ana' } });
    });

    it('rechaza menos de 2 caracteres, vacío o solo espacios', () => {
      expect(ok({ nombres: 'A' }).success).toBe(false);
      expect(ok({ nombres: '' }).success).toBe(false);
      expect(ok({ apellidos: '    ' }).success).toBe(false);
    });

    it('rechaza más de 100 caracteres', () => {
      expect(ok({ nombres: 'a'.repeat(101) }).success).toBe(false);
      expect(ok({ nombres: 'a'.repeat(100) }).success).toBe(true);
    });

    it('rechaza valores que no son texto', () => {
      expect(ok({ nombres: 123 }).success).toBe(false);
      expect(ok({ nombres: null }).success).toBe(false);
    });
  });

  describe('cédula', () => {
    it('puede ser null para quitarla, y una cadena vacía también se guarda como null', () => {
      expect(ok({ cedula: null })).toMatchObject({ data: { cedula: null } });
      expect(ok({ cedula: '' })).toMatchObject({ data: { cedula: null } });
      expect(ok({ cedula: '   ' })).toMatchObject({ data: { cedula: null } });
    });

    it('máximo 20 caracteres', () => {
      expect(ok({ cedula: '1'.repeat(20) }).success).toBe(true);
      expect(ok({ cedula: '1'.repeat(21) }).success).toBe(false);
    });
  });

  describe('fecha de nacimiento', () => {
    it('acepta una fecha pasada con formato AAAA-MM-DD', () => {
      expect(ok({ fechaNacimiento: '2012-05-01' }).success).toBe(true);
    });

    it('rechaza hoy y fechas futuras ("debe ser anterior a hoy")', () => {
      const hoy = new Date().toISOString().slice(0, 10);
      expect(ok({ fechaNacimiento: hoy }).success).toBe(false);
      expect(ok({ fechaNacimiento: '2999-01-01' }).success).toBe(false);
    });

    it.each(['2012-02-31', '2012-13-01', '2012-00-10', '12/05/2012', '2012-5-1', '2012-05-01T00:00:00Z', 'ayer', ''])(
      'rechaza %p',
      (f) => {
        expect(ok({ fechaNacimiento: f }).success).toBe(false);
      },
    );

    it('acepta el 29 de febrero solo en año bisiesto', () => {
      expect(ok({ fechaNacimiento: '2012-02-29' }).success).toBe(true);
      expect(ok({ fechaNacimiento: '2013-02-29' }).success).toBe(false);
    });
  });
});

describe('idEstudianteSchema', () => {
  it('convierte el id de la URL a número entero positivo', () => {
    expect(idEstudianteSchema.parse('42')).toBe(42);
  });

  it.each(['0', '-1', '1.5', 'abc', '', '1; drop table estudiantes', '1 or 1=1'])('rechaza %p', (v) => {
    expect(idEstudianteSchema.safeParse(v).success).toBe(false);
  });
});
