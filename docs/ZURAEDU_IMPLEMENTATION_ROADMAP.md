# ZuraEdu — Roadmap de Implementación (2026-09-05)

El prompt sugirió un orden de fases (Experiencia → Administración → ZuraPlan
→ Integración → Portal público → Configuración → Mobile → IA) pero pidió
explícitamente **no asumirlo** y determinar el orden real a partir de la
auditoría y las dependencias encontradas. El orden aquí difiere del sugerido
en dos puntos, justificados abajo.

## Fases (orden determinado por dependencias reales, no por la sugerencia inicial)

### FASE 0 — Unificar la fuente de verdad de módulos activables
*(No estaba en el orden sugerido — se agrega porque varias fases
posteriores dependen de ella.)*
Decidir y resolver la relación entre `TenantFeature` (SuperAdmin) y
`ConfigInstitucional::moduloActivo()` (self-service del centro). Sin esto,
tanto el "centro de administración" (Fase 3) como el "portal público"
(Fase 4, que necesitaría saber qué módulos mostrar) heredarían la misma
inconsistencia.

### FASE 1 — Experiencia y navegación (vista "Hoy" por rol)
Coincide con la sugerencia del prompt. Es la de menor riesgo y mayor
impacto inmediato: los datos ya existen en los controladores actuales, solo
falta consolidarlos. No depende de la Fase 0.

