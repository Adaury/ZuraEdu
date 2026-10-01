import { ahoraUtc, MODELO_ESTUDIANTE } from './audit.service';

describe('MODELO_ESTUDIANTE', () => {
  it('es exactamente el nombre de clase de Laravel, con UNA barra invertida entre segmentos', () => {
    // Estudiante::class en PHP → "App\Models\Estudiante". En un literal TS cada barra se escribe doble;
    // si se escribiera 'App\Models\Estudiante' el resultado sería "AppModelsEstudiante" sin aviso del compilador.
    expect(MODELO_ESTUDIANTE).toBe(String.raw`App\Models\Estudiante`);
    expect(MODELO_ESTUDIANTE.split('\\')).toEqual(['App', 'Models', 'Estudiante']);
    expect(MODELO_ESTUDIANTE).toHaveLength(21);
  });
});

describe('ahoraUtc', () => {
  it("tiene el formato de Laravel 'Y-m-d H:i:s'", () => {
    expect(ahoraUtc()).toMatch(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/);
  });

  it('es la hora UTC (no la local del servidor)', () => {
    const ahora = new Date();
    const esperado = ahora.toISOString().slice(0, 19).replace('T', ' ');
    const obtenido = ahoraUtc();
    // Tolera el cambio de segundo entre las dos lecturas.
    const dif = Math.abs(new Date(obtenido.replace(' ', 'T') + 'Z').getTime() - new Date(esperado.replace(' ', 'T') + 'Z').getTime());
    expect(dif).toBeLessThanOrEqual(1000);
  });
});
