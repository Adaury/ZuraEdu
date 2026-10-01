import { Injectable } from '@nestjs/common';
import { ClsService } from 'nestjs-cls';
import type { TenantStore } from './tenant.store';

/**
 * Acceso tipado al tenant y al usuario de la petición actual (los fija SanctumGuard a partir del token).
 * Lanza si se usa fuera de una petición autenticada: nunca devuelve un tenant "por defecto".
 */
@Injectable()
export class TenantContext {
  constructor(private readonly cls: ClsService<TenantStore>) {}

  get tenantId(): number {
    const id = this.cls.get('tenantId');
    if (id === undefined || id === null) throw new Error('No hay tenant en el contexto de la petición.');
    return id;
  }

  get userId(): number {
    const id = this.cls.get('userId');
    if (id === undefined || id === null) throw new Error('No hay usuario en el contexto de la petición.');
    return id;
  }
}
