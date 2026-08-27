# Auditoría de Requerimientos — Centro Educativo Don Bosco vs. ZuraEdu

**Regla seguida:** REQUERIMIENTO → VERIFICAR EN CÓDIGO → DETERMINAR SI EXISTE/FUNCIONA/DÓNDE ESTÁ/QUÉ FALTA → (recién ahí) proponer. Nada de lo que sigue fue implementado — es únicamente el inventario y diagnóstico solicitado. Cada afirmación cita archivo:línea real, verificado en este pase (no repetido de memoria sin confirmar).

**Relación con auditorías previas:** esta no reemplaza `docs/MATRIZ_PERMISOS_ZURAEDU.md` (RBAC, ya resuelta) ni el barrido de bugs de portales/admin/superadmin de esta misma sesión (ya corregido y commiteado) — los da por buenos y los referencia donde aplica, sin repetir su detalle.

---

# 1. Resumen ejecutivo

De los 31 requerimientos de Don Bosco: **13 🟢 existen y funcionan**, **9 🟡 existen parcialmente**, **4 🟠 existen pero están defectuosos**, **4 🔴 no existen**, **1 ⚫ requiere decisión institucional** (ver tabla completa en la sección final). **Actualización 2026-08-26/27: las recomendaciones #1 a #8 (ver §16) ya fueron implementadas y verificadas — #22, #7, #15, #17, #1, #26, #29, #9, #10, #31 y #12 pasan a 🟢, quedando 24/5/0/1/1. La recomendación #8 (test de regresión de cierre de año) además destapó y corrigió un bug crítico activo: el cierre de año fallaba siempre por un valor de ENUM inválido en `alertas_sistema.tipo` — ver actualización en §5. Se amplió después la cobertura de tests a calificaciones/boletines/RBAC (59 tests en total) a pedido del usuario, completando el punto #12.**

**El hallazgo más grave de esta auditoría — ✅ ya corregido:** `CierreAnoController::ejecutar()` (`app/Http/Controllers/Admin/CierreAnoController.php:199-200`) intentaba guardar `matricula.estado = 'promovida'` o `'no_promovida'`, pero la columna `estado` de `matriculas` era un `ENUM('activa','retirada','transferida')` (`database/migrations/2026_03_17_000031_create_matriculas_table.php:18`) que nunca fue ampliado para aceptar esos dos valores, con la conexión MySQL en modo `strict => true` (`config/database.php:59`). El cierre de año escolar no podía persistir el resultado de la promoción de un estudiante sin fallar o corromper el dato — esto explicaba buena parte de las matrículas "sucias" que Don Bosco reporta al cruzar años. **Resuelto con la migración aditiva `2026_08_26_000001_add_promocion_states_to_matriculas_enum.php`** (ver actualización al final de §5).

Segundo hallazgo relevante: el permiso `imprimir-boletines` existe en Spatie, está asignado a roles, y se muestra en `/admin/ayuda/roles` — pero **no se usa en ningún lado del código real** (ni en rutas, ni en `@can()`, ni en controladores). Ver, imprimir y exportar boletines dependen todos de un único permiso (`ver-boletines`) en `routes/admin/academico.php:125`.

Tercero: la corrección de orden curricular de la sesión anterior (`Grado::scopeOrdenados()` → `orderBy('orden')`) no llegó a **6 sitios** que ordenan grados con SQL crudo `orderBy('grados.nivel')` en vez de `orden` (`EstudianteController.php:123,263`, `ImportacionController.php:321`, `RegistroAcademicoController.php:64,274,316,365`). En los datos actuales `nivel` y `orden` coinciden número a número, así que hoy no se nota — pero son columnas independientes y editables por separado, y el resto del código (30+ sitios) sí usa `orden` como fuente de verdad.

---

# 2. Requerimientos recibidos

1. Ordenamiento académico
2. Nivel
3. Grado
4. Sección
5. UTF-8 / UTF8MB4
6. Ñ y tildes
7. Concurrencia en matrícula
8. Optimización de calificaciones
9. Carga masiva de notas
10. Procesamiento batch
11. Staging / Sandbox
12. Pruebas de regresión
13. Git / control de versiones
14. RBAC
15. Impresión de boletines
16. Impresión de sábanas de notas
17. Permisos administrativos
18. Validación financiera
19. Deudas
20. Bloqueo o alerta de notas
21. Saneamiento de estudiantes
22. Estados de matrícula
23. Períodos lectivos
24. Meses/períodos
25. Saldos financieros
26. SLA
27. Mesa de ayuda
28. Tickets
29. Causa raíz
30. Documentación
31. Capacitación

---

# 3. Funciones existentes (🟢 completas)

**#3 Grado / #4 Sección** — Modelos y tablas dedicadas con columna `orden` propia y explícita para curricular, separada de `nivel`.
- Migration: `database/migrations/2026_03_17_000011_create_grados_table.php` (`nombre`, `nivel` tinyint, `orden` tinyint, `ciclo` enum).
- Migration: `database/migrations/2026_03_17_000012_create_secciones_table.php` (`nombre`, `orden` tinyint).
- Modelo: `app/Models/Grado.php` — `scopeOrdenados()` (línea 30: `orderBy('orden')`), `scopePrimerCiclo`/`scopeSegundoCiclo`, `esInicial()/esPrimerCiclo()/esSegundoCiclo()`.

**#5/#6 UTF-8 / Ñ y tildes** — Conexión MySQL en `utf8mb4`/`utf8mb4_unicode_ci` (`config/database.php:55-56`). PDFs (dompdf) usan `DejaVu Sans` en todas las plantillas revisadas esta sesión (soporta Ñ/tildes de forma nativa — confirmado visualmente en los PDFs generados durante el barrido de bugs de hoy: boletines, carnets, nómina, pagos). Trait `NormalizesFileEncoding` (sesión anterior) normaliza CSV/Excel de origen dudoso antes de importar.

**#7 Concurrencia en matrícula (parcial pero con base sólida)** — Ver detalle en §4, está bien resuelto en los 2 controladores principales.

