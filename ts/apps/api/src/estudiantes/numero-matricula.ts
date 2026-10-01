const DIGITOS = 5;

/**
 * Siguiente número de matrícula automático del año, con el formato de Laravel: `AAAA-NNNNN` (p. ej. 2026-00042).
 *
 * `ultimo` es el mayor número ya usado ese año por el colegio, INCLUYENDO los estudiantes borrados lógicamente (la
 * restricción única de la base también los cuenta).
 *
 * Mejora deliberada sobre Laravel: allí es `count(creados este año) + 1`, que (1) tiene una condición de carrera entre
 * dos altas simultáneas y (2) no cuenta a los borrados, así que tras borrar a alguien puede generar un número ya usado y
 * fallar con un error 500 de la base. Tomar el máximo existente evita ambos casos y da la misma secuencia mientras no
 * haya borrados.
 */
export function siguienteNumeroMatricula(anio: number, ultimo: string | null): string {
  const prefijo = `${anio}-`;
  let siguiente = 1;
  if (ultimo !== null && ultimo.startsWith(prefijo)) {
    const sufijo = ultimo.slice(prefijo.length);
    if (/^\d+$/.test(sufijo)) siguiente = Number(sufijo) + 1;
  }
  return `${prefijo}${String(siguiente).padStart(DIGITOS, '0')}`;
}

/** Patrón LIKE para buscar los números del año (el `-` y los dígitos no son comodines de LIKE). */
export function patronNumerosDelAnio(anio: number): string {
  return `${anio}-%`;
}
