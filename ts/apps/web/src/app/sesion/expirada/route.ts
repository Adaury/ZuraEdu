import { config } from '@/lib/config';
import { redirigir } from '@/lib/respuestas';
import { cookieBorrada } from '@/lib/validacion';

export const dynamic = 'force-dynamic';

/** Destino cuando la API responde 401 (token vencido o revocado): borra la cookie y vuelve al login. Solo borra; no cambia datos. */
export function GET(): Response {
  return redirigir('/login?error=expirada', [cookieBorrada(config.cookieSesion, config.cookieSecure)]);
}
