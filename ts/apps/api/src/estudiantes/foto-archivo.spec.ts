import { existsSync, mkdirSync, mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';
import { borrarFoto, rutaSegura } from './foto-archivo';

describe('rutaSegura', () => {
  const base = resolve(tmpdir(), 'zura-public');

  it('resuelve una ruta relativa normal dentro de la carpeta', () => {
    expect(rutaSegura(base, 'fotos/estudiantes/abc.jpg')).toBe(resolve(base, 'fotos/estudiantes/abc.jpg'));
  });

  it.each([
    '../secreto.txt',
    '../../.env',
    'fotos/../../.env',
    'fotos/estudiantes/../../../x',
    '/etc/passwd',
    'C:\\Windows\\win.ini',
    '..',
    '.',
    '',
    'fotos/\0evil.jpg',
  ])('rechaza %p (se saldría de la carpeta pública o no es un archivo)', (v) => {
    expect(rutaSegura(base, v)).toBeNull();
  });

  it('acepta ".." que no sale de la carpeta (fotos/../fotos/a.jpg)', () => {
    expect(rutaSegura(base, 'fotos/../fotos/a.jpg')).toBe(resolve(base, 'fotos/a.jpg'));
  });
});

describe('borrarFoto', () => {
  let base: string;
  beforeEach(() => {
    base = mkdtempSync(join(tmpdir(), 'zura-foto-'));
    mkdirSync(join(base, 'fotos'), { recursive: true });
  });
  afterEach(() => rmSync(base, { recursive: true, force: true }));
  const aviso = (_m: string) => undefined;

  it('borra el archivo existente', async () => {
    writeFileSync(join(base, 'fotos', 'a.jpg'), 'x');
    expect(await borrarFoto(base, 'fotos/a.jpg', aviso)).toBe('borrada');
    expect(existsSync(join(base, 'fotos', 'a.jpg'))).toBe(false);
  });

  it('un archivo que ya no existe no es un error', async () => {
    expect(await borrarFoto(base, 'fotos/no-esta.jpg', aviso)).toBe('no-existia');
  });

  it('sin foto o sin carpeta configurada no hace nada', async () => {
    expect(await borrarFoto(base, null, aviso)).toBe('sin-foto');
    expect(await borrarFoto(undefined, 'fotos/a.jpg', aviso)).toBe('sin-configurar');
  });

  it('una ruta que se sale de la carpeta NO se borra aunque el archivo exista', async () => {
    const nombre = `fuera-${Date.now()}.txt`;
    const fuera = join(base, '..', nombre);
    writeFileSync(fuera, 'no me borres');
    try {
      expect(await borrarFoto(base, `../${nombre}`, aviso)).toBe('rechazada');
      expect(existsSync(fuera)).toBe(true);
    } finally {
      rmSync(fuera, { force: true });
    }
  });

  it('un directorio no se borra como si fuera una foto (unlink falla y se informa)', async () => {
    expect(await borrarFoto(base, 'fotos', aviso)).toBe('error');
    expect(existsSync(join(base, 'fotos'))).toBe(true);
  });
});
