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
import type { Loaded } from '../common/ttl-cache';
import { Database, SYSTEM_DB } from '../db/db.module';
import { HostTenantResolver } from '../tenancy/host-tenant.resolver';
import type { AuthContext, TenantStore } from '../tenancy/tenant.store';
import { AuthCache } from './auth-cache';
import { IS_PUBLIC } from './decorators';
import { hashesIguales, hashToken, ParsedToken, parseBearer } from './sanctum-token';

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
 *
 * Caché: la validación completa (token → usuario → tenant) se cachea por AUTH_CACHE_TTL_SECONDS
 * (ver AuthCache; 3 consultas menos por petición). La comprobación del Host NO se cachea aquí:
 * corre en cada petición porque depende de lo que mande el cliente.
 */
@Injectable()
export class SanctumGuard implements CanActivate {
  constructor(
    private readonly reflector: Reflector,
    private readonly cls: ClsService<TenantStore>,
    private readonly hosts: HostTenantResolver,
    private readonly cache: AuthCache,
    @Inject(SYSTEM_DB) private readonly db: Database,
  ) {}

  async canActivate(context: ExecutionContext): Promise<boolean> {
    const esPublica = this.reflector.getAllAndOverride<boolean>(IS_PUBLIC, [context.getHandler(), context.getClass()]);
    if (esPublica) return true;

    const req = context.switchToHttp().getRequest<AuthenticatedRequest>();
    const token = parseBearer(req.headers.authorization);
    if (!token) throw new UnauthorizedException('No autenticado.');

    const hash = hashToken(token.plain);
    // La clave lleva el HASH del token (nunca el token en claro); un secreto equivocado da otra clave.
    const clave = `${token.id ?? 'h'}:${hash}`;
    const auth = await this.cache.sesiones.getOrLoad(clave, () => this.cargarSesion(token, hash));
    if (!auth) throw new UnauthorizedException('No autenticado.');

    const tenantDelHost = await this.hosts.resolve(req.headers.host);
    if (tenantDelHost !== undefined && tenantDelHost !== auth.tenantId) {
      throw new ForbiddenException('El token no pertenece a esta institución.');
    }

    req.auth = auth;
    this.cls.set('tenantId', auth.tenantId);
    this.cls.set('userId', auth.userId);
    return true;
  }

  /** Validación completa contra la base. Lanza (y por tanto NO cachea) ante cualquier rechazo. */
  private async cargarSesion(token: ParsedToken, hash: string): Promise<Loaded<AuthContext>> {
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

    // expires_at lo escribe Laravel en UTC (config/app.php timezone = UTC).
    let msHastaExpirar: number | undefined;
    if (fila.expires_at) {
      const expira = new Date(String(fila.expires_at).replace(' ', 'T') + 'Z').getTime();
      msHastaExpirar = expira - Date.now();
      if (msHastaExpirar <= 0) throw new UnauthorizedException('Token expirado.');
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

    return {
      // Si el token vence antes que el TTL, la entrada cacheada vence con él (nunca sobrevive al token).
      ttlMs: msHastaExpirar,
      value: { userId: Number(usuario.id), tenantId: Number(tenant.id), name: String(usuario.name) },
    };
  }
}
