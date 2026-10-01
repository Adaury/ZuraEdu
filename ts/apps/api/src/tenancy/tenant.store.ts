import type { ClsStore } from 'nestjs-cls';

/**
 * Contexto de la petición (AsyncLocalStorage vía nestjs-cls). Lo llena el guard de autenticación
 * a partir del USUARIO del token, nunca de un parámetro, header o body que mande el cliente.
 */
export interface TenantStore extends ClsStore {
  tenantId: number;
  userId: number;
}

export interface AuthContext {
  userId: number;
  tenantId: number;
  name: string;
}
