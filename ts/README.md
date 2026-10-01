# ZuraEdu — esqueleto TypeScript

Punto de partida de la migración **gradual** (strangler) desde Laravel a TypeScript.
Laravel sigue siendo la fuente de verdad: este workspace **lee la misma base MySQL** y reconoce
los **mismos usuarios, roles y tokens**. Se migra un módulo a la vez y Laravel se apaga al final.

> Estado: esqueleto + primer corte vertical (`GET /api/v1/estudiantes`, solo lectura).
> No hay escrituras todavía. Nada de PHP se modificó.

## Estructura

```
ts/
├── packages/shared      Tipos y constantes del dominio (roles, permisos, estados de asistencia, DTOs)
├── apps/api             NestJS 11 + Kysely (MySQL) — API REST
└── apps/web             Next.js 16 (App Router) — esqueleto que consume la API
```

## Puesta en marcha

Requiere Node ≥ 22 y la base MySQL de Laravel ya migrada.

```bash
cd ts
cp .env.example .env          # ajusta DATABASE_URL
npm install
npm run dev:api               # http://127.0.0.1:3100  (compila @zuraedu/shared antes)
npm run dev:web               # http://127.0.0.1:3101
```

| Comando | Qué hace |
|---|---|
| `npm run typecheck` | Compila shared + api + web sin emitir |
| `npm test` | Pruebas unitarias de la API (no necesitan base de datos) |
| `npm run test:e2e -w @zuraedu/api` | E2E contra MySQL real (`TEST_DATABASE_URL`, por defecto `sge_bench`). Cada prueba crea y borra sus propios colegios, usuarios y tokens: sirve cualquier base con el esquema migrado y `RolesSeeder` |
| `npm run db:types` | Regenera `apps/api/src/db/db.d.ts` desde el esquema real |
| `npm run db:types:check -w @zuraedu/api` | Falla si `db.d.ts` quedó desfasado del esquema |
| `npm run build` | Compila todo para producción |

Después de **cada migración de Laravel** ejecuta `npm run db:types` y revisa el diff de `db.d.ts`:
así el compilador te avisa de cualquier cambio de esquema que rompa la API.

**CI** (`.github/workflows/ts.yml`): typecheck, unitarias y build; y un segundo trabajo que levanta MySQL, aplica las
migraciones de Laravel y `RolesSeeder` (como en producción), comprueba que `db.d.ts` sigue al día y corre las e2e.
Se dispara también cuando cambian `database/migrations/**` o `RolesSeeder.php`: un cambio de esquema en PHP que rompa
la API tiene que saltar ahí, no en producción.

## Seguridad multi-tenant

Reglas del proyecto (CLAUDE.md): aislar por tenant y **no confiar en el cliente**.

- **El tenant sale del usuario del token**, nunca de un header, parámetro o body.
  `SanctumGuard` carga el usuario y su tenant desde la base y los guarda en el contexto de la petición.
- Si el `Host` identifica a *otro* tenant (token del colegio A usado en el dominio del colegio B) → `403`.
  Las reglas de resolución de host replican `ResolveTenant` de Laravel.
- Un tenant `suspendido`/`cancelado` o un usuario inactivo → rechazados (igual que `Tenant::estaActivo()`
  y `CheckUserActivo`).
- **`TenantScopePlugin`** (equivalente de `BelongsToTenant`, pero más estricto): añade
  `AND <tabla>.tenant_id = ?` a todo `SELECT`/`UPDATE`/`DELETE` de tablas con `tenant_id`, incluidos
  `JOIN` (en el `ON`, para no convertir un `LEFT JOIN` en `INNER`) y subconsultas.
  - **Falla cerrado:** sin tenant en contexto, consultar una tabla de tenant **lanza** en vez de devolver todo.
  - Un `INSERT` en tabla de tenant sin `tenant_id` también lanza.
  - La lista de tablas con `tenant_id` se lee de `information_schema` al arrancar (nunca queda desfasada).
