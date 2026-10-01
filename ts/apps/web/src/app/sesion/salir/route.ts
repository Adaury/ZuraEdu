import { cerrarSesionLaravel } from '@/lib/api';
import { config } from '@/lib/config';
import { contextoDe, redirigir, rechazarOtroOrigen } from '@/lib/respuestas';
import { cookieBorrada } from '@/lib/validacion';

export const dynamic = 'force-dynamic';

/** Cierra la sesión: revoca el token en Laravel (mejor esfuerzo) y borra la cookie. */
export async function POST(request: Request): Promise<Response> {
  const rechazo = rechazarOtroOrigen(request);
  if (rechazo) return rechazo;

  const ctx = contextoDe(request);
  if (ctx.token) await cerrarSesionLaravel(ctx);
  return redirigir('/login', [cookieBorrada(config.cookieSesion, config.cookieSecure)]);
}
