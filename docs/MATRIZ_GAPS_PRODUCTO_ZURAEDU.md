# Matriz de Gaps de Producto — ZuraEdu (2026-09-05)

Auditoría de solo lectura (cero código modificado) pedida explícitamente para
construir el roadmap 2026-2027. Cubre Fase 1 (inventario), Fase 2 (los 10
gaps de la evaluación previa, re-auditados con evidencia de código real) y
Fase 3 (módulos adicionales no cubiertos antes). Ejecutada en 4 líneas de
revisión (3 forks + trabajo directo del coordinador leyendo el código).

**Regla aplicada en todo el documento**: nada se clasificó por impresión.
Cada fila cita archivo real. Cuando una evaluación anterior de esta sesión
se equivocó, se corrige explícitamente aquí — no se repite el error.

Estados: 🟢 COMPLETO · 🟡 FUNCIONAL/MEJORABLE · 🟠 PARCIAL · 🔴 AUSENTE ·
⚠️ DEFECTUOSO · 🔵 DIFERENCIADOR.

---

## FASE 1 — Inventario de módulos ya auditados esta sesión

(Detalle completo en la evaluación de producto previa — artifact
`b38545c9-1f67-4c86-9206-9ab7433330fd`. Resumen aquí, sin repetir el
detalle completo.)

| Módulo | Estado | Nota |
|---|---|---|
| Estructura académica / horarios | 🟢 | Generador con backtracking + validación + integridad |
| Calificaciones (académica/técnica) | 🟡 | Dos modelos de datos reconciliados vía `PromedioEstudianteService` |
| MINERD/SIGERD (registro, boletines, exportaciones) | 🟢 | Módulo más maduro del sistema |
| Portales Padre/Docente/Estudiante | 🟡 | Completos pero dispersos en 8+ endpoints |
| Carnet+ (core: QR, kiosco, risk score) | 🟢 | Ver corrección en GAP 03 abajo |
| ZuraClass | 🟢 | Tareas, rúbricas, quizzes, recursos |
| Gamificación | 🟡 | Puntos/insignias básicos, sin rachas/leaderboard |
| App móvil | 🟡 | 79 pantallas, cobertura amplia — ver punto 6 de Fase 3 |
| Pagos/Finanzas (core) | 🟡 | Ver GAP 01 y GAP 05 |
| Biblioteca | 🟡 | Sin multas ni reservas |
| Cafetería | 🟢 | Prepago real funcionando |
| Transporte (gestión de rutas) | 🟡 | Ver corrección en GAP 06 |
| SuperAdmin / Billing SaaS / Onboarding | 🟡 | Más maduro de lo esperado — ver GAP 10 |
| ZuraAI | 🟡 | Ver GAP 07 |

---

## FASE 2 — Los 10 gaps, re-auditados

### GAP 01 — NCF / comprobante fiscal

**Estado: 🟡 PARCIAL** (cambió de 🔴 a 🟡 en esta misma sesión: se implementó
un MVP, commit `e177ff1`, ANTES de este roadmap).

| Pregunta del prompt | Respuesta con evidencia |
|---|---|
| ¿Existe configuración fiscal? | **No.** Cero campo `rnc` en `Tenant`, `ConfigInstitucional` ni ningún seeder — confirmado por grep exhaustivo. |
| ¿Existe RNC? | **No**, ni del centro educativo ni de los padres/pagadores. |
| ¿Existe generación de comprobantes? | **No.** ZuraEdu no genera NCF/e-CF — decisión de alcance explícita (ver `docs/GATE_PRODUCCION...` no, ver commit `e177ff1`: requiere software homologado DGII, Ley 32-23). |
| ¿Existe numeración? | **No hay numeración propia.** Solo un campo de texto libre (`pagos.numero_comprobante_fiscal`) donde el centro pega el número que ya obtuvo por su cuenta. |
| ¿Existe algún concepto relacionado? | Sí, el campo mencionado (nuevo, MVP). También existe `referencia` (preexistente) para referencia de transacción/pasarela — concepto distinto, no confundir. |
| ¿Existe PDF? | Sí — `resources/views/admin/pagos/recibo_pdf.blade.php` ya muestra el NCF/e-CF si está presente. |
| ¿Existe historial? | Solo implícito (aparece en el listado/Excel de pagos). No hay una vista dedicada "historial de comprobantes fiscales". |
| ¿Existe anulación? | **No.** No hay concepto de nota de crédito/anulación de comprobante. |
| ¿Existe auditoría? | **No.** `Pago` no tiene Observer (confirmado en Fase 3, punto 5) — un cambio al campo NCF no queda registrado en `ActivityLog`. |
| ¿Soporte multi-tenant? | Sí — `Pago` ya usa `BelongsToTenant`, el campo hereda ese aislamiento automáticamente. |
| ¿Integración con proveedor fiscal? | **No.** |
| ¿Integración DGII? | **No**, y no es viable sin que el centro contrate un proveedor certificado o use el Facturador Gratuito de la DGII (portal sin API pública confirmada). |

