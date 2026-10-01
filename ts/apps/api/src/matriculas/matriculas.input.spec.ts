import { crearMatriculaSchema } from './matriculas.input';

const valido = { schoolYearId: 1, estudianteId: 2, grupoId: 3, fechaMatricula: '2026-08-15' };

describe('crearMatriculaSchema', () => {
  it('acepta el cuerpo mínimo y deja observaciones en null', () => {
    const r = crearMatriculaSchema.parse(valido);
    expect(r).toEqual({ ...valido, observaciones: null });
  });

  it('recorta observaciones y convierte la cadena vacía (o solo espacios) en null, como ConvertEmptyStringsToNull', () => {
    expect(crearMatriculaSchema.parse({ ...valido, observaciones: '  hola  ' }).observaciones).toBe('hola');
    expect(crearMatriculaSchema.parse({ ...valido, observaciones: '' }).observaciones).toBeNull();
    expect(crearMatriculaSchema.parse({ ...valido, observaciones: '   ' }).observaciones).toBeNull();
    expect(crearMatriculaSchema.parse({ ...valido, observaciones: null }).observaciones).toBeNull();
  });

  it.each(['schoolYearId', 'estudianteId', 'grupoId', 'fechaMatricula'])('%s es obligatorio', (campo) => {
    const { [campo]: _quitado, ...resto } = valido as Record<string, unknown>;
    expect(crearMatriculaSchema.safeParse(resto).success).toBe(false);
  });

  it.each([0, -1, 1.5, '1', null, Number.NaN])('rechaza un id inválido (%p)', (id) => {
    expect(crearMatriculaSchema.safeParse({ ...valido, grupoId: id }).success).toBe(false);
  });

  it.each(['2026-02-31', '2026-13-01', '15/08/2026', '2026-8-5', '', 'hoy'])('rechaza la fecha %p', (f) => {
    expect(crearMatriculaSchema.safeParse({ ...valido, fechaMatricula: f }).success).toBe(false);
  });

  it('acepta el 29 de febrero solo en año bisiesto', () => {
    expect(crearMatriculaSchema.safeParse({ ...valido, fechaMatricula: '2028-02-29' }).success).toBe(true);
    expect(crearMatriculaSchema.safeParse({ ...valido, fechaMatricula: '2027-02-29' }).success).toBe(false);
  });

  it.each(['tenantId', 'tenant_id', 'estado', 'numeroOrden', 'numero_orden', 'id', 'registradoPor'])(
    'rechaza el campo desconocido %s (el servidor decide colegio, estado y orden)',
    (campo) => {
      expect(crearMatriculaSchema.safeParse({ ...valido, [campo]: 1 }).success).toBe(false);
    },
  );

  it('limita observaciones a 5.000 caracteres', () => {
    expect(crearMatriculaSchema.safeParse({ ...valido, observaciones: 'x'.repeat(5000) }).success).toBe(true);
    expect(crearMatriculaSchema.safeParse({ ...valido, observaciones: 'x'.repeat(5001) }).success).toBe(false);
  });
});
