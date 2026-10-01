import { BadRequestException } from '@nestjs/common';
import type { ZodError } from 'zod';

/** 400 con la lista de campos inválidos (misma forma en toda la API). */
export function peticionInvalida(error: ZodError): BadRequestException {
  return new BadRequestException({
    message: 'Parámetros inválidos.',
    errors: error.issues.map((i) => ({ campo: i.path.join('.'), mensaje: i.message })),
  });
}
