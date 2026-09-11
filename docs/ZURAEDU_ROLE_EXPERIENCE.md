# Experiencia por Rol — Dashboard Dinámico y Vista "Hoy" (2026-09-05)

Auditoría de solo lectura. Regla fundamental confirmada como ya vigente en
el sistema: **no todos los usuarios ven el mismo dashboard** — ya existe
lógica condicional por rol en `layouts/admin.blade.php` (39 secciones
gateadas por `$isAdmin`/`$isDir`/`$isCoord`/`$isDocente`/etc., ya trabajado
esta sesión) y controladores de portal separados por rol
(`PortalDocenteController`, `PortalPadreController`,
`PortalEstudianteController`). Lo que falta no es "diferenciar por rol"
(ya existe) — es consolidar cada rol en una vista **"Hoy"** con foco en
acción, no en navegación.

## Estado real por rol

### Docente — 🟡 la base más sólida, falta consolidar en acciones
`PortalDocenteController::dashboard()` ya reúne: horario del día,
estadísticas de grupos/asignaturas/estudiantes, comunicados recientes,
notificaciones no leídas, rendimiento por asignación, suplencias próximas.

**Falta**: agregación explícita de pendientes accionables —
"asistencia sin tomar hoy" (hoy la asistencia es una acción separada por
asignación, sin contador agregado) y "entregas/tareas sin calificar" (dato
que existe en ZuraClass pero no se trae al dashboard). La lista de accesos
rápidos pedida en el prompt (Registro, Asistencia, Calificaciones, ZuraPlan,
Tareas, ZuraClass, Mensajes, Horario) ya existe como rutas — falta
presentarlas como acciones directas desde el dashboard en vez de requerir
navegar el menú lateral.

### Director/Administrador — 🟡 los datos existen, mal repartidos
`Admin\DashboardController::index()` es un dashboard de métricas generales
(conteos, horario activo). `KpiController` (recién corregido esta sesión)
ya calcula exactamente lo que una vista "Hoy" necesita: asistencia del día,
notas pendientes por docente, alertas activas. **El problema no es que
falten datos — es que viven en dos páginas distintas** (`/admin/dashboard`
y `/admin/kpis`) en vez de una sola vista consolidada.

### Padre — 🔴 confirmado disperso (auditoría previa de esta sesión)
8+ endpoints separados (boletines, estado de cuenta, pagos, comunicados,
calendario, gamificación) sin una vista "hoy" que los una.

### Estudiante — ⚠️ no auditado en profundidad, no asumir estado
`PortalEstudianteController::dashboard()` existe; su contenido real no se
verificó en esta pasada. Evaluar antes de proponer cambios ahí.

## Diseño conceptual de "Hoy" por rol (no implementado)

No requiere tablas nuevas — es una vista que **agrega datos que ya se
calculan en controladores existentes**, sin duplicar esa lógica:

- **Docente/Hoy**: horario de hoy (ya existe) + contador de asistencia
  pendiente (nueva agregación sobre `Asistencia`, sin tabla nueva) +
  entregas sin calificar (nueva agregación sobre ZuraClass) + mensajes +
  eventos del día (ya existen).
- **Padre/Hoy**: por hijo — entrada/salida de Carnet+ (ya existe el dato en
  `CarnetAcceso`), tareas pendientes, próximo pago, mensajes — todo dato ya
  calculado en otros controladores, solo falta una vista que los junte.
- **Director/Hoy**: fusionar lo que ya calcula `KpiController` dentro del
  dashboard principal, en vez de mantenerlos en páginas separadas.

## Riesgo de NO hacer esto

Sin una vista "Hoy" consolidada, cada rol sigue teniendo que navegar
múltiples páginas para saber "qué necesito hacer ahora" — exactamente lo
que el prompt identifica como el problema central de una app administrativa
tradicional (vs. lo que se busca: un sistema operativo diario del centro).
