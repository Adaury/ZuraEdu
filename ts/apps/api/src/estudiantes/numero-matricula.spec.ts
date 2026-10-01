import { patronNumerosDelAnio, siguienteNumeroMatricula } from './numero-matricula';

describe('siguienteNumeroMatricula', () => {
  it('el primero del año es AAAA-00001', () => {
    expect(siguienteNumeroMatricula(2026, null)).toBe('2026-00001');
  });

  it('suma uno al mayor existente y conserva el relleno de 5 dígitos', () => {
    expect(siguienteNumeroMatricula(2026, '2026-00041')).toBe('2026-00042');
    expect(siguienteNumeroMatricula(2026, '2026-00009')).toBe('2026-00010');
    expect(siguienteNumeroMatricula(2026, '2026-00099')).toBe('2026-00100');
  });

  it('pasa de 5 a 6 dígitos sin romperse (99999 → 100000)', () => {
    expect(siguienteNumeroMatricula(2026, '2026-99999')).toBe('2026-100000');
  });

  it('un cambio de año reinicia la secuencia', () => {
    expect(siguienteNumeroMatricula(2027, '2026-00500')).toBe('2027-00001');
  });

  it('ignora números que no siguen el formato (matrícula manual como "ABC-1" o "2026-ABC")', () => {
    expect(siguienteNumeroMatricula(2026, 'ABC-1')).toBe('2026-00001');
    expect(siguienteNumeroMatricula(2026, '2026-ABC')).toBe('2026-00001');
    expect(siguienteNumeroMatricula(2026, '2026-')).toBe('2026-00001');
  });

  it('no confunde años que comparten el comienzo (2026 vs 20266)', () => {
    expect(siguienteNumeroMatricula(2026, '20266-00007')).toBe('2026-00001');
  });

  it('el resultado nunca supera los 20 caracteres de la columna', () => {
    expect(siguienteNumeroMatricula(2026, '2026-999999999')).toHaveLength(15);
    expect(siguienteNumeroMatricula(2026, null).length).toBeLessThanOrEqual(20);
  });
});

describe('patronNumerosDelAnio', () => {
  it('es "AAAA-%"', () => {
    expect(patronNumerosDelAnio(2026)).toBe('2026-%');
  });
});
