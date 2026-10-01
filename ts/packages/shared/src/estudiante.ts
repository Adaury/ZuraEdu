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
