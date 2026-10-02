# Nginx: Laravel + API TypeScript + web nueva en el mismo servidor (strangler)

Tres archivos, **probados de punta a punta con Nginx 1.22** (TLS, redirección a HTTPS, PHP por FastCGI, API y web reales):

| Archivo | Dónde va | Qué hace |
|---|---|---|
| `zuraedu-strangler-http.conf` | dentro de `http { }` (incluirlo **antes** del `server`) | `upstream` de la API (`:3100`) y de la web (`:3101`) y las zonas de límite por IP |
| `zuraedu-strangler-locations.conf` | dentro del `server { }`, **antes** de `location /` | enruta lo migrado a TypeScript y la web nueva; todo lo demás sigue en PHP-FPM |
| `ejemplo-server.conf` | referencia | `server { }` **completo**: TLS, HTTP→HTTPS, HSTS, PHP-FPM, archivos ocultos denegados, y los `include` de arriba (ajustar los valores marcados «AJUSTAR») |

## Reglas de enrutamiento (verificadas a través del proxy)

| Petición | Va a |
|---|---|
| `/api/v1/estudiantes` y `/api/v1/estudiantes/{id}`, `/api/v1/matriculas` | **API TypeScript** (Node) |
| `/openapi.json` | API TypeScript |
| `/ts-health` (solo desde localhost) | API TypeScript (`/health`) |
| `/nuevo/…` (la web nueva: `/nuevo/login`, `/nuevo/estudiantes`…) | **web Next.js** |
| `/login`, `/api/v1/auth/*`, `/api/v1/dashboard`… (API móvil), `/admin/*`, `/portal/*`, `/health` y todo lo demás | **Laravel** |

- **No enrutes todo `/api/v1/` a Node:** Laravel usa ese mismo prefijo para la API móvil. Cada módulo nuevo se añade por nombre;
  para revertir uno basta con quitarlo (vuelve a Laravel de inmediato: misma BD, mismas reglas).
- **La web vive bajo `/nuevo`, no en la raíz,** porque Laravel ya tiene `/login` y `/`. El prefijo debe ser el **mismo** con el que se compiló
  (`WEB_BASE_PATH=/nuevo npm run build -w @zuraedu/web`; Next lo fija al compilar) y el mismo que arranca (`next start`). Con él, además, la cookie
  del token (`Path=/nuevo`) **no viaja a las rutas de Laravel**. No se puede usar un subdominio aparte: la API decide el colegio por el primer
  segmento del `Host`.

## Qué hace el proxy que las aplicaciones no hacen

- **Límite por IP**, con respuesta `429`:
  - API TypeScript: 300 peticiones/min por IP (ráfaga 100). La aplicación solo limita por *usuario autenticado* (60/min) porque detrás de un
    proxy `req.ip` es la del proxy; las peticiones sin token solo se frenan aquí.
  - Web: 600/min (ráfaga 200; una página trae decenas de archivos estáticos).
  - **Inicio de sesión** (`/nuevo/sesion/entrar`): 20/min (ráfaga 10). Medido: 30 intentos seguidos → 22 × `429`; el resto de la web y Laravel no se ven afectados.
- **El `Host` se conserva** (`proxy_set_header Host $host`): la API decide el colegio por él y responde `403` si un token se usa en el dominio de otro colegio.
- **IP real del cliente:** Nginx **sobrescribe** `X-Forwarded-For` con `$remote_addr` (no la añade a lo que mande el cliente). Laravel limita el login a 10/min **por IP**
  y la web le reenvía la IP del usuario en esa cabecera; Laravel solo la respeta si la llamada viene de un proxy de confianza (`TRUSTED_PROXIES`, por defecto solo
  loopback: la web Next llega desde el mismo servidor). Ver «Un ataque que se demostró y se corrigió» más abajo.

## Requisitos para que los límites no se puedan esquivar

1. La API y la web escuchan **solo en `127.0.0.1`**: la API por `API_HOST` (por defecto), la web porque el script `start` lleva `-H 127.0.0.1` (**`next start` escucha por defecto en `0.0.0.0` y `::`:**
   se comprobó con `netstat`; arrancarlo a mano sin `-H` abre el puerto). Con `0.0.0.0` cualquiera que alcance el puerto se salta Nginx. Cierra también `3100` y `3101` en el firewall.