- Dos conexiones: `DB` (con el filtro, para módulos de negocio) y `SYSTEM_DB` (sin filtro, **solo**
  autenticación, resolución de tenant y permisos). Los módulos de negocio no deben inyectar `SYSTEM_DB`.
- **Límite conocido:** el SQL crudo (`sql\`...\``) no pasa por el plugin. Evítalo en módulos de negocio
  o filtra a mano por tenant.
- Todo es privado por defecto; solo lo marcado con `@Public()` (`/health`) no pide token.
- `tests/e2e` incluye una prueba de aislamiento entre dos colegios; quitar el plugin hace fallar 3 pruebas
  (verificado con una mutación manual).

## Autenticación y permisos compatibles con Laravel

- **Tokens de Sanctum** (`id|texto`): se valida el SHA-256 contra `personal_access_tokens` en tiempo constante,
  `tokenable_type = App\Models\User` y `expires_at`. La app móvil actual funcionaría sin cambios.
  (No se actualiza `last_used_at`: esta API es de solo lectura por ahora.)
- **Permisos de Spatie** leídos de `model_has_roles`, `role_has_permissions`, `permissions` y
  `model_has_permissions` (sin "teams"). `super_admin` pasa siempre, como el `Gate::before` de Laravel.
- Se usa con `@RequirePermission('ver-estudiantes')`; los nombres están tipados en `@zuraedu/shared`.

### Caché de sesión y permisos (`AuthCache`)

En memoria, por proceso, con vencimiento (`AUTH_CACHE_TTL_SECONDS`, 30 s por defecto, `0` la desactiva, máximo 300).

- **Qué cachea:** `token → usuario → tenant` (3 consultas) y `usuario → todos sus permisos` (2–3 consultas).
  Pasa de 7 a **2 consultas por petición** (solo las de datos).
- **La clave lleva el hash del token** (`<id>:<sha256>`), nunca el token en claro. Un secreto equivocado produce
  otra clave, así que jamás acierta la caché (hay una prueba e2e).
- **Solo se cachean sesiones válidas.** Un token rechazado nunca queda guardado.
- **Nunca sobrevive al token:** si el token tiene `expires_at`, la entrada vence con él.
- **Single-flight:** peticiones concurrentes por la misma clave cargan una sola vez.
- **La comprobación del `Host` no se cachea** (depende de lo que manda el cliente; corre en cada petición).
- **Compromiso explícito:** no hay invalidación cruzada con Laravel. Revocar un token, desactivar un usuario,
  suspender un tenant o quitar un permiso tarda **hasta `AUTH_CACHE_TTL_SECONDS`** en notarse aquí
  (probado con MySQL real). Si no te sirve esa ventana, baja el TTL o ponlo en `0`.
  Con varias instancias de la API cada una tiene su caché; cuando haya Redis en TypeScript conviene moverla ahí.

## Escrituras y auditoría

Ciclo completo de estudiantes migrado (todo con el permiso `gestionar-estudiantes`, igual que las rutas de mutación de Laravel):

| Operación | Ruta | Auditoría (como el observer de Laravel) |
|---|---|---|
| Alta | `POST /api/v1/estudiantes` → `201` | `estudiante.creado` |
| Edición parcial | `PATCH /api/v1/estudiantes/:id` → `200` | `estudiante.actualizado` y, si cambia un campo sensible, `estudiante.editado` |
| Borrado lógico | `DELETE /api/v1/estudiantes/:id` → `204` | `estudiante.eliminado` |

El `PATCH` y el `POST` aceptan **todos los campos del formulario de Laravel salvo la foto**: matrícula, cédula, nombres,
apellidos, fecha de nacimiento, sexo, nacionalidad, lugar de nacimiento, teléfono, correo, dirección, sector, municipio,
provincia, estado, datos del tutor (nombre, parentesco, teléfono, trabajo) y notas médicas. Comparten **una sola definición**
de campos (`estudiantes.update.ts`), así que las reglas no se desalinean. Quedan fuera: la **foto** (depende de dónde se guarden
los archivos) y matricular al crear (`grupo_id`, que es el módulo de matrículas). Ambos se rechazan con 400.

