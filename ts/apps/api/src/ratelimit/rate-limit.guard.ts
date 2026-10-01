import { CanActivate, ExecutionContext, HttpException, HttpStatus, Inject, Injectable } from '@nestjs/common';
import { Reflector } from '@nestjs/core';
import type { Response } from 'express';
import { IS_PUBLIC } from '../auth/decorators';
import type { AuthenticatedRequest } from '../auth/sanctum.guard';
import { ENV, Env } from '../config/env';
import { RateLimiter } from './rate-limiter';

/**
 * Limita las peticiones por USUARIO autenticado (por defecto 60/min, igual que `RateLimiter::for('api')` de Laravel).
 * Va DESPUÉS de SanctumGuard (necesita saber quién es) y ANTES de PermissionGuard: los 403 también cuentan, como en Laravel.
 *
 * Qué NO cubre y por qué: las peticiones sin token válido no llegan hasta aquí (SanctumGuard responde 401 antes). No se
 * limitan por IP desde la aplicación porque, detrás de un proxy, `req.ip` es la del proxy y todos los clientes
 * compartirían un solo cubo. Eso corresponde al proxy (Nginx `limit_req`). Adivinar un token de Sanctum no es viable
 * (40 caracteres hexadecimales aleatorios, comparados por hash).
 */
@Injectable()
export class RateLimitGuard implements CanActivate {
  constructor(
    private readonly reflector: Reflector,
    private readonly limiter: RateLimiter,
    @Inject(ENV) private readonly env: Env,
  ) {}

  async canActivate(context: ExecutionContext): Promise<boolean> {
    const limite = this.env.RATE_LIMIT_PER_MINUTE;
    if (limite <= 0) return true;
    if (this.reflector.getAllAndOverride<boolean>(IS_PUBLIC, [context.getHandler(), context.getClass()])) return true;

    const http = context.switchToHttp();
    const req = http.getRequest<AuthenticatedRequest>();
    if (!req.auth) return true; // sin sesión no hay a quién contar (SanctumGuard ya habría rechazado)

    const res = http.getResponse<Response>();
    const r = await this.limiter.registrar(`u:${req.auth.tenantId}:${req.auth.userId}`, limite);
    res.setHeader('X-RateLimit-Limit', String(r.limite));
    res.setHeader('X-RateLimit-Remaining', String(r.restante));
    if (!r.permitido) {
      res.setHeader('Retry-After', String(r.reiniciaEnSegundos));
      throw new HttpException(
        { statusCode: 429, error: 'Too Many Requests', message: 'Demasiadas solicitudes. Intenta de nuevo en unos segundos.' },
        HttpStatus.TOO_MANY_REQUESTS,
      );
    }
    return true;
  }
}
