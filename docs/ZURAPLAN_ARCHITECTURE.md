# ZuraPlan — Arquitectura (2026-09-05)

Auditoría de solo lectura. El hallazgo más importante de todo el "Prompt
Maestro": **ZuraPlan, tal como se describe en el pedido, ya existe — pero
fragmentado en 3 sistemas paralelos que nunca se unificaron.** Construir un
cuarto sistema nuevo violaría directamente la regla "NO DUPLICAR" del propio
prompt.

## Las 3 líneas reales

### Línea A — Planificación anual (`PlanifAnual` → `PlanifUnidad`)
Migración `2026_05_16_260000_create_planif_anual_tables.php`.
- `PlanifAnual`: docente_id, asignacion_id, school_year_id, titulo, descripcion.
- `PlanifUnidad`: numero, titulo, período, semanas, objetivos, competencias
  (JSON de texto libre — las 6 competencias fundamentales MINERD como
  strings, NO relación real), indicadores (texto libre), contenidos,
  estrategias, recursos, evaluación, fechas.
- **Es literalmente Plan → Unidad → (objetivos/competencias/indicadores/
  contenidos/evaluación)** de la jerarquía pedida en el prompt. Falta el
  nivel "Tema" explícito y la relación real (FK) a `CompetenciaEspecifica`/
  `IndicadorLogro`, que ya existen en el módulo MINERD.

### Línea B — Plan de clase semanal (`PlanClase` → `PlanClaseMomento`)
- `PlanClase`: asignacion_id, docente_id, titulo, área, tipo_plan, semana,
  intención pedagógica, estrategias, archivo adjunto, **`publicado`
  (booleano)** — el patrón borrador/publicado ya existe a nivel de dato.
- `PlanClaseMomento`: tipo (inicio/desarrollo/cierre, con duraciones
  predefinidas), competencias específicas (texto), contenidos, actividades,
  indicador de logro, recursos.
- Es el nivel **Preparación → Actividad** de la jerarquía pedida — el
  formato de "preparación de clase" clásico dominicano (inicio/desarrollo/
  cierre).

### Línea C — Planificación técnico-profesional (`Planificacion` → `PlanificacionRaItem`/`PlanificacionActividad`)
Migración `2026_04_13_000001`. Campos del track vocacional (familia
profesional, código MF, código UC, sesión) — el mismo patrón dual
académica/técnica ya conocido y ya aceptado en Calificaciones
(`CalificacionAcademica` vs. `Calificacion`), aplicado también aquí.
`raItems` = Resultados de Aprendizaje; `actividades` = plan por actividad.
También tiene `publicado` (booleano).

## ZuraPlanificacionAI — el patrón "borrador, el docente aprueba" ya es real

`generarRA()`, `generarActividad()` y `mejorarTexto()` **no persisten
nada** — devuelven JSON/texto crudo al controller, que decide si guardarlo.
Esto es exactamente la arquitectura pedida en el prompt ("la IA NO publica
automáticamente, el docente revisa/modifica/aprueba"). Limitación real: hoy
solo genera contenido para la **Línea C** (técnico-profesional) — no para
`PlanifUnidad` (Línea A) ni `PlanClase`/`PlanClaseMomento` (Línea B).

## Conexión con ZuraClass — confirmado inexistente

Cero referencias cruzadas (`plan_clase_id`, `planif_anual_id`,
`planif_unidad_id`, `planificacion_id`) en ningún modelo de ZuraClass. El
docente planifica en un lado y publica materiales/tareas en ZuraClass por
otro, sin ningún vínculo — exactamente el problema que el prompt pide
evitar.

## Decisión de negocio pendiente (⚠️ requiere decisión, no es tarea técnica)

**Opción 1 — Unificar las 3 líneas bajo una sola marca "ZuraPlan".**
Ventaja: experiencia consistente, un solo lugar donde planificar.
Riesgo: las 3 líneas sirven contextos genuinamente distintos (plan anual de
largo plazo vs. preparación semanal vs. formato técnico-vocacional con
codificación MF/UC) — fusionar podría perder matices específicos de cada
uno o requerir una migración de datos histórica compleja.

**Opción 2 — Mantenerlas como 3 productos deliberadamente distintos**
(igual que la separación académica/técnica en calificaciones, ya aceptada
en este proyecto) **y solo conectarlas a ZuraClass**, sin fusionarlas entre
sí. Menor riesgo, menor esfuerzo, resuelve el problema real más urgente
(planificación aislada de ZuraClass) sin tocar 3 modelos de datos maduros.

**Recomendación de esta auditoría** (no una decisión tomada): Opción 2
primero — conectar cada línea existente a ZuraClass (agregar
`plan_clase_id`/`planif_unidad_id`/`planificacion_id` nullable a los
modelos de tarea/recurso de ZuraClass) es de mucho menor riesgo que
fusionar 3 modelos de datos maduros, y resuelve el 80% del dolor real
descrito en el prompt. La unificación de marca/UI puede evaluarse después,
una vez conectado.

## Arquitectura conceptual para conectar con ZuraClass (si se aprueba Opción 2)

- Agregar columnas nullable `planif_unidad_id`, `plan_clase_id`,
  `planificacion_id` (una sola de las tres, según cuál origen tenga esa
  tarea/material) al modelo de tarea/recurso de ZuraClass que exista hoy.
- Al crear una tarea en ZuraClass desde el contexto de una planificación,
  pre-rellenar competencias/indicadores desde la unidad/momento de origen
  en vez de que el docente los reescriba.
- No requiere fusionar tablas ni migrar datos existentes — es aditivo.
