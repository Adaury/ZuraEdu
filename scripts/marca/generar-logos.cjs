#!/usr/bin/env node
/**
 * Genera TODOS los recursos de la marca ZuraEdu (SVG + PNG + favicon + íconos de la app móvil) a partir de un solo diseño.
 * Es la fuente de verdad del logo: para cambiar colores o forma se edita AQUÍ y se vuelve a generar.
 *
 *   node scripts/marca/generar-logos.cjs [carpeta-de-salida-de-prueba]
 *
 * Sin argumentos escribe en public/brand/ y en mobile/assets/. Con una carpeta, escribe solo ahí (para revisar el diseño sin tocar nada).
 * Requiere `sharp` (ya instalado en ts/node_modules).
 *
 * Diseño: un birrete (gorro de graduación) sobre una Z geométrica dentro de una insignia azul marino; el nombre «ZuraEdu» va en trazo
 * monolineal geométrico (letras dibujadas con trazos, no con una tipografía) para que se vea igual en cualquier equipo, PDF o correo.
 */
const fs = require('fs');
const path = require('path');
const sharp = require('../../ts/node_modules/sharp');

const RAIZ = path.resolve(__dirname, '..', '..');
const SALIDA_PRUEBA = process.argv[2] ? path.resolve(process.argv[2]) : null;

// ── Paleta ────────────────────────────────────────────────────────────────────
const C = {
  marino: '#1e3a6e',   // el mismo azul que ya usa la app móvil
  azul: '#2563eb',
  cielo: '#7dd3fc',    // el birrete
  cieloClaro: '#bae6fd',
  blanco: '#ffffff',
};

// ── Símbolo (insignia) en un lienzo de 512 × 512 ─────────────────────────────
// `fondo`: dibuja la insignia redondeada; sin fondo, solo el símbolo (para íconos adaptativos y de notificación).
function simbolo({ fondo = 'azul', z = C.blanco, birrete = C.cielo, borla = C.cielo } = {}) {
  const rect = {
    azul: `<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${C.marino}"/><stop offset="1" stop-color="#2a56b0"/></linearGradient></defs>` +
          `<rect width="512" height="512" rx="116" fill="url(#g)"/>`,
    blanco: `<rect width="512" height="512" rx="116" fill="${C.blanco}"/>`,
    plano: `<rect width="512" height="512" fill="${C.marino}"/>`,
    ninguno: '',
  }[fondo];

  return rect +
    // Z geométrica (bajada 12 px para dejar aire bajo el birrete)
    `<path d="M136 252H376V298L206 382H376V428H136V382L306 298H136Z" fill="${z}"/>` +
    // birrete: tablero en rombo + banda inferior + borla
    `<path d="M256 66L408 134L256 202L104 134Z" fill="${birrete}"/>` +
    `<path d="M178 168V194C178 214 214 230 256 230C298 230 334 214 334 194V168L256 202Z" fill="${birrete}" opacity=".85"/>` +
    `<path d="M408 134V204" stroke="${borla}" stroke-width="9" stroke-linecap="round"/>` +
    `<circle cx="408" cy="214" r="14" fill="${borla}"/>`;
}

// ── Nombre «ZuraEdu» en trazo monolineal (altura 110, ancho ≈ 496) ───────────
function nombre({ zura = C.marino, edu = C.azul, grosor = 15 } = {}) {
  const t = (c) => `fill="none" stroke="${c}" stroke-width="${grosor}" stroke-linecap="round" stroke-linejoin="round"`;
  // Cada letra se dibuja con su origen en (0,0); línea base y = 100, altura de x = 44, mayúsculas/ascendentes = 10.
  const letra = (x, d, c) => `<g transform="translate(${x} 0)"><path d="${d}" ${t(c)}/></g>`;
  const circulo = (x, c) => `<g transform="translate(${x} 0)"><circle cx="28" cy="72" r="28" ${t(c)}/></g>`;
  return [
    letra(0,   'M0 10H56L0 100H56', zura),                                    // Z
    letra(80,  'M0 44V76A24 24 0 0 0 48 76M48 44V100', zura),                  // u
    letra(150, 'M0 44V100M0 74C0 54 12 44 30 44', zura),                       // r
    circulo(202, zura), letra(202, 'M56 44V100', zura),                        // a
    letra(296, 'M46 10H0V100H46M0 55H36', edu),                                // E
    circulo(366, edu), letra(366, 'M56 10V100', edu),                          // d
    letra(444, 'M0 44V76A24 24 0 0 0 48 76M48 44V100', edu),                   // u
  ].join('');
}
const ANCHO_NOMBRE = 492 + 15;   // 444 + 48 + grosor

