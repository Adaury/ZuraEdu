# Web (Next.js) — primera rebanada funcional

Login, listado de estudiantes con búsqueda y paginación, y edición. Es una **rebanada vertical**, no la web completa: el resto del
sistema sigue en Laravel (ver `../../README.md`).

## Cómo funciona

```
navegador ──► Next.js (esta app) ──► Laravel      POST /api/v1/auth/login · /logout   (emite y revoca el token de Sanctum)
                                 └─► API TypeScript   GET/PATCH /api/v1/estudiantes…  (mismo token)
```

- El token vive en la cookie **`zura_token`** (`HttpOnly`, `SameSite=Lax`, `Secure` en producción, 8 h): el JavaScript del
  navegador nunca lo ve. Todas las llamadas a la API salen del **servidor** de Next.
- Las pantallas son componentes de servidor; el login, el cierre de sesión y el guardado son **manejadores de ruta** (`POST`
  de formulario normal, sin JavaScript en el cliente).

| Ruta | Qué hace |
|---|---|
| `/login`, `POST /sesion/entrar` | inicia sesión contra Laravel |
| `POST /sesion/salir` | revoca el token en Laravel y borra la cookie |
| `/sesion/expirada` | la API respondió 401: borra la cookie y vuelve al login |
| `/estudiantes` | listado (búsqueda `q`, filtro `estado`, `page`; 20 por página) |
| `/estudiantes/[id]/editar`, `POST …/guardar` | edición (nombres, apellidos, cédula, sexo, estado) |
| `/estado` | estado de la API (público) |

## Decisiones de seguridad

- **El navegador manda ids y datos; nada se da por bueno.** Los ids de la URL solo se aceptan con dígitos (`/^[1-9][0-9]{0,14}$/`);
  `estado` y `page` se validan; `q` se recorta a 100 caracteres. El colegio, los permisos y las reglas de negocio los decide la API.
- **CSRF:** la cookie es `SameSite=Lax` y además todo `POST` exige que `Origin` sea el mismo sitio que `Host` (sin `Origin` se rechaza).
- **Sin texto libre en la URL:** los errores viajan como códigos fijos (`?e=invalido&c=nombres`); lo que no está en la lista se ignora,
  así que un enlace manipulado no puede mostrar mensajes ni inyectar HTML.
- **Sin redirecciones abiertas:** tras el login siempre se va a `/estudiantes`; las ubicaciones son relativas.
- **Aislamiento entre colegios:** un estudiante de otro colegio responde 404 (igual que uno inexistente).
- Cabeceras: `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy: same-origin`, sin `X-Powered-By`.
- **El `Host` se reenvía a la API con `node:http`, no con `fetch`:** el `fetch` de Node ignora esa cabecera y la API decide el
  colegio por ella. Se reenvía también `X-Forwarded-For`: Laravel limita el login a 10 por minuto **por IP** y, sin la IP real del
  usuario, todos compartirían el cupo del servidor web.

## Configuración (solo servidor; ninguna variable llega al navegador)

| Variable | Por defecto | Para qué |
|---|---|---|
| `API_URL` | `http://127.0.0.1:3100` | API TypeScript |
| `LARAVEL_URL` | `http://127.0.0.1:8000` | Laravel (login/logout) |
| `WEB_BASE_PATH` | vacío (raíz) | prefijo bajo el que vive la web (en producción `/nuevo`). **Debe ser el mismo al compilar y al arrancar**: Next lo fija en `next build` |
| `COOKIE_SECURE` | `true` en producción | `false` solo para probar por http sin TLS |

**La web escucha solo en `127.0.0.1`** (el script `start` lleva `-H 127.0.0.1`; sin esa opción `next start` abre `0.0.0.0` y se salta el proxy).

Detrás de Nginx, `Host` debe llegar intacto (`proxy_set_header Host $host`) y `X-Forwarded-For` también. La configuración completa (TLS, prefijo,
límites por IP) y lo que se probó está en [`../../deploy/nginx/`](../../deploy/nginx/README.md).

**Por qué un prefijo:** Laravel ya tiene `/login` y `/` en el mismo servidor, y un subdominio no sirve (la API decide el colegio por el primer segmento del
`Host`). Con `WEB_BASE_PATH=/nuevo` las pantallas viven en `/nuevo/login`, `/nuevo/estudiantes`…, y la cookie del token lleva `Path=/nuevo`, así que **no se
envía a ninguna ruta de Laravel**. Un valor inválido (con espacios, `..`, saltos de línea…) hace fallar el arranque y la compilación.

## Pruebas

- `npm test -w @zuraedu/web`: utilidades puras (ids, consulta, CSRF, códigos de error, cookies, IP, prefijo) — 108 pruebas.
- `test/smoke.mjs`: **45 comprobaciones** (también con `SMOKE_BASE=/nuevo`, `https://…` y `SMOKE_INSECURE=1` para un certificado autofirmado) por HTTP contra servicios reales (login con credenciales buenas y malas, cookie, listado,
  búsqueda, parámetros hostiles, edición, errores 400/409, CSRF, ids raros, otro colegio, cierre de sesión y token revocado).
  `test/preparar-smoke.mjs` crea los datos; el CI lo ejecuta (job `e2e`). Ver la cabecera del archivo para correrlo en local.

## Lo que NO hace todavía

Borrar estudiantes, crear estudiantes, foto, matrículas y el resto de módulos desde la web; recuperar contraseña; roles distintos del
administrativo (la API responde 403 y la pantalla lo dice); CSP (Next inyecta scripts en línea: requiere nonces); caché de la API
(la revocación de un token tarda hasta `AUTH_CACHE_TTL_SECONDS` en notarse: 30 s por defecto).
