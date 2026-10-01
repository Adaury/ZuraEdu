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

Primera escritura migrada: `PATCH /api/v1/estudiantes/:id` (edición parcial de los campos de identidad: `cedula`,
`nombres`, `apellidos`, `fechaNacimiento`, `estado`). Requiere `gestionar-estudiantes`, igual que las rutas de
mutación de Laravel.

- **Auditoría idéntica a Laravel.** Escribe en `activity_logs` con `accion = estudiante.editado`,
  `modelo = App\Models\Estudiante` y la descripción `Estudiante #12: cedula: — → 001 | estado: activo → inactivo`
  (mismo orden de campos, `—` para nulos, fechas como `2012-05-01 00:00:00` porque Laravel castea a `date`).
  La pantalla de auditoría actual muestra juntos los cambios hechos desde PHP y desde TypeScript.
  `tenant_id` y `user_id` salen del token, nunca del cuerpo.
- **Dos registros por edición, como Laravel.** Cada edición escribe, en este orden, `estudiante.actualizado`
  (lo genera `EstudianteObserver::updated()`: `Estudiante actualizado: Apellidos, Nombres | Campos: nombres, estado, updated_at`)
  y `estudiante.editado` (lo escribe el controlador). **Lección general de la migración:** los *observers*, eventos y
  listeners de PHP **no se disparan** cuando la escritura la hace otro lenguaje; si no se reproducen a mano, las
  pantallas e informes que dependen de ellos dejan de ver esos cambios. Antes de migrar la escritura de cada módulo hay
  que inventariar sus observers (hoy: `Estudiante`, `Matricula`, `Calificacion`, `CalificacionAcademica`) y sus
  `Event::dispatch` / listeners. Esta paridad se descubrió después del primer intento y la fija una prueba e2e.
- **Atómica y sin carreras.** Todo ocurre en una transacción con la fila bloqueada (`for update`): se leen los
  valores, se calculan los cambios, se actualiza y se audita. Si algo falla no queda nada a medias, y dos ediciones
  simultáneas no se pisan (la cadena de auditoría "antes → después" queda coherente; hay pruebas e2e de ambas cosas).
- **Sin cambios no hay escritura**: si ningún campo auditado cambia, responde 200 sin tocar la fila ni auditar (como Laravel).
- **Asignación masiva imposible**: el cuerpo se valida con `zod` en modo estricto; un campo desconocido
  (`tenant_id`, `id`, `deleted_at`...) se **rechaza con 400** en vez de ignorarse.
- **Códigos de respuesta**: 404 si el estudiante es de otro colegio, está borrado lógicamente o no existe (no se
  distingue); 409 si la cédula ya existe en el colegio (la unicidad es `(tenant_id, cedula)`, así que la misma
  cédula en otro colegio es válida); 403 sin permiso; 401 sin token; 400 si la validación falla.
- Las mismas reglas de validación de `UpdateEstudianteRequest`: nombres/apellidos 2–100, cédula ≤ 20, fecha anterior a hoy.

Patrón para las próximas escrituras: `AuditService.registrar(trx, ...)` dentro de la misma transacción, `TenantContext`
para el tenant/usuario, y un esquema `zod` estricto.

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

1. **Más escrituras**, con la misma plantilla (transacción + auditoría + `zod` estricto): resto de campos del estudiante,
   luego asistencia y calificaciones (estas respetando `periodos.cerrado`). Pagos y MINERD al final, con tests de paridad.
   **Antes de cada módulo, inventariar sus observers/eventos/listeners de PHP** (ver "Escrituras y auditoría"): no se
   disparan desde TypeScript y hay que reproducir sus efectos (auditoría, notificaciones, recálculos).
2. **Conteo con filtros** (búsqueda por texto / estado): sin filtros ya es index-only (1,6 ms); con filtros el optimizador decide.
3. **Redis**: `/health` solo comprueba la base; añadir cuando haya colas en TypeScript. Mover ahí la caché de autenticación
   si hay más de una instancia de la API.
4. **Autenticación de la web** (hoy la web solo muestra el estado de la API).
5. **Siguiente módulo de lectura** — propuesta: portales de solo lectura (padre/estudiante).
6. **Proxy de enrutamiento** (Nginx) para mandar cada ruta a Laravel o a TypeScript durante la transición.
7. **Documentación OpenAPI** de la API (hoy el contrato vive en `@zuraedu/shared`).
