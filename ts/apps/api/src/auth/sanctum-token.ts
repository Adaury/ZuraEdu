import { createHash, timingSafeEqual } from 'node:crypto';

export interface ParsedToken {
  /** id de personal_access_tokens, si el token viene como "id|texto". */
  id: number | null;
  plain: string;
}

/**
 * Formato de Laravel Sanctum: "<id>|<texto plano>". En la base solo se guarda
 * sha256(<texto plano>) en la columna `token`. (Si llega sin "id|", Sanctum busca por el hash.)
 */
export function parseBearer(header: string | undefined): ParsedToken | null {
  if (!header) return null;
  const m = /^Bearer\s+(.+)$/i.exec(header.trim());
  if (!m) return null;
  const raw = m[1].trim();
  if (!raw) return null;

  const pipe = raw.indexOf('|');
  if (pipe === -1) return { id: null, plain: raw };

  const id = Number(raw.slice(0, pipe));
  const plain = raw.slice(pipe + 1);
  if (!Number.isInteger(id) || id <= 0 || !plain) return null;
  return { id, plain };
}

export function hashToken(plain: string): string {
  return createHash('sha256').update(plain, 'utf8').digest('hex');
}

/** Comparación en tiempo constante (evita ataques de temporización sobre el hash). */
export function hashesIguales(a: string, b: string): boolean {
  const ba = Buffer.from(a, 'utf8');
  const bb = Buffer.from(b, 'utf8');
  return ba.length === bb.length && timingSafeEqual(ba, bb);
}