**Antes de migrar otro módulo, lee [`docs/EFECTOS_SECUNDARIOS_PHP.md`](docs/EFECTOS_SECUNDARIOS_PHP.md)**: inventario de los
observers, hooks, eventos, cachés y efectos en archivos de Laravel, con su estado de paridad en TypeScript y las diferencias
deliberadas.

**Alta.** El colegio sale del token. Si no se envía `numeroMatricula` se genera (`AAAA-NNNNN`): la asignación está
**serializada por colegio** con un bloqueo de fila sobre el tenant (se libera al confirmar), así que 12 altas simultáneas
obtienen números consecutivos y sin colisiones; se reintenta además por si Laravel (que calcula el suyo con `count()+1`) toma
el mismo número entre medias. A diferencia de Laravel, el número **no se reutiliza tras un borrado** (allí colisionaría y daría
un 500). Cédula o matrícula repetidas en el colegio → `409` (la misma cédula en otro colegio es válida).

**Borrado.** Es lógico (como `SoftDeletes`): se marcan `deleted_at` y `updated_at`, la fila se conserva y solo se audita
`estudiante.eliminado` (Eloquent dispara `deleted`, no `updated`). Un número de matrícula de un estudiante borrado no se puede
reutilizar (la restricción única los cuenta). También se borra el archivo de la foto, como Laravel, si la API conoce la carpeta pública (`FOTOS_PUBLICAS_DIR`, mismo servidor que Laravel); sin ella queda huérfano.

- **Dos registros de auditoría, como Laravel, con las mismas condiciones.** Se escriben en `activity_logs`, en este orden:
  1. `estudiante.actualizado` — lo genera `EstudianteObserver::updated()` en PHP: **siempre** que cambie alguna
     columna, `Estudiante actualizado: Apellidos, Nombres | Campos: telefono, estado, updated_at` (columnas cambiadas
     en el **orden de la tabla**, que es el que usa Eloquent, y cerrando con `updated_at`; una prueba lo compara con
     `information_schema`).
  2. `estudiante.editado` — lo escribe el controlador: **solo** si cambió algún campo sensible (cédula, nombres, apellidos,
     fecha de nacimiento, estado), con el detalle `Estudiante #12: cedula: — → 001 | estado: activo → inactivo`
     (`—` para nulos; las fechas como `2012-05-01 00:00:00` porque Laravel castea a `date`).
  Así, cambiar solo el teléfono deja un único registro y la pantalla de auditoría actual muestra juntos los cambios hechos
  desde PHP y desde TypeScript. `tenant_id` y `user_id` salen del token, nunca del cuerpo.
- **Lección general de la migración: los observers de PHP no se disparan.** Los *observers*, eventos y listeners de
  Laravel **no se ejecutan** cuando la escritura la hace otro lenguaje; si no se reproducen a mano, las pantallas e informes
  que dependen de ellos dejan de ver esos cambios. Antes de migrar la escritura de cada módulo hay que inventariar sus
  observers (hoy: `Estudiante`, `Matricula`, `Calificacion`, `CalificacionAcademica`) y sus `Event::dispatch` / listeners.
  Esta paridad se descubrió después del primer intento y la fijan pruebas e2e.
- **Atómica y sin carreras.** Todo ocurre en una transacción con la fila bloqueada (`for update`): se leen los
  valores, se calculan los cambios, se actualiza (solo las columnas que cambian, más `updated_at`) y se audita. Si algo
  falla no queda nada a medias, y dos ediciones simultáneas no se pisan (la cadena "antes → después" queda coherente).
- **Sin cambios no hay escritura**: si ninguna columna cambia, responde 200 sin tocar la fila ni auditar (como Laravel,
  que no dispara el evento si el modelo no está sucio). Una cadena vacía y `null` son lo mismo (como el middleware
  `ConvertEmptyStringsToNull`).