2. **`TRUSTED_PROXIES` (Laravel).** Por defecto solo loopback, que es lo correcto con este despliegue. Si pones un balanceador o una CDN delante de Nginx, añade **sus**
   rangos a `TRUSTED_PROXIES` y configura el módulo `realip` de Nginx; si no, Laravel verá la IP del balanceador para todos los usuarios y el límite por IP los juntará.
   **No pongas `*`:** vuelve a aceptar cualquier IP falsificada.
3. Con varias instancias de la API, configura Redis (`REDIS_HOST`…) para que el límite por usuario se comparta.

## Qué se probó y qué no

Probado, a través del Nginx real con el `server { }` de ejemplo (certificado autofirmado, `php-cgi` por FastCGI, API y web reales, 45 comprobaciones por HTTPS):
enrutamiento de cada prefijo, redirección HTTP→HTTPS, HSTS, cookie `Secure; HttpOnly; SameSite=Lax; Path=/nuevo`, login, listado, edición, CSRF, ids hostiles,
aislamiento entre colegios, cierre de sesión con revocación del token, y los límites por IP.

**Un error que solo apareció con el proxy real:** una regla `location = /nuevo { return 301 /nuevo/; }` creaba un **bucle infinito** con Next, que normaliza
`/nuevo/` → `/nuevo` (308). Ahora `/nuevo` se pasa a Next (→ `/nuevo/login`). No añadas una redirección a `/nuevo/`.

No probado: PHP-FPM propiamente dicho (se usó `php-cgi` por FastCGI, que habla el mismo protocolo), HTTP/2 real con un cliente, certificados reales, ni el `server { }` de tu
servidor de producción. Revisa que los `include` queden antes del `location /` y del `location ~ \.php$` (la web usa `^~` para que esa expresión regular no capture `/nuevo/…`).

## Cómo se probó (para repetirlo)

1. Base con el esquema de Laravel y roles (`RolesSeeder`); `node apps/web/test/preparar-smoke.mjs` crea colegio, administrador, estudiantes y un colegio ajeno.
2. `php-cgi -b 127.0.0.1:9000` (con `DB_*` y `CACHE_STORE=array`), la API (`RATE_LIMIT_PER_MINUTE=0 AUTH_CACHE_TTL_SECONDS=0`), y la web
   (`WEB_BASE_PATH=/nuevo … next start -p 3101`; con `LARAVEL_URL=https://…` y `NODE_TLS_REJECT_UNAUTHORIZED=0` solo si es certificado autofirmado).
3. Nginx con `ejemplo-server.conf` ajustado (puertos, certificado, `root`, `fastcgi_pass 127.0.0.1:9000`).
4. `WEB_URL=https://127.0.0.1:8443 SMOKE_BASE=/nuevo SMOKE_INSECURE=1 node apps/web/test/smoke.mjs`.

En Git Bash de Windows, `WEB_BASE_PATH=/nuevo` se convierte en `C:/Program Files/Git/nuevo`; define `MSYS_NO_PATHCONV=1` (la web **rechaza** un prefijo inválido en vez de usarlo).

## Un ataque que se demostró y se corrigió (IP falsificada con `X-Forwarded-For`)

Con `TrustProxies` en `'*'` (Laravel confiaba en cualquiera) se reprodujo contra este mismo Nginx + FastCGI, desde una IP de red local:

| Petición | Laravel veía como IP del cliente |
|---|---|
| sin cabecera | `192.168.0.8` (la real) |
| con `X-Forwarded-For: 1.2.3.4` | **`1.2.3.4`** (falsificada) |

Consecuencias: (1) el límite de 10 intentos de login por minuto **por IP** se esquivaba rotando una IP falsa en cada intento (11 logins fallidos por la web: ninguno
limitado); (2) la IP que guardan `ActivityLog`, `CalificacionAudit` y el carnet se podía falsificar. **Cerrar el acceso directo a PHP-FPM no lo evitaba**: con FastCGI,
Nginx pasa a PHP todas las cabeceras del cliente.

Corrección (tres capas, todas probadas): `TrustProxies` solo confía en `TRUSTED_PROXIES` (loopback por defecto); Nginx **sobrescribe** `X-Forwarded-For` con `$remote_addr`
(con «añadir» llegaba `falsa, real` y la web tomaba la primera); y la web toma la **última** entrada. Resultado medido con el mismo ataque: desde la IP de red con la cabecera
falsa Laravel mantiene `192.168.0.8`, y los 11 logins con IP falsa rotando se limitan en el 11.º intento, igual que sin cabecera.

