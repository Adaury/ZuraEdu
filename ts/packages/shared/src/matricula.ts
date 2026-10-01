import type { MatriculaEstado } from './estudiante';

/** Contrato JSON de POST /api/v1/matriculas (lo consumen la web y, a futuro, la app móvil). */
export interface MatriculaDto {
  id: number;
  schoolYearId: number;
  estudianteId: number;
  grupoId: number;
  /** YYYY-MM-DD */
  fechaMatricula: string;
  /** Posición dentro del grupo (cuenta todas las matrículas del grupo, no solo las activas). */
  numeroOrden: number;
  estado: MatriculaEstado;
  observaciones: string | null;
}

/** Cuerpo de POST /api/v1/matriculas. */
export interface CrearMatriculaInput {
  schoolYearId: number;
  estudianteId: number;
  grupoId: number;
  /** YYYY-MM-DD, fecha real. */
  fechaMatricula: string;
  observaciones?: string | null;
}
