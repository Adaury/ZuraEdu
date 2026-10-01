import { ESTUDIANTE_CAMPOS_AUDITADOS, EstudianteCampoAuditado } from '@zuraedu/shared';

/** Valores de un estudiante tal como están en la base (fechas como 'YYYY-MM-DD'). */
export type EstudianteAuditable = Record<EstudianteCampoAuditado, string | null>;

/**
 * Formato de fecha de Laravel en la descripción: el modelo castea `fecha_nacimiento` a `date` (Carbon) y
 * `(string) $carbon` da 'Y-m-d H:i:s'. Se replica para que los registros de auditoría de PHP y de TypeScript
 * sean idénticos y se lean igual en la pantalla actual.
 */
function comoLaravel(campo: EstudianteCampoAuditado, valor: string | null): string | null {
  if (valor !== null && campo === 'fecha_nacimiento' && /^\d{4}-\d{2}-\d{2}$/.test(valor)) {
    return `${valor} 00:00:00`;
  }
  return valor;
}

/**
 * Lista de cambios `campo: antes → después` entre dos estados de un estudiante, en el orden de
 * ESTUDIANTE_CAMPOS_AUDITADOS. Vacía si no cambió ningún campo auditado.
 *
 * Igual que Laravel: se compara como texto (`(string) null === ''`, así que null y '' son lo mismo) y los
 * nulos se muestran como '—'.
 */
export function cambiosAuditados(antes: EstudianteAuditable, despues: EstudianteAuditable): string[] {
  const cambios: string[] = [];
  for (const campo of ESTUDIANTE_CAMPOS_AUDITADOS) {
    const a = comoLaravel(campo, antes[campo]);
    const d = comoLaravel(campo, despues[campo]);
    if ((a ?? '') === (d ?? '')) continue;
    cambios.push(`${campo}: ${a ?? '—'} → ${d ?? '—'}`);
  }
  return cambios;
}

/** Descripción final: `Estudiante #12: cedula: — → 001 | estado: activo → inactivo`. */
export function descripcionEdicionEstudiante(id: number, cambios: string[]): string {
  return `Estudiante #${id}: ${cambios.join(' | ')}`;
}
