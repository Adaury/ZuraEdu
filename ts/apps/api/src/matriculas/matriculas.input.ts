import { z } from 'zod';

const idPositivo = z.number().int().positive();

/** 'YYYY-MM-DD' que además sea una fecha real (rechaza 2025-02-31). */
const fechaReal = z
  .string()
  .regex(/^\d{4}-\d{2}-\d{2}$/, 'Debe tener el formato AAAA-MM-DD.')
  .refine((s) => {
    const d = new Date(`${s}T00:00:00Z`);
    return !Number.isNaN(d.getTime()) && d.toISOString().slice(0, 10) === s;
  }, 'No es una fecha válida.');

/**
 * Cuerpo de POST /api/v1/matriculas. Mismos campos y obligatoriedad que `MatriculaController@store` de Laravel
 * (school_year_id, estudiante_id, grupo_id, fecha_matricula obligatorios; observaciones opcional), en camelCase como el
 * resto de la API. `.strict()`: un campo desconocido (tenant_id, estado, numero_orden…) se rechaza con 400 en vez de
 * ignorarse: ni el colegio, ni el estado, ni el número de orden los decide el cliente.
 *
 * Que los ids EXISTAN en este colegio (y que el grupo sea del año indicado) se comprueba contra la BD en el servicio:
 * un número válido aquí no prueba nada.
 */
export const crearMatriculaSchema = z
  .object({
    schoolYearId: idPositivo,
    estudianteId: idPositivo,
    grupoId: idPositivo,
    fechaMatricula: fechaReal,
    observaciones: z
      .string()
      .trim()
      .max(5000)
      .nullish()
      .transform((v) => (v === undefined || v === null || v === '' ? null : v)), // '' -> null, como ConvertEmptyStringsToNull
  })
  .strict();

export type CrearMatricula = z.infer<typeof crearMatriculaSchema>;
