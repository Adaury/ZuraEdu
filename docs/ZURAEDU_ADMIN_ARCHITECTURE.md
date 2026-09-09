# Administración Tipo Moodle — Arquitectura (2026-09-05, implementado 2026-09-09)

> **Estado: implementado.** El "Centro de Administración" descrito abajo ya
> no es una recomendación — existe en `admin.centro-administracion`
> (`/admin/configuracion`), commit `101fa63` (versión inicial, 35 tarjetas /
> 6 categorías) completado el 2026-09-09 (101 tarjetas / 13 categorías,
> filtro por módulo activo, dark mode, buscador insensible a acentos). Ver
> `app/Http/Controllers/Admin/CentroAdministracionController.php` para el
> catálogo real y `tests/Feature/CentroAdministracionTest.php` para su
> cobertura. El resto de este documento se conserva como registro del
> diagnóstico original que motivó la implementación.

Auditoría de solo lectura. El pedido es que un centro pueda administrar su
ecosistema completo **sin tocar código** — esto, en gran parte, **ya es
cierto hoy**: el gap no es de backend, es de experiencia consolidada.

## Lo que ya existe y funciona (no duplicar)

| Capacidad pedida | Estado |
|---|---|
| CRUD de usuarios, grados, secciones, asignaturas, docentes, estudiantes | 🟢 completo |
| Roles y permisos (RBAC real a nivel de backend, no solo UI) | 🟢 completo, con granularidad `ver-X`/`gestionar-X` en expansión |
| Búsqueda, filtros, acciones rápidas en listados | 🟢 ya presentes en la mayoría de módulos |
| Importación/exportación masiva | 🟢 ya existe (estudiantes, docentes, calificaciones, SIGERD) |
| Configuración de módulos activos | 🟠 existe pero fragmentado (ver `ZURAEDU_PRODUCT_GAPS.md` — `TenantFeature` vs. `ConfigInstitucional`) |
| Identidad institucional (nombre, logo, colores, contacto) | 🟢 vía `ConfigInstitucional`, ya administrable desde el panel |

## El gap real: no falta funcionalidad, falta un "centro" consolidado

Hoy la administración está correctamente construida pero **repartida**: 39
secciones de menú lateral (ya mejoradas esta sesión con acordeón + búsqueda
visual), más paneles separados para pagos, SIGERD, KPIs, biblioteca,
transporte, etc. Es funcionalmente equivalente a lo que Moodle resuelve con
su "Site Administration" — ZuraEdu tiene las piezas pero no el hub.

**Implementado**: un "Centro de Administración" como página de aterrizaje
que agrupa accesos por categoría con buscador global — reutilizando
exactamente las rutas/permisos que ya existían, sin tocar ningún
controlador de los módulos en sí. Es una capa de navegación sobre lo que ya
funciona, no un reemplazo del sidebar (que se mantiene intacto).

**Catálogo real (2026-09-09)**: 13 categorías, 101 tarjetas — Personas y
Comunidad, Estructura Académica, Docencia y Aula, Evaluación y
Calificaciones, Bienestar y Convivencia, Comunicación, Finanzas, Servicios
y Logística, Reportes y Analítica, Solicitudes y Soporte, Integraciones e
Importación, Configuración del Sistema, Página Web Institucional. El
catálogo completo (tarjeta → ruta → permiso → feature) vive como la única
fuente de verdad en `CentroAdministracionController::CATEGORIAS` — no
duplicar esta lista en otro lugar; para consultarla en código o tests usar
`CentroAdministracionController::catalogo()`.

## Módulos activables — resuelto para el Centro de Administración

Ver detalle completo en `ZURAEDU_PRODUCT_GAPS.md`. `TenantFeature`
(SuperAdmin→tenant, con relación a plan) y `ConfigInstitucional::moduloActivo()`
(self-service del centro) siguen siendo dos fuentes de verdad no
sincronizadas — ese gap de arquitectura **no se resolvió**, se evitó: el
Centro de Administración filtra sus tarjetas usando **exclusivamente**
`TenantFeature`/`Tenant::can()`, porque es lo que realmente bloquea el
acceso a cada ruta (`App\Http\Middleware\CheckTenantFeature`, aplicado en
`routes/web.php:659-717`). `ConfigInstitucional::moduloActivo()` es
puramente cosmético en el sidebar y no bloquea ninguna ruta — usarlo aquí
habría mostrado tarjetas que rebotan u ocultado tarjetas accesibles. El día
que se unifiquen los dos sistemas, el hub hereda la unificación sin
cambios porque solo conoce `Tenant::can()`.

## SuperAdmin — confirmado correctamente separado

SuperAdmin ya administra la plataforma global (tenants, planes,
suscripciones, features, billing, MRR vía `SubscriptionController`/
`BillingController`, ya auditado en el roadmap de producto anterior) en un
panel, rutas y capa de datos completamente distintos al Administrador de
un centro — la separación pedida en el prompt **ya existe**, no requiere
cambios.