**Requisito confirmado**: la Ley 32-23 exige e-CF de forma escalonada
(fuente: búsqueda web realizada en esta sesión, ver Benchmark). **Plazo
para pequeños/micro contribuyentes: 15/11/2026 — REQUIERE VALIDACIÓN**
confirmar con el/la contador(a) del centro qué categoría de contribuyente
aplica a cada tenant real, porque cambia la urgencia.

**Recomendación** (no requisito confirmado): evaluar en Fase 1 del roadmap
si construir una integración real con UN proveedor certificado específico
tiene sentido de negocio — eso es una decisión comercial, no técnica, y
debe tomarse antes de invertir esfuerzo de ingeniería ahí.

---

### GAP 02 — Algoritmo MINERD de promoción

**Ubicación real**: `app/Services/RegistroAcademicoService.php:290-341`,
método `calcularPromocion()`. Leído completo — no se modificó nada.

**Lo que el algoritmo realmente hace** (más matizado que la descripción de
la evaluación anterior, que decía "promedio≥65 y asistencia≥75%" sin
distinguir ciclo):

- **Primer ciclo**: usa la **escala cualitativa 1-4** (no la de 100 puntos)
  — aprueba si `promedio_general >= 2.5` Y (asistencia≥75% O asistencia
  es `null`).
- **Segundo ciclo**: aprueba si `promedio_final >= 65` Y (asistencia≥75% O
  `null`). Si no aprueba pero tiene ≤2 materias reprobadas → `condicionado`.
  Si no aprueba y tiene 3+ reprobadas → `no_promovido`.
- Si `promedio_final` es `null` (sin notas aún) → estado `pendiente`.

**Edge case real detectado, no cubierto en la descripción previa**:
`$pctAsistencia === null || $pctAsistencia >= 75` — si la asistencia es
`null` (nunca se calculó), el estudiante **pasa el requisito de asistencia
por defecto**. Esto es una decisión de diseño existente, no un bug — pero
es exactamente el tipo de caso límite que un test debe fijar explícitamente
para que no cambie sin querer en un refactor futuro.

**Tests existentes: 0** (confirmado). El algoritmo simple no-oficial
(`CierreAnoController::ejecutar`) sí tiene 9 tests, pero es un algoritmo
distinto que no aplica la regla MINERD real.

**Plan de pruebas propuesto** (sin tocar el algoritmo):

1. Primer ciclo — promedio exactamente 2.5 → promovido (límite inclusive).
2. Primer ciclo — promedio 2.49 → no_promovido.
3. Primer ciclo — asistencia null → no bloquea la promoción.
4. Primer ciclo — asistencia 74% con promedio suficiente → no_promovido.
5. Segundo ciclo — promedio exactamente 65 y asistencia exactamente 75% →
   promovido (ambos límites inclusive).
6. Segundo ciclo — promedio 64, 1 materia reprobada → condicionado.
7. Segundo ciclo — promedio 64, 3 materias reprobadas → no_promovido.
8. Segundo ciclo — promedio suficiente pero asistencia 70% y 1 reprobada →
   confirmar si da `condicionado` o `no_promovido` (el código sugiere
   `condicionado` porque solo mira `numReprobadas`, no la causa del fallo
   — **fijar este comportamiento con un test explícito**, es el caso más
   sutil de todo el método).
