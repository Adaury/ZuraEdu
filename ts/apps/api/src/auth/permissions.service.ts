import { Inject, Injectable } from '@nestjs/common';
import type { Permiso } from '@zuraedu/shared';
import { Database, SYSTEM_DB } from '../db/db.module';
import { AuthCache, PermisosUsuario } from './auth-cache';

/** model_type que Spatie guarda para los usuarios de Laravel. */
const USER_MODEL = 'App\\Models\\User';

/**
 * Permisos de Spatie leídos de las MISMAS tablas que usa Laravel
 * (model_has_roles, roles, role_has_permissions, permissions, model_has_permissions).
 * Spatie no usa "teams" en este proyecto (config/permission.php: teams=false).
 *
 * Replica `Gate::before`: super_admin pasa siempre.
 *
 * Se cargan TODOS los permisos del usuario de una vez y se cachean por AUTH_CACHE_TTL_SECONDS
 * (ver AuthCache): antes eran 2–3 consultas por cada petición.
 */
@Injectable()
export class PermissionsService {
  constructor(
    @Inject(SYSTEM_DB) private readonly db: Database,
    private readonly cache: AuthCache,
  ) {}

  async can(userId: number, permiso: Permiso): Promise<boolean> {
    const p = await this.permisosDe(userId);
    return p.esSuperAdmin || p.nombres.has(permiso);
  }

  async permisosDe(userId: number): Promise<PermisosUsuario> {
    const p = await this.cache.permisos.getOrLoad(String(userId), async () => ({
      value: await this.cargar(userId),
    }));
    return p!; // getOrLoad nunca devuelve undefined aquí: el cargador siempre aporta un valor
  }

  private async cargar(userId: number): Promise<PermisosUsuario> {
    const roles = await this.db
      .selectFrom('model_has_roles as mhr')
      .innerJoin('roles as r', 'r.id', 'mhr.role_id')
      .select(['r.id as roleId', 'r.name as roleName'])
      .where('mhr.model_type', '=', USER_MODEL)
      .where('mhr.model_id', '=', userId)
      .execute();

    if (roles.some((r) => r.roleName === 'super_admin')) {
      return { esSuperAdmin: true, nombres: new Set() };
    }

    const nombres = new Set<string>();

    if (roles.length > 0) {
      const porRol = await this.db
        .selectFrom('role_has_permissions as rhp')
        .innerJoin('permissions as p', 'p.id', 'rhp.permission_id')
        .select('p.name')
        .where(
          'rhp.role_id',
          'in',
          roles.map((r) => Number(r.roleId)),
        )
        .execute();
      for (const f of porRol) nombres.add(f.name);
    }

    const directos = await this.db
      .selectFrom('model_has_permissions as mhp')
      .innerJoin('permissions as p', 'p.id', 'mhp.permission_id')
      .select('p.name')
      .where('mhp.model_type', '=', USER_MODEL)
      .where('mhp.model_id', '=', userId)
      .execute();
    for (const f of directos) nombres.add(f.name);

    return { esSuperAdmin: false, nombres };
  }
}
