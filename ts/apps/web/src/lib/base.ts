/**
 * Prefijo de ruta bajo el que vive la web (`WEB_BASE_PATH`, p. ej. `/nuevo`). Laravel ya usa `/login` y `/` en el mismo servidor, así que
 * la web nueva necesita su propio prefijo; con él, además, la cookie del token solo viaja a esa parte del sitio.
 *
 * Sin dependencias: lo usan `next.config.ts` (en tiempo de compilación) y la configuración del servidor.
 * Devuelve `''` (sin prefijo) o `/algo` — nunca con barra final. Un valor con caracteres raros se rechaza: acabaría dentro de cabeceras
 * `Location` y `Set-Cookie`.
 */
export function normalizarBase(crudo: string | undefined | null): string {
  const limpio = (crudo ?? '').trim().replace(/^\/+|\/+$/g, '');
  if (limpio === '') return '';
  if (!/^[a-z0-9][a-z0-9_-]*(\/[a-z0-9][a-z0-9_-]*)*$/i.test(limpio)) {
    throw new Error(`WEB_BASE_PATH inválido: ${JSON.stringify(crudo)}. Usa letras, números, guion o guion bajo, p. ej. /nuevo.`);
  }
  return `/${limpio}`;
}
