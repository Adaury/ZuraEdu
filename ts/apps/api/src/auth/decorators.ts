import { SetMetadata } from '@nestjs/common';
import type { Permiso } from '@zuraedu/shared';

export const IS_PUBLIC = 'isPublic';
export const REQUIRED_PERMISSION = 'requiredPermission';

/** Ruta sin autenticación (solo /health). Todo lo demás exige token. */
export const Public = () => SetMetadata(IS_PUBLIC, true);

/** Exige un permiso de Spatie (equivalente a `middleware('can:ver-estudiantes')`). */
export const RequirePermission = (permiso: Permiso) => SetMetadata(REQUIRED_PERMISSION, permiso);