// ── Composiciones ─────────────────────────────────────────────────────────────
function svg(ancho, alto, cuerpo) {
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${ancho} ${alto}" width="${ancho}" height="${alto}" role="img" aria-label="ZuraEdu">${cuerpo}</svg>\n`;
}

/** Logo horizontal: insignia + nombre. */
function logoHorizontal({ oscuro = false } = {}) {
  const ins = oscuro
    ? simbolo({ fondo: 'blanco', z: C.marino, birrete: C.azul, borla: C.azul })
    : simbolo();
  const nom = oscuro ? nombre({ zura: C.blanco, edu: C.cieloClaro }) : nombre();
  const alto = 140, lado = 132;
  return svg(lado + 34 + ANCHO_NOMBRE + 8, alto,
    `<g transform="translate(0 4) scale(${lado / 512})">${ins}</g>` +
    `<g transform="translate(${lado + 34} ${(alto - 110) / 2 + 4})">${nom}</g>`);
}

/** Solo la insignia. */
function iconoSolo({ oscuro = false } = {}) {
  return svg(512, 512, oscuro ? simbolo({ fondo: 'blanco', z: C.marino, birrete: C.azul, borla: C.azul }) : simbolo());
}

// ── Escritura ─────────────────────────────────────────────────────────────────
function destino(rutaRelativa) {
  const p = SALIDA_PRUEBA ? path.join(SALIDA_PRUEBA, path.basename(rutaRelativa)) : path.join(RAIZ, rutaRelativa);
  fs.mkdirSync(path.dirname(p), { recursive: true });
  return p;
}
const escribir = (rel, contenido) => { fs.writeFileSync(destino(rel), contenido); console.log('  ', rel); };
async function png(rel, svgTexto, ancho, opts = {}) {
  const buf = await sharp(Buffer.from(svgTexto), { density: 384 }).resize({ width: ancho, ...opts }).png({ compressionLevel: 9 }).toBuffer();
  fs.writeFileSync(destino(rel), buf);
  console.log('  ', rel, `(${ancho}px, ${(buf.length / 1024).toFixed(1)} KB)`);
  return buf;
}

/** .ico con PNG incrustados (válido desde Windows Vista y todos los navegadores). */
function ico(imagenes) {
  const n = imagenes.length;
  const cab = Buffer.alloc(6 + 16 * n);
  cab.writeUInt16LE(0, 0); cab.writeUInt16LE(1, 2); cab.writeUInt16LE(n, 4);
  let off = cab.length;
  imagenes.forEach(({ lado, buf }, i) => {
    const e = 6 + 16 * i;
    cab.writeUInt8(lado >= 256 ? 0 : lado, e); cab.writeUInt8(lado >= 256 ? 0 : lado, e + 1);
    cab.writeUInt8(0, e + 2); cab.writeUInt8(0, e + 3);
    cab.writeUInt16LE(1, e + 4); cab.writeUInt16LE(32, e + 6);
    cab.writeUInt32LE(buf.length, e + 8); cab.writeUInt32LE(off, e + 12);
    off += buf.length;
  });
  return Buffer.concat([cab, ...imagenes.map((i) => i.buf)]);
}

