import type { Page } from './pagination';

/** Estados de `estudiantes.estado` y `matriculas.estado` (valores reales de los ENUM). */
export const ESTUDIANTE_ESTADOS = ['activo', 'inactivo', 'egresado', 'transferido'] as const;
export type EstudianteEstado = (typeof ESTUDIANTE_ESTADOS)[number];

export const MATRICULA_ESTADOS = ['activa', 'retirada', 'transferida', 'promovida', 'no_promovida'] as const;
export type MatriculaEstado = (typeof MATRICULA_ESTADOS)[number];

/** Contrato JSON de GET /api/v1/estudiantes (lo consumen la web y, a futuro, la app móvil). */
export interface EstudianteDto {
  id: number;
  numeroMatricula: string;
  cedula: string | null;
  nombres: string;
  apellidos: string;
  sexo: 'M' | 'F';
  estado: EstudianteEstado;
}

export type EstudiantesPage = Page<EstudianteDto>;

/**
 * Campos que Laravel audita al editar un estudiante (EstudianteController@update, `$camposSensibles`):
 * cambiarlos en silencio permitiría fraude de identidad o de matrícula. La API TypeScript audita los mismos.
 */
export const ESTUDIANTE_CAMPOS_AUDITADOS = ['cedula', 'nombres', 'apellidos', 'fecha_nacimiento', 'estado'] as const;
export type EstudianteCampoAuditado = (typeof ESTUDIANTE_CAMPOS_AUDITADOS)[number];

/**
 * Cuerpo de PATCH /api/v1/estudiantes/:id. Todos los campos son opcionales (edición parcial), pero debe venir
 * al menos uno. Solo los campos de identidad por ahora; el resto se migra con el formulario completo.
 */
export interface ActualizarEstudianteInput {
  cedula?: string | null;
  nombres?: string;
  apellidos?: string;
  /** YYYY-MM-DD, anterior a hoy. */
  fechaNacimiento?: string;
  estado?: EstudianteEstado;
}
