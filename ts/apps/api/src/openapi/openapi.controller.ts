import { Controller, Get, Inject, NotFoundException } from '@nestjs/common';
import { Public } from '../auth/decorators';
import { ENV, Env } from '../config/env';
import { construirOpenApi } from './openapi';

/**
 * Contrato OpenAPI de la API. Público: describe rutas y reglas de validación pero no contiene datos de ningún
 * colegio. Se puede apagar con OPENAPI_ENABLED=false (responde 404).
 */
@Controller()
export class OpenApiController {
  private readonly documento = construirOpenApi();

  constructor(@Inject(ENV) private readonly env: Env) {}

  @Public()
  @Get('openapi.json')
  openapi(): Record<string, unknown> {
    if (!this.env.OPENAPI_ENABLED) throw new NotFoundException();
    return this.documento;
  }
}
