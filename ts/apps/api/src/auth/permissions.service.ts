import { Inject, Injectable } from '@nestjs/common';
import type { Permiso } from '@zuraedu/shared';
import { Database, SYSTEM_DB } from '../db/db.module';

/** model_type que Spatie guarda para los usuarios de Laravel. */
const USER_MODEL = 'App\\Models\\User';

/**
 * Permisos de Spatie leídos de las MISMAS tablas que usa Laravel
 * (model_has_roles, roles, role_has_permissions, permissions, model_has_permissions).
 * Spatie no usa "teams" en este proyecto (config/permission.php: teams=false).
 *
 * Replica `Gate::before`: super_admin pasa siempre.
 */
@Injectable()
export class PermissionsService {
  constructor(@Inject(SYSTEM_DB) private readonly db: Database) {}

  async can(userId: number, permiso: Permiso): Promise<boolean> {
    const roles = await this.db
      .selectFrom('model_has_roles as mhr')
      .innerJoin('roles as r', 'r.id', 'mhr.role_id')
      .select(['r.id as roleId', 'r.name as roleName'])
      .where('mhr.model_type', '=', USER_MODEL)
      .where('mhr.model_id', '=', userId)
      .execute();

    if (roles.some((r) => r.roleName === 'super_admin')) return true;

    if (roles.length > 0) {
      const porRol = await this.db
        .selectFrom('role_has_permissions as rhp')
        .innerJoin('permissions as p', 'p.id', 'rhp.permission_id')
        .select('p.id')
        .where('p.name', '=', permiso)
        .where(
          'rhp.role_id',
          'in',
          roles.map((r) => Number(r.roleId)),
        )
        .executeTakeFirst();
      if (porRol) return true;
    }

    const directo = await this.db
      .selectFrom('model_has_permissions as mhp')
      .innerJoin('permissions as p', 'p.id', 'mhp.permission_id')
      .select('p.id')
      .where('p.name', '=', permiso)
      .where('mhp.model_type', '=', USER_MODEL)
      .where('mhp.model_id', '=', userId)
      .executeTakeFirst();
    return !!directo;
  }
}
