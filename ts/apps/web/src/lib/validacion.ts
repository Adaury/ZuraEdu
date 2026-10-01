/**
 * Utilidades PURAS de la web (sin React ni red): validan lo que llega del navegador antes de usarlo y mapean los errores de la API a
 * mensajes fijos. Todo lo que viene de la URL o de un formulario se trata como no confiable.
 */

export interface Consulta {
  q: string;
  estado: string;
  page: number;
}

type Parametros = Record<string, string | string[] | undefined>;

const primero = (v: string | string[] | undefined): string => (Array.isArray(v) ? (v[0] ?? '') : (v ?? ''));

/** Id de la URL: solo dígitos, sin ceros a la izquierda, hasta 15 (lo mismo que acepta la API). */
export function idValido(valor: string): number | null {
  return /^[1-9][0-9]{0,14}$/.test(valor) ? Number(valor) : null;
}

/**
 * Parámetros del listado. `estado` solo si es uno de los estados válidos (si no, se ignora), `q` se recorta a 100 caracteres (el
 * máximo de la API) y `page` debe ser un entero positivo razonable.
 */
export function leerConsulta(params: Parametros, estados: readonly string[]): Consulta {
  const q = primero(params.q).trim().slice(0, 100);
  const estadoCrudo = primero(params.estado);
  const estado = estados.includes(estadoCrudo) ? estadoCrudo : '';
  const paginaCruda = primero(params.page);
  const page = /^[1-9][0-9]{0,5}$/.test(paginaCruda) ? Number(paginaCruda) : 1;
  return { q, estado, page };
}

/** Parámetros que se envían a la API. */
export function consultaParaApi(c: Consulta, perPage: number): string {
  const p = new URLSearchParams({ page: String(c.page), perPage: String(perPage) });
  if (c.q) p.set('q', c.q);
  if (c.estado) p.set('estado', c.estado);
  return p.toString();
}

/** Enlace del listado (para la paginación) conservando la búsqueda y el filtro. */
export function enlaceListado(c: Consulta, page: number): string {
  const p = new URLSearchParams();
  if (c.q) p.set('q', c.q);
  if (c.estado) p.set('estado', c.estado);
  if (page > 1) p.set('page', String(page));
  const s = p.toString();
  return s ? `/estudiantes?${s}` : '/estudiantes';
}

/**
 * Protección CSRF de defensa en profundidad (además de la cookie SameSite=Lax): un POST solo se acepta si su `Origin` es el mismo
 * sitio que el `Host`. Sin `Origin` se rechaza (los navegadores lo envían en todo POST de formulario). Se comparan nombres de host,
 * sin puerto: detrás de Nginx el `Host` puede llegar sin él.
 */
export function origenValido(origin: string | null, host: string | null): boolean {
  if (!origin || !host) return false;
  try {
    return new URL(origin).hostname.toLowerCase() === host.split(':')[0].toLowerCase();
  } catch {
    return false;
  }
}

/** Campos que la pantalla de edición envía a la API (nombres de la API, en camelCase). */
export const CAMPOS_EDICION = ['nombres', 'apellidos', 'cedula', 'sexo', 'estado'] as const;
export type CampoEdicion = (typeof CAMPOS_EDICION)[number];

/** Códigos de error que viajan en la URL: SOLO estos (nunca texto libre que alguien pueda inyectar en un enlace). */
export type CodigoError = 'invalido' | 'duplicado' | 'noexiste' | 'expirada' | 'permiso' | 'limite' | 'servicio' | 'credenciales' | 'desactivada' | 'pendiente';

export const MENSAJES: Readonly<Record<CodigoError, string>> = {
  invalido: 'Hay datos inválidos. Revisa los campos marcados.',
  duplicado: 'Esa cédula o ese número de matrícula ya existe en el colegio.',
  noexiste: 'El estudiante ya no existe.',
  expirada: 'Tu sesión expiró. Inicia sesión de nuevo.',
  permiso: 'No tienes permiso para esta acción.',
  limite: 'Demasiadas solicitudes. Espera unos segundos e inténtalo de nuevo.',
  servicio: 'El servicio no responde. Inténtalo de nuevo en unos minutos.',
  credenciales: 'Correo o contraseña incorrectos.',
  desactivada: 'Tu cuenta está desactivada.',
  pendiente: 'Tu cuenta está pendiente de aprobación.',
};

export const esCodigoError = (v: string): v is CodigoError => Object.prototype.hasOwnProperty.call(MENSAJES, v);

/** Código de error a partir del estado HTTP que devolvió la API. */
export function codigoPorEstado(status: number): CodigoError {
  switch (status) {
    case 400:
      return 'invalido';
    case 401:
      return 'expirada';
    case 403:
      return 'permiso';
    case 404:
      return 'noexiste';
    case 409:
      return 'duplicado';
    case 422:
      return 'invalido';
    case 429:
      return 'limite';
    default:
      return 'servicio';
  }
}

/** Nombres de campo (de la lista permitida) que la API marcó como inválidos; el resto se descarta. */
export function camposInvalidos(cuerpo: unknown): CampoEdicion[] {
  const errores = (cuerpo as { errors?: unknown } | null)?.errors;
  if (!Array.isArray(errores)) return [];
  const vistos = new Set<CampoEdicion>();
  for (const e of errores) {
    const campo = (e as { campo?: unknown } | null)?.campo;
    if (typeof campo === 'string' && (CAMPOS_EDICION as readonly string[]).includes(campo)) vistos.add(campo as CampoEdicion);
  }
  return [...vistos];
}

/** Lee la lista de campos de `?c=a,b` quedándose solo con los permitidos. */
export function leerCampos(valor: string): CampoEdicion[] {
  return valor
    .split(',')
    .filter((c): c is CampoEdicion => (CAMPOS_EDICION as readonly string[]).includes(c));
}

/** Valor de la cookie `Set-Cookie` de sesión (HttpOnly, SameSite=Lax, Path=/). */
export function serializarCookie(nombre: string, valor: string, opciones: { secure: boolean; maxAgeSegundos: number; path?: string }): string {
  const partes = [`${nombre}=${encodeURIComponent(valor)}`, `Path=${opciones.path || '/'}`, 'HttpOnly', 'SameSite=Lax', `Max-Age=${opciones.maxAgeSegundos}`];
  if (opciones.secure) partes.push('Secure');
  return partes.join('; ');
}

/** Cookie que borra la de sesión. */
export function cookieBorrada(nombre: string, secure: boolean, path?: string): string {
  return serializarCookie(nombre, '', { secure, maxAgeSegundos: 0, path });
}

/** Valor de una cookie en la cabecera `Cookie`, o `null`. Nunca lanza con un valor mal codificado. */
export function leerCookie(cabecera: string | null, nombre: string): string | null {
  if (!cabecera) return null;
  for (const trozo of cabecera.split(';')) {
    const i = trozo.indexOf('=');
    if (i < 0 || trozo.slice(0, i).trim() !== nombre) continue;
    try {
      return decodeURIComponent(trozo.slice(i + 1).trim());
    } catch {
      return null;
    }
  }
  return null;
}

/** IP del cliente: la primera de `X-Forwarded-For` (la pone Nginx). Sin proxy no hay forma de conocerla y no se inventa. */
export function ipDe(xForwardedFor: string | null): string | null {
  const primera = xForwardedFor?.split(',')[0]?.trim();
  return primera && /^[0-9a-fA-F:.]{3,45}$/.test(primera) ? primera : null;
}
