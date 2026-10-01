/**
 * Valores reales de la columna `asistencias.estado` (migración 2026_03_17_000071):
 *   ENUM('presente','ausente','tarde','excusa','retiro')
 *
 * Lección de la versión PHP: varios módulos comparaban con 'tardanza'/'justificado'
 * (enum anterior) y los contadores daban 0. Aquí el tipo lo hace imposible de compilar.
 */
export const ASISTENCIA_ESTADOS = ['presente', 'ausente', 'tarde', 'excusa', 'retiro'] as const;
export type AsistenciaEstado = (typeof ASISTENCIA_ESTADOS)[number];

/** Estados que cuentan como "asistió" al calcular el porcentaje (presente, tarde y excusa justificada). */
export const ASISTENCIA_CUENTA_COMO_PRESENTE: readonly AsistenciaEstado[] = ['presente', 'tarde', 'excusa'];

export function esAsistenciaEstado(valor: unknown): valor is AsistenciaEstado {
  return typeof valor === 'string' && (ASISTENCIA_ESTADOS as readonly string[]).includes(valor);
}