9. `promedio_final` null → estado `pendiente`, sin excepción.
10. Persistencia: `updateOrCreate` no duplica filas en `Promocion` al
    llamarse dos veces para la misma matrícula/año.
11. `materias_reprobadas_detalle` contiene los nombres correctos de
    asignatura cuando hay 0, 1 y 3+ reprobadas.

**Riesgo si no se cierra**: decide si un estudiante real repite el año.
Es la única lógica de negocio de alto impacto en todo el sistema (según
esta auditoría completa) sin ninguna red de seguridad.

---

### GAP 03 — Carnet+ + Notificaciones a representantes

**Estado: 🟡 PARCIAL — corrección importante a la evaluación anterior**,
que asumió "el dato ya existe, solo falta un listener". Eso es inexacto.

- `CarnetCheckinController::scan()` (kiosco admin) **ya dispara**
  `NotificarPadreAccesoJob`, que **ya envía notificación in-app** al
  representante (`app/Jobs/NotificarPadreAccesoJob.php`).
- Lo que falta de verdad: ese job **no llama a `WhatsAppService::send()`**
  — el canal WhatsApp, ya consolidado en pagos/SIGERD/calificaciones
  (`PagoController.php:802`, `SigerdController.php:148`,
  `CalificacionController.php:451`), nunca se extendió a Carnet+.
- **Bug real, no solo gap de feature**: `Api\CarnetApiController::scan()`
  (el flujo usado por la app móvil/kiosco vía API) **no dispara el job en
  absoluto** — inconsistencia entre el flujo admin-web y el flujo API.

**Arquitectura recomendada**: extender `NotificarPadreAccesoJob::handle()`
con una llamada a `WhatsAppService::send()` (mismo patrón que pagos) +
corregir `CarnetApiController::scan()` para que también despache el job.
**No crear ningún Event/Listener nuevo** — ya existe la pieza correcta,
solo está incompleta.

---

### GAP 04 — Reingreso

**Estado: 🔴 AUSENTE, confirmado** (0 referencias en el código, ya
verificado en la evaluación anterior y no contradicho aquí). Es
explícitamente una decisión de negocio pendiente, no una tarea técnica:
falta definir qué distingue un reingreso de una matrícula nueva (¿tiempo
fuera del sistema? ¿motivo de salida? ¿mismo número de matrícula?).

---

### GAP 05 — Dashboard financiero consolidado

**Estado: 🟡 FUNCIONAL/MEJORABLE.** `PagoController::dashboard()`
(`app/Http/Controllers/Admin/PagoController.php:24-74`) ya calcula:
total pagado/pendiente/vencido del año activo, recaudación mensual
(últimos 8 meses), top 6 deudores, últimos 6 pagos.

**MVP vs. fase avanzada**:

| MVP (ya existe) | Fase avanzada (no existe) |
|---|---|
| Totales por estado | Ingresos por concepto/grado/sección |
| Recaudación mensual (8 meses) | Proyección de cobranza |
| Top deudores | Comparación año-contra-año |
| Últimos pagos | Flujo de caja real (con egresos, no solo ingresos) |

No agregar más métricas de las de la columna derecha sin que Dirección
confirme que las usaría — el riesgo de esta mejora es construir un
dashboard que nadie mira por exceso de datos.

---

### GAP 06 — Carnet+ + Transporte escolar

**Estado: 🟡 en gestión de rutas / 🔴 en integración con Carnet+ —
corrección a la evaluación anterior**, que afirmó que no existía
conductor/vehículo. Sí existen (`RutaTransporte`: campos `conductor`,
`telefono_conductor`, `vehiculo`) aunque como texto simple, sin
vencimientos de licencia/seguro ni entidades separadas.

Confirmado ausente: cero relación entre `CarnetAcceso` y
`RutaTransporte`/`EstudianteRuta`.

