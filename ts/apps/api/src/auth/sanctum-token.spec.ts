import { createHash } from 'node:crypto';
import { hashesIguales, hashToken, parseBearer } from './sanctum-token';

describe('sanctum-token', () => {
  describe('parseBearer', () => {
    it('lee "id|texto"', () => {
      expect(parseBearer('Bearer 12|abcDEF123')).toEqual({ id: 12, plain: 'abcDEF123' });
    });

    it('acepta texto sin id (Sanctum busca por hash)', () => {
      expect(parseBearer('Bearer solotexto')).toEqual({ id: null, plain: 'solotexto' });
    });

    it('el esquema es insensible a mayúsculas y tolera espacios', () => {
      expect(parseBearer('  bearer   5|xyz  ')).toEqual({ id: 5, plain: 'xyz' });
    });

    it.each([
      undefined,
      '',
      'Basic abc',
      'Bearer',
      'Bearer ',
      'Bearer |abc',
      'Bearer 0|abc',
      'Bearer -3|abc',
      'Bearer 1.5|abc',
      'Bearer abc|def',
      'Bearer 7|',
    ])('rechaza %p', (valor) => {
      expect(parseBearer(valor as string | undefined)).toBeNull();
    });

    it('un "|" dentro del texto no rompe el parseo (solo el primero separa el id)', () => {
      expect(parseBearer('Bearer 3|a|b')).toEqual({ id: 3, plain: 'a|b' });
    });
  });

  describe('hashToken', () => {
    it('es sha256 hexadecimal del texto plano (igual que Sanctum)', () => {
      const esperado = createHash('sha256').update('mi-token').digest('hex');
      expect(hashToken('mi-token')).toBe(esperado);
      expect(hashToken('mi-token')).toHaveLength(64);
    });

    it('coincide con un valor conocido de sha256', () => {
      // sha256("abc") — vector de prueba estándar
      expect(hashToken('abc')).toBe('ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad');
    });
  });

  describe('hashesIguales', () => {
    it('true solo si son idénticos', () => {
      const h = hashToken('x');
      expect(hashesIguales(h, h)).toBe(true);
      expect(hashesIguales(h, hashToken('y'))).toBe(false);
    });

    it('longitudes distintas no lanzan, devuelven false', () => {
      expect(hashesIguales('abc', 'abcd')).toBe(false);
      expect(hashesIguales('', 'a')).toBe(false);
    });
  });
});
