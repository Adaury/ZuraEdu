import { unlink } from 'node:fs/promises';
import { isAbsolute, relative, resolve, sep } from 'node:path';

/**
 * Ruta absoluta del archivo de una foto, o `null` si el valor de la BD intenta salirse de la carpeta pública.
 *
 * `estudiantes.foto` es una ruta relativa al disco `public` de Laravel (p. ej. `fotos/estudiantes/abc.jpg`). Aunque salga de la
 * BD, no se confía en ella: un valor como `../../.env` o `/etc/passwd` jamás debe terminar en un `unlink`. Se resuelve y se
 * comprueba que el resultado quede DENTRO de la carpeta base.
 */
export function rutaSegura(base: string, fotoRelativa: string): string | null {
  if (fotoRelativa === '' || fotoRelativa.includes('\0') || isAbsolute(fotoRelativa)) return null;
  const raiz = resolve(base);
  const destino = resolve(raiz, fotoRelativa);
  const rel = relative(raiz, destino);
  if (rel === '' || rel.startsWith('..') || isAbsolute(rel) || rel.split(sep).includes('..')) return null;
  return destino;
}

export type ResultadoBorradoFoto = 'borrada' | 'no-existia' | 'rechazada' | 'sin-configurar' | 'sin-foto' | 'error';

/**
 * Borra el archivo de la foto como lo hace `Storage::disk('public')->delete()` de Laravel al eliminar un estudiante. Nunca
 * lanza: el estudiante ya se eliminó y un archivo que no se pueda borrar no debe convertirlo en un error.
 */
export async function borrarFoto(base: string | undefined, foto: string | null, aviso: (m: string) => void): Promise<ResultadoBorradoFoto> {
  if (!foto) return 'sin-foto';
  if (!base) return 'sin-configurar';
  const ruta = rutaSegura(base, foto);
  if (ruta === null) {
    aviso(`Foto con ruta sospechosa (no se borra): ${JSON.stringify(foto)}`);
    return 'rechazada';
  }
  try {
    await unlink(ruta);
    return 'borrada';
  } catch (e) {
    if ((e as NodeJS.ErrnoException).code === 'ENOENT') return 'no-existia';
    aviso(`No se pudo borrar la foto ${ruta}: ${(e as Error).message}`);
    return 'error';
  }
}