**Arquitectura recomendada**: nuevos valores de `tipo_evento` en
`CarnetAcceso` (`bus_subida`/`bus_bajada`), validar que el estudiante esté
asignado a esa ruta antes de crear el registro, reutilizar
`NotificarPadreAccesoJob` con mensaje distinto. **No requiere GPS ni
tracking en vivo** — es control de abordaje puntual, coherente con cómo
ya funciona Carnet+ (check-in, no tracking continuo).

---

### GAP 07 — Unificar ZuraAI

**Estado: 🟡 fragmentado, pero mejor cubierto de lo que parece.**
4 puntos de entrada confirmados: `Admin/ChatController`,
`Api/TutorIaApiController`, `Portal/AsistenteIAController` (4 rutas:
estudiante/padre/docente/admin), `Services/ZuraPlanificacionAI`.

**Corrección importante**: los 4 puntos de entrada **sí tienen rate
limiting consistente** (`throttle:30,1` en las 4 rutas de chat,
confirmado en `routes/web.php` y `routes/api.php`) — el problema NO es
falta de protección contra abuso, es:
1. Código de integración con Gemini duplicado 4 veces (mantenimiento).
2. **Sin medición de uso/costo por tenant** — imposible saber cuánto gasta
   cada centro en la API de Gemini, lo que bloquea vender un plan "IA
   ilimitada vs. básica" con confianza en el margen.

---

### GAP 08 — Reserva de citas padre-docente

**Estado: 🟠 PARCIAL — corrección a la evaluación anterior**, que lo
marcó como ausente. `SolicitudRepresentante::TIPOS` ya incluye
`'cita_docente'` con flujo completo: el padre crea la solicitud
(`PortalPadreController`), el staff aprueba/rechaza con texto
(`SolicitudesAdminController::responder()`), se notifica al representante.

**Lo que falta de verdad** (esto sí es el gap real): no es una agenda de
citas — no hay selección de un docente destinatario (`docente_id`), no
hay cruce contra disponibilidad real (`HorarioDetalle`), no hay slots de
tiempo con duración, solo una fecha + texto libre.

**Nota de nombres engañosos**: existe un `AgendaDocenteController` — NO es
una agenda de citas, es el CRUD de tareas de ZuraClass. No confundir al
planificar.

**Arquitectura recomendada**: extender `SolicitudRepresentante` (agregar
`docente_id` nullable) en vez de crear un modelo paralelo. Derivar
disponibilidad de los huecos en `HorarioDetalle` del docente en vez de
mantener una tabla de disponibilidad manual duplicada.

---

### GAP 09 — Justificación digital de inasistencias

**Estado: 🟠 PARCIAL — corrección más importante de todo este documento.**
`Asistencia` ya tiene `estado` (incluye `'justificado'`), `justificacion`
y `justificacion_tipo`. `PortalPadreController::solicitarJustificacion()`
(línea 1197) ya permite al padre enviarla, creando una
`SolicitudRepresentante` tipo `justificacion_ausencia`.

**El hueco real**: `SolicitudesAdminController::responder()` solo cambia
el estado de la *solicitud* — **nunca toca el registro de `Asistencia`
real**. Una solicitud puede quedar "aprobada" sin que el % de asistencia
que alimenta `calcularPromocion()` (GAP 02) cambie en absoluto.

**Tranquilidad sobre el riesgo de integridad que se planteó al pedir este
análisis**: el problema NO es que un padre pueda auto-aprobar y alterar su
propio % de asistencia — eso no puede pasar hoy porque las dos piezas ni
siquiera están conectadas. El riesgo real es el opuesto: aprobaciones que
no surten efecto y generan confusión.

También confirmado: existe columna `adjunto` en `SolicitudRepresentante`
pero `solicitarJustificacion()` no valida ni guarda ningún archivo — la
evidencia adjunta no funciona hoy pese a que el campo existe.

**Arquitectura recomendada**: en `responder()`, cuando el tipo sea
`justificacion_ausencia` y se apruebe, actualizar la(s) `Asistencia`
correspondiente(s) a `estado = 'justificado'`. La aprobación la sigue
haciendo el staff/docente, nunca el padre directamente — se preserva la
integridad para MINERD.

