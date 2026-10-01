import { ESTUDIANTE_ESTADOS, PER_PAGE_DEFAULT, PER_PAGE_MAX } from '@zuraedu/shared';
import { z } from 'zod';

/** Validación de los query params de GET /api/v1/estudiantes (todo lo que llega del cliente se valida). */
export const estudiantesQuerySchema = z.object({
  page: z.coerce.number().int().min(1).default(1),
  perPage: z.coerce.number().int().min(1).max(PER_PAGE_MAX).default(PER_PAGE_DEFAULT),
  q: z
    .string()
    .trim()
    .max(100)
    .optional()
    .transform((v) => (v ? v : undefined)),
  estado: z.enum(ESTUDIANTE_ESTADOS).optional(),
});

export type EstudiantesQuery = z.infer<typeof estudiantesQuerySchema>;

/** Escapa los comodines de LIKE (% _ \) para que la búsqueda sea literal. */
export function escapeLike(texto: string): string {
  return texto.replace(/[\\%_]/g, (c) => `\\${c}`);
}
