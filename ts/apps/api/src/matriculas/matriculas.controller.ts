import { Body, Controller, Post, Req } from '@nestjs/common';
import type { MatriculaDto } from '@zuraedu/shared';
import type { Request } from 'express';
import { RequirePermission } from '../auth/decorators';
import { peticionInvalida } from '../common/peticion-invalida';
import { crearMatriculaSchema } from './matriculas.input';
import { MatriculasService } from './matriculas.service';

@Controller('api/v1/matriculas')
export class MatriculasController {
  constructor(private readonly matriculas: MatriculasService) {}

  /**
   * Matricula a un estudiante en un grupo. Requiere `gestionar-matriculas` (igual que la ruta de Laravel). El colegio sale del
   * token; el estado y el número de orden los decide el servidor (un campo desconocido en el cuerpo se rechaza con 400).
   */
  @Post()
  @RequirePermission('gestionar-matriculas')
  async crear(@Body() cuerpo: unknown, @Req() req: Request): Promise<MatriculaDto> {
    const datos = crearMatriculaSchema.safeParse(cuerpo ?? {});
    if (!datos.success) throw peticionInvalida(datos.error);

    return this.matriculas.crear(datos.data, { ip: req.ip ?? null, userAgent: req.headers['user-agent'] ?? null });
  }
}
