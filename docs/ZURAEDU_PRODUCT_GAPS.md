# ZuraEdu — Matriz de Gaps: Evolución a Plataforma Tipo Moodle + EduPage (2026-09-05)

Auditoría de solo lectura del "Prompt Maestro" de evolución de producto. Cero
código modificado. Clasificación exacta pedida: 🟢 existe · 🟡 existe
parcialmente · 🟠 existe pero necesita mejora · 🔴 no existe · ⚠️ requiere
decisión.

## Multi-tenancy y SuperAdmin (ya auditado extensamente en sesiones previas — resumen, no repetido en detalle)

| Componente | Estado | Evidencia |
|---|---|---|
| Identificación/resolución de tenant | 🟢 | `Tenant.dominio`/`dominio_personalizado`, middleware `ResolveTenant` |
| Filtrado de datos por tenant | 🟢 | Trait `BelongsToTenant`, aislamiento verificado en auditorías previas sin ningún FAIL |
| Provisión de tenant nuevo | 🟢 | `TenantProvisioningService::provision()`, onboarding self-service (`/onboarding`) |
| SuperAdmin separado del admin de centro | 🟢 | Paneles, rutas, layouts y guards completamente distintos, confirmado |
| Roles/permisos (RBAC) | 🟢 | Spatie roles/permissions; patrón `ver-X`/`gestionar-X` granular ya iniciado (commit `a8d8c50`) |
| Permisos a nivel de backend (no solo ocultar botones) | 🟢 | Middleware `can:` en rutas, no solo condicionales de vista — confirmado en múltiples auditorías |

## Módulos activables por centro

| Componente | Estado | Evidencia |
|---|---|---|
| Activación de módulos por tenant | 🟡 **fragmentado en 2 sistemas** | `TenantFeature` (SuperAdmin→tenant, `SubscriptionController::toggleFeature`) vs. `ConfigInstitucional::moduloActivo()` (self-service del propio centro) — **no sincronizados entre sí** |
| Relación plan↔módulos incluidos | 🟡 | `FEATURES_POR_PLAN` sí existe y se aplica al crear/cambiar plan (`activarFeaturesPlan`), pero después de eso los dos sistemas divergen sin control cruzado |
| Riesgo real identificado | ⚠️ | SuperAdmin puede desactivar un `TenantFeature`, pero el centro puede seguir viendo el módulo activo vía `ConfigInstitucional` porque nada los sincroniza — **requiere decisión**: ¿cuál es la fuente de verdad? |

## Portal público institucional (por tenant)

| Componente | Estado | Evidencia |
|---|---|---|
| Página pública distinta por centro | 🔴 | `landing.blade.php` es 100% marketing del SaaS, sin ninguna referencia a `Tenant`/`ConfigInstitucional` |
| Modelo de "Noticia" | 🔴 | No existe como modelo, ninguna migración |
| Modelo de "Galería" pública | 🔴 | No existe |
| "Evento" reutilizable como contenido público | 🟡 | `Evento` existe pero es interno (inscripción de la comunidad ya autenticada), sin campo de visibilidad pública |
| Identificador para URL pública por centro | 🟢 | `Tenant.dominio` ya existe y ya se usa para resolver tenant — reutilizable sin inventar un slug nuevo |

## Constructor visual de bloques/secciones

| Componente | Estado | Evidencia |
|---|---|---|
| Sistema de bloques/secciones configurables | 🔴 | Cero coincidencias de "Bloque"/"SeccionPagina"/"PageBuilder"/"Widget" en todo el código — funcionalidad completamente nueva |
| Arquitectura mínima viable (diseño, no implementado) | ⚠️ | Ver `ZURAEDU_TENANT_SITE_ARCHITECTURE.md` — 1 tabla nueva (`pagina_secciones`), sin duplicar `ConfigInstitucional` |

## Dashboard dinámico "Hoy" por rol

| Rol | Estado | Evidencia |
|---|---|---|
| Docente | 🟡 | `PortalDocenteController::dashboard()` ya reúne horario del día, comunicados, notificaciones, rendimiento, suplencias — falta agregación explícita de "asistencia pendiente hoy" y "tareas por calificar" |
| Director/Administrador | 🟡 | Los datos de "Hoy" ya existen (`KpiController`) pero viven en una página separada del dashboard principal (`Admin\DashboardController`) — no unificados |
| Padre | 🔴 | Confirmado en auditoría previa: disperso en 8+ endpoints, sin vista unificada |
| Estudiante | ⚠️ | No auditado en profundidad esta vez — pendiente de revisión antes de asumir estado |

## ZuraPlan (planificación docente)

| Componente | Estado | Evidencia |
|---|---|---|
| Jerarquía Plan→Unidad→Tema→... | 🟡 **fragmentado en 3 sistemas paralelos** | Línea A `PlanifAnual`/`PlanifUnidad` (anual), Línea B `PlanClase`/`PlanClaseMomento` (semanal, inicio/desarrollo/cierre), Línea C `Planificacion`/`PlanificacionRaItem` (técnico-profesional) |
| Relación con competencias/indicadores MINERD reales | 🟠 | Existen como texto libre en las 3 líneas, no como FK a `CompetenciaEspecifica`/`IndicadorLogro` ya existentes |
| IA generativa con patrón "borrador, el docente aprueba" | 🟢 | `ZuraPlanificacionAI::generarRA/generarActividad/mejorarTexto` no persisten nada, solo retornan JSON — el patrón pedido ya es la arquitectura real (aunque solo cubre la Línea C hoy) |
| Conexión planificación ↔ ZuraClass | 🔴 | Cero referencias cruzadas — confirmado, son sistemas totalmente desconectados |
| Duplicar/compartir plan entre docentes o períodos | 🔴 | No existe en ninguna de las 3 líneas |
| Decisión pendiente | ⚠️ | ¿Unificar las 3 líneas bajo una sola marca "ZuraPlan", o formalizar que son 3 productos deliberadamente distintos (como ya ocurre con calificaciones académica/técnica) y solo conectarlos a ZuraClass sin fusionarlos? |

## Administración tipo Moodle (centro de administración unificado)

| Componente | Estado | Evidencia |
|---|---|---|
| CRUD de usuarios/grados/secciones/asignaturas/docentes/estudiantes/roles/permisos | 🟢 | Todo existe y funciona (confirmado extensamente en auditorías previas de esta sesión) |
| Búsqueda/filtros/acciones rápidas | 🟢 | Ya presentes en la mayoría de listados admin |
| Importación/exportación masiva | 🟢 | Ya existe para estudiantes/docentes/calificaciones/SIGERD |
| Experiencia unificada "un solo centro de administración" (vs. 39 secciones de menú) | 🟠 | Existe pero disperso — es exactamente lo que se acaba de mejorar parcialmente esta sesión con el menú colapsable por secciones; falta una vista de administración más consolidada tipo Moodle (un hub, no solo un menú lateral mejor organizado) |
| Configuración de módulos desde UI simple | 🟠 | Existe (`SubscriptionController` para SuperAdmin, `configIndex` de pagos para el centro) pero repartida, no en un solo lugar |

## No construir sin antes decidir (⚠️ marcados explícitamente arriba)

1. Unificación de `TenantFeature` vs `ConfigInstitucional::moduloActivo`.
2. Unificar o mantener separadas las 3 líneas de planificación.
3. Alcance real del constructor visual (¿bloques predefinidos configurables, o edición libre tipo builder completo?) — el prompt pide "analizar si es viable", no construirlo todavía.
