import { ESTUDIANTE_ESTADOS } from '@zuraedu/shared';
import { z } from 'zod';

const hoyUtc = () => new Date().toISOString().slice(0, 10);

/** 'YYYY-MM-DD' que además sea una fecha real (rechaza 2012-02-31). */
const fechaReal = z
  .string()
  .regex(/^\d{4}-\d{2}-\d{2}$/, 'Debe tener el formato AAAA-MM-DD.')
  .refine((s) => {
    const d = new Date(`${s}T00:00:00Z`);
    return !Number.isNaN(d.getTime()) && d.toISOString().slice(0, 10) === s;
  }, 'No es una fecha válida.');

/**
 * Cuerpo de PATCH /api/v1/estudiantes/:id. Mismas reglas que UpdateEstudianteRequest de Laravel
 * (nombres/apellidos 2–100, cédula ≤ 20, fecha anterior a hoy, estado dentro del ENUM).
 *
 * `.strict()`: cualquier campo desconocido (p. ej. `tenant_id`, `id`, `deleted_at`) se RECHAZA en vez de
 * ignorarse → protección contra asignación masiva. Nunca se acepta un tenant que mande el cliente.
 */
export const actualizarEstudianteSchema = z
  .strictObject({
    cedula: z
      .string()
      .trim()
      .max(20)
      .nullable()
      .transform((v) => (v === '' ? null : v)),
    nombres: z.string().trim().min(2, 'El nombre es obligatorio.').max(100),
    apellidos: z.string().trim().min(2, 'El apellido es obligatorio.').max(100),
    fechaNacimiento: fechaReal.refine((s) => s < hoyUtc(), 'La fecha de nacimiento debe ser anterior a hoy.'),
    estado: z.enum(ESTUDIANTE_ESTADOS),
  })
  .partial()
  .refine((o) => Object.keys(o).length > 0, 'Envía al menos un campo para actualizar.');

export type ActualizarEstudiante = z.infer<typeof actualizarEstudianteSchema>;

/** El id de la ruta: entero positivo (nunca se concatena en SQL; solo se usa como parámetro). */
export const idEstudianteSchema = z.coerce.number().int().positive();