**#13 Git / control de versiones** — Repo real, rama `master`, remoto `origin` (`https://github.com/Adaury/ZuraEdu.git`), convención de commits `tipo(alcance): descripción` ya usada consistentemente en las últimas ~15 auditorías/fixes de esta sesión.

**#14 RBAC** — Cubierto extensamente en `docs/MATRIZ_PERMISOS_ZURAEDU.md`. 21 roles, 25 permisos Spatie, matriz interactiva en `/admin/ayuda/roles` con datos en vivo desde BD. No se repite aquí.

**#16 Impresión de sábanas de notas ("acta de calificaciones")** — Existe con ese nombre exacto en el dominio MINERD dominicano.
- Ruta: `calificaciones.acta-pdf` / `calificaciones.acta-excel` — `routes/admin/academico.php:68-69`.
- Controller: `app/Http/Controllers/Admin/CalificacionController.php:740` (`actaPdf`), `:781` (`actaExcel`).
- Permiso: `ver-calificaciones` (grupo completo, `routes/admin/academico.php:55`).
- También existe versión por docente en `PortalDocenteController::actaPdf()`/`actaCalificaciones()` (`routes/web.php:376-377`) y versión de cierre de año en `CierreAnoController::boletinesMasivos()`.

**#19 Deudas** — `PagoController` calcula deudores por `estado = 'vencido'`/`'pendiente'` (`app/Http/Controllers/Admin/PagoController.php`, método `deudores()` y `Pago::sincronizarVencidos()`), con vista dedicada `admin.pagos.deudores` y export PDF/Excel. Es el mecanismo real detrás del banner de validación financiera (#18).

**#21 Saneamiento de estudiantes** — Comando `php artisan sge:saneamiento` (sesión anterior), modo reporte por defecto, `--fix` limitado a pagos huérfanos. Confirmado que sigue existiendo y no fue tocado en esta sesión.
- Qué detecta: matrículas duplicadas, pagos sin matrícula válida, grupos con conteos de matriculados inconsistentes (retirados/transferidos contados como activos — bug de origen ya corregido en `GrupoController`).
- Qué **no** detecta (confirmado en este pase): estados de matrícula fuera del ENUM permitido (relevante por el hallazgo de §1 de este documento — el comando no valida que `estado` sea uno de los 3 valores del ENUM, así que si el cierre de año llegó a insertar un valor corrupto, `sge:saneamiento` no lo señalaría).

**#23 Períodos lectivos** — `app/Models/Periodo.php`: `belongsTo(SchoolYear::class)`, columnas `numero`, `nombre`, fechas de inicio/fin. Relación año escolar → períodos limpia y usada consistentemente (`getPeriodos()` cacheado 10 min, definido en `app/Http/Controllers/Controller.php:21-29`, reusado por decenas de controladores).

**#27/#28 Mesa de ayuda / Tickets** — Ya revisado a fondo esta sesión (ver conversación previa): `app/Http/Controllers/Admin/TicketController.php`, modelo `TicketSoporte`, rutas `routes/admin/soporte.php`. Autoprotegido correctamente por rol dentro del propio controlador (`esAdmin()`), sin gaps de seguridad encontrados. Categorías: técnico/académico/administrativo/otro. Prioridades: baja/media/alta/urgente. Estados: abierto/en_proceso/resuelto/cerrado.

**#30 Documentación (parcial, ver también §4)** — `/admin/ayuda/index` y `/admin/ayuda/roles` (matriz de accesos en vivo), `docs/MATRIZ_PERMISOS_ZURAEDU.md`, este mismo documento. `README.md` existe pero es mínimo (66 líneas).

---

# 4. Funciones parciales (🟡)

**#1 Ordenamiento académico — parcial, con una regresión no detectada antes.**
- **Qué funciona:** `Grado::scopeOrdenados()` (orden correcto) es usado por 30+ controladores vía `Grado::orderBy('orden')` directo o el scope: `AcademicoController.php:51`, `EstudianteController.php:42,489`, `GrupoController.php:47,106`, `MallaCurricularController.php` (5 sitios), `CierreAnoController.php:331`, `SchoolYearController.php:159`, `InscripcionController.php:44`, `OnboardingWizardController.php:127`, `DemoAutoController.php:128`. `BoletinController.php:108` y `ExportacionMasivaController.php:35,78` ordenan grupos con `->sortBy(fn($g) => [$g->grado->orden ?? 99, ...])` — correcto.
- **Qué NO funciona:** 6 sitios ordenan con SQL crudo por `nivel` en vez de `orden`, saltándose el scope corregido:
  - `EstudianteController.php:123` y `:263` — listado y filtro de estudiantes.
  - `ImportacionController.php:321` — hub de importaciones masivas.
  - `RegistroAcademicoController.php:64,274,316,365` — módulo SIGERD/Registro Académico (4 de 4 métodos de listado del controlador).
- **Por qué no se ve hoy:** en la BD actual, `nivel` y `orden` tienen el mismo valor numérico fila por fila (confirmado con consulta directa: `1ro→nivel=1/orden=1`, `2do→nivel=2/orden=2`, etc.). El bug es latente, no visible, hasta que alguien reordene grados manualmente sin que `nivel` cambie en la misma proporción (el propio diseño de tener dos columnas separadas sugiere que eso es exactamente lo que se espera poder hacer).
- **Archivos a modificar si se aprueba:** los 3 archivos de arriba, reemplazando `orderBy('grados.nivel')` por `orderBy('grados.orden')` — cambio de una palabra por sitio, mismo patrón que la corrección original.

