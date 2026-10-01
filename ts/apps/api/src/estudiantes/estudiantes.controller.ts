import { BadRequestException, Body, Controller, Get, Param, Patch, Query, Req } from '@nestjs/common';
import type { EstudianteDto, EstudiantesPage } from '@zuraedu/shared';
import type { Request } from 'express';
import type { ZodError } from 'zod';
import { RequirePermission } from '../auth/decorators';
import { estudiantesQuerySchema } from './estudiantes.query';
import { EstudiantesService } from './estudiantes.service';
import { actualizarEstudianteSchema, idEstudianteSchema } from './estudiantes.update';

function peticionInvalida(error: ZodError): BadRequestException {
  return new BadRequestException({
    message: 'Parámetros inválidos.',
    errors: error.issues.map((i) => ({ campo: i.path.join('.'), mensaje: i.message })),
  });
}

@Controller('api/v1/estudiantes')
export class EstudiantesController {
  constructor(private readonly estudiantes: EstudiantesService) {}

  /** Requiere el permiso Spatie `ver-estudiantes` (igual que la ruta de Laravel). */
  @Get()
  @RequirePermission('ver-estudiantes')
  async listar(@Query() query: Record<string, unknown>): Promise<EstudiantesPage> {
    const parsed = estudiantesQuerySchema.safeParse(query);
    if (!parsed.success) throw peticionInvalida(parsed.error);
    return this.estudiantes.listar(parsed.data);
  }

  /**
   * Edición parcial de los campos de identidad. Requiere `gestionar-estudiantes` (igual que las rutas de
   * mutación de Laravel). El tenant sale del token, no de la URL ni del cuerpo; un campo desconocido en el
   * cuerpo (p. ej. tenant_id) se rechaza con 400.
   */
  @Patch(':id')
  @RequirePermission('gestionar-estudiantes')
  async actualizar(@Param('id') idCrudo: string, @Body() cuerpo: unknown, @Req() req: Request): Promise<EstudianteDto> {
    const id = idEstudianteSchema.safeParse(idCrudo);
    if (!id.success) throw peticionInvalida(id.error);

    const datos = actualizarEstudianteSchema.safeParse(cuerpo ?? {});
    if (!datos.success) throw peticionInvalida(datos.error);

    return this.estudiantes.actualizar(id.data, datos.data, {
      ip: req.ip ?? null,
      userAgent: req.headers['user-agent'] ?? null,
    });
  }
}
