import { iniciarSesionLaravel } from '@/lib/api';
import { config } from '@/lib/config';
import { contextoDe, redirigir, rechazarOtroOrigen } from '@/lib/respuestas';
import { serializarCookie } from '@/lib/validacion';

export const dynamic = 'force-dynamic';

/** Inicio de sesión: valida contra Laravel y guarda el token de Sanctum en una cookie HttpOnly. */
export async function POST(request: Request): Promise<Response> {
  const rechazo = rechazarOtroOrigen(request);
  if (rechazo) return rechazo;

  const form = await request.formData().catch(() => null);
  const email = String(form?.get('email') ?? '').trim().slice(0, 150);
  const password = String(form?.get('password') ?? '').slice(0, 200);
  if (!email || !password) return redirigir('/login?error=credenciales');

  const r = await iniciarSesionLaravel(contextoDe(request), email, password);
  if (!r.ok) return redirigir(`/login?error=${r.codigo}`);

  // Siempre al listado: no se acepta un destino desde la URL (evita redirecciones abiertas).
  return redirigir('/estudiantes', [serializarCookie(config.cookieSesion, r.token, { secure: config.cookieSecure, maxAgeSegundos: config.sesionSegundos })]);
}