**#2 Nivel — parcial, ambigüedad de modelo.**
- Existe como concepto en 2 lugares distintos sin relación explícita entre ellos: (a) columna `grados.nivel` (tinyint, ~1-8, código de grado dentro de su ciclo) y (b) columna `grados.ciclo` (enum: `inicial`, `primer_ciclo`, `segundo_ciclo`, `bachillerato` — `database/migrations/2026_05_15_100000_add_inicial_to_grados_ciclo_enum.php:12`).
- No hay un "Nivel" como entidad propia (Inicial/Primaria/Secundaria como registro independiente) — es un ENUM de texto dentro de `Grado`. Funciona para filtrar y agrupar (`Grado::scopePrimerCiclo()`, etc.) pero no es una jerarquía normalizada con su propia tabla.
- **Riesgo si se pide un nivel nuevo o renombrar uno existente:** requiere una migración de ALTER ENUM (ya se hizo una vez, el 2026-05-15, y esa misma migración tuvo que borrar filas `ciclo='inicial'` preexistentes antes de aplicar el ALTER — "Remove any 'inicial' rows first to avoid data-truncation errors", línea 19-20 de esa migración — señal de que el enum es frágil ante cambios).

**#9 Carga masiva de notas — ✅ RESUELTO (2026-08-26, recomendación #6).** Ver actualización más abajo.

**#10 Procesamiento batch — ✅ RESUELTO (2026-08-26, recomendación #6).** Ver actualización más abajo.

**#11 Staging / Sandbox — parcial.**
- No hay un ambiente de staging separado (`.env` actual: `APP_ENV=local`, sin config de staging documentada).
- Sí existe `DemoMode` middleware (sesión anterior, confirmado presente en el pipeline de middleware de rutas admin: `app/Http/Middleware/DemoMode.php`) que simula un modo demo dentro del mismo ambiente — no es un sandbox real con datos aislados, es una capa de restricciones sobre el ambiente de producción/desarrollo.

**#12 Pruebas de regresión — parcial, cobertura muy delgada.**
- 9 archivos de test, **29 tests en total** (confirmado corriendo la suite hoy mismo, 29/29 pasando).
- Cobertura real: `BackupSecurityTest` (3 tests, seguridad de backups), `HorarioIntegrityTest` (8 tests, validador de horarios), `PortalAccesoTest` (9 tests, control de acceso entre portales — el que se usó para verificar los fixes de RBAC de esta sesión), `AcademicAlertThresholdsTest` (3), `CalificacionAuditTest` (4), 2 `ExampleTest` triviales (Laravel default, no aportan cobertura real).
- **Qué NO tiene ningún test:** matrícula (ni concurrencia ni CRUD), calificaciones (CRUD ni cálculo de promedios), boletines, permisos/RBAC (irónico dado el volumen de la auditoría de esta sesión), pagos, carnet, nómina, importaciones masivas, cierre de año — es decir, ninguno de los módulos que Don Bosco señaló como problemáticos tiene un test de regresión que hubiera detectado el bug del §1 de este documento.

