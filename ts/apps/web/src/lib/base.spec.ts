import { normalizarBase } from './base';
import { cookieBorrada, serializarCookie } from './validacion';

describe('normalizarBase', () => {
  it.each([
    [undefined, ''],
    [null, ''],
    ['', ''],
    ['/', ''],
    ['  ', ''],
    ['/nuevo', '/nuevo'],
    ['nuevo', '/nuevo'],
    ['/nuevo/', '/nuevo'],
    ['//nuevo//', '/nuevo'],
    ['/app/web', '/app/web'],
    ['/mi-app_2', '/mi-app_2'],
  ])('%p → %p', (entrada, esperado) => {
    expect(normalizarBase(entrada as string | null | undefined)).toBe(esperado);
  });

  it.each(['/a b', '/a?x=1', '/a#b', '/../etc', '/a/../b', '/a;b', '/ñandú', '/a\r\nSet-Cookie: x=1', '/a"b', '/<x>', 'http://evil.com', '/a//b', '/.oculto'])(
    'rechaza %p (acabaría dentro de cabeceras Location y Set-Cookie)',
    (entrada) => {
      expect(() => normalizarBase(entrada)).toThrow(/WEB_BASE_PATH/);
    },
  );
});

describe('cookie con Path del prefijo', () => {
  it('la cookie de sesión solo viaja bajo el prefijo (no llega a las rutas de Laravel)', () => {
    const c = serializarCookie('zura_token', 'x', { secure: true, maxAgeSegundos: 10, path: '/nuevo' });
    expect(c).toContain('Path=/nuevo;');
    expect(c).not.toContain('Path=/;');
  });

  it('sin path usa la raíz', () => {
    expect(serializarCookie('a', 'b', { secure: false, maxAgeSegundos: 1 })).toContain('Path=/;');
    expect(serializarCookie('a', 'b', { secure: false, maxAgeSegundos: 1, path: '' })).toContain('Path=/;');
  });

  it('para borrarla hay que usar el MISMO Path con el que se creó', () => {
    expect(cookieBorrada('zura_token', true, '/nuevo')).toContain('Path=/nuevo;');
    expect(cookieBorrada('zura_token', true, '/nuevo')).toContain('Max-Age=0');
  });
});
