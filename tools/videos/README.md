# Videos de ZuraEdu

Graba los videos de la portada con un navegador real (Playwright) sobre el sistema en marcha y los convierte a MP4
(sin audio, con textos en pantalla y cursor visible). Genera en `public/videos/`:

| Archivo | Dónde se usa |
|---------|--------------|
| `zuraedu-presentacion.mp4` | Video del **inicio** (sección "Míralo en acción") |
| `zuraedu-promo.mp4` | Video **publicitario** (misma sección; también sirve para redes) |
| `zuraedu-promo-vertical.mp4` | Publicitario **vertical 9:16 (1080×1920, ~40 s)** para Instagram (Reels/Stories), Facebook y YouTube Shorts. No sale en la portada: se sube a las redes |
| `modulo-*.mp4` (8) | **Un video por módulo** (inscripción/matrícula, asistencia, notas y boletines, pagos, cafetería, comunicados, portal docente, portal de familias) |

Cada MP4 sale con su miniatura `.jpg`. La portada (`resources/views/partials/landing-videos.blade.php`) solo muestra
los videos cuyo archivo existe.

## Requisitos

- Servidor local con una base de **demostración** (nunca datos reales: los videos se publican). Por defecto
  `http://127.0.0.1:8091`; cambia con `VIDEO_BASE`. Ejemplo: `php artisan serve --port=8091`.
- Cuentas de demostración (administrador, docente, familia). Sus correos se cambian con `VIDEO_ADMIN`, `VIDEO_DOCENTE`
  y `VIDEO_PADRE`.
- `ffmpeg` en el PATH (o `FFMPEG`), `playwright-core` (o `PLAYWRIGHT_PATH`) y Chromium de Playwright
  (o `CHROME_PATH`).
- La contraseña de las cuentas demo, **nunca en el código**: `VIDEO_CLAVE` o `VIDEO_CLAVE_FILE` (ruta de un archivo
  que la contiene, fuera del repositorio).

## Uso

```bash
VIDEO_CLAVE_FILE=/ruta/fuera/del/repo/clave.txt node tools/videos/grabar.cjs todos
VIDEO_CLAVE_FILE=... node tools/videos/grabar.cjs zuraedu-promo modulo-pagos   # solo algunos
```

Los guiones están en `escenas.cjs` (una entrada por video); la mecánica de grabación, en `lib.cjs`.
`VIDEO_OUT` cambia la carpeta de salida.

## Notas

- Una escena puede grabarse a menor tamaño y entregarse ampliada con `escalarA` en sus opciones (el vertical se graba en 540×960 y se entrega en 1080×1920).
- Al grabar el vertical, el panel de administración tarda ~2 s en pintarse tras iniciar sesión; ese tramo en blanco se recortó a mano con ffmpeg (de 0:09 a 0:13). Si se regraba, hay que repetir el recorte.
- Los iconos de redes sociales sueltos están en `public/brand/redes/` (instagram, facebook, youtube; SVG). Los enlaces del pie de la portada se configuran con `BRAND_INSTAGRAM_URL`, `BRAND_FACEBOOK_URL` y `BRAND_YOUTUBE_URL` en el `.env`.