### FASE 2 — ZuraPlan: decisión + conexión con ZuraClass
Primero la decisión de negocio (unificar las 3 líneas o solo conectarlas —
ver `ZURAPLAN_ARCHITECTURE.md`), después la conexión técnica con ZuraClass.
Se adelanta respecto al orden sugerido porque es el gap más citado
explícitamente en el prompt ("NO mantener planificación y ZuraClass
separados") y es independiente de la Fase 0.

### FASE 3 — Centro de administración tipo Moodle
Reorganización de navegación sobre lo que ya existe (no nuevo backend).
Se beneficia de tener la Fase 0 resuelta (una sola fuente de verdad de
módulos que mostrar en el centro de administración).

### FASE 4 — Portal público + constructor visual de bloques
La pieza genuinamente nueva de todo el roadmap (dos tablas nuevas, rutas
públicas nuevas). Depende de la Fase 0 (qué módulos/secciones mostrar) y se
beneficia de la Fase 3 (el centro de administración es el lugar natural
para "administrar mi página").

### FASE 5 — Configuración avanzada por tenant
Branding fino, plantillas de comunicación, notificaciones configurables —
extensión natural de la Fase 4, mismo tipo de trabajo (configuración
self-service).

### FASE 6 — Mobile
Extender la vista "Hoy" (Fase 1) y la conexión ZuraPlan↔ZuraClass (Fase 2)
a la app móvil ya existente (79 pantallas, API amplia ya confirmada en
auditorías previas) — tiene más sentido una vez que la versión web ya
resolvió el diseño de esas dos piezas.

### FASE 7 — IA integrada de punta a punta
Expandir `ZuraPlanificacionAI` (hoy solo cubre la Línea C técnica) a las
líneas de planificación restantes una vez resuelta la Fase 2, y evaluar IA
para redactar contenido del portal público (Fase 4) una vez que ese
constructor exista. Al final porque depende del resultado de dos fases
anteriores.

## Diferencias respecto al orden sugerido originalmente, y por qué

| Cambio | Razón |
|---|---|
| Se agrega Fase 0 (unificación de módulos), no sugerida | Sin una sola fuente de verdad, el "centro de administración" y el "portal público" heredarían una inconsistencia ya identificada |
| ZuraPlan (antes Fase 3) sube a Fase 2, antes que "Administración del centro" | Es el gap más citado explícitamente en el prompt del usuario, es independiente de la Fase 0, y no depende de la reorganización de navegación de Fase 3 |

---

# ¿EN QUÉ DEBE CONVERTIRSE ZuraEdu?

**En el sistema donde cada centro educativo vive su operación diaria completa —no solo la administra— porque las piezas que ya construyó (planificación, ZuraClass, Carnet+, finanzas, comunicación) por fin están conectadas entre sí en vez de ser módulos independientes.**

## TOP 10 CAMBIOS

**Actualización 2026-09-11**: al retomar este roadmap para empezar por el
punto 2 se descubrió que 9 de los 10 ítems ya se habían implementado entre
el 2026-09-06 y el 2026-09-10 (antes de esta sesión), sin que este
documento se actualizara. Verificado contra `git log` y el código real,
no solo contra los mensajes de commit.

1. ~~Unificar `TenantFeature` y `ConfigInstitucional::moduloActivo()`.~~ **HECHO** — commit `41c2213` (Fase 0): `CheckTenantFeature::handle()` exige ambos ahora.
2. ~~Vista "Hoy" consolidada para Docente.~~ **HECHO** — commit `94de8bd` (web) + `9e01165` (mobile). Verificado en `PortalDocenteController::dashboard()` líneas 121-169 y `dashboard.blade.php`.
3. ~~Fusionar `KpiController` dentro del dashboard principal.~~ **HECHO** — commit `040ed6d`.
4. ~~Decidir el futuro de las 3 líneas de planificación.~~ **HECHO** — decisión aprobada 2026-09-07 (PlanClase↔PlanifUnidad/Planificacion, referencia opcional) + commit `23452fa`.
5. ~~Conectar planificación con ZuraClass.~~ **HECHO** — commit `452cd45` ("conexión de solo lectura Planificación → ZuraClass").
6. ~~Vista "Hoy" para Padre.~~ **HECHO** — commit `e000eb3` (web, Carnet+/tareas/próximo pago) + `9e01165` (mobile).
7. ~~Portal público por centro.~~ **HECHO** — commits `6f0e9bb`, `f3caf92`, `a9a1cf0` y varios fixes posteriores (carrusel, homepage por dominio, etc.).
8. ~~Constructor visual de bloques del portal público.~~ **HECHO** — commit `d5d350e` (Fase 2).
9. ~~Centro de administración unificado tipo Moodle.~~ **HECHO** — commit `101fa63` + `b4c17e3` (completar cobertura/módulos/dark mode/buscador).
10. **PENDIENTE (único ítem real que queda)**: extender `ZuraPlanificacionAI` a las líneas de planificación no técnicas. Verificado en código: `PlanificacionAIController::generarRA()`/`generarActividad()` siguen usando vocabulario exclusivamente técnico (`familia_profesional`, `ra_codigo`, `modulo`) — no hay equivalente para `PlanifAnual`/`PlanifUnidad` (académica).

## PRIMER DESARROLLO RECOMENDADO

**CERRADO/OBSOLETO (2026-09-11)**: la vista "Hoy" para Docente (punto 2)
ya estaba implementada antes de esta sesión, en web y mobile. No requirió
ningún desarrollo nuevo.

Con 9 de los 10 puntos de este roadmap ya cerrados, el único desarrollo
real pendiente de esta lista es el **punto 10: extender
`ZuraPlanificacionAI` a las líneas de planificación no técnicas**
(`PlanifAnual`/`PlanifUnidad`, área académica) — hoy el servicio de IA
(`generarRA`, `generarActividad`, `mejorarTexto`) solo sirve al área
técnico-vocacional.

### Reconciliación con el otro roadmap (docs/ROADMAP_PRODUCTO_ZURAEDU_2026_2027.md)
El roadmap de auditoría de gaps (misma sesión) había identificado como
ítem de mayor riesgo los tests del algoritmo MINERD de promoción
(`RegistroAcademicoService::calcularPromocion`). Al retomarlo (2026-09-11)
se descubrió que ya existían (`tests/Feature/RegistroAcademicoServicePromocionTest.php`,
commit `af24ee1`) y pasan 12/12 contra el código actual — ese ítem también
quedó cerrado sin trabajo adicional.

### Por qué
Es el cambio de menor riesgo y mayor impacto diario de los 10: no requiere
ninguna decisión de negocio previa (a diferencia de ZuraPlan o los
módulos), no toca ninguna tabla existente, y el docente es el rol que más
tiempo pasa en el sistema día a día.

### Qué existe
`PortalDocenteController::dashboard()` (línea 40 en adelante) ya calcula y
muestra: horario del día (`cargarHorario()`), estadísticas de
grupos/asignaturas/estudiantes, comunicados recientes, notificaciones no
leídas, rendimiento por asignación, suplencias próximas.

### Qué falta
Dos agregaciones nuevas sobre datos que ya existen en otras tablas:
"asistencia sin tomar hoy" (contar asignaciones del docente sin registro de
`Asistencia` en la fecha de hoy) y "entregas sin calificar" (contar
entregas de ZuraClass pendientes de nota). Ninguna requiere tabla nueva —
son consultas de agregación sobre `Asistencia` y el modelo de entregas de
ZuraClass ya existentes.

### Archivos que se verían afectados
- `app/Http/Controllers/Portal/PortalDocenteController.php` (método
  `dashboard()` — agregar las dos consultas nuevas al array de datos que
  ya arma).
- La vista Blade del dashboard docente (agregar la sección "Hoy" con
  accesos rápidos, reutilizando los mismos datos).
- Ningún archivo de rutas, ninguna migración, ningún modelo nuevo.

### Dependencias
Ninguna — es completamente independiente del resto del roadmap.

### Riesgos
Bajo. El único cuidado real es de rendimiento: la consulta de "entregas sin
calificar" debe filtrarse correctamente por tenant y por las asignaciones
reales del docente (no un conteo global) para no introducir una consulta
lenta en la carga del dashboard — mismo patrón de cuidado ya aplicado en
`KpiController` esta sesión.

### Resultado esperado
El docente abre su dashboard y ve, sin navegar a ningún otro menú, si tiene
asistencia pendiente de tomar y entregas pendientes de calificar hoy —
el primer paso concreto hacia "Hoy" como principio de UX en todo el sistema.

---

# REGLA FINAL CUMPLIDA

No se modificó código, no se creó ninguna migración, no se instaló ninguna
dependencia, no se hizo ningún commit ni push durante esta auditoría. La
implementación, cuando se apruebe, será una funcionalidad a la vez,
empezando por la recomendada arriba si el usuario la aprueba.
