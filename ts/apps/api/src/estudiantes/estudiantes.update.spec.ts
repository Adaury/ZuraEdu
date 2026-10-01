import { actualizarEstudianteSchema, crearEstudianteSchema, idEstudianteSchema } from './estudiantes.update';

const ok = (o: unknown) => actualizarEstudianteSchema.safeParse(o);

describe('crearEstudianteSchema', () => {
  const minimo = { nombres: 'Ana', apellidos: 'Perez', fechaNacimiento: '2012-05-01', sexo: 'F', estado: 'activo' };
  const crear = (o: unknown) => crearEstudianteSchema.safeParse(o);

  it('acepta los cinco campos obligatorios y nada más', () => {
    expect(crear(minimo)).toMatchObject({ success: true, data: minimo });
  });

  it.each(['nombres', 'apellidos', 'fechaNacimiento', 'sexo', 'estado'])('rechaza si falta el obligatorio "%s"', (campo) => {
    const { [campo]: _quitado, ...sinCampo } = minimo as Record<string, string>;
    const r = crear(sinCampo);
    expect(r.success).toBe(false);
    if (!r.success) expect(r.error.issues.map((i) => i.path.join('.'))).toContain(campo);
  });

  it('rechaza un cuerpo vacío', () => {
    expect(crear({}).success).toBe(false);
  });

  it('numeroMatricula, cédula, nacionalidad y el resto son opcionales', () => {
    expect(crear({ ...minimo, numeroMatricula: '2026-00099', cedula: '001', nacionalidad: 'Haitiana', telefono: '809' }).success).toBe(true);
  });

  it('NO inventa valores: lo que no se envía no aparece (nacionalidad la pone el DEFAULT de la columna)', () => {
    const r = crear(minimo);
    expect(r.success && 'nacionalidad' in r.data).toBe(false);
    expect(r.success && 'numeroMatricula' in r.data).toBe(false);
  });

  it('nacionalidad no admite null ni vacío; numeroMatricula vacío o null se rechaza (para autogenerar, se omite)', () => {
    expect(crear({ ...minimo, nacionalidad: null }).success).toBe(false);
    expect(crear({ ...minimo, nacionalidad: '' }).success).toBe(false);
    expect(crear({ ...minimo, numeroMatricula: '' }).success).toBe(false);
    expect(crear({ ...minimo, numeroMatricula: null }).success).toBe(false);
  });

  describe('protección contra asignación masiva (strict)', () => {
    it.each(['tenant_id', 'tenantId', 'id', 'user_id', 'userId', 'deleted_at', 'created_at', 'grupo_id', 'grupoId', 'foto', 'tutor_email'])(
      'rechaza el campo no permitido "%s"',
      (campo) => {
        expect(crear({ ...minimo, [campo]: 1 }).success).toBe(false);
      },
    );
  });

  it('aplica las mismas reglas que la edición (fecha futura, estado inválido, sexo inválido, nombre corto, correo)', () => {
    expect(crear({ ...minimo, fechaNacimiento: '2999-01-01' }).success).toBe(false);
    expect(crear({ ...minimo, estado: 'borrado' }).success).toBe(false);
    expect(crear({ ...minimo, sexo: 'X' }).success).toBe(false);
    expect(crear({ ...minimo, nombres: 'A' }).success).toBe(false);
    expect(crear({ ...minimo, email: 'no-es-correo' }).success).toBe(false);
    expect(crear({ ...minimo, tutorParentesco: 'a'.repeat(51) }).success).toBe(false);
  });

  it('recorta espacios y guarda los opcionales vacíos como null', () => {
    expect(crear({ ...minimo, nombres: '  Ana  ', telefono: '   ', sector: null })).toMatchObject({
      success: true,
      data: { nombres: 'Ana', telefono: null, sector: null },
    });
  });
});

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
    it.each(['tenant_id', 'tenantId', 'id', 'deleted_at', 'created_at', 'updated_at', 'user_id', 'userId', 'foto', 'tutor_email', 'tutorEmail', 'numero_matricula', 'fecha_nacimiento'])(
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

describe('campos de la edición completa', () => {
  it('acepta todos los campos editables a la vez', () => {
    const completo = {
      numeroMatricula: 'M-100', cedula: '00100000001', nombres: 'Ana', apellidos: 'Perez', fechaNacimiento: '2012-05-01',
      sexo: 'F', nacionalidad: 'Dominicana', lugarNacimiento: 'Santo Domingo', telefono: '8095551234', email: 'ana@example.com',
      direccion: 'Calle 1', sector: 'Centro', municipio: 'DN', provincia: 'Distrito Nacional', estado: 'activo',
      tutorNombre: 'Maria', tutorParentesco: 'Madre', tutorTelefono: '8095550000', tutorTrabajo: 'Docente', notasMedicas: 'Ninguna',
    };
    expect(ok(completo).success).toBe(true);
  });

  it('sexo solo M o F', () => {
    expect(ok({ sexo: 'M' }).success).toBe(true);
    expect(ok({ sexo: 'F' }).success).toBe(true);
    for (const malo of ['X', 'm', '', null]) expect(ok({ sexo: malo }).success).toBe(false);
  });

  it('los campos de texto opcionales se pueden vaciar (null o cadena vacía se guardan como null)', () => {
    for (const campo of ['lugarNacimiento', 'telefono', 'email', 'direccion', 'sector', 'municipio', 'provincia', 'tutorNombre', 'tutorParentesco', 'tutorTelefono', 'tutorTrabajo', 'notasMedicas']) {
      expect(ok({ [campo]: null })).toMatchObject({ success: true, data: { [campo]: null } });
      expect(ok({ [campo]: '   ' })).toMatchObject({ success: true, data: { [campo]: null } });
    }
  });

  it('nacionalidad NO admite null ni vacío (la columna es NOT NULL; Laravel dice nullable pero fallaría en la base)', () => {
    expect(ok({ nacionalidad: null }).success).toBe(false);
    expect(ok({ nacionalidad: '' }).success).toBe(false);
    expect(ok({ nacionalidad: 'Dominicana' }).success).toBe(true);
  });

  it('numeroMatricula no puede quedar vacío', () => {
    expect(ok({ numeroMatricula: '' }).success).toBe(false);
    expect(ok({ numeroMatricula: '   ' }).success).toBe(false);
    expect(ok({ numeroMatricula: null }).success).toBe(false);
  });

  it.each([
    ['numeroMatricula', 20], ['cedula', 20], ['nombres', 100], ['nacionalidad', 50], ['lugarNacimiento', 100], ['telefono', 20],
    ['email', 150], ['direccion', 500], ['sector', 100], ['municipio', 100], ['provincia', 100], ['tutorNombre', 150],
    ['tutorParentesco', 50], ['tutorTelefono', 20], ['tutorTrabajo', 100], ['notasMedicas', 2000],
  ])('%s: máximo %i caracteres (los de la columna real)', (campo, max) => {
    const valor = (n: number) => (campo === 'email' ? 'a'.repeat(n - 12) + '@example.com' : 'a'.repeat(n));
    expect(ok({ [campo]: valor(max) }).success).toBe(true);
    expect(ok({ [campo]: valor(max + 1) }).success).toBe(false);
  });

  it('tutorParentesco y tutorTrabajo usan el límite de la COLUMNA, más estricto que la regla de Laravel (150)', () => {
    expect(ok({ tutorParentesco: 'a'.repeat(51) }).success).toBe(false);
    expect(ok({ tutorTrabajo: 'a'.repeat(101) }).success).toBe(false);
  });

  it.each(['sin-arroba', 'a@', '@b.com', 'a b@c.com', 'a@b'])('rechaza el correo inválido %p', (email) => {
    expect(ok({ email }).success).toBe(false);
  });

  it('acepta un correo válido y lo recorta', () => {
    expect(ok({ email: '  ana@example.com  ' })).toMatchObject({ data: { email: 'ana@example.com' } });
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