- **Asignación masiva imposible**: el cuerpo se valida con `zod` en modo estricto; un campo desconocido (`tenant_id`,
  `id`, `user_id`, `deleted_at`, `foto`...) se **rechaza con 400** en vez de ignorarse.
- **Códigos de respuesta**: 404 si el estudiante es de otro colegio, está borrado lógicamente o no existe (no se
  distingue); 409 si la cédula o el número de matrícula ya existen en el colegio (la unicidad es por colegio:
  `(tenant_id, cedula)` y `(tenant_id, numero_matricula)`; el mensaje dice cuál); 403 sin permiso; 401 sin token; 400
  si la validación falla.
- **Validación: las reglas de `UpdateEstudianteRequest`, con tres correcciones deliberadas donde Laravel está desalineado
  con la base de datos** (en PHP esos casos dan un error 500 de la base; aquí dan un 400 claro): `tutor_parentesco` ≤ 50
  (Laravel dice 150), `tutor_trabajo` ≤ 100 (Laravel dice 150) y `nacionalidad` **no admite null** (la columna es NOT NULL;
  Laravel dice `nullable`). `tutor_email` está en las reglas de Laravel pero no existe como columna ni en `$fillable`, así
  que no se acepta.

Patrón para las próximas escrituras: `AuditService.registrar(trx, ...)` dentro de la misma transacción, `TenantContext`
para el tenant/usuario, un esquema `zod` estricto, la tabla de columnas en el orden de la tabla
(`estudiante-columnas.ts`) y la constante `MODELO_ESTUDIANTE` para el nombre de modelo.

## Contrato OpenAPI

`GET /openapi.json` (OpenAPI 3.1, público, apagable con `OPENAPI_ENABLED=false`) describe las rutas, los parámetros, el
cuerpo del `PATCH`, las respuestas y el esquema de seguridad (`bearerAuth` con el token de Sanctum). No se escribe a mano
nada que pueda desfasarse:

- Los esquemas de **respuesta** salen de `src/openapi/respuestas.ts` (`zod`), y cada uno lleva
  `satisfies z.ZodType<Dto>`: si cambia el DTO compartido (el que usan la web y la app móvil) y no el esquema, **no compila**.
- El **cuerpo del `PATCH`** sale del mismo esquema `zod` que valida la petición.
- Prueba unitaria de **cobertura**: recorre las rutas registradas en Nest y exige que cada una esté documentada
  (añadir un endpoint sin documentarlo rompe el CI). Otras pruebas comprueban que los límites del contrato (`perPage` máximo,
  longitudes, enums) son los que aplica la validación de verdad.
- Prueba e2e: las respuestas **reales** del servidor (incluidos los errores 400/401/403/404/409) cumplen los esquemas, y cada
  código de estado observado está declarado en su operación.

Al añadir una ruta: documentarla en `src/openapi/openapi.ts` (el test de cobertura te lo exige).

## Decisiones tomadas (y por qué)

| Decisión | Motivo |
|---|---|
| **NestJS 11**, no 12 | Nest 12 (y Kysely ≥ 0.28.15, que declara `"type": "module"`) se cargan como ESM: desde un proyecto CommonJS el compilador falla (TS1479) y Jest 29 no los puede cargar. Con ESM habría que escribir `.js` en todos los imports y reemplazar Jest. Nest 11 + Kysely 0.28.14 (CommonJS) es el conjunto probado. Pasar a ESM más adelante es un cambio aislado. |
| **TypeScript 5.9**, no 6 ni 7 | `@nestjs/cli` 11 depende exactamente de 5.9.3. TS 7 es muy reciente. Se fija con `overrides` porque npm instalaba otra versión anidada por una dependencia par. |
| **Kysely** (+ `kysely-codegen`), no Prisma | Prisma `latest` apunta a un release candidate y la v7 exige adaptadores de driver. Kysely es SQL tipado, ideal para los reportes con agregados que se optimizaron en PHP, y genera los tipos de las 183 tablas desde la base viva. |
| **Tipos de fecha como `string`** | La conexión usa `dateStrings: true` (igual que PHP, evita desfases de zona horaria). El generador lo refleja (`date/datetime/timestamp → string`) para que los tipos no mientan. |
| **`zod`** para entorno y parámetros | Todo lo que llega de fuera se valida; si el `.env` es inválido la API **no arranca**. |
| **Estados de asistencia tipados** | `asistencias.estado` es `presente/ausente/tarde/excusa/retiro`. En PHP varios módulos comparaban con `'tardanza'/'justificado'` y los contadores daban 0; aquí no compila. |

