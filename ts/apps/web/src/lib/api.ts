import type { EstudianteDto, EstudiantesPage } from '@zuraedu/shared';
import { config } from './config';
import { jsonSeguro, peticion } from './http';

/** Datos de la petición del navegador que se reenvían a la API: el colegio (Host), la IP real y el token. */
export interface Contexto {
  host: string | null;
  ip: string | null;
  token: string | null;
}

export type Resultado<T> = { ok: true; data: T } | { ok: false; status: number; cuerpo: unknown; reintentarEnSegundos: number | null };

async function llamar<T>(url: string, ctx: Contexto, metodo: 'GET' | 'POST' | 'PATCH', cuerpoJson?: unknown): Promise<Resultado<T>> {
  const headers: Record<string, string> = {};
  if (ctx.token) headers.Authorization = `Bearer ${ctx.token}`;
  if (ctx.ip) headers['X-Forwarded-For'] = ctx.ip; // Laravel limita el login por IP: debe ser la del usuario, no la de este servidor
  try {
    const r = await peticion(url, { method: metodo, headers, host: ctx.host, cuerpoJson });
    const cuerpo = jsonSeguro(r);
    if (r.status >= 200 && r.status < 300) return { ok: true, data: cuerpo as T };
    const espera = Number(r.headers['retry-after']);
    return { ok: false, status: r.status, cuerpo, reintentarEnSegundos: Number.isFinite(espera) && espera > 0 ? espera : null };
  } catch {
    return { ok: false, status: 0, cuerpo: null, reintentarEnSegundos: null }; // sin conexión o tiempo agotado
  }
}

export const listarEstudiantes = (ctx: Contexto, consulta: string) =>
  llamar<EstudiantesPage>(`${config.apiUrl}/api/v1/estudiantes?${consulta}`, ctx, 'GET');

export const obtenerEstudiante = (ctx: Contexto, id: number) => llamar<EstudianteDto>(`${config.apiUrl}/api/v1/estudiantes/${id}`, ctx, 'GET');

export const actualizarEstudiante = (ctx: Contexto, id: number, cuerpo: Record<string, unknown>) =>
  llamar<EstudianteDto>(`${config.apiUrl}/api/v1/estudiantes/${id}`, ctx, 'PATCH', cuerpo);

/** Formato de un token de Sanctum: `id|texto`. Solo se guarda en la cookie algo con esta forma. */
const FORMATO_TOKEN = /^[0-9]{1,15}\|[A-Za-z0-9]{20,100}$/;

export type ResultadoLogin = { ok: true; token: string } | { ok: false; codigo: 'credenciales' | 'desactivada' | 'pendiente' | 'limite' | 'servicio' | 'permiso' };

/** Inicia sesión contra Laravel (`POST /api/v1/auth/login`), que emite el token de Sanctum que entiende también la API TypeScript. */
export async function iniciarSesionLaravel(ctx: Contexto, email: string, password: string): Promise<ResultadoLogin> {
  const r = await llamar<{ token?: unknown }>(`${config.laravelUrl}/api/v1/auth/login`, { ...ctx, token: null }, 'POST', { email, password, device: 'web' });
  if (r.ok) {
    const token = r.data?.token;
    return typeof token === 'string' && FORMATO_TOKEN.test(token) ? { ok: true, token } : { ok: false, codigo: 'servicio' };
  }
  if (r.status === 429) return { ok: false, codigo: 'limite' };
  if (r.status === 401 || r.status === 422) return { ok: false, codigo: 'credenciales' };
  if (r.status === 403) {
    const mensaje = String((r.cuerpo as { message?: unknown } | null)?.message ?? '').toLowerCase();
    if (mensaje.includes('desactivada')) return { ok: false, codigo: 'desactivada' };
    if (mensaje.includes('pendiente')) return { ok: false, codigo: 'pendiente' };
    return { ok: false, codigo: 'permiso' };
  }
  return { ok: false, codigo: 'servicio' };
}

/** Revoca el token en Laravel (mejor esfuerzo: la cookie se borra igual aunque Laravel no responda). */
export async function cerrarSesionLaravel(ctx: Contexto): Promise<void> {
  await llamar(`${config.laravelUrl}/api/v1/auth/logout`, ctx, 'POST');
}