---

### GAP 10 — Historial/factura del billing del SaaS

**Estado: 🔴 AUSENTE, confirmado.** `BillingController` tiene
`index/checkout/success/cancel/transferencia/activarSuscripcion/
downgradarAFree` — ningún método ni vista genera factura/recibo PDF.
Stripe internamente sí genera invoices (queda del lado de Stripe), pero
ZuraEdu no las expone en una UI ni las descarga.

---

## FASE 3 — Módulos adicionales (no cubiertos en la evaluación previa)

### 1. Disciplina y conducta — 🟢 COMPLETO
`FaltaDisciplinaria` + `DisciplinaController` (11 métodos: CRUD completo,
`toggleResuelto()`, `dashboard()`, `expedientePdf()`, exports). Más
maduro de lo que sugería el nombre "tab del expediente" en memoria previa.

### 2. Salud escolar — 🟢 COMPLETO
Dos tablas reales: `fichas_salud` (tipo de sangre, alergias, condiciones,
medicamentos, contacto de emergencia, seguro médico) e
`incidentes_medicos` (accidente/enfermedad/alergia/otro). `SaludController`
+ `SaludApiController` (móvil) ya existen.

### 3. Inventario/Equipos — 🟡 funcional, con solapamiento de diseño
Dos sistemas paralelos sin unificar: `EquipoController` (activos fijos:
laptops, proyectores, préstamo/devolución) e `InventarioController` +
`ArticuloInventario` (consumibles: stock, movimientos, alertas). Ambos
completos por separado — el problema es que un administrador ve dos
módulos distintos para un concepto que debería sentirse como uno.

### 4. Documentos/Certificados — 🟡 disperso
Generación de certificados repartida en 6 controladores sin servicio
central. Existe `DocumentosApiController` (hub de PDFs con token temporal
para móvil) — intento de centralización, pero solo del lado API.

### 5. Seguridad/Auditoría — ⚠️ cobertura parcial e inconsistente
4 Observers reales: `CalificacionObserver`, `EstudianteObserver`,
`CalificacionAcademicaObserver`, **`MatriculaObserver`** (dato nuevo —
Matrícula SÍ tiene auditoría, corrige una suposición de sesiones
anteriores). **`Pago` no tiene Observer** (hallazgo nuevo, relevante para
GAP 01: cambios al NCF no quedan auditados). Login/logout y cambios de
rol siguen sin auditoría, confirmado otra vez.

### 6. API móvil — 🟢 muy amplia
`routes/api.php`: ~30 grupos de recursos (auth, calificaciones,
asistencia, horario, notificaciones, pagos, classroom, mensajería,
gamificación, risk score, IA, encuestas, tareas, cafetería, transporte,
documentos, conducta, salud, reuniones, proyectos, eventos, biblioteca,
Carnet+). Consistente con las "79 pantallas" ya conocidas — el backend ya
expone casi todo lo que tiene el panel admin.

### 7. Multi-campus — 🔴 AUSENTE, confirmado
Cero coincidencias de "campus"/"sede"/"sucursal". Un tenant = un centro
completo. **REQUIERE VALIDACIÓN**: no se confirmó que algún cliente real
lo necesite — no construir especulativamente.

---

## Correcciones acumuladas a la evaluación de producto anterior (2026-09-05, artifact previo)

Para que quede explícito y no se repita el error:

| Gap | Evaluación previa decía | Esta auditoría encontró |
|---|---|---|
| 03 Carnet+ notif. | "Falta notificar al representante" | Ya notifica in-app; falta solo WhatsApp + arreglar paridad API |
| 06 Transporte | "No existe registro de vehículo/conductor" | Sí existe (campos simples en `RutaTransporte`) |
| 08 Citas padre-docente | "No existe" | Existe como tipo de solicitud completo; falta la agenda real |
| 09 Justificación ausencias | "No existe" | Existe el envío; falta conectar la aprobación con `Asistencia` |
