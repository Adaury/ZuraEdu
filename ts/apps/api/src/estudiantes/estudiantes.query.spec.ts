import { escapeLike, estudiantesQuerySchema } from './estudiantes.query';

describe('estudiantesQuerySchema', () => {
  it('aplica valores por defecto', () => {
    expect(estudiantesQuerySchema.parse({})).toEqual({ page: 1, perPage: 30, q: undefined, estado: undefined });
  });

  it('convierte strings de la URL a números', () => {
    const r = estudiantesQuerySchema.parse({ page: '3', perPage: '50' });
    expect(r.page).toBe(3);
    expect(r.perPage).toBe(50);
  });

  it('rechaza perPage por encima del máximo (evita volcar la tabla completa)', () => {
    expect(estudiantesQuerySchema.safeParse({ perPage: '101' }).success).toBe(false);
  });

  it.each([{ page: '0' }, { page: '-1' }, { page: 'abc' }, { perPage: '0' }, { estado: 'borrado' }])(
    'rechaza %p',
    (q) => {
      expect(estudiantesQuerySchema.safeParse(q).success).toBe(false);
    },
  );

  it('recorta espacios y trata la búsqueda vacía como ausente', () => {
    expect(estudiantesQuerySchema.parse({ q: '  perez  ' }).q).toBe('perez');
    expect(estudiantesQuerySchema.parse({ q: '   ' }).q).toBeUndefined();
  });

  it('limita el largo de la búsqueda', () => {
    expect(estudiantesQuerySchema.safeParse({ q: 'a'.repeat(101) }).success).toBe(false);
  });

  it('solo acepta los estados reales del ENUM', () => {
    expect(estudiantesQuerySchema.parse({ estado: 'egresado' }).estado).toBe('egresado');
  });
});

describe('escapeLike', () => {
  it('escapa % _ y \\ para que la búsqueda sea literal', () => {
    expect(escapeLike('100%')).toBe('100\\%');
    expect(escapeLike('a_b')).toBe('a\\_b');
    expect(escapeLike('a\\b')).toBe('a\\\\b');
  });

  it('no altera texto normal', () => {
    expect(escapeLike('Pérez García')).toBe('Pérez García');
  });
});
