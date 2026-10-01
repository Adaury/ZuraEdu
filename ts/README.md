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
| `npm run test:e2e -w @zuraedu/api` | E2E contra MySQL real (`TEST_DATABASE_URL`, por defecto `sge_bench`) |
| `npm run db:types` | Regenera `apps/api/src/db/db.d.ts` desde el esquema real |
| `npm run db:types:check -w @zuraedu/api` | Falla si `db.d.ts` quedó desfasado del esquema |
| `npm run build` | Compila todo para producción |

Después de **cada migración de Laravel** ejecuta `npm run db:types` y revisa el diff de `db.d.ts`:
así el compilador te avisa de cualquier cambio de esquema que rompa la API.

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

Cada petición hace hoy ~8 consultas, la mayoría de **autenticación y permisos sin caché**. Es la primera
optimización pendiente (ver abajo).

## Pendiente (orden sugerido)

1. **Caché de permisos y de validación de token** (TTL corto) — es el grueso de las consultas por petición.
2. **Escrituras** con auditoría (`ActivityLog`) y respeto de `periodos.cerrado`, antes de migrar calificaciones.
3. **Redis**: `/health` solo comprueba la base; añadir cuando haya colas en TypeScript.
4. **Autenticación de la web** (hoy la web solo muestra el estado de la API).
5. **E2E en CI**: necesita una base con el esquema de Laravel; hoy solo corren typecheck, unitarias y build.
6. **Siguiente módulo** — propuesta: portales de solo lectura (padre/estudiante), luego asistencia y
   calificaciones, y al final pagos y MINERD (tests de paridad contra Laravel primero).
7. **Proxy de enrutamiento** (Nginx) para mandar cada ruta a Laravel o a TypeScript durante la transición.
8. **Documentación OpenAPI** de la API (hoy el contrato vive en `@zuraedu/shared`).