async function main() {
  console.log(SALIDA_PRUEBA ? `Modo prueba → ${SALIDA_PRUEBA}` : 'Escribiendo en public/brand y mobile/assets');

  // SVG (vectoriales: web y pantallas)
  const logo = logoHorizontal(), logoBlanco = logoHorizontal({ oscuro: true });
  const icono = iconoSolo(), iconoBlanco = iconoSolo({ oscuro: true });
  escribir('public/brand/zuraedu-logo.svg', logo);
  escribir('public/brand/zuraedu-logo-blanco.svg', logoBlanco);
  escribir('public/brand/zuraedu-icono.svg', icono);
  escribir('public/brand/zuraedu-icono-blanco.svg', iconoBlanco);
  escribir('public/brand/favicon.svg', icono);

  // PNG (correos, PDF y lugares que no admiten SVG)
  await png('public/brand/zuraedu-logo.png', logo, 1200);
  await png('public/brand/zuraedu-logo-blanco.png', logoBlanco, 1200);
  await png('public/brand/zuraedu-logo-300.png', logo, 300);          // pequeño para PDF y correos
  await png('public/brand/zuraedu-icono-512.png', icono, 512);
  await png('public/brand/zuraedu-icono-192.png', icono, 192);        // PWA
  await png('public/brand/apple-touch-icon.png', icono, 180);
  const f16 = await png('public/brand/favicon-16.png', icono, 16);
  const f32 = await png('public/brand/favicon-32.png', icono, 32);
  const f48 = await png('public/brand/favicon-48.png', icono, 48);
  fs.writeFileSync(destino('public/favicon.ico'), ico([{ lado: 16, buf: f16 }, { lado: 32, buf: f32 }, { lado: 48, buf: f48 }]));
  console.log('   public/favicon.ico (16, 32 y 48 px)');

  // Imagen para compartir en redes (1200×630): logo blanco sobre el azul de la marca
  const alto = 630, ancho = 1200, lado = 700;
  const og = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${ancho} ${alto}" width="${ancho}" height="${alto}">` +
    `<defs><linearGradient id="f" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${C.marino}"/><stop offset="1" stop-color="#2a56b0"/></linearGradient></defs>` +
    `<rect width="${ancho}" height="${alto}" fill="url(#f)"/>` +
    `<g transform="translate(${(ancho - lado) / 2} ${(alto - lado * 140 / 650) / 2 - 30}) scale(${lado / 650})">${logoBlanco.replace(/<\/?svg[^>]*>/g, '')}</g>` +
    `</svg>`;
  await png('public/brand/og-image.png', og, 1200);

  // App móvil (mismas dimensiones que los archivos actuales)
  const planoIcono = svg(1024, 1024, `<g transform="scale(2)">${simbolo({ fondo: 'plano' })}</g>`);
  await png('mobile/assets/icon.png', planoIcono, 1024);
  // Adaptativo de Android: solo el símbolo, centrado y reducido a la zona segura (≈ 66 %), sobre fondo transparente
  const adaptativo = svg(1024, 1024, `<g transform="translate(170 170) scale(1.35)">${simbolo({ fondo: 'ninguno' })}</g>`);
  await png('mobile/assets/adaptive-icon.png', adaptativo, 1024);
  // Pantalla de arranque: símbolo blanco sobre transparente (el fondo azul lo pone app.json)
  const arranque = svg(512, 512, simbolo({ fondo: 'ninguno', z: C.blanco, birrete: C.blanco, borla: C.blanco }));
  await png('mobile/assets/splash-icon.png', arranque, 512);
  // Notificación de Android: SOLO blanco con transparencia (Android lo tiñe)
  const notificacion = svg(512, 512, simbolo({ fondo: 'ninguno', z: C.blanco, birrete: C.blanco, borla: C.blanco }));
  await png('mobile/assets/notification-icon.png', notificacion, 96);
  await png('mobile/assets/favicon.png', icono, 48);
  console.log('Listo.');
}

main().catch((e) => { console.error(e); process.exit(1); });
