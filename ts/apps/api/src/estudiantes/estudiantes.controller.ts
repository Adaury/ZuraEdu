import { BadRequestException, Controller, Get, Query } from '@nestjs/common';
import type { EstudiantesPage } from '@zuraedu/shared';
import { RequirePermission } from '../auth/decorators';
import { estudiantesQuerySchema } from './estudiantes.query';
import { EstudiantesService } from './estudiantes.service';

@Controller('api/v1/estudiantes')
export class EstudiantesController {
  constructor(private readonly estudiantes: EstudiantesService) {}

  /** Requiere el permiso Spatie `ver-estudiantes` (igual que la ruta de Laravel). */
  @Get()
  @RequirePermission('ver-estudiantes')
  async listar(@Query() query: Record<string, unknown>): Promise<EstudiantesPage> {
    const parsed = estudiantesQuerySchema.safeParse(query);
    if (!parsed.success) {
      throw new BadRequestException({
        message: 'Parámetros inválidos.',
        errors: parsed.error.issues.map((i) => ({ campo: i.path.join('.'), mensaje: i.message })),
      });
    }
    return this.estudiantes.listar(parsed.data);
  }
}
