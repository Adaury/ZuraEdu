import {
  camposInvalidos,
  codigoPorEstado,
  consultaParaApi,
  cookieBorrada,
  enlaceListado,
  esCodigoError,
  idValido,
  ipDe,
  leerCampos,
  leerConsulta,
  leerCookie,
  MENSAJES,
  origenValido,
  serializarCookie,
} from './validacion';

const ESTADOS = ['activo', 'inactivo', 'egresado', 'transferido'] as const;

describe('idValido', () => {
  it('acepta solo enteros positivos escritos con dígitos', () => {
    expect(idValido('42')).toBe(42);
    expect(idValido('123456789012345')).toBe(123456789012345);
  });

  it.each(['0', '007', '-1', '1.5', '1e3', '0x10', ' 7', '7 ', '+7', '', 'abc', '1234567890123456', '1; drop table x', '../1'])('rechaza %p', (v) => {
    expect(idValido(v)).toBeNull();
  });
});

describe('leerConsulta', () => {
  it('valores por defecto', () => {
    expect(leerConsulta({}, ESTADOS)).toEqual({ q: '', estado: '', page: 1 });
  });

  it('recorta q y la limita a 100 caracteres (el máximo de la API)', () => {
    expect(leerConsulta({ q: '  Ana  ' }, ESTADOS).q).toBe('Ana');
    expect(leerConsulta({ q: 'x'.repeat(300) }, ESTADOS).q).toHaveLength(100);
  });

  it('solo acepta un estado válido; cualquier otro se ignora', () => {
    expect(leerConsulta({ estado: 'activo' }, ESTADOS).estado).toBe('activo');
    expect(leerConsulta({ estado: 'hacker' }, ESTADOS).estado).toBe('');
    expect(leerConsulta({ estado: 'constructor' }, ESTADOS).estado).toBe('');
  });

  it.each([['3', 3], ['1', 1], ['0', 1], ['-2', 1], ['abc', 1], ['1e3', 1], ['2.5', 1], ['9999999', 1], ['', 1]])('page %p → %p', (crudo, esperado) => {
    expect(leerConsulta({ page: crudo }, ESTADOS).page).toBe(esperado);
  });

  it('con un parámetro repetido toma el primero', () => {
    expect(leerConsulta({ q: ['uno', 'dos'], estado: ['activo', 'inactivo'], page: ['2', '9'] }, ESTADOS)).toEqual({ q: 'uno', estado: 'activo', page: 2 });
  });
});

describe('consultaParaApi y enlaceListado', () => {
  it('la consulta a la API lleva page y perPage, y solo los filtros presentes', () => {
    expect(consultaParaApi({ q: '', estado: '', page: 1 }, 20)).toBe('page=1&perPage=20');
    expect(consultaParaApi({ q: 'Ana María', estado: 'activo', page: 3 }, 20)).toBe('page=3&perPage=20&q=Ana+Mar%C3%ADa&estado=activo');
  });

  it('el valor de q se codifica: no puede inyectar parámetros', () => {
    const r = consultaParaApi({ q: 'x&perPage=100000&estado=activo', estado: '', page: 1 }, 20);
    expect(new URLSearchParams(r).get('perPage')).toBe('20');
    expect(new URLSearchParams(r).get('q')).toBe('x&perPage=100000&estado=activo');
  });

  it('el enlace de paginación conserva la búsqueda y omite la página 1', () => {
    expect(enlaceListado({ q: '', estado: '', page: 1 }, 1)).toBe('/estudiantes');
    expect(enlaceListado({ q: 'Ana', estado: 'activo', page: 1 }, 2)).toBe('/estudiantes?q=Ana&estado=activo&page=2');
  });
});

describe('origenValido (CSRF)', () => {
  it.each([
    ['https://demo.zuraedu.com', 'demo.zuraedu.com'],
    ['https://demo.zuraedu.com', 'demo.zuraedu.com:443'],
    ['http://localhost:3101', 'localhost:3101'],
    ['HTTPS://DEMO.ZURAEDU.COM', 'demo.zuraedu.com'],
  ])('acepta mismo sitio (%s vs %s)', (origin, host) => {
    expect(origenValido(origin, host)).toBe(true);
  });

  it.each([
    ['https://evil.com', 'demo.zuraedu.com'],
    ['https://demo.zuraedu.com.evil.com', 'demo.zuraedu.com'],
    ['https://evil.com/demo.zuraedu.com', 'demo.zuraedu.com'],
    ['null', 'demo.zuraedu.com'],
    ['no es una url', 'demo.zuraedu.com'],
    ['', 'demo.zuraedu.com'],
    [null, 'demo.zuraedu.com'],
    ['https://demo.zuraedu.com', null],
  ])('rechaza %p con host %p', (origin, host) => {
    expect(origenValido(origin as string | null, host as string | null)).toBe(false);
  });
});

