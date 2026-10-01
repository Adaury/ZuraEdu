import type { Contexto } from './api';
import { config } from './config';
import { ipDe, leerCookie, origenValido } from './validacion';

/**
 * Redirección 303 (tras un POST el navegador debe volver con GET). La ubicación es RELATIVA a propósito: detrás de un proxy la URL
 * de la petición es la interna, y una absoluta mandaría al usuario a esa dirección.
 */
export function redirigir(ubicacion: string, cookies: string[] = []): Response {
  const h = new Headers({ Location: `${config.base}${ubicacion}`, 'Cache-Control': 'no-store' });
  for (const c of cookies) h.append('Set-Cookie', c);
  return new Response(null, { status: 303, headers: h });
}

/** Contexto (Host, IP, token de la cookie) a partir de una petición de un manejador de ruta. */
export function contextoDe(request: Request): Contexto {
  const token = leerCookie(request.headers.get('cookie'), config.cookieSesion);
  return { host: request.headers.get('host'), ip: ipDe(request.headers.get('x-forwarded-for')), token: token && token.length > 0 ? token : null };
}

/** Rechazo (403) si el POST no viene del mismo sitio. Devuelve `null` si todo está bien. */
export function rechazarOtroOrigen(request: Request): Response | null {
  if (origenValido(request.headers.get('origin'), request.headers.get('host'))) return null;
  return new Response('Origen no permitido.', { status: 403, headers: { 'Content-Type': 'text/plain; charset=utf-8' } });
}
