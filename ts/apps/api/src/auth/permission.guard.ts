import { CanActivate, ExecutionContext, ForbiddenException, Injectable } from '@nestjs/common';
import { Reflector } from '@nestjs/core';
import type { Permiso } from '@zuraedu/shared';
import { REQUIRED_PERMISSION } from './decorators';
import { PermissionsService } from './permissions.service';
import type { AuthenticatedRequest } from './sanctum.guard';

/** Corre después de SanctumGuard: aplica @RequirePermission() usando las tablas de Spatie. */
@Injectable()
export class PermissionGuard implements CanActivate {
  constructor(
    private readonly reflector: Reflector,
    private readonly permisos: PermissionsService,
  ) {}

  async canActivate(context: ExecutionContext): Promise<boolean> {
    const requerido = this.reflector.getAllAndOverride<Permiso | undefined>(REQUIRED_PERMISSION, [
      context.getHandler(),
      context.getClass(),
    ]);
    if (!requerido) return true;

    const req = context.switchToHttp().getRequest<AuthenticatedRequest>();
    if (!req.auth) throw new ForbiddenException();

    if (!(await this.permisos.can(req.auth.userId, requerido))) {
      throw new ForbiddenException('No tienes permiso para esta acción.');
    }
    return true;
  }
}
