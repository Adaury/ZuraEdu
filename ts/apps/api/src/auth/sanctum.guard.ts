import {
  CanActivate,
  ExecutionContext,
  ForbiddenException,
  Inject,
  Injectable,
  UnauthorizedException,
} from '@nestjs/common';
import { Reflector } from '@nestjs/core';
import type { Request } from 'express';
import { ClsService } from 'nestjs-cls';
import { Database, SYSTEM_DB } from '../db/db.module';
import { HostTenantResolver } from '../tenancy/host-tenant.resolver';
import type { AuthContext, TenantStore } from '../tenancy/tenant.store';
import { IS_PUBLIC } from './decorators';
import { hashesIguales, hashToken, parseBearer } from './sanctum-token';

const USER_MODEL = 'App\\Models\\User';

export type AuthenticatedRequest = Request & { auth?: AuthContext };

/**
 * Autenticación por token de Laravel Sanctum + fijación del tenant.
 *
 * Reglas (iguales o más estrictas que Laravel):
 *  - El token se valida contra personal_access_tokens (hash SHA-256, tiempo constante) y su
 *    expires_at. tokenable_type debe ser App\Models\User.
 *  - El usuario debe estar activo (como CheckUserActivo) y su tenant en estado activo/prueba
 *    (como Tenant::estaActivo()).
 *  - El tenant sale del USUARIO. Si el Host identifica a otro tenant → 403 (token del colegio
 *    A usado en el dominio del colegio B). Nunca se acepta un tenant que mande el cliente.
 */
@Injectable()
export class SanctumGuard implements CanActivate {
  constructor(
    private readonly reflector: Reflector,
    private readonly cls: ClsService<TenantStore>,
    private readonly hosts: HostTenantResolver,
    @Inject(SYSTEM_DB) private readonly db: Database,
  ) {}

  async canActivate(context: ExecutionContext): Promise<boolean> {
    const esPublica = this.reflector.getAllAndOverride<boolean>(IS_PUBLIC, [context.getHandler(), context.getClass()]);
    if (esPublica) return true;

    const req = context.switchToHttp().getRequest<AuthenticatedRequest>();
    const token = parseBearer(req.headers.authorization);
    if (!token) throw new UnauthorizedException('No autenticado.');

    const hash = hashToken(token.plain);
    const fila = token.id
      ? await this.db
          .selectFrom('personal_access_tokens')
          .select(['id', 'tokenable_type', 'tokenable_id', 'token', 'expires_at'])
          .where('id', '=', token.id)
          .executeTakeFirst()
      : await this.db
          .selectFrom('personal_access_tokens')
          .select(['id', 'tokenable_type', 'tokenable_id', 'token', 'expires_at'])
          .where('token', '=', hash)
          .executeTakeFirst();

    if (!fila || !hashesIguales(String(fila.token), hash) || fila.tokenable_type !== USER_MODEL) {
      throw new UnauthorizedException('No autenticado.');
    }
    if (fila.expires_at && new Date(String(fila.expires_at).replace(' ', 'T') + 'Z').getTime() <= Date.now()) {
      throw new UnauthorizedException('Token expirado.');
    }

    const usuario = await this.db
      .selectFrom('users')
      .select(['id', 'name', 'tenant_id', 'activo'])
      .where('id', '=', Number(fila.tokenable_id))
      .executeTakeFirst();
    if (!usuario || !usuario.activo) throw new UnauthorizedException('No autenticado.');
    if (usuario.tenant_id === null || usuario.tenant_id === undefined) {
      // Usuarios de plataforma (super_admin) no tienen tenant: no usan esta API por ahora.
      throw new ForbiddenException('Cuenta sin institución asignada.');
    }

    const tenant = await this.db
      .selectFrom('tenants')
      .select(['id', 'estado'])
      .where('id', '=', Number(usuario.tenant_id))
      .where('deleted_at', 'is', null)
      .executeTakeFirst();
    if (!tenant || (tenant.estado !== 'activo' && tenant.estado !== 'prueba')) {
      throw new ForbiddenException('Institución suspendida o no disponible.');
    }

    const tenantDelHost = await this.hosts.resolve(req.headers.host);
    if (tenantDelHost !== undefined && tenantDelHost !== Number(tenant.id)) {
      throw new ForbiddenException('El token no pertenece a esta institución.');
    }

    const auth: AuthContext = { userId: Number(usuario.id), tenantId: Number(tenant.id), name: String(usuario.name) };
    req.auth = auth;
    this.cls.set('tenantId', auth.tenantId);
    this.cls.set('userId', auth.userId);
    return true;
  }
}