## Medido (informativo, no es una comparación con Laravel)

`GET /api/v1/estudiantes?perPage=30` sobre `sge_bench` (4.950 estudiantes), API compilada, Windows,
MySQL con buffer pool de 128 MB: **~31 ms por petición** (p95 41 ms) y **~176 req/s con 20 conexiones**.
Con búsqueda por texto y página 40: ~65 ms. No es comparable con la página HTML de Laravel (que además
renderiza vistas); sirve como línea base.

**Con la caché de autenticación:** las consultas por petición bajan de 7 a 2, pero la latencia casi no cambia
(30,3 → 30,0 ms; 214 → 223 req/s con 20 conexiones). Es esperable: las consultas de autenticación eran baratas.
Midiendo por partes, Node + Nest cuestan ~1 ms (`/health`: 0,94 ms) y **todo lo demás son las 2 consultas de
datos** a MySQL: `count(*)` 11,7 ms y el listado ordenado 17,3 ms para solo 4.950 filas. La caché reduce la carga
sobre la base de datos (importante con muchos usuarios), no la latencia de una petición suelta.

El cuello de botella es el índice, no el lenguaje. En `sge_bench` (solo medición, sin aplicar a la base real):

| Consulta | Hoy | Con índice `(tenant_id, deleted_at, apellidos, nombres)` |
|---|---|---|
| Listado, página 1 | 17,0 ms (usa `filesort`) | **0,4 ms** |
| Listado, página 100 | 27,1 ms | 7,8 ms |
| `count(*)` | 11,6 ms | 11,6 ms (el optimizador sigue eligiendo el índice único `(tenant_id, cedula)`) |

Forzando un índice estrecho `(tenant_id, deleted_at)` el `count(*)` baja a 1,5 ms, pero MySQL 8.0.30 no lo elige
solo y Kysely no ofrece hints sin romper el filtro de tenant. Es la misma consulta que usa el listado de Laravel,
así que el índice nuevo también le serviría (requiere una migración nueva de Laravel).

## Pendiente (orden sugerido)

1. **Foto del estudiante** — primero decidir dónde se guardan los archivos (hoy el disco de Laravel; al borrar un estudiante
   Laravel además borra el archivo). Verificado el 2026-10-01: en Laravel el borrado es lógico (`SoftDeletes`) y **ningún código
   restaura estudiantes** (`restore`/`withTrashed` no se usan), así que borrar el archivo allí es coherente; en TypeScript el
   `DELETE` ahora también borra el archivo (si `FOTOS_PUBLICAS_DIR` apunta a `storage/app/public` de Laravel, mismo servidor; con
   una defensa que nunca borra fuera de esa carpeta). La **subida** de fotos sigue en Laravel. **Matrículas — `POST /api/v1/matriculas` HECHO** (2026-10-01: cupo, orden, evento del dashboard y notificaciones; ver
   [`docs/EFECTOS_SECUNDARIOS_PHP.md`](docs/EFECTOS_SECUNDARIOS_PHP.md)); siguen en Laravel la matrícula masiva, el cambio de grupo, el
   cambio de estado y la baja (ojo: `MatriculaObserver` no está registrado).
