import { cookies, headers } from 'next/headers';
import { redirect } from 'next/navigation';
import type { Contexto } from './api';
import { config } from './config';
import { ipDe } from './validacion';

/** Contexto de la petición actual (Host, IP y token de la cookie) para las páginas de servidor. */
export async function contextoActual(): Promise<Contexto> {
  const h = await headers();
  const token = (await cookies()).get(config.cookieSesion)?.value ?? null;
  return { host: h.get('host'), ip: ipDe(h.get('x-forwarded-for')), token: token && token.length > 0 ? token : null };
}

/** Exige una sesión: sin cookie se manda al login. Devuelve el contexto con el token. */
export async function requerirSesion(): Promise<Contexto & { token: string }> {
  const ctx = await contextoActual();
  if (!ctx.token) redirect('/login');
  return { ...ctx, token: ctx.token };
}
