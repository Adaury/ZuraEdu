import { normalizarBase } from './base';

/**
 * Configuración del servidor web (solo servidor: ninguna variable lleva el prefijo NEXT_PUBLIC_, así que nada de esto llega al
 * navegador). Las URL apuntan a las direcciones INTERNAS; el colegio se decide por la cabecera Host que reenvía la web.
 */
export const config = {
  /** API TypeScript (apps/api). */
  apiUrl: (process.env.API_URL ?? 'http://127.0.0.1:3100').replace(/\/+$/, ''),
  /** Laravel: aquí se inicia y se cierra la sesión (emite y revoca los tokens de Sanctum). */
  laravelUrl: (process.env.LARAVEL_URL ?? 'http://127.0.0.1:8000').replace(/\/+$/, ''),
  /**
   * Prefijo bajo el que vive la web (`WEB_BASE_PATH`, p. ej. `/nuevo`; vacío = raíz). Debe ser el MISMO valor con el que se compiló
   * (`next build`): Next lo fija al compilar. Laravel ya usa `/login` y `/`, por eso en producción la web va bajo un prefijo.
   */
  base: normalizarBase(process.env.WEB_BASE_PATH),
  /** Nombre de la cookie de sesión (HttpOnly: el JavaScript del navegador no puede leerla). */
  cookieSesion: 'zura_token',
  /** Vida de la sesión en la cookie (8 h). El token de Sanctum puede vencer antes: entonces la API responde 401. */
  sesionSegundos: 8 * 60 * 60,
  /** `Secure` en producción; `COOKIE_SECURE=false` solo para probar por http sin TLS. */
  cookieSecure: process.env.COOKIE_SECURE ? process.env.COOKIE_SECURE !== 'false' : process.env.NODE_ENV === 'production',
  porPagina: 20,
} as const;

/** Ruta de la aplicación con el prefijo delante (para `href`, `action` y `Location`; `redirect()` y `<Link>` de Next ya lo añaden solos). */
export const ruta = (p: string): string => `${config.base}${p}`;