2. **Más escrituras**, con la misma plantilla (transacción + auditoría + `zod` estricto): asistencia y calificaciones (estas
   respetando `periodos.cerrado`); pagos y MINERD al final, con tests de paridad. **Antes de cada módulo, repetir el
   inventario de [`docs/EFECTOS_SECUNDARIOS_PHP.md`](docs/EFECTOS_SECUNDARIOS_PHP.md)**: los observers, eventos, cachés y
   archivos de PHP no se disparan desde TypeScript y hay que reproducir sus efectos.
3. **Redis** — HECHO a medias: ya existe el cliente (`src/redis/`, opcional con `REDIS_HOST`), `/health` informa
   `checks.redis` (`ok` / `error` → `degraded` / `omitido`) y `LaravelCache` invalida claves de la caché de Laravel con
   sus DOS prefijos (verificado con Laravel real). **Falta**: llamarlo desde cada escritura que en PHP hace `Cache::forget`
   (hoy ninguna lo necesita: estudiantes no toca cachés), (b) emitir eventos en tiempo real (Reverb) y (c) mover ahí la
   caché de autenticación si hay más de una instancia de la API.
4. **Conteo con filtros** — MEDIDO, sin cambios (2026-10-01, `sge_bench`, 4.950 estudiantes): sin filtros 1,6 ms (index-only); con `estado` 20 ms; búsqueda por texto 16 ms; el hint del índice de listado no mejora el caso de `estado` (18 ms) porque esa columna no está en el índice. A esta escala no justifica un índice nuevo; volver a medir si un colegio supera ~50.000 estudiantes.
5. **Autenticación de la web** (hoy la web solo muestra el estado de la API).
6. **Siguiente módulo de lectura** — propuesta: portales de solo lectura (padre/estudiante).
7. **Proxy de enrutamiento** (Nginx) para mandar cada ruta a Laravel o a TypeScript durante la transición.

## Medición de lecturas de Laravel (2026-10-01) — qué migrar y qué no

Perfilado en proceso (PHP 8.3 con OPcache, `DB_DATABASE=sge_bench`, 4.950 estudiantes, 49,5 k notas, 49,5 k pagos, 742 k
asistencias, `CACHE_STORE=array` = siempre en frío), segunda petición de cada ruta:

| Grupo | Rutas | Tiempo | Consultas |
|---|---|---|---|
| Admin (18 de 21) | estudiantes, boletines, calificaciones, asistencia, carnet, gamificación, horarios, becas, nómina, disciplina… | 27–172 ms | 10–21 |
| Portal estudiante | dashboard, boletín, asistencia, horario, comunicados, observaciones | 14–54 ms | 9–31 |
| Portal padre | dashboard, hijo, asistencia, horario, observaciones | 16–56 ms | 16–37 |
| Portal docente | dashboard | 146 ms | 12 |
| **Más lentas** | `admin/dashboard` 501 ms · `admin/pagos` 483 ms · `admin/integraciones/sigerd/validar` 559 ms | | |

- `admin/dashboard`: 413 ms son SQL (agregados sobre notas), pero en uso real va **cacheado 180–300 s**; el 501 ms es solo la
  primera carga.
- `admin/pagos`: 3 agregados sobre 49,5 k pagos (188 + 132 + 88 ms). Probé un índice cubriente
  `(matricula_id, estado, monto, fecha_pago)` y **no mejora** (184 → 190 ms): el coste está en el semi-join con `matriculas`.
  Reescribirlo en TypeScript no lo aceleraría, porque el tiempo es de MySQL.
- `sigerd/validar`: 93 ms de SQL y ~470 ms de PHP (validaciones fila a fila).

**Conclusión:** ninguna ruta de lectura es lenta por culpa de PHP. Las tres lentas están dominadas por SQL (no mejora
con otro lenguaje) o por una validación puntual. **No hay lectura que justifique migrar por rendimiento.** Si se migra
una lectura, que sea por otra razón (p. ej. para que la web nueva consuma un contrato OpenAPI), no por velocidad.
Volver a medir si un colegio supera ~50.000 estudiantes o con concurrencia real (esta medición es de una sola petición).

## Carga concurrente: Laravel vs API TypeScript (2026-10-01)

