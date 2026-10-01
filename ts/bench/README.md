# Benchmark de carga: Laravel vs API TypeScript

Herramientas para repetir la medición de [`../README.md`](../README.md#carga-concurrente-laravel-vs-api-typescript-2026-10-01).
**Solo para pruebas locales sobre una base sintética (`sge_bench`). No se despliega.**

- `loadgen.js` — generador de carga mínimo y neutro (el mismo para PHP y Node):
  `node loadgen.js URL CONEXIONES SEGUNDOS "Header: valor" ...`
- `php_bench_router.php` — router de `php -S` que arranca Laravel y añade `GET /api/v1/_bench/estudiantes`, con los mismos
  middleware de la API móvil (`auth:sanctum`, `api.tenant`) y la misma respuesta que la API TypeScript, para que ambos hagan
  el MISMO trabajo. Quita a propósito el limitador de tasa (`throttle:api`) para medir trabajo y no límites.

## Receta

1. Base sintética `sge_bench` y un token Sanctum de un usuario del tenant 1 (ver `ts/apps/api/test/helpers/fixtures.ts`,
   `crearToken`: `tokenable_type` es `App\Models\User`, con barras; se pierden si se escribe por shell).
2. PHP (1 proceso, desde `public/`): `DB_DATABASE=sge_bench php -d opcache.enable_cli=1 -d opcache.validate_timestamps=0 -S 127.0.0.1:8002 ts/bench/php_bench_router.php`.
   - **No uses `APP_ENV=production` en local**: carga `.env.production` (credenciales de plantilla).
   - Para el caso "con cachés": `APP_CONFIG_CACHE=` y `APP_ROUTES_CACHE=` con una ruta **relativa a la raíz del proyecto**
     (Laravel antepone la ruta base a las rutas con letra de unidad) y ejecutar `artisan config:cache` y `route:cache` con
     esas variables; así `bootstrap/cache` de desarrollo no se toca.
3. Node: `npm run build` y `NODE_ENV=production DATABASE_URL=mysql://root@127.0.0.1:3306/sge_bench node apps/api/dist/main.js`
   (`AUTH_CACHE_TTL_SECONDS=0` para desactivar la caché de autenticación).
4. Antes de medir, verificar con `curl` que ambos devuelven **200** y el **mismo cuerpo** (`data` idéntico, 2.106 bytes).
5. Medir un servidor cada vez (no compiten por CPU): `node loadgen.js ... 1|10|50 15`.