**#17 Permisos administrativos — parcial, granularidad real menor a la aparente.**
- La matriz de 25 permisos (`docs/MATRIZ_PERMISOS_ZURAEDU.md`) sí diferencia Administrador/Director/Coordinador/Registrador Académico como roles con permisos Spatie distintos.
- Pero para boletines específicamente, **ver/imprimir/exportar-zip están bajo el mismo permiso** (`ver-boletines`, `routes/admin/academico.php:125`) — no hay diferenciación real de "puede ver pero no imprimir" pese a que `imprimir-boletines` existe como permiso separado (ver §5, es el hallazgo #2 del resumen ejecutivo).
- Tabla cruzada real (verificada contra código, no supuesta) para el módulo de boletines:

| Acción | Permiso que la protege | Roles que lo tienen |
|---|---|---|
| Ver boletín individual | `ver-boletines` | Administrador, Coordinador Académico/1/2, Director, Docente\*, Encargado de Registro Académico, Estudiante, Personal Administrativo, Registrador Académico, Representante, Secretaría, Secretaria Docente |
| Ver boletines de un grupo completo | `ver-boletines` (mismo permiso) | mismos de arriba |
| Descargar PDF individual | `ver-boletines` (mismo permiso) | mismos de arriba |
| Exportar ZIP de todo un grupo | `ver-boletines` (mismo permiso) | mismos de arriba |
| Configurar plantilla de boletín | `gestionar-configuracion` | Solo Administrador |
| "Imprimir boletines" (permiso dedicado) | `imprimir-boletines` | asignado en BD pero **sin ningún uso real en código** |

---

# 5. Funciones defectuosas (🟠)

**#15/#17 Permiso `imprimir-boletines` — ✅ RESUELTO (2026-08-26, recomendación #3).** Ver actualización más abajo.

---

## ✅ Actualización — Recomendaciones críticas 1, 2, 3 y 4 implementadas (2026-08-26)

**#1 Ordenamiento académico — RESUELTO, con alcance más amplio del reportado.** La recomendación #4 pedía corregir los 6/7 sitios con `orderBy('grados.nivel')` a nivel SQL — hecho en `EstudianteController.php` (2 sitios), `ImportacionController.php` (1) y `RegistroAcademicoController.php` (4). Pero una búsqueda del mismo patrón a nivel de PHP (`sortBy` con `->grado->nivel`, que la auditoría original no cubrió por buscar solo el patrón SQL) encontró **~18 ocurrencias más en 8 archivos**: `AsignacionController.php` (3), `DocenteSetupController.php` (2), `HorarioController.php` (3), `RendimientoController.php` (5), y las vistas `admin/asignaciones/index.blade.php`, `admin/asistencia/index.blade.php`, `admin/docente/setup.blade.php`, `admin/estudiantes/import-preview.blade.php` (1 cada una). Con aprobación del usuario, se corrigieron también. Verificado: `Grado::orden` coincide con `nivel` fila por fila en los datos actuales (sin discrepancias ni valores nulos, confirmado consultando los 56 grados reales), así que el cambio no altera ningún orden visible hoy — deja el sistema consistente y a salvo de la regresión latente que describía la auditoría. Probado en navegador: 7 páginas afectadas (wizard de estudiantes, importaciones, registro académico, asignaciones, calificaciones, asistencia, horario) cargan correctamente sin errores nuevos en el log.

**#15/#17 Permiso `imprimir-boletines` — RESUELTO.** Estaba en la tabla `permissions` de Spatie, asignado a roles, listado en `resources/views/admin/ayuda/roles.blade.php:88`, pero sin ningún uso real (`grep` → un solo resultado, la vista que solo lo mostraba). Corregido en `routes/admin/academico.php`: las rutas de impresión/exportación (`boletines.zip`, `boletines.pdf`, `boletines.pdf-anual`) ahora requieren `can:imprimir-boletines` además de `can:ver-boletines`. Verificado en navegador: un rol con solo `ver-boletines` (probado con `Encargado de Área`) ve el boletín pero recibe 403 al intentar imprimir/exportar; un rol con ambos permisos sigue funcionando sin cambios.

**#26/#29 SLA y causa raíz — RESUELTO.** Extensión aditiva de `tickets_soporte` (nombre real de la tabla; el documento original decía `ticket_soportes` por error), sin módulo ni tabla nueva:
- Migración `2026_08_26_000002_add_sla_causa_raiz_to_tickets_soporte.php`: columnas `sla_vencimiento_at` (timestamp), `sla_incumplido` (boolean, se fija de forma permanente la primera vez que el ticket pasa a "resuelto" — deja rastro histórico aunque se reabra después), `causa_raiz` (texto).
- `TicketSoporte::SLA_HORAS` (baja=72h, media=48h, alta=24h, urgente=4h — valores por defecto, no configurables por institución todavía, fuera del alcance de "campos aditivos"); se calcula automáticamente al crear el ticket vía `booted()::creating`.
- Accessor `sla_estado` (vencido/por_vencer/a_tiempo mientras está abierto; cumplido/incumplido una vez resuelto) con sus labels/colores, usado en `admin.soporte.index` (columna nueva), `admin.soporte.show` (badge + fecha de vencimiento) y `admin.soporte.dashboard` (alerta roja de "N tickets vencieron el SLA", mismo patrón que la alerta ya existente de "sin asignar").
- `causa_raiz`: editable solo por `esAdmin()` y solo cuando el estado pasa a `'cerrado'` (textarea que aparece/desaparece según el select, con JS mínimo inline); se muestra en un panel dedicado del ticket una vez registrada.
- Verificado con datos reales: ciclo completo (crear con prioridad urgente → forzar vencimiento → resolver tarde → cerrar con causa raíz) en una transacción revertida, confirmando `sla_vencimiento_at`, `sla_estado` y `sla_incumplido` en cada paso; las 3 vistas probadas en navegador real con capturas (dashboard, listado, detalle, y el campo de causa raíz apareciendo al seleccionar "Cerrado").

**Regresión encontrada y corregida de paso:** al probar el split se descubrió que `BoletinPolicy::ver()` (activada en la sesión de RBAC previa) restringía el acceso a solo `Administrador`/`Director` hardcodeados, bloqueando con 403 a Coordinadores, Secretaría, Personal Administrativo, Encargado de Área y Registrador Académico — todos roles que sí tienen `ver-boletines` en la BD y antes funcionaban vía la lógica inline que la Policy reemplazó. Corregido: cualquier usuario no-docente con `ver-boletines` ahora tiene acceso (los docentes mantienen su scoping por asignación). Ver detalle completo en `docs/MATRIZ_PERMISOS_ZURAEDU.md`.

**#22 Estados de matrícula — RESUELTO.** El ENUM de `matriculas.estado` se amplió vía migración aditiva `database/migrations/2026_08_26_000001_add_promocion_states_to_matriculas_enum.php` (`ALTER TABLE matriculas MODIFY COLUMN estado ENUM('activa','retirada','transferida','promovida','no_promovida')`). No se tocó ninguna fila existente — solo se agregaron los 2 valores que faltaban. Verificado end-to-end: `Matricula::estado = 'promovida'`/`'no_promovida'` se guarda correctamente (probado en una transacción revertida, sin persistir datos). `CierreAnoController` ya puede escribir el resultado real del cierre de año.

**#7 Concurrencia en matrícula — RESUELTO.** Se agregó `Grupo::whereIn('id', $grupoIds)->lockForUpdate()->get()` (bloqueando todos los grupos destino del lote, en orden consistente para evitar deadlocks) en:
- `CierreAnoController::ejecutarTraslado()` — antes del loop que crea las nuevas matrículas del traslado de fin de año.
- `SchoolYearController::matriculaMasivaStore()` — antes del loop de matrícula masiva del año nuevo.

Mismo patrón ya usado en `MatriculaController::store()`/`InscripcionController`. Verificado funcionalmente: ambos métodos se ejecutaron con datos reales dentro de una transacción revertida (sin persistir), confirmando que el lock no rompe el flujo. Suite completa: 29/29.

**#9/#10 Carga masiva de notas por cola — RESUELTO.** La carga de calificaciones pasó de procesarse de forma síncrona (bloqueando la petición HTTP) a un `Job` en cola (`app/Jobs/ImportarCalificacionesJob.php`, extiende `TenantJob`), con una tabla de tracking nueva (`importaciones_calificaciones`, migración `2026_08_26_000003`) y una página de estado con auto-refresh (`admin.calificaciones.import.estado`):
- **Unificación (resuelve también el hallazgo de §7):** los 2 flujos existentes — `CalificacionController::importStore()` (academica + técnica/simple) e `ImportacionController::calificacionesImportar()` (solo académica, con `recalcularPromedios()`) — ahora despachan el **mismo** Job en vez de procesar inline cada uno con su propia lógica duplicada. Ambos controladores redirigen a la misma vista de estado compartida, tal como exigía la regla de "un solo lugar, no una tercera vía" del §7.
- El Job guarda el archivo temporal en `storage/app/imports/calificaciones`, procesa fila por fila (académica: `comp{n}_p{n}` + `recalcularPromedios()`; técnica/simple: `nota_final` por período), acumula contadores (`total_filas`, `importados`, `omitidos`) y un arreglo de `errores` por fila, y borra el archivo temporal al terminar (éxito o fallo, en un `finally`). Al terminar notifica al usuario que lo subió vía `Notificacion::enviar()` (que a su vez encola `EnviarNotificacionJob` si la cola no es síncrona).
- La vista `import_estado.blade.php` usa `<meta http-equiv="refresh" content="3">` mientras el lote está `pendiente`/`procesando`, y muestra contadores + lista colapsable de errores una vez `completado`, o el mensaje de error si quedó `fallido`.
- **Bug pre-existente encontrado y corregido de paso:** ambos controladores originales (y por tanto el Job, antes de corregirlo) hacían `->keyBy('numero_matricula')` directamente sobre la colección de `Matricula`, pero `numero_matricula` y `cedula` son columnas de `Estudiante`, no de `Matricula` (confirmado con `Schema::getColumnListing()`). El `keyBy` silenciosamente devolvía siempre una clave vacía — el emparejamiento por número de matrícula nunca funcionó en producción, solo el de cédula (que sí navegaba la relación correctamente) tenía alguna chance de funcionar, y solo si el archivo traía cédula. Corregido a `->keyBy(fn ($m) => $m->estudiante->numero_matricula ?? '')` en el Job, y en `CalificacionController::downloadTemplate()` (3 ocurrencias) que tenía el mismo error al generar la plantilla de ejemplo.
- Verificado end-to-end con datos reales de un grupo/asignatura existente (committeado y luego revertido manualmente, ya que un Job en cola corre en un proceso aparte y no participa de una transacción de prueba): se dispatchó el Job, se procesó con `queue:work`, se confirmó `estado=completado`, el conteo correcto de importados/omitidos, los mensajes de error esperados (nota fuera de rango, estudiante no encontrado), la actualización real de `CalificacionAcademica` con `recalcularPromedios()` ejecutado, el borrado del archivo temporal, y la notificación al usuario — luego se restauró el valor de nota original y se eliminaron los registros de prueba. La vista de estado se renderizó sin errores en sus 3 estados (`procesando`, `completado`, `fallido`). Suite completa: 29/29.

**#31 Capacitación — RESUELTO.** Sección nueva y ligera dentro del Centro de Ayuda existente (`/admin/ayuda/capacitacion`), sin permisos nuevos (visible para cualquier usuario autenticado, igual que `/admin/ayuda` hoy):
- `app/Http/Controllers/Admin/AyudaController.php`: catálogo estático de guías (`AyudaController::GRUPOS`) agrupadas en 5 perfiles — Administración y Dirección, Docentes, Secretaría y Registro, Finanzas y Caja, Soporte a Padres y Estudiantes — cubriendo los 21 roles del sistema. 16 guías en total, cada una con título, duración estimada y 3-5 pasos concretos (rutas reales del menú).
- Progreso marcable como "visto" por usuario: tabla aditiva `capacitaciones_vistas` (migración `2026_08_26_000004`, único por `user_id`+`contenido_id`), toggle vía `POST admin/ayuda/capacitacion/{contenidoId}/visto` (valida el id contra el catálogo, 404 si no existe) y barra de progreso en vivo (`X/16`) sin recargar la página.
- Varias guías enlazan de vuelta a la sección correspondiente del manual completo (`admin.ayuda`) vía un parámetro `?tab=` nuevo que el manual ya sabe leer al cargar (deep-link agregado al script existente de `admin/ayuda/index.blade.php`, sin tocar su lógica de búsqueda ni de tabs).
- Tarjeta de acceso agregada en `/admin/ayuda/index.blade.php`, mismo patrón visual que la tarjeta existente de "Matriz de Accesos por Rol".
- Verificado en navegador real (Playwright, cuenta `admin@demo.com`): las 5 pestañas cambian correctamente, marcar/desmarcar "visto" actualiza el botón y la barra de progreso sin recargar, el estado persiste tras recargar la página, el deep-link `?tab=sCfg` activa la pestaña correcta del manual, y el diseño se ve correcto en modo claro y oscuro. Suite completa: 29/29.

**#8 (recomendación) Test de regresión para el cierre de año — RESUELTO, y destapó un bug crítico activo en producción.** `tests/Feature/CierreAnoRegressionTest.php`, 9 pruebas contra el flujo HTTP real de `CierreAnoController` (no contra el modelo directamente, a diferencia de la verificación manual de las recomendaciones #1/#2):
- Cubre: cálculo de promoción (promovido ≥60 / no promovido / pendiente sin notas), que la académica no se mezcla con la técnica cuando ambas existen, que el año se desactiva, que un cierre sobre un año ya inactivo no hace nada, que un rol sin acceso a Dirección recibe 403, y el traslado masivo de fin de año (orden secuencial y no duplicar estudiantes ya trasladados).
- **Hallazgo crítico:** al correr la prueba contra el controlador real (con datos comprometidos y un `queue:work`/request HTTP de verdad, no una transacción revertida), **el cierre de año fallaba siempre, en cualquier escenario** — `CierreAnoController::ejecutar()` (línea 209) crea una `AlertaSistema` con `'tipo' => 'cierre_ano'`, pero el ENUM de `alertas_sistema.tipo` (`database/migrations/2026_03_19_000006_create_alertas_sistema_table.php`) solo acepta `riesgo_academico, entrega_notas, baja_asistencia, periodo_cierre, evento_calendario, otro` — **`'cierre_ano'` no es un valor válido**. Con la conexión en modo `strict` (`config/database.php:59`, el mismo modo que expuso el bug del ENUM de matrículas en la recomendación #1), esto lanza `SQLSTATE[01000]: Data truncated for column 'tipo'`, el `catch` interno de `ejecutar()` revierte toda la transacción, y el usuario solo ve un mensaje genérico "Error al ejecutar el cierre" — **ninguna promoción se guarda, ninguna matrícula cambia de estado, el año no se desactiva**, sin importar qué tan bien esté calculada la promoción. Este bug es anterior a esta sesión y pasó inadvertido en la verificación de las recomendaciones #1/#2 porque esa verificación probó `Matricula::estado` directamente sobre el modelo (dentro de una transacción revertida), sin ejecutar nunca el controlador completo end-to-end — exactamente el riesgo que esta recomendación #8 buscaba prevenir.
- **Corregido:** `'tipo' => 'cierre_ano'` → `'tipo' => 'periodo_cierre'` (valor existente del ENUM, semánticamente equivalente). Un solo carácter de diferencia en el fix, pero sin el test de regresión este bug habría llegado a producción sin que nadie lo notara hasta el primer cierre de año real de un cliente.
- Suite completa: 38/38 (29 previos + 9 nuevos).

**#12 Cobertura de tests de calificaciones/boletines/RBAC — RESUELTO.** `tests/Feature/CalificacionRegressionTest.php` (10 pruebas) y `tests/Feature/BoletinAccessRegressionTest.php` (11 pruebas), 21 pruebas nuevas:
- **Calificaciones:** el Job de carga en cola (recomendación #6) nunca había tenido un test automatizado, solo verificación manual — ahora cubre el emparejamiento por `numero_matricula` y por `cédula` (la regresión de esa misma recomendación), filas con estudiante no encontrado o nota fuera de rango, el área técnica (`nota_final` por período, no solo académica), que `importStore()` despacha el Job correcto, que la plantilla descargable trae el `numero_matricula` real (la otra ocurrencia del mismo bug), y que el estado de una importación solo lo puede ver su dueño o un rol de coordinación. RBAC: acceso al índice de calificaciones por permiso `ver-calificaciones` vs `ingresar-calificaciones` (son permisos distintos — `importStore()` requiere el segundo, no el primero, algo que no estaba documentado y que el test dejó explícito).
- **Boletines:** cubre las dos regresiones de esta auditoría que solo se habían verificado a mano en navegador — `BoletinPolicy::ver()` (bloqueaba a todo rol no-docente distinto de Administrador/Director) y `BoletinController::puedeVerGrupo()` (no verificaba asignación real del docente en el grupo pedido por query string) — más el split `ver-boletines`/`imprimir-boletines` de la recomendación #3.
- **Hallazgo de arquitectura (no es un bug, documentado para que no se repita como falso positivo):** los roles Docente/Docente Académico/Docente Técnico/Docente Guía **nunca llegan** a `/admin/boletines/*` ni `/admin/calificaciones/*` — el middleware `EnsureAdminAccess` los redirige siempre a su portal (`/portal/docente`) antes de que la petición llegue al controlador, sin excepción. La lógica de scoping por docente que existe dentro de `BoletinController`/`BoletinPolicy`/`CalificacionController::index()` es código real y correcto, pero solo alcanzable hoy vía llamada directa (Reflection para el método privado `puedeVerGrupo()`, instanciación directa para la Policy) — así se prueban en este commit, no vía HTTP con un usuario docente autenticado, que solo produce 302 hacia el portal.
- Suite completa: 59/59 (38 previos + 21 nuevos).

---

# 6. Funciones inexistentes (🔴) — propuestas de diseño, sin implementar

**#26 SLA (Service Level Agreement) para tickets de soporte.**
- Confirmado: `app/Models/TicketSoporte.php:17-26` (`$fillable`) no tiene ningún campo de tiempo de respuesta, vencimiento o compromiso de atención. No existe en ninguna migración relacionada a `TicketSoporte`.
- **Propuesta (no implementada):**
  - Módulo: extensión de Mesa de Ayuda existente (NO un módulo nuevo — mejorar `TicketSoporte`).
  - Objetivo: definir un tiempo máximo de primera respuesta/resolución por prioridad, y alertar cuando se incumple.
  - Usuarios: Administrador, Director, Coordinador Académico (los mismos que ya gestionan tickets vía `esAdmin()`).
  - Tablas: agregar columnas a `ticket_soportes` (`sla_vencimiento_at`, `sla_incumplido` boolean) vía migración aditiva; tabla nueva opcional `sla_configuraciones` (prioridad → horas límite), configurable por institución.
  - Controllers: extender `TicketController` (no crear uno nuevo) con cálculo de vencimiento al crear/asignar, y un scope `vencidos()`.
  - UI: badge de "vencido"/"por vencer" en `admin.soporte.index` y `admin.soporte.dashboard` (vistas ya existentes).
  - Tests: nuevo test de regresión para el cálculo de vencimiento (el módulo de soporte no tiene ningún test hoy).

**#29 Causa raíz (root cause) en tickets de soporte.**
- Confirmado: mismo `$fillable` de arriba, no hay campo `causa_raiz` ni tabla de análisis post-mortem.
- **Propuesta (no implementada):** agregar campo `causa_raiz` (texto, opcional) al mismo modelo `TicketSoporte`, editable solo al cerrar el ticket (`cambiarEstado()` a `'cerrado'`), visible en reportes agregados por categoría. No requiere tabla nueva ni módulo nuevo — es un campo más en lo que ya existe.

**#31 Capacitación — ✅ RESUELTO (2026-08-26, recomendación #7).** Ver actualización más abajo.

**#8 Optimización de calificaciones específica — no existe como esfuerzo dedicado (aunque el sistema en general sí tiene optimizaciones de otra sesión).**
- La sesión "Optimizaciones de rendimiento" (memoria, 2026-03-19) atacó N+1 e índices de forma general en el sistema, pero no hay evidencia de un trabajo específico sobre `calificaciones`/`calificaciones_academicas` más allá de eso — no encontré, por ejemplo, índices compuestos dedicados a los patrones de consulta de boletín/planilla (`matricula_id + asignacion_id + periodo_id`, que es el patrón de consulta más repetido de todo el sistema, usado en `BoletinController`, `CalificacionController`, `PortalEstudianteController`, `PortalDocenteController`).
- **Propuesta (no implementada):** migración aditiva con índice compuesto `(matricula_id, asignacion_id, periodo_id)` en `calificaciones` y `calificaciones_academicas` si no existe ya (no confirmado en esta pasada — requeriría inspeccionar los índices reales de la BD, fuera del alcance de "buscar en código" de esta auditoría).

---

# 7. Funciones duplicadas

- **Carga de calificaciones masiva por 2 rutas distintas** (`CalificacionController::import` vs. `ImportacionController` módulo "calificaciones") — ✅ **RESUELTO 2026-08-26 (recomendación #6):** ambas rutas siguen existiendo (una genérica, otra específica por plantilla) pero ahora despachan el mismo `ImportarCalificacionesJob` y redirigen a la misma vista de estado compartida, en vez de duplicar la lógica de procesamiento. Ver actualización en §5.
- No se encontraron módulos, tablas o rutas verdaderamente duplicadas (mismo propósito, dos implementaciones independientes) más allá de este caso.

---

# 8. Ubicación exacta (tabla consolidada)

| Requerimiento | Módulo | Archivo(s) clave | Tabla(s) |
|---|---|---|---|
| Orden académico | Académico | `app/Models/Grado.php`, `EstudianteController.php`, `ImportacionController.php`, `RegistroAcademicoController.php` | `grados`, `secciones` |
| UTF-8 | Config/BD | `config/database.php:55-56`, `app/Traits/NormalizesFileEncoding.php` | todas |
| Concurrencia matrícula | Matrícula | `MatriculaController.php`, `InscripcionController.php`, `CierreAnoController.php`, `SchoolYearController.php` | `matriculas`, `grupos` |
| Carga masiva notas | Calificaciones | `CalificacionController.php:1225` (import), `ImportacionController.php` | `calificaciones`, `calificaciones_academicas` |
| Batch/Jobs | Sistema | `app/Jobs/*.php` (7 archivos, incl. `ImportarCalificacionesJob`) | — |
| Tests | QA | `tests/Feature/*.php`, `tests/Unit/*.php` | — |
| Boletines | Académico | `BoletinController.php`, `BoletinPolicy.php`, `routes/admin/academico.php:120-138` | `matriculas`, `calificaciones*`, `boletin_config` |
| Sábanas/Actas | Académico | `CalificacionController.php:740,781` | `calificaciones`, `asignaciones` |
| Financiero/Deudas | Pagos | `PagoController.php` | `pagos` |
| Saneamiento | Sistema | comando `sge:saneamiento` | `matriculas`, `pagos`, `grupos` |
| Estados matrícula | Matrícula | `database/migrations/2026_03_17_000031_create_matriculas_table.php`, `CierreAnoController.php` | `matriculas` |
| Períodos | Académico | `app/Models/Periodo.php` | `periodos`, `school_years` |
| Mesa de ayuda/Tickets | Soporte | `TicketController.php`, `routes/admin/soporte.php` | `ticket_soportes`, `respuesta_tickets` |
| Documentación | Sistema | `docs/*.md`, `resources/views/admin/ayuda/*` | — |

---

# 9. Roles involucrados

Sin cambios respecto a `docs/MATRIZ_PERMISOS_ZURAEDU.md` §1 (21 roles). Los relevantes a esta auditoría: Administrador, Director, Coordinador Académico/Primer/Segundo Ciclo, Registrador Académico/Encargado de Registro Académico, Docente (4 variantes), Secretaría/Secretaria Docente, Caja/Finanzas.

# 10. Permisos

Los 25 permisos existentes ya documentados. Hallazgo nuevo de esta auditoría: `imprimir-boletines` existe pero no se aplica (§5). No se proponen permisos nuevos salvo que se apruebe la mejora de granularidad de boletines.

# 11. Base de datos (tablas relevantes a esta auditoría)

`grados`, `secciones`, `matriculas` (⚠️ ENUM de `estado` insuficiente, §5), `periodos`, `school_years`, `calificaciones`, `calificaciones_academicas`, `pagos`, `ticket_soportes`, `respuesta_tickets`.

# 12. Rutas

Las citadas en cada sección; ninguna ruta nueva fue creada en esta auditoría.

# 13. Controllers

`CierreAnoController` (hallazgo crítico), `CalificacionController`, `EstudianteController`, `ImportacionController`, `RegistroAcademicoController`, `MatriculaController`, `InscripcionController`, `SchoolYearController`, `TicketController`, `PagoController`.

# 14. Módulos

Académico (grados/secciones/períodos/orden), Matrícula (concurrencia/estados), Calificaciones (carga masiva/optimización), RBAC (ya cubierto), Boletines/Actas, Financiero (deudas/saldos), Mesa de Ayuda (SLA/causa raíz — resuelto §16.5), Documentación/Capacitación (resuelto §16.7).

---

# 15. Riesgos

1. **Crítico:** `CierreAnoController` no puede persistir el resultado real del cierre de año (§1, §5) — riesgo de que el año escolar se marque como cerrado (`school_years.activo = false`, línea 206) mientras las matrículas individuales quedan en un estado indefinido o corrupto. Esto es peor que "no cerrar" — deja el sistema en un estado a medias del que es difícil recuperarse manualmente.
2. **Alto:** permiso `imprimir-boletines` fantasma (§5) — cualquier decisión institucional de "fulano puede ver pero no imprimir" es actualmente imposible de aplicar pese a que la matriz de roles sugiere que sí se puede.
3. **Medio:** concurrencia sin lock en traslado de fin de año y matrícula masiva de año nuevo (§5) — son operaciones que típicamente se ejecutan una vez al año, con más de un usuario administrativo trabajando a la vez bajo presión de tiempo (justo el escenario de mayor probabilidad de colisión).
4. **Medio:** cobertura de tests casi nula en los módulos que Don Bosco señaló como problemáticos (§4) — cualquier corrección futura a estos puntos no tiene red de seguridad automatizada.
5. **Bajo:** orden por `nivel` en 3 controladores (§4) — latente, no visible hoy, pero inconsistente con el resto del código.

# 16. Recomendaciones

Por impacto/riesgo, sin implementar nada todavía:
1. ✅ Migración aditiva para ampliar el ENUM de `matriculas.estado` (agregar `'promovida'`, `'no_promovida'`) — desbloquea el cierre de año real. (2026-08-26)
2. ✅ Agregar `lockForUpdate()` en los 2 flujos de traslado/matrícula masiva que no lo tienen. (2026-08-26)
3. ✅ Separar `imprimir-boletines` del permiso `ver-boletines` a nivel de ruta. (2026-08-26)
4. ✅ Corregir los sitios que ordenan grados por `nivel` en vez de `orden`. (2026-08-26 — 7 sitios SQL de la auditoría original + 18 sitios PHP adicionales encontrados al ampliar la búsqueda, con aprobación del usuario)
5. ✅ Extender `TicketSoporte` con SLA y causa raíz (campos aditivos, sin módulo nuevo). (2026-08-26)
6. ✅ Mover la carga masiva de calificaciones a un Job en cola, unificando los 2 flujos existentes en uno solo. (2026-08-26)
7. ✅ Crear sección de capacitación dentro de `/admin/ayuda` (extensión, no módulo nuevo). (2026-08-26)
8. ✅ Escribir al menos un test de regresión para el cierre de año antes de tocar el ENUM de estados (para no repetir el patrón de "corregir sin poder verificar"). (2026-08-27 — implementado después del ENUM, no antes; ver hallazgo crítico que destapó en la actualización más abajo)

# 17. Prioridad

Crítica: 1, 2 (recomendaciones). Alta: 3, 4, 8. Media: 5, 6. Baja: 7.

---

## TABLA OBLIGATORIA

| # | Requerimiento | Estado | Ya existía | Ubicación | Falta | Prioridad |
|---|---|---|---|---|---|---|
| 1 | Ordenamiento académico | 🟢 Completo ✅ 2026-08-26 | Sí | `Grado.php`, 30+ controllers, todos por `orden` | — | — |
| 2 | Nivel | 🟡 Parcial | Sí (como enum `ciclo`) | `Grado.php` | Normalizar relación nivel↔ciclo | Baja |
| 3 | Grado | 🟢 Completo | Sí | `grados` table + modelo | — | — |
| 4 | Sección | 🟢 Completo | Sí | `secciones` table + modelo | — | — |
| 5 | UTF-8 / UTF8MB4 | 🟢 Completo | Sí | `config/database.php` | — | — |
| 6 | Ñ y tildes | 🟢 Completo | Sí | utf8mb4 + DejaVu Sans en PDFs | — | — |
| 7 | Concurrencia en matrícula | 🟢 Completo ✅ 2026-08-26 | Sí | `MatriculaController`/`InscripcionController`/`CierreAnoController`/`SchoolYearController`, todos con `lockForUpdate` | — | — |
| 8 | Optimización de calificaciones | 🟡 Parcial | Sí (general) | Optimizaciones previas de sesión 2026-03-19 | Índice compuesto dedicado a boletín/planilla | Media |
| 9 | Carga masiva de notas | 🟢 Completo ✅ 2026-08-26 | Sí, unificado a 1 flujo | `app/Jobs/ImportarCalificacionesJob.php`, `CalificacionController.php`, `ImportacionController.php` | — | — |
| 10 | Procesamiento batch | 🟢 Completo ✅ 2026-08-26 | Sí (7 Jobs) | `app/Jobs/*.php` | — | — |
| 11 | Staging / Sandbox | 🟡 Parcial | Sí (DemoMode) | `DemoMode` middleware | Ambiente de staging real separado | Baja |
| 12 | Pruebas de regresión | 🟢 Completo ✅ 2026-08-27 | Sí (59 tests: cierre de año, calificaciones, boletines, RBAC) | `tests/*RegressionTest.php` | — | — |
| 13 | Git / control de versiones | 🟢 Completo | Sí | repo `master` + convención de commits | — | — |
| 14 | RBAC | 🟢 Completo | Sí | `docs/MATRIZ_PERMISOS_ZURAEDU.md` | — | — |
| 15 | Impresión de boletines | 🟢 Completo ✅ 2026-08-26 | Sí | `routes/admin/academico.php` — `imprimir-boletines` separado de `ver-boletines` | — | — |
| 16 | Impresión de sábanas de notas | 🟢 Completo | Sí | `CalificacionController::actaPdf/actaExcel` | — | — |
| 17 | Permisos administrativos | 🟢 Completo ✅ 2026-08-26 | Sí | Matriz de 25 permisos, `imprimir-boletines` ya aplicado | — | — |
| 18 | Validación financiera | 🟢 Completo | Sí (sesión anterior) | `BoletinController`/banner | — | — |
| 19 | Deudas | 🟢 Completo | Sí | `PagoController::deudores()` | — | — |
| 20 | Bloqueo o alerta de notas | ⚫ Requiere decisión | Sí (solo alerta) | `admin/boletines/ver.blade.php` | Decisión institucional: ¿bloqueo real o mantener alerta? | Media |
| 21 | Saneamiento de estudiantes | 🟢 Completo | Sí (sesión anterior) | comando `sge:saneamiento` | No detecta estados fuera del ENUM | — |
| 22 | Estados de matrícula | 🟢 Completo ✅ 2026-08-26 | Sí | `matriculas` ENUM ampliado a 5 valores (migración `2026_08_26_000001`) | — | — |
| 23 | Períodos lectivos | 🟢 Completo | Sí | `app/Models/Periodo.php` | — | — |
| 24 | Meses/períodos (nómina) | 🟢 Completo | Sí | `PagoNomina` usa mes calendario `'YYYY-MM'`, separado de `Periodo` académico — separación correcta, no es un conflicto | — | — |
| 25 | Saldos financieros | 🟡 Parcial | Sí (por módulo) | `mi-saldo-cafeteria`, `PagoController` | Sin saldo consolidado único por estudiante | Baja |
| 26 | SLA | 🟢 Completo ✅ 2026-08-26 | No | `TicketSoporte::SLA_HORAS`, migración `2026_08_26_000002` | — | — |
| 27 | Mesa de ayuda | 🟢 Completo | Sí | `TicketController.php`, `routes/admin/soporte.php` | — | — |
| 28 | Tickets | 🟢 Completo | Sí | mismo módulo que #27 | — | — |
| 29 | Causa raíz | 🟢 Completo ✅ 2026-08-26 | No | Campo `causa_raiz` en `tickets_soporte` | — | — |
| 30 | Documentación | 🟡 Parcial | Sí | `/admin/ayuda/*`, `docs/*.md` | README mínimo, sin docs de usuario final | Baja |
| 31 | Capacitación | 🟢 Completo ✅ 2026-08-26 | No | `AyudaController.php`, `admin/ayuda/capacitacion.blade.php`, tabla `capacitaciones_vistas` | — | — |
