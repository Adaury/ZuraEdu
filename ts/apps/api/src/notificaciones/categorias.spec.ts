import { existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { categoriaDe, CATEGORIAS, CATEGORIAS_INAPP_BLOQUEADAS, ICONOS, iconoDe, TIPO_CATEGORIA } from './categorias';

const MODELO_PHP = resolve(__dirname, '../../../../../app/Models/Notificacion.php');
const hayPhp = existsSync(MODELO_PHP);

/** Texto entre `const NOMBRE = [` y el `];` que lo cierra. */
function bloque(php: string, nombre: string): string {
  const i = php.indexOf(`const ${nombre} = [`);
  if (i < 0) throw new Error(`No encuentro const ${nombre} en Notificacion.php`);
  return php.slice(i, php.indexOf('\n    ];', i));
}
const pares = (texto: string): Record<string, string> =>
  Object.fromEntries([...texto.matchAll(/'([a-z_]+)'\s*=>\s*'([^']+)'/g)].map((m) => [m[1], m[2]]));

describe('catálogo de notificaciones', () => {
  it('un tipo desconocido cae en la categoría "sistema" y en el icono por defecto', () => {
    expect(categoriaDe('lo_que_sea')).toBe('sistema');
    expect(iconoDe('lo_que_sea')).toBe('bi-bell');
    expect(categoriaDe('ausencia')).toBe('alertas');
    expect(iconoDe('ausencia')).toBe('bi-calendar-x');
  });

  it('todas las categorías usadas por los tipos existen en la lista', () => {
    for (const cat of Object.values(TIPO_CATEGORIA)) expect(CATEGORIAS).toContain(cat);
  });

  (hayPhp ? describe : describe.skip)('paridad con app/Models/Notificacion.php (se lee el archivo PHP)', () => {
    const php = hayPhp ? readFileSync(MODELO_PHP, 'utf8') : '';

    it('ICONOS es idéntico', () => {
      expect(ICONOS).toEqual(pares(bloque(php, 'ICONOS')));
    });

    it('TIPO_CATEGORIA es idéntico', () => {
      expect(TIPO_CATEGORIA).toEqual(pares(bloque(php, 'TIPO_CATEGORIA')));
    });

    it('las categorías y las que tienen el in-app bloqueado son las mismas', () => {
      const cats = bloque(php, 'CATEGORIAS');
      const nombres = [...cats.matchAll(/^\s{8}'([a-z]+)'\s*=>\s*\['label'/gm)].map((m) => m[1]);
      const bloqueadas = [...cats.matchAll(/^\s{8}'([a-z]+)'\s*=>\s*\[[^\n]*'inapp_bloqueado'\s*=>\s*true/gm)].map((m) => m[1]);
      expect([...CATEGORIAS].sort()).toEqual(nombres.sort());
      expect([...CATEGORIAS_INAPP_BLOQUEADAS].sort()).toEqual(bloqueadas.sort());
    });
  });
});
