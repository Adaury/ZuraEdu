import { Body, Controller, Delete, Get, HttpCode, Param, Patch, Post, Query, Req } from '@nestjs/common';
import type { EstudianteDto, EstudiantesPage } from '@zuraedu/shared';
import type { Request } from 'express';
import { RequirePermission } from '../auth/decorators';
import { peticionInvalida } from '../common/peticion-invalida';
import { estudiantesQuerySchema } from './estudiantes.query';
import { EstudiantesService } from './estudiantes.service';
import { actualizarEstudianteSchema, crearEstudianteSchema, idEstudianteSchema } from './estudiantes.update';

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
   * Edición parcial de los datos del estudiante. Requiere `gestionar-estudiantes` (igual que las rutas de
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

  /**
   * Alta de un estudiante. Requiere `gestionar-estudiantes`. El colegio sale del token. Si no se envía
   * `numeroMatricula` se genera. No admite `grupo_id` ni `foto` todavía (se rechazan con 400 como campos desconocidos).
   */
  @Post()
  @RequirePermission('gestionar-estudiantes')
  async crear(@Body() cuerpo: unknown, @Req() req: Request): Promise<EstudianteDto> {
    const datos = crearEstudianteSchema.safeParse(cuerpo ?? {});
    if (!datos.success) throw peticionInvalida(datos.error);

    return this.estudiantes.crear(datos.data, {
      ip: req.ip ?? null,
      userAgent: req.headers['user-agent'] ?? null,
    });
  }

  /** Borrado lógico. Requiere `gestionar-estudiantes`. Responde 204 sin cuerpo. */
  @Delete(':id')
  @HttpCode(204)
  @RequirePermission('gestionar-estudiantes')
  async eliminar(@Param('id') idCrudo: string, @Req() req: Request): Promise<void> {
    const id = idEstudianteSchema.safeParse(idCrudo);
    if (!id.success) throw peticionInvalida(id.error);

    await this.estudiantes.eliminar(id.data, {
      ip: req.ip ?? null,
      userAgent: req.headers['user-agent'] ?? null,
    });
  }
}
