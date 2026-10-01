# Nginx: enrutar a la API TypeScript sin tocar Laravel (strangler)

Dos archivos, probados con Nginx 1.22 (`nginx -t` y peticiones reales, 2026-10-01):

| Archivo | Dónde va | Qué hace |
|---|---|---|
| `zuraedu-strangler-http.conf` | dentro de `http { }` | `upstream` de la API TypeScript y la zona de límite por IP |
| `zuraedu-strangler-locations.conf` | dentro del `server { }` de la app, **antes** de `location /` | enruta por ruta exacta lo que ya migró; todo lo demás sigue en PHP-FPM |

## Reglas de enrutamiento (verificadas)

| Petición | Va a |
|---|---|
| `/api/v1/estudiantes` y `/api/v1/estudiantes/{id}` | **Node** (API TypeScript) |
| `/openapi.json` | Node |
| `/ts-health` (solo desde localhost) | Node (`/health` de la API) |
| `/api/v1/auth/login`, `/api/v1/dashboard`… (API móvil), `/admin/*`, `/portal/*`, `/health` y `/api/v1/estudiantesXYZ` | **Laravel** |

**No enrutes todo `/api/v1/` a Node:** Laravel usa ese mismo prefijo para la API móvil. Cada módulo nuevo se añade a la lista por
nombre; para revertir uno basta con quitarlo (vuelve a Laravel de inmediato: misma BD, mismas reglas).

## Qué hace el proxy que la aplicación no hace

- **Límite por IP** (300 peticiones/min sostenidas, ráfaga de 100, responde `429`) para todo lo que entra a Node, incluidas las
  peticiones **sin token**. La aplicación solo limita por usuario autenticado (60/min) porque detrás de un proxy `req.ip` es
  la del proxy. Medido: 150 peticiones sin token seguidas → 119 × `401` y 31 × `429`; las rutas de Laravel no se tocan.
- **`proxy_set_header Host $host`** es imprescindible: la API decide el colegio por el Host y responde `403` si un token se usa
  en el dominio de otro colegio.

## Requisitos para que el límite no se pueda esquivar

1. La API escucha por defecto **solo en `127.0.0.1`** (`API_HOST`, ver `ts/.env.example`). Con `0.0.0.0` cualquiera que alcance
   el puerto 3100 se salta Nginx. Verificado con `netstat`. Usa `0.0.0.0` solo dentro de un contenedor o red privada.
2. Cierra el puerto 3100 en el firewall hacia fuera aunque escuche en loopback.
3. Con varias instancias de la API, configura Redis (`REDIS_HOST`…) para que el límite por usuario se comparta; sin Redis cada
   instancia cuenta por separado (N instancias = N veces el tope).

## Cómo se probó (para repetirlo)

1. Un `php -S 127.0.0.1:8091` con un `index.php` que responde `LARAVEL <uri>` y la cabecera `X-Servido-Por: laravel`.
2. La API: `NODE_ENV=production DATABASE_URL=… API_PORT=3100 node apps/api/dist/main.js`.
3. Un `nginx.conf` mínimo con `include` de los dos archivos, `listen 8090` y `location / { proxy_pass http://127.0.0.1:8091; }`.
4. `nginx -t -p <dir> -c <dir>/nginx.conf`, y `curl` a cada ruta de la tabla de arriba.

Lo que **no** se probó: TLS, HTTP/2, PHP-FPM real ni el `server { }` de producción (este repo no incluye uno; el `DEPLOY.md`
solo documenta el bloque de Reverb). Revisa que los `include` queden antes del `location /` y del `location ~ \.php$`.
