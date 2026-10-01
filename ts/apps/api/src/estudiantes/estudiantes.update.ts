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
 * Texto opcional que se puede vaciar: se recorta y una cadena vacía se guarda como null (igual que el middleware
 * ConvertEmptyStringsToNull de Laravel).
 */
const textoNulable = (max: number) =>
  z
    .string()
    .trim()
    .max(max)
    .nullable()
    .transform((v) => (v === '' ? null : v));

const correo = z
  .string()
  .trim()
  .max(150)
  .nullable()
  .transform((v) => (v === '' ? null : v))
  .refine((v) => v === null || z.email().safeParse(v).success, 'Correo electrónico inválido.');

/**
 * Cuerpo de PATCH /api/v1/estudiantes/:id (edición parcial; al menos un campo).
 *
 * Mismas reglas que UpdateEstudianteRequest de Laravel, con una diferencia deliberada: donde la regla de Laravel es
 * MÁS PERMISIVA que la columna real, aquí manda la columna (así un valor demasiado largo da 400 y no un error 500 de
 * base de datos como en PHP): tutor_parentesco ≤ 50 (Laravel: 150), tutor_trabajo ≤ 100 (Laravel: 150) y
 * nacionalidad NO admite null (la columna es NOT NULL; Laravel dice `nullable`). `tutor_email` de las reglas de Laravel
 * no existe como columna ni en $fillable, así que no se acepta.
 *
 * `.strict()`: cualquier campo desconocido (p. ej. `tenant_id`, `id`, `deleted_at`, `user_id`) se RECHAZA en vez de
 * ignorarse → protección contra asignación masiva. Nunca se acepta un tenant que mande el cliente.
 */
export const actualizarEstudianteSchema = z
  .strictObject({
    numeroMatricula: z.string().trim().min(1, 'El número de matrícula es obligatorio.').max(20),
    cedula: textoNulable(20),
    nombres: z.string().trim().min(2, 'El nombre es obligatorio.').max(100),
    apellidos: z.string().trim().min(2, 'El apellido es obligatorio.').max(100),
    fechaNacimiento: fechaReal.refine((s) => s < hoyUtc(), 'La fecha de nacimiento debe ser anterior a hoy.'),
    sexo: z.enum(['M', 'F']),
    nacionalidad: z.string().trim().min(1, 'La nacionalidad es obligatoria.').max(50),
    lugarNacimiento: textoNulable(100),
    telefono: textoNulable(20),
    email: correo,
    direccion: textoNulable(500),
    sector: textoNulable(100),
    municipio: textoNulable(100),
    provincia: textoNulable(100),
    estado: z.enum(ESTUDIANTE_ESTADOS),
    tutorNombre: textoNulable(150),
    tutorParentesco: textoNulable(50),
    tutorTelefono: textoNulable(20),
    tutorTrabajo: textoNulable(100),
    notasMedicas: textoNulable(2000),
  })
  .partial()
  .refine((o) => Object.keys(o).length > 0, 'Envía al menos un campo para actualizar.');

export type ActualizarEstudiante = z.infer<typeof actualizarEstudianteSchema>;

/** El id de la ruta: entero positivo (nunca se concatena en SQL; solo se usa como parámetro). */
export const idEstudianteSchema = z.coerce.number().int().positive();
