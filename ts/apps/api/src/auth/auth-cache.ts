import { Inject, Injectable } from '@nestjs/common';
import { TtlCache } from '../common/ttl-cache';
import { ENV, Env } from '../config/env';
import type { AuthContext } from '../tenancy/tenant.store';

export interface PermisosUsuario {
  esSuperAdmin: boolean;
  nombres: ReadonlySet<string>;
}

/**
 * Cachés de autenticación (en memoria, por proceso):
 *
 *  - `sesiones`: token → {usuario, tenant}. Evita 3 consultas por petición (token, usuario, tenant).
 *    La CLAVE es "<id>:<sha256 del token>", nunca el token en claro; un token con secreto equivocado
 *    produce otra clave, así que jamás acierta la caché. Solo se guardan sesiones válidas.
 *  - `permisos`: usuario → todos sus permisos (2–3 consultas una vez por TTL en vez de por petición).
 *
 * Compromiso de seguridad (explícito): sin invalidación cruzada con Laravel, revocar un token,
 * desactivar un usuario, suspender un tenant o quitar un permiso tarda hasta AUTH_CACHE_TTL_SECONDS
 * en notarse aquí. Por eso el TTL es corto (30 s) y configurable; con 0 no hay caché.
 * Spatie en Laravel cachea permisos mucho más tiempo, así que este margen es menor que el del origen.
 */
@Injectable()
export class AuthCache {
  readonly sesiones: TtlCache<AuthContext>;
  readonly permisos: TtlCache<PermisosUsuario>;

  constructor(@Inject(ENV) env: Env) {
    const opciones = { ttlMs: env.AUTH_CACHE_TTL_SECONDS * 1000, maxEntries: env.AUTH_CACHE_MAX_ENTRIES };
    this.sesiones = new TtlCache<AuthContext>(opciones);
    this.permisos = new TtlCache<PermisosUsuario>(opciones);
  }

  clear(): void {
    this.sesiones.clear();
    this.permisos.clear();
  }
}