Mismo trabajo en los dos lados: listar 15 estudiantes ordenados (apellidos, nombres, id) con total, autenticado con el mismo
token Sanctum y el mismo tenant, sobre `sge_bench` (4.950 estudiantes). **Cuerpo idéntico verificado** (`data` igual, 2.106
bytes). **Un solo proceso** de cada lado, generador de carga neutro ([`bench/`](bench/README.md)), 15 s por punto, todo en la
misma máquina de desarrollo (el generador comparte CPU, así que las cifras son conservadoras para ambos).

| Configuración (1 proceso) | 1 conexión | 10 conexiones | 50 conexiones |
|---|---|---|---|
| PHP 8.3 + OPcache, sin cachés de config/rutas | 14 req/s · p50 71 ms | 14 req/s · p50 730 ms | 14 req/s · p50 3.590 ms |
| **PHP 8.3 + OPcache + `config:cache` + `route:cache`** (lo que hace `deploy.sh`) | **36 req/s · p50 26 ms** | **39 req/s · p50 247 ms** | — |
| Node (API TypeScript), caché de auth desactivada | 282 req/s · p50 3,4 ms | 1.194 req/s · p50 8,2 ms | — |
| Node (API TypeScript), caché de auth 30 s (por defecto) | 450 req/s · p50 2,1 ms | 2.200 req/s · p50 4 ms | 1.936 req/s · p50 23 ms |

Comparación honesta (PHP con cachés de producción frente a Node sin la caché de auth, es decir, solo lenguaje y framework):
**≈ 8× con una conexión y ≈ 30× con diez**. La mayor parte de la diferencia a 10 conexiones es que un proceso de PHP atiende de a
una petición y uno de Node intercala la espera de MySQL; en producción PHP corre con varios workers (PHP-FPM), así que la
cifra relevante por núcleo es la de una conexión (≈ 8×). Cero errores y cero respuestas no-2xx en todas las corridas.

**Qué significa a la escala de un colegio.** 1.000 usuarios conectados pidiendo una página cada 10 s son ≈ 100 req/s. Con
PHP cacheado (≈ 36 req/s por worker) eso son 3 workers; con Node, una fracción de un núcleo. Es una diferencia real pero **no
cambia lo que un colegio necesita**: ninguna de las dos opciones es el cuello de botella antes de miles de usuarios
simultáneos, y la latencia de PHP (26 ms) ya es buena. El ahorro sería de costo de servidor, no de experiencia del usuario.

**Lo que esta medición NO cubre**: una sola ruta (listado simple); no hay escrituras ni rutas con renderizado de vistas
(la página HTML de `admin/estudiantes` en PHP pesa 292 KB y dio 10 req/s por proceso, pero compararla con un JSON de 2 KB
no es justo y no se usa como evidencia); no hay PHP-FPM/Apache real con varios workers ni un servidor Linux.

**Diferencias que hay que conocer antes de comparar APIs completas**
- ~~La API TypeScript no tiene limitación de tasa~~ — **resuelto el 2026-10-01**: `RATE_LIMIT_PER_MINUTE` (60 por usuario y
  minuto, como el grupo `api` de Laravel; `RateLimitGuard` + `RateLimiter`, contador compartido por Redis entre instancias o en
  memoria si no hay Redis). Responde `429` con `Retry-After` y `X-RateLimit-*`. No limita por IP las peticiones sin token
  (detrás de un proxy `req.ip` es la del proxy): eso corresponde a Nginx `limit_req`.
- La caché de autenticación de 30 s es la mitad de la ventaja a 1 conexión (450 → 282 req/s sin ella) y a cambio un token
  revocado tarda hasta 30 s en dejar de valer (ver "Caché de autenticación").

**Enrutamiento con Nginx:** configuración probada (límite por IP, ruta exacta por módulo, `Host` del colegio) en
[`deploy/nginx/`](deploy/nginx/README.md). La API escucha solo en loopback por defecto (`API_HOST`).
