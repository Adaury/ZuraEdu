import {
  ESTUDIANTE_ESTADOS,
  EstudianteDto,
  EstudiantesPage,
  HealthResponse,
} from '@zuraedu/shared';
import { z } from 'zod';

/**
 * Esquemas de las RESPUESTAS de la API. Dos funciones a la vez:
 *  1. Generan los esquemas del documento OpenAPI (nada escrito a mano que pueda desfasarse).
 *  2. Las pruebas e2e validan las respuestas reales contra ellos: si el servicio cambia la forma de lo que
 *     devuelve y no se actualiza el contrato, la prueba falla.
 *
 * `satisfies z.ZodType<...>` hace que el compilador compruebe que cada esquema coincide con el DTO compartido
 * (los tipos que usan la web y la app móvil): si un lado cambia sin el otro, no compila.
 */
export const estudianteDtoSchema = z.object({
  id: z.number().int().positive(),
  numeroMatricula: z.string(),
  cedula: z.string().nullable(),
  nombres: z.string(),
  apellidos: z.string(),
  sexo: z.enum(['M', 'F']),
  estado: z.enum(ESTUDIANTE_ESTADOS),
}) satisfies z.ZodType<EstudianteDto>;

export const estudiantesPageSchema = z.object({
  data: z.array(estudianteDtoSchema),
  meta: z.object({
    page: z.number().int().min(1),
    perPage: z.number().int().min(1),
    total: z.number().int().min(0),
    lastPage: z.number().int().min(1),
  }),
}) satisfies z.ZodType<EstudiantesPage>;

export const healthSchema = z.object({
  status: z.enum(['ok', 'degraded']),
  checks: z.record(z.string(), z.string()),
  version: z.string(),
  runtime: z.literal('node'),
}) satisfies z.ZodType<HealthResponse>;

/** Forma de los errores: los de Nest ({message, error, statusCode}) y los de validación (con `errors`). */
export const errorSchema = z.object({
  message: z.string(),
  error: z.string().optional(),
  statusCode: z.number().int().optional(),
  errors: z.array(z.object({ campo: z.string(), mensaje: z.string() })).optional(),
});