describe('códigos de error', () => {
  it.each([[400, 'invalido'], [401, 'expirada'], [403, 'permiso'], [404, 'noexiste'], [409, 'duplicado'], [422, 'invalido'], [429, 'limite'], [500, 'servicio'], [0, 'servicio'], [502, 'servicio']])(
    'HTTP %i → %s',
    (estado, codigo) => {
      expect(codigoPorEstado(estado)).toBe(codigo);
    },
  );

  it('esCodigoError solo acepta los códigos definidos (no propiedades heredadas del objeto)', () => {
    expect(esCodigoError('invalido')).toBe(true);
    for (const malo of ['toString', 'constructor', '__proto__', 'hasOwnProperty', '', '<script>']) expect(esCodigoError(malo)).toBe(false);
  });

  it('todo código tiene un mensaje no vacío', () => {
    for (const mensaje of Object.values(MENSAJES)) expect(mensaje.length).toBeGreaterThan(5);
  });
});

describe('camposInvalidos y leerCampos', () => {
  it('toma solo los nombres de campo permitidos y sin repetir', () => {
    const cuerpo = { errors: [{ campo: 'cedula', mensaje: 'x' }, { campo: 'cedula', mensaje: 'y' }, { campo: 'tenantId', mensaje: 'z' }, { campo: '<img onerror=1>' }, { campo: 5 }, null, 'texto'] };
    expect(camposInvalidos(cuerpo)).toEqual(['cedula']);
  });

  it.each([null, undefined, 'x', 5, {}, { errors: 'no' }, { errors: null }])('con un cuerpo raro (%p) devuelve vacío', (cuerpo) => {
    expect(camposInvalidos(cuerpo)).toEqual([]);
  });

  it('leerCampos filtra la lista de la URL', () => {
    expect(leerCampos('nombres,apellidos,hack,<x>,estado')).toEqual(['nombres', 'apellidos', 'estado']);
    expect(leerCampos('')).toEqual([]);
  });
});

describe('cookies', () => {
  it('la cookie de sesión es HttpOnly, SameSite=Lax y con Max-Age; el token (con "|") se codifica', () => {
    const c = serializarCookie('zura_token', '12|abcDEF', { secure: false, maxAgeSegundos: 3600 });
    expect(c).toBe('zura_token=12%7CabcDEF; Path=/; HttpOnly; SameSite=Lax; Max-Age=3600');
  });

  it('con secure añade el atributo Secure', () => {
    expect(serializarCookie('a', 'b', { secure: true, maxAgeSegundos: 1 })).toContain('; Secure');
  });

  it('cookieBorrada vacía el valor y expira de inmediato', () => {
    const c = cookieBorrada('zura_token', true);
    expect(c).toContain('zura_token=;');
    expect(c).toContain('Max-Age=0');
    expect(c).toContain('HttpOnly');
  });

  it('leerCookie encuentra y decodifica el valor entre varias cookies', () => {
    expect(leerCookie('a=1; zura_token=12%7CabcDEF; b=2', 'zura_token')).toBe('12|abcDEF');
  });

  it.each([null, '', 'a=1; b=2', 'zura_token_otro=1', 'zura_token=%E0%A4%A'])('devuelve null con %p (ausente o mal codificada) sin lanzar', (cabecera) => {
    expect(leerCookie(cabecera as string | null, 'zura_token')).toBeNull();
  });

  it('no confunde una cookie con nombre parecido', () => {
    expect(leerCookie('xzura_token=malo; zura_token=bueno', 'zura_token')).toBe('bueno');
  });
});

describe('ipDe', () => {
  it('toma la ÚLTIMA IP de X-Forwarded-For: la añade el proxy de confianza; la primera la puede escribir el cliente', () => {
    expect(ipDe('203.0.113.7')).toBe('203.0.113.7');
    expect(ipDe('1.2.3.4, 203.0.113.7')).toBe('203.0.113.7'); // 1.2.3.4 es una IP falsa mandada por el cliente
    expect(ipDe('1.2.3.4, 5.6.7.8 ,  203.0.113.7  ')).toBe('203.0.113.7');
    expect(ipDe('2001:db8::1')).toBe('2001:db8::1');
  });

  it('si la última entrada no es una IP, no se cae a otra: devuelve null', () => {
    expect(ipDe('203.0.113.7, <script>')).toBeNull();
    expect(ipDe('203.0.113.7,')).toBeNull();
  });

  it.each([null, '', 'no-es-ip', '<script>', '1.2.3.4; drop', 'x'.repeat(60)])('descarta %p', (v) => {
    expect(ipDe(v as string | null)).toBeNull();
  });
});
