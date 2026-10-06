# Bitácora de trabajo — ZuraEdu / SGE

> Del 19 de marzo al 6 de octubre de 2026 · 545 commits en `master` (desde el 4 de mayo; lo anterior sale de las notas del proyecto).
> Octubre está curado a mano; mayo-septiembre se agrupa automáticamente por día y área desde el historial de git.

## Cómo usar esta bitácora

- Cada fila de la tabla es una entrega verificable; la columna **Commits** apunta al código (`git show <hash>`).
- **Estado:** *Hecho* · *Hecho con pendiente* (falta una credencial real o probarlo en real; el detalle está en «Resultado») · *Pendiente* (depende de ti, no del código).
- Para añadir una entrada nueva: copia la última fila, cambia fecha y texto, y pega el hash del commit.

## Resumen

| Área | Qué quedó |
|---|---|
| Seguridad | `exists:`/`unique:` por colegio, TrustProxies, respaldo global solo superadministrador, permisos de cafetería, IDOR de Classroom |
| Funciones | Respaldo automático (hora, carpeta, Drive), copia de datos por colegio, menú por rol, accesos rápidos, guías por rol, calendario con avisos, Odoo, cierre de año automático |
| Correcciones clave | Chat de soporte, cola de WhatsApp/notificaciones, ficha del estudiante, editar encuesta, estadísticas del docente, botones de la cafetería |
| Rendimiento | Pagos 3×, validación SIGERD 14×, páginas más ligeras, KPIs en caché, prueba de carga en Linux |
| Calidad | Pruebas nuevas que impiden repetir defectos: Alpine huérfano, compilación de todas las vistas, funciones inexistentes, marca |

## Antes de octubre

Resumen por mes; el detalle fila por fila está en `bitacora_trabajo.csv` (base de datos de Notion).

### Marzo 2026 — 3 entradas

| Fecha | Área | Tipo | Qué se hizo | Commits |
|---|---|---|---|---|
| 2026-03-19 | Rendimiento | Rendimiento | Optimizaciones de rendimiento: consultas N+1, índices de base de datos y caché. | — |
| 2026-03-26 | Horarios | Función | Módulo de horarios (timetable): migraciones, modelos, servicios, controlador, rutas y vistas; capa de depuración, validación e integridad del generador con pruebas. | — |
| 2026-03-27 | Landing | Función | Landing pública, ingreso de demostración automático por rol y middleware DemoMode. | — |

### Abril 2026 — 6 entradas

| Fecha | Área | Tipo | Qué se hizo | Commits |
|---|---|---|---|---|
| 2026-04-02 | Portal docente | Función | Portal docente: calificaciones editables P1–P4, boletines filtrados por docente, modo oscuro en el panel. | — |
| 2026-04-04 | Portales | Función | Portales del representante (inicio e hijo) y del estudiante (inicio y notificaciones); estado global del sistema documentado. | — |
| 2026-04-16 | Académico | Función | Planificaciones completas, notificaciones conectadas, sidebars unificados, matrícula masiva, búsqueda global y branding dinámico. | — |
| 2026-04-17 | Reportes | Función | PDF de asistencia, Excel de situación, estado de cuenta, Cuadro de Honor, instrumento PDF, comunicado PDF y recordatorio de pagos vencidos. | — |
| 2026-04-27 | Classroom | Función | ZuraClass: Google Classroom integrado, sincronización de notas (GradeSync), rúbricas, quizzes, recursos y pestañas de docente/estudiante. | — |
| 2026-04-29 | Multi-colegio | Función | Arquitectura multi-tenant: base compartida con tenant_id, middleware ResolveTenant, trait BelongsToTenant, panel SuperAdmin y 31 feature flags. | — |

### Mayo 2026 — 166 entradas · 256 commits

| Fecha | Área | Tipo | Qué se hizo | Commits |
|---|---|---|---|---|
| 2026-05-04 | Pagos | Corrección | Agregar stripe_session_id y stripe_payment_intent al fillable de Subscription | `7221fd0` |
| 2026-05-04 | Classroom | Función | ZuraClass Pro — chat en tiempo real + videoconferencia Jitsi | `2f9c7e6` |
| 2026-05-04 | General | Mantenimiento | Agregar todos los archivos del proyecto ZuraEdu | `9e8397f` |
| 2026-05-04 | General | Corrección | Make billing migration idempotent to handle partial previous run | `0302a6d` |
| 2026-05-04 | Calidad | Función | Add comprehensive test data seeders | `b0e0274` |
| 2026-05-05 | Classroom | Función | Crear vistas faltantes del portal ZuraClass docente | `3594ec8` |
| 2026-05-05 | Calidad | Función | Completar módulo de avisos de emergencia (show + destroy) | `52c3db4` |
| 2026-05-05 | General | Función | Completar módulo de encuestas (edit + update) | `932706c` |
| 2026-05-05 | Pagos | Función | Integración completa CardNet RD — Pago en Línea | `3df18a0` |
| 2026-05-05 | Pagos | Corrección | Convertir metodo_pago de ENUM a VARCHAR(50) en tabla pagos | `d7bccf7` |
| 2026-05-08 | Dashboard | Función | Completar 21 módulos admin + portales enriquecidos + dashboard mejorado; Dashboard de estadísticas con gráficas completas + Excel | `86c39b2, 7f20425` |
| 2026-05-08 | Matrículas | Función | Pre-matricula publica online con codigo de seguimiento; Flujo completo de conversión pre-matrícula → matrícula real | `1a9eb1f, f5352b8` |
| 2026-05-08 | Docentes | Función | Integrar ZuraIA en módulo de planificación docente (Gemini Flash) | `94df247` |
| 2026-05-08 | Matrículas | Corrección | Usar withoutGlobalScopes en instData para páginas públicas de pre-matrícula | `8c2b014` |
| 2026-05-08 | ZuraAI | Corrección | Chat Zura — nombre dinámico, modelo gemini-2.0-flash y bug historial duplicado | `176c778` |
| 2026-05-08 | Calidad | Función | Panel de configuración del sistema con 5 pestañas | `4215709` |
| 2026-05-08 | Familias | Función | Notificaciones avanzadas — ausencias, alerta académica a representante, panel mejorado; Módulo de solicitudes del representante (portal padre mejorado) | `559eff0, 0c4cb28` |
| 2026-05-08 | General | Corrección | Página de registro y admin usan nombre dinámico del sistema (ya no hardcoded PSAC) | `705d65b` |
| 2026-05-09 | Matrículas | Corrección | Navbar páginas públicas de inscripción usa system_name dinámico | `05bae90` |
| 2026-05-09 | Estudiantes | Función | Portal estudiante mejorado — solicitudes, historial académico y constancia; Certificados y documentos PDF oficiales desde portal estudiante | `fec34f2, 4ce4d90` |
| 2026-05-11 | Asistencia | Función | Asistencia digital QR — docente proyecta, estudiante escanea | `598f504` |
| 2026-05-14 | Servicios | Función | Realtime completo — Reverb + Echo, 14 eventos broadcast, tenant chat y notificaciones | `3b0eb2c` |
| 2026-05-14 | Asistencia | Función | Chat de primera asistencia — widget público en landing/inscripción + panel admin realtime | `2cbde16` |
| 2026-05-14 | Pagos | Interfaz | Mejoras módulo de pagos — filtros rápidos, semáforo mora, progreso portal | `9d2e99c` |
| 2026-05-14 | General | Mantenimiento | Integrar cambios acumulados de sesiones anteriores | `26e5148` |
| 2026-05-14 | WhatsApp | Función | Alertas automáticas — cumpleaños, pagos próximos y WhatsApp en vencidos | `bbf8c61` |
| 2026-05-14 | Reportes | Función | Modulo reportes ejecutivos mejorado para directores | `acb8a55` |
| 2026-05-14 | Dashboard | Interfaz | Agregar accesos rapidos PDF/Excel al sidebar del Dashboard Ejecutivo | `77d1abf` |
| 2026-05-14 | Docentes | Interfaz | Agregar Dashboard Ejecutivo al sidebar del portal docente | `bdcc5f9` |
| 2026-05-14 | Estudiantes | Interfaz | Agregar Dashboard Ejecutivo al sidebar de portales padre y estudiante | `2365d2d` |
| 2026-05-15 | SuperAdmin | Interfaz | Agregar Dashboard Ejecutivo al sidebar del superadmin | `efb6830` |
| 2026-05-15 | Notificaciones | Interfaz | Agregar Dashboard Ejecutivo al sidebar del portal de notificaciones | `4a5bc06` |
| 2026-05-15 | Classroom | Interfaz | Agregar sidebar al portal classroom (estudiante, padre, docente) | `ad61974` |
| 2026-05-15 | Servicios | Función | Stripe webhooks completos — idempotencia, 5 eventos, emails | `aee97fe` |
| 2026-05-15 | Multi-colegio | Función | Onboarding wizard de 4 pasos para nuevos tenants | `ed21e3f` |
| 2026-05-15 | Permisos | Función | API REST completa — 11 controladores, tenant middleware, 28 endpoints | `4e84fe4` |
| 2026-05-15 | General | Función | PWA — manifest dinámico, iconos GD, service worker offline | `ca92fd0` |
| 2026-05-15 | Calidad | Función | Prompt de instalación PWA — banner Android e instrucciones iOS | `6433fbf` |
| 2026-05-15 | Mensajería | Corrección | Chat de soporte admin — bugs y mejoras UX completas; Rezagados 500 y chat soporte 404 | `c82c507, 9b5057e` |
| 2026-05-15 | General | Corrección | Migrar 58 llamadas PhpSpreadsheet 1.x deprecadas a 5.x | `d20d3fa` |
| 2026-05-15 | Estudiantes | Función | Nivel Inicial, onboarding paso 3 con secciones, estado de cuenta HTML y portal-estudiante layout | `02f78a9` |
| 2026-05-15 | Onboarding | Corrección | Onboarding paso 3 — chips de secciones con opciones predefinidas A-D | `67540d1` |
| 2026-05-15 | Dashboard | Función | Checklist post-onboarding en dashboard admin | `cb9c6ed` |
| 2026-05-15 | Cierre de año | Función | Cierre de año escolar — workflow completo en 4 fases | `728021e` |
| 2026-05-16 | General | Corrección | GenerarCuotas usaba estado 'activo' en lugar de 'activa' | `1078df3` |
| 2026-05-16 | Asistencia | Corrección | Asistencia portal docente — estados ENUM tarde/excusa; Asistencia PDF usaba 'tardanza' en lugar de 'tarde' y faltaba 'excusa' | `b704343, 1a81a91` |
| 2026-05-16 | Docentes | Función | Plan de evaluacion por periodo en portal docente; Reporte de rendimiento del grupo en portal docente; Comunicados al grupo desde portal docente; Mis estadisticas globales del docente mejoradas; Retroalimentación en entregas de tareas — portal docente; Justific… | `e31bdec, 52ac386, b7fb699, ef75ec1, 59963c6, 36b9c63, 20ea660, c027676, c24fd5e` |
| 2026-05-16 | Estudiantes | Función | Plan de evaluacion visible en portal padre y estudiante; Ficha completa del estudiante en portal docente; Evaluaciones online (quiz) para docente y estudiante | `251ffd3, 932ab48, 8ac2d73` |
| 2026-05-16 | Evaluación | Refactor | Rediseñar vista de evaluacion por instrumento al estilo portal | `2555d73` |
| 2026-05-16 | Calificaciones | Función | Aplicar notas de instrumentos al periodo en portal docente; Historial de notas y fecha aplicación en instrumentos — portal docente; Acta de calificaciones con estadísticas en portal docente | `cd73136, 52d8628, 5ea1e69` |
| 2026-05-16 | Comunicados | Corrección | Comunicados renderizaban HTML crudo en portales; Comunicados admin mostraban HTML crudo en vista mis comunicados | `0b8c255, 31c411e` |
| 2026-05-16 | MINERD / SIGERD | Función | Módulo Bachillerato Técnico MINERD (Áreas → Cursos → Módulos) | `da43443` |
| 2026-05-16 | Menú | Función | Bachillerato técnico visible para super_admin en sidebar y acceso rápido | `b7588f7` |
| 2026-05-16 | Asistencia | Función | Asistencia rápida en portal docente (hub multi-clase con auto-guardado); Estadísticas de asistencia en portal docente | `6ea1647, cf6742a` |
| 2026-05-16 | Evaluación | Función | Diario de clase + fix layout en evaluaciones y banco de preguntas | `25c4dbc` |
| 2026-05-16 | Planificación | Función | Planificación anual por unidades curriculares | `3024fe7` |
| 2026-05-17 | Estudiantes | Función | Seguimiento de tareas por estudiante con recordatorio masivo; ZuraAI extendido al portal del estudiante; Expediente digital completo del estudiante | `47bc5a1, c5c5889, 1be0632` |
| 2026-05-17 | General | Función | Vista admin de rúbricas y mejoras a los tres portales | `d1fb780` |
| 2026-05-17 | Docentes | Función | ZuraAI — asistente académico con Claude API en portal docente | `e042e40` |
| 2026-05-17 | Familias | Función | ZuraAI extendido al portal del padre/representante | `d15d782` |
| 2026-05-17 | WhatsApp | Corrección | Activar tarjeta WhatsApp Business en Centro de Integraciones | `6d7ec35` |
| 2026-05-17 | ZuraAI | Función | ZuraAI en panel admin (asistente institucional); ZuraAI página completa admin (antigravity Gemini style) + fixes; ZuraAI admin — diseño full-viewport antigravity Google Gemini; ZuraAI welcome — efecto antigravity con partículas y satélites | `b9233a2, 44a6771, 50569af, c21d873` |
| 2026-05-18 | General | Función | Rediseño visual premium ZuraEdu estilo SaaS moderno; Premium design expandido a páginas clave del admin | `965af94, 848add1` |
| 2026-05-18 | Landing | Función | Premium redesign landing page ZuraEdu | `6ea538a` |
| 2026-05-18 | Calificaciones | Corrección | 3 bugs — boletin 500, chat no limpia, hero sin animacion | `78e0efd` |
| 2026-05-18 | Estudiantes | Función | Migrar ZuraAI de Anthropic a Gemini (portal docente/estudiante/padre); Módulo Comunicados Internos + limpieza portal estudiante; ZuraEdu Mobile — app React Native Expo para portales Estudiante, Padre y Docente; Tutor IA para estudiantes — chat académico con Zu… | `87be7aa, 2d40aa6, fd1194c, aa345eb, 7516507` |
| 2026-05-18 | ZuraAI | Corrección | Cambiar gemini-2.0-flash a gemini-1.5-flash (quota free tier); Cambiar v1beta a v1 en endpoints Gemini (gemini-1.5-flash requiere v1); Usar gemini-1.5-flash-latest en v1beta (soporta systemInstruction) | `1eaabf5, d0add74, 55eca9d` |
| 2026-05-18 | ZuraAI | Función | Migrar ZuraAI a gemini-2.5-flash, rediseño integraciones y fixes de chat | `0033140` |
| 2026-05-18 | Calidad | Mantenimiento | Agregar React al proyecto con framer-motion, lucide-react y apexcharts | `cfd8967` |
| 2026-05-18 | Dashboard | Función | Dashboard ejecutivo con React + ApexCharts | `80acce3` |
| 2026-05-18 | Familias | Función | Tutor IA para representantes/padres en portal padre | `69fce16` |
| 2026-05-18 | Riesgo académico | Función | Academic Risk Score — sistema de puntuación de riesgo académico; Mi situación académica (risk score) en portal del estudiante; Situación académica (risk score) en portal del representante; Risk score en app móvil del estudiante — pantalla Mi Estado; Situación … | `7b32451, f050bfb, 29b1bc5, 6875503, 900a03e` |
| 2026-05-18 | Classroom | Función | Duplicar aula virtual a otro grupo en Classroom del docente; Classroom en app móvil del estudiante; Classroom en app móvil del representante (padre) | `8374d80, f72e990, 7a22988` |
| 2026-05-18 | App móvil | Función | Pantallas completas para todos los roles en app móvil | `48867d1` |
| 2026-05-18 | Permisos | Función | Acceso rápido a pantallas ocultas desde el dashboard de cada rol | `e603acf` |
| 2026-05-18 | Gamificación | Función | Gamificación en portal estudiante + app móvil; Gamificación en portal padre + app móvil; Gamificacion en portal docente | `e451018, 8eefece, 8cdee4d` |
| 2026-05-18 | General | Otro | @ feat: módulo Año Escolar — Cursos y Materias en admin | `4aa89f8` |
| 2026-05-18 | Matrículas | Función | Módulo Inscripciones — flujo Matrícula → Inscripción → Asignación; Módulo de matrícula completo | `198175d, 9fe79b3` |
| 2026-05-18 | Pagos | Función | Módulo de pagos — conceptos predefinidos + fixes de campos | `7703425` |
| 2026-05-18 | Reportes | Corrección | Módulo nómina — procesar-solo por empleado, recibo PDF con desglose TSS/ISR, dark mode, flash | `5b2b474` |
| 2026-05-18 | Servicios | Corrección | Módulo biblioteca — renovaciones, bug devuelto Excel, dark mode, preselección libro | `45d663b` |
| 2026-05-19 | Cafetería | Función | App móvil — encuestas, tareas, cafetería y transporte | `a7f6535` |
| 2026-05-19 | Estudiantes | Función | App móvil — documentos y solicitudes para estudiante y padre; Observaciones del estudiante y representante en app móvil; Conducta y plan de evaluación para estudiante y padre; Ocultar Documentos del portal estudiante (móvil) | `316f41d, 152d7e8, 1831440, 4e877f3` |
| 2026-05-19 | App móvil | Función | App móvil — calendario, notificaciones y perfil (3 roles); App móvil — tab badges, deeplinks por tipo, nueva aula virtual, foto de perfil; Portal admin móvil — dashboard + routing + layout de tabs | `38afe66, 177af00, 0f78ba8` |
| 2026-05-19 | Docentes | Función | Módulo docente — observaciones y tareas en app móvil; Conducta del docente en app móvil; Plan de evaluación e instrumentos para docente (móvil); Solicitudes docente — pantalla móvil + endpoint API; Dashboard docente — grupos con color + KPIs reales + acceso rá… | `28b10dc, 1f90556, 5955d64, 12e5a27, 6501cb1` |
| 2026-05-19 | Riesgo académico | Función | Riesgo académico por grupo (docente) y resultados de evaluación (estudiante/padre) | `0e261f7` |
| 2026-05-19 | Classroom | Función | App móvil — edición notas, crear materiales classroom, editar perfil | `d28c89b` |
| 2026-05-19 | Calificaciones | Función | Push notifications nativas + publicar calificaciones por período; Pantalla grupos docente — color backend, navegación modal, botones asistencia/calificaciones | `963de63, 8dcc735` |
| 2026-05-19 | Familias | Corrección | Solicitudes representante — reescritura completa de la pantalla | `3913e3e` |
| 2026-05-19 | Estudiantes | Corrección | Solicitudes estudiante — reescritura con patrón consistente | `b32569b` |
| 2026-05-19 | Pagos | Corrección | Pantallas pendientes app móvil — notas/asistencia/pagos padre + comunicados estudiante | `4d46690` |
| 2026-05-19 | Familias | Función | Dashboard padre — hijos cards + acceso rápido organizado por sección | `a1d515a` |
| 2026-05-19 | Gamificación | Función | Dashboard estudiante — perfil card + KPIs reales + gamificación + acceso rápido por sección | `91588e5` |
| 2026-05-19 | Permisos | Función | Pantalla de login — inputs con foco, error inline, Ionicons, 4 roles | `688cb33` |
| 2026-05-20 | Calificaciones | Corrección | RefreshControl en notas y asistencia del estudiante | `3b49a13` |
| 2026-05-20 | Familias | Función | Descarga nativa de PDFs en documentos del padre | `77ebdb5` |
| 2026-05-20 | Estudiantes | Función | Descarga nativa de PDFs en documentos del estudiante; Rehabilitar Documentos en portal estudiante (móvil) | `953d30e, 1b832c8` |
| 2026-05-20 | Landing | Corrección | Agregar favicon a landing page y layout portal | `fd499a4` |
| 2026-05-20 | Permisos | Corrección | Deeplink push notifications para rol Administrador/Director; BackButton y renderBackBtn fuera del componente — referencia estable evita re-mount del header en cada re-render | `d93a470, 89e9a88` |
| 2026-05-20 | App móvil | Mantenimiento | Agregar eas.json y .env.example al proyecto móvil | `173e9cf` |
| 2026-05-20 | Notificaciones | Mantenimiento | Agregar notification-icon.png para push notifications Android | `1a0d782` |
| 2026-05-20 | Marca | Mantenimiento | Assets de marca ZuraEdu — birrete + Z sobre navy #1e3a6e | `67385b2` |
| 2026-05-20 | General | Mantenimiento | Google-services template y .gitignore actualizado | `56a9828` |
| 2026-05-20 | App móvil | Documentación | DEPLOY.md app móvil ZuraEdu; DEPLOY.md portal web — multi-tenant, push notifications Expo, seeding inicial, env local | `7aa40f5, edaf8cc` |
| 2026-05-20 | Gamificación | Corrección | Gamificación 500 — quitar BelongsToTenant de modelos sin tenant_id; back button en tabs ocultas; ocultar Documentos del menú estudiante | `e1b05b5` |
| 2026-05-20 | Estudiantes | Corrección | Ocultar Mis Documentos del sidebar del portal estudiante; Back button estable en layouts padre y docente — mismo patrón que estudiante | `c441c7d, a6da394` |
| 2026-05-20 | General | Corrección | Eliminar canGoBack() del render — back button estático en tabs ocultas para evitar salto del header | `3315705` |
| 2026-05-20 | General | Otro | Eas.json — iOS production con credentialsSource remote, preview ios, language es-419 | `1267753` |
| 2026-05-21 | Permisos | Corrección | Revisión sistemática de 88+ controladores Admin — N+1, ConfigInstitucional, bugs de campo | `290cd12` |
| 2026-05-21 | Matrículas | Corrección | Portal controllers — ConfigInstitucional::first(), numero_matricula, Notificacion::enviar | `b0989b2` |
| 2026-05-21 | Servicios | Corrección | Módulo inventario — tenant_id en 4 tablas, estado reparacion, fillables | `d08a575` |
| 2026-05-21 | Servicios | Función | Inventario — costo unitario y valor total del inventario | `2d9e3a5` |
| 2026-05-21 | Familias | Función | Módulo Salud Escolar — dashboard, editar incidente, hora, notificado_representante, tenant_id | `a5eb73d` |
| 2026-05-21 | Estudiantes | Corrección | Pasar $estudiante=null en editarIncidente — evita undefined variable en vista | `620d646` |
| 2026-05-21 | Cafetería | Función | Cafetería — dashboard, ajuste de saldo, tenant_id | `72bbfe3` |
| 2026-05-21 | Horarios | Función | Transporte — tenant_id, horario_salida/regreso, telefono_conductor | `8a91919` |
| 2026-05-22 | Servicios | Función | Módulo Biblioteca — tenant_id, dashboard con estadísticas y acciones rápidas; Módulo Inventario TI — dashboard, campos marca/modelo/ubicación/año, formulario ampliado; Módulo Eventos — tenant_id en fillable, dashboard con próximos y más populares; Módulo Trans… | `5f53076, 5fc9875, 3434ff7, 4252cf9, 81d279d` |
| 2026-05-22 | Multi-colegio | Función | Módulo Encuestas — tenant_id en fillable, dashboard con estadísticas y toggle rápido; Módulo Tickets/Soporte — tenant_id en fillable, dashboard con estados, urgentes y categorías; Módulo Nómina — tenant_id en fillable, dashboard con evolución salarial y top sa… | `879785c, 5d6bfb9, d79b4e3, 66c0935, 91e3296, 600cd84` |
| 2026-05-22 | Planificación | Función | Módulo Planificaciones — tenant_id en fillable, dashboard con cumplimiento por asignación | `d94bad4` |
| 2026-05-22 | Pagos | Función | Módulo Pagos — tenant_id en fillable, dashboard financiero con KPIs y recaudación mensual; Módulo Becas — tenant_id en fillable, dashboard con catálogo y beneficiarios | `700f118, c1b3260` |
| 2026-05-22 | Estudiantes | Función | Módulo Reconocimientos — dashboard con tipos, top estudiantes y catálogo; Portales — Mis Reconocimientos (estudiante/padre), Salud Hijo, Mis Evaluaciones y Mis Reuniones (docente) | `7c34c5f, bee2715` |
| 2026-05-22 | Dashboard | Función | Módulo Proyectos — dashboard con estados, áreas, top proyectos y recientes; Módulo Reuniones — dashboard con tipos, próximas, acuerdos y actas | `d5cc59d, f160e9c` |
| 2026-05-22 | Docentes | Función | Evaluaciones Docentes — tenant_id en fillable, sidebar apunta a dashboard; App móvil — Reconocimientos (est/padre), Salud Hijo (padre), Evaluaciones y Reuniones (docente) | `1fc5efe, b68bfc9` |
| 2026-05-22 | Riesgo académico | Función | Módulo Seguimiento Social — dashboard con tipos, riesgo y casos críticos | `39edc84` |
| 2026-05-22 | Classroom | Corrección | Insignia sin_faltas por estudiante_id, refactor @php en calendario/ejecutivo/classroom | `dcb3a40` |
| 2026-05-22 | Cierre de año | Función | Módulo Cierre de Año — gestión de promociones, reporte PDF y estado condicionado | `6f4a69e` |
| 2026-05-22 | MINERD / SIGERD | Corrección | SIGERD PDF exports — vistas dedicadas y datos correctos | `d8295c4` |
| 2026-05-22 | MINERD / SIGERD | Refactor | SIGERD index — diseño modernizado con sistema de diseño del proyecto | `7a1f19d` |
| 2026-05-22 | Planificación | Corrección | Pasar \$planificacion=null en createRa y createActividad | `f6ee567` |
| 2026-05-22 | Calificaciones | Corrección | Boletin 500 + dashboard ejecutivo graficas en blanco; Corregir default color_secundario en boletines_config | `3284958, de84f69` |
| 2026-05-22 | Calidad | Función | Editor de diseño en configuración del boletín | `ee9c96f` |
| 2026-05-22 | Reportes | Corrección | Restaurar fallback #1e3a6e en @php de pdf y pdf_anual | `90d9aa1` |
| 2026-05-22 | Calificaciones | Función | Botón Configurar Diseño visible en boletines index y grupo; Config. Boletín visible en sidebar junto a Boletines | `a3edcd1, 274b5ff` |
| 2026-05-23 | Carnet+ | Función | ZuraEdu Carnet+ — identidad digital, QR, control de acceso y asistencia inteligente; App móvil — Carnet+ (estudiante + padre) con QR, Risk Score e historial de accesos; App móvil — Carnet+ docente: dashboard accesos hoy + escáner QR Carnet+; Carnet+ en portale… | `40c9127, af13b2c, 7548cf8, 0507aa4` |
| 2026-05-23 | Estudiantes | Corrección | GenerarMasivo — saltar estudiantes sin user_id (withDefault devuelve User vacío); instalar expo-constants y expo-linking; Aumentar paddingBottom en dashboard estudiante para scroll completo | `b01cbff, 4996a1f` |
| 2026-05-23 | Carnet+ | Corrección | Carnet+ como tab visible en los 3 roles — mueve de grid oculto al tab bar principal; API comunicados (columna publicado→activo/cuerpo), carnet 404→200 null, classroom eager load docente | `e93a982, 5d93600` |
| 2026-05-23 | Notificaciones | Corrección | Duplicate isAdmin declaration en usePushNotifications + actualizar package-lock | `7f96e0f` |
| 2026-05-23 | Docentes | Corrección | PaddingBottom 100 en dashboards docente y padre para scroll completo | `7e42ccb` |
| 2026-05-24 | Permisos | Corrección | PaddingBottom 100 en dashboard admin para scroll completo; URL preview sin /sge (ngrok->artisan serve) + fix roles precedencia | `232421b, ffd2f0b` |
| 2026-05-24 | App móvil | Corrección | Downgrade reanimated a v3.17.4 compatible con Expo SDK 54 + babel.config.js explícito; Restaurar reanimated ~4.1.1 + worklets-core para EAS build (Expo SDK 54); Eliminar expo-barcode-scanner (deprecado, falla Gradle SDK 54); Corregir crash de pantalla Perfil y… | `aa28b71, ead5382, 05878e2, 24115a6` |
| 2026-05-24 | Cafetería | Corrección | Login Android + crashes Pagos/Cafetería en Hermes | `9c5d733` |
| 2026-05-24 | Pagos | Corrección | Pagos estudiante — usar data.pagos en lugar de data.data + proteger estado null | `2128fff` |
| 2026-05-24 | Asistencia | Función | Notificaciones push a representantes al registrar asistencia desde app móvil | `7b37771` |
| 2026-05-24 | General | Mantenimiento | Agregar google-services.json, projectId EAS y config eas.json para build Android; Actualizar package-lock.json para EAS build | `e69eedf, 98e61ca` |
| 2026-05-24 | General | Corrección | Quitar react-native-worklets-core (paquete inexistente en npm); Agregar react-native-worklets + .npmrc para EAS build; Preview build con ngrok + header bypass + trust proxies; Corregir URL preview, incluir /sge (subdirectorio Laragon) | `fb25a32, d2e318c, 3ac4dd1, cbce0c0` |
| 2026-05-24 | Docentes | Corrección | Crash perfil, routing post-crash y seguridad rutas docente | `d110f85` |
| 2026-05-26 | App móvil | Corrección | Corregir errores TypeScript y compatibilidad SDK 54 | `f708309` |
| 2026-05-27 | General | Mantenimiento | Fix blank line in AGENTS.md | `acca8ef` |
| 2026-05-28 | App móvil | Corrección | Aumentar timeout axios a 30s para iOS | `a064ad7` |
| 2026-05-29 | Estudiantes | Función | Wizard de registro de estudiante en 5 pasos | `7fd4cec` |
| 2026-05-29 | Permisos | Función | Área Registro Académico — rol, dashboard y sidebar dedicado | `6b9bac5` |
| 2026-05-30 | MINERD / SIGERD | Función | Registro MINERD — PDF, portal docente (edición CE/IL) y portales padre/estudiante (lectura); Sección Evaluación por Competencias MINERD en boletín PDF y vista web; Sección MINERD en boletín portal estudiante y portal padre; Sección MINERD en boletin_ver del po… | `73c7e84, c83b01e, 2d0a317, c652364` |
| 2026-05-30 | Calificaciones | Función | BoletinObservacion desde portal docente con autosave AJAX; Validación nota mínima al cerrar período | `d1c1519, b85e183` |
| 2026-05-30 | Cierre de año | Función | Panel de períodos con cierre/reapertura desde módulo Registro | `05e8f98` |
| 2026-05-30 | Permisos | Función | Añadir rol Encargado de Registro Académico; Sistema de visibilidad y permisos inteligentes por rol; Dashboard contextual por rol | `77c4ae8, e75c904, 1215b5e` |
| 2026-05-30 | Pagos | Función | Conectar Stripe para pagos de cuotas de estudiantes | `673db34` |
| 2026-05-30 | General | Función | Agregar vista de detalle del usuario | `6c3d325` |
| 2026-05-30 | Permisos | Corrección | Permitir acceso al panel admin al rol Encargado de Registro Academico; Corregir nombre de ruta en sidebar coordinador | `990bad7, fbfaa9e` |
| 2026-05-30 | Pagos | Corrección | Agregar middleware de permisos a todas las rutas del modulo pagos | `edbd679` |
| 2026-05-30 | WhatsApp | Función | Envios asincronos via cola + fix bug critico en PagoController | `6f50e9b` |
| 2026-05-31 | MINERD / SIGERD | Función | Integracion completa — middleware, notificaciones, comando validacion, sidebar | `15595ca` |
| 2026-05-31 | Pagos | Función | Notificacion post-pago via Event/Listener | `92553d8` |
| 2026-05-31 | Permisos | Corrección | Agregar middleware a 69 rutas admin sin proteccion especifica | `8e9e4b7` |
| 2026-05-31 | Servicios | Función | Partials contextuales para Registro Academico y Biblioteca | `c4ebd6f` |
| 2026-05-31 | Dashboard | Corrección | Grupo.seccion mostraba JSON en vez del nombre de seccion | `09ef52b` |
| 2026-05-31 | General | Corrección | Eliminar PSAC hardcodeado — usar system_name/system_abbr de settings | `ae9c9d9` |

### Junio 2026 — 10 entradas · 11 commits

| Fecha | Área | Tipo | Qué se hizo | Commits |
|---|---|---|---|---|
| 2026-06-01 | SuperAdmin | Función | Dark mode superadmin, fix acceso backend, encuesta publica ZuraEdu | `90c8ad6` |
| 2026-06-01 | General | Función | RegistroAcademicoUserSeeder — usuario Encargado de Registro | `bc47951` |
| 2026-06-01 | General | Corrección | Redirigir Encargado/Registrador de Registro Académico a su panel | `7f30d34` |
| 2026-06-01 | Reportes | Función | Módulo completo — bajas, traslados, sin grupo, reporte consolidado | `a9fe5bf` |
| 2026-06-01 | Estudiantes | Función | Agregar botones Registrar Traslado y Registrar Baja | `0015383` |
| 2026-06-01 | Landing | Función | BajasTrasladosDemoSeeder — 3 retiros + 3 traslados de prueba | `2f8412c` |
| 2026-06-01 | Seguridad | Documentación | Actualizar guía completa para envío al servidor | `268c088` |
| 2026-06-15 | General | Corrección | Auditoria completa — criticos, altos y medios corregidos; FOUC dark mode, Bootstrap JS faltante y rutas hardcoded en portal | `d9f57ed, 5b0585c` |
| 2026-06-15 | Comunicados | Corrección | Revertir escape de cuerpo — el HTML se renderiza via SanitizeInput | `ce4b4ab` |
| 2026-06-15 | Multi-colegio | Corrección | Sa_tenant_id tiene prioridad sobre resolucion por dominio | `4b12620` |

### Agosto 2026 — 29 entradas · 35 commits

| Fecha | Área | Tipo | Qué se hizo | Commits |
|---|---|---|---|---|
| 2026-08-24 | Seguridad | Corrección | XSS, CSP, RBAC superadmin y gaps del informe Don Bosco | `75e34d5` |
| 2026-08-24 | General | Corrección | Gaps encontrados al verificar en navegador los fixes previos | `18d12d5` |
| 2026-08-24 | Notificaciones | Corrección | Eliminar "PSAC" hardcodeado de docs oficiales/emails; manual de ayuda actualizado | `8a7edca` |
| 2026-08-24 | Matrículas | Función | Agregar página de Matriz de Accesos al manual de ayuda | `b3396c5` |
| 2026-08-25 | Permisos | Corrección | Segmentar permisos por módulo y corregir bypass de super_admin roto por middleware role: | `fe9b12e` |
| 2026-08-25 | Permisos | Documentación | Confirmar que comunicaciones.php y soporte.php no necesitan can: de ruta | `53cf35c` |
| 2026-08-25 | Calificaciones | Corrección | Resolver §2.3 — activar BoletinPolicy, eliminar 2 Policies muertas, cerrar gap en boletines por grupo; Recomendación 3 de auditoría Don Bosco — separar imprimir-boletines de ver-boletines, más regresión real en BoletinPolicy | `c56f019, d06decd` |
| 2026-08-25 | Docentes | Corrección | Corregir bloqueo total de Docente Académico/Técnico/Guía en portal/docente | `5d88238` |
| 2026-08-25 | Servicios | Corrección | 500 en avisos del padre por lazy loading, enlace roto de préstamos en sidebar Biblioteca | `32ba176` |
| 2026-08-25 | Estudiantes | Corrección | 6 bugs encontrados auditando docente/estudiante por errores similares al del padre | `1af801f` |
| 2026-08-25 | General | Corrección | 20 bugs encontrados auditando el panel admin (393 páginas recorridas con datos reales) | `223c772` |
| 2026-08-25 | Matrículas | Corrección | Recomendaciones críticas 1 y 2 de auditoría Don Bosco — ENUM de estado y concurrencia en cierre de año | `bee6275` |
| 2026-08-26 | Calidad | Corrección | Recomendación 4 de auditoría Don Bosco — ordenar grados por 'orden' en vez de 'nivel' en todo el sistema | `1ac8dd5` |
| 2026-08-26 | Calidad | Función | Recomendación 5 de auditoría Don Bosco — SLA y causa raíz en tickets de soporte; Agregar sección de Capacitación con guías por perfil (recomendación 7) | `9869ebc, 0e27984` |
| 2026-08-26 | Calificaciones | Función | Mover carga masiva de notas a Job en cola (recomendación 6) | `50c1f16` |
| 2026-08-26 | Cierre de año | Corrección | Corregir ENUM inválido que rompía siempre el cierre de año + test de regresión (recomendación 8) | `f9c4a2c` |
| 2026-08-26 | Calificaciones | Pruebas | Agregar cobertura de regresión y RBAC (recomendación 12) | `ab76b1d` |
| 2026-08-27 | Calificaciones | Corrección | Corregir 500 al abrir la grilla de una asignatura académica | `3785a32` |
| 2026-08-27 | Calificaciones | Documentación | Verificar que el índice compuesto de calificaciones ya existía | `cb79454` |
| 2026-08-27 | Pagos | Función | Bloqueo real y opcional de impresión por pagos vencidos (recomendación 20); Saldo financiero consolidado por estudiante (recomendación 25) | `9e33331, d25ab69` |
| 2026-08-27 | Calidad | Refactor | Quitar fragilidad del ENUM ciclo, sin forzar un mapeo nivel↔ciclo (recomendación 2) | `4d7044d` |
| 2026-08-27 | Permisos | Corrección | Auditar y proteger gestión de comunicados institucionales | `eb301f6` |
| 2026-08-27 | Permisos | Documentación | Actualizar sección de próximos pasos, todos ya ejecutados | `3b0edfe` |
| 2026-08-27 | Calidad | Corrección | Actualizar dependencias con vulnerabilidades conocidas | `618c390` |
| 2026-08-27 | Seguridad | Mantenimiento | Fase 0 — red de seguridad pre-upgrade | `a255480` |
| 2026-08-27 | Plataforma | Mantenimiento | Fase 1 — Laravel 10 → 11 | `e4d7b3d` |
| 2026-08-28 | Plataforma | Mantenimiento | Fase 2 — Laravel 11 → 12; Fase 3 — Laravel 12 → 13 (gate de cutover) | `0875775, e4807b6` |
| 2026-08-28 | Horarios | Corrección | Corregir 500 al generar horario por lazy loading; Evitar que el generador se cuelgue con franjas/aulas reales; Corregir 500 en vista-maestra por lazy loading de aula | `f7e58a3, 55ce80f, 180ddad` |
| 2026-08-31 | Landing | Corrección | Dashboard KPIs y chat de portada rotos por CDN faltante | `4d1e438` |

### Septiembre 2026 — 112 entradas · 143 commits

| Fecha | Área | Tipo | Qué se hizo | Commits |
|---|---|---|---|---|
| 2026-09-01 | Calificaciones | Corrección | Corregir 500 en auditoria por relaciones faltantes; Eliminar N+1 en admin/boletines/grupo (hallazgo Medio) | `85724f7, dd9985f` |
| 2026-09-01 | Multi-colegio | Corrección | Aislar branding institucional por tenant_id; Quitar bypass del scope de tenant en 4 archivos | `3fab790, 623fc0e` |
| 2026-09-01 | Calidad | Corrección | Quitar Cache::flush() global al guardar página principal | `239a2b3` |
| 2026-09-01 | MINERD / SIGERD | Corrección | Hacer tenant-aware los 8 crons de alertas/pagos/sigerd | `c56aa7c` |
| 2026-09-01 | Seguridad | Corrección | Cerrar bypass de javascript: en comunicados (hallazgo Alto); Sanitizar SVG de logo/favicon institucional (hallazgo Medio) | `5f2b4fd, 9c45771` |
| 2026-09-01 | Horarios | Corrección | Índice faltante en horario_detalles.aula_id + 2 redundantes en asistencias | `5a3545f` |
| 2026-09-01 | Horarios | Mantenimiento | Eliminar módulo Scheduling huérfano (11 archivos, 9 tablas) | `55177e1` |
| 2026-09-01 | Calificaciones | Refactor | Unificar el cálculo de promedio en un solo servicio | `56c5d19` |
| 2026-09-02 | Calidad | Pruebas | Cobertura para generarCuotas, procesarMes y activarSuscripcion | `11fe2b7` |
| 2026-09-02 | WhatsApp | Corrección | Jobs sin contexto de tenant + caché de Setting sin aislar | `fa56a69` |
| 2026-09-02 | MINERD / SIGERD | Corrección | Inyección de fórmulas en exports + bug de datos en CSV | `5535e20` |
| 2026-09-03 | Gamificación | Corrección | Estadísticas globales sin filtro de tenant | `3a4bfdd` |
| 2026-09-03 | Carnet+ | Corrección | El endpoint de escaneo de la API móvil no restringía por rol | `37a27af` |
| 2026-09-03 | Classroom | Corrección | GradeSync sobrescribía notas + sin whitelist de archivos | `080673c` |
| 2026-09-03 | Riesgo académico | Corrección | N+1 real + 5ª reimplementación del promedio | `c1064ec` |
| 2026-09-04 | Classroom | Corrección | Impedir que un docente fije/elimine mensajes de otra clase; Exigir whitelist de tipo de archivo en subirArchivo() | `cc91e56, 6deaab2` |
| 2026-09-04 | ZuraAI | Corrección | Limitar tasa de las rutas de chat IA (throttle:30,1) | `6da8661` |
| 2026-09-04 | Multi-colegio | Corrección | Registrar en ActivityLog las acciones sobre tenants | `084d76c` |
| 2026-09-04 | Permisos | Corrección | Comparar contra el rol real 'super_admin', no 'SuperAdmin'; Exigir permiso específico en sub-recursos que solo tenían el gate genérico | `d2e4dea, 27e8388` |
| 2026-09-04 | Carnet+ | Corrección | Canal privado con autorización para escaneos de Carnet+; corregir nombres de rol en routes/channels.php; Dejar de filtrar nombre y grupo en el escaneo público del QR; Deduplicar escaneos repetidos del mismo carnet+evento | `fc528b1, 4d1e41a, e2806a4` |
| 2026-09-04 | Tiempo real | Corrección | Eliminar el doble prefijo private-/presence- en todo el sistema de canales | `4b8aaff` |
| 2026-09-04 | General | Documentación | Agregar CLAUDE.md con la regla de verificar antes de crear/modificar | `152da11` |
| 2026-09-04 | General | Corrección | Reemplazar el CDN de Tailwind por un build compilado con Vite | `5d65f4e` |
| 2026-09-04 | Cierre de año | Corrección | Restringir calcular-promociones al nivel de Dirección | `cb8f461` |
| 2026-09-04 | Reportes | Documentación | Reporte de la auditoría completa de ZuraEdu (2026-09-04) | `07186c6` |
| 2026-09-04 | Calificaciones | Corrección | Consolidar PromedioEstudianteService en 14 sitios más | `738cfae` |
| 2026-09-04 | Permisos | Documentación | Aclarar el nombre ambiguo "Curso" en dos controladores | `b6cf8d8` |
| 2026-09-04 | App móvil | Corrección | Agregar botón hamburguesa para el sidebar en móvil | `47d44dc` |
| 2026-09-04 | Respaldo | Función | Respaldo automático diario de BD + archivos (Gate blocker #1); Script de deploy con backup previo + rollback (Gate blocker #3, parte 2/3) | `3dcc0e6, ec67629` |
| 2026-09-04 | Calidad | Función | Detectar QUEUE_CONNECTION=sync en producción (Gate blocker #2) | `2fb3ec8` |
| 2026-09-04 | Calidad | Mantenimiento | Agregar workflow de GitHub Actions (Gate blocker #3, parte 1/3); Actualizar acciones a sus últimas versiones mayores | `9fc0bb7, 698a289` |
| 2026-09-04 | Despliegue | Documentación | Documentar decisión de no crear rama staging todavía (Gate blocker #3, parte 3/3) | `bf0b205` |
| 2026-09-04 | Estudiantes | Corrección | Caja/Finanzas ya no puede eliminar/crear/editar estudiantes (Gate warning #3) | `a8d8c50` |
| 2026-09-04 | Pagos | Función | Registrar y mostrar NCF/e-CF en el recibo de pago | `e177ff1` |
| 2026-09-04 | MINERD / SIGERD | Pruebas | Agregar cobertura al algoritmo de promoción (Roadmap P0 #1) | `af24ee1` |
| 2026-09-05 | WhatsApp | Función | WhatsApp al representante + paridad kiosco/API (Roadmap P0 #2) | `9680a41` |
| 2026-09-05 | Menú | Función | Menú admin colapsable por secciones, con íconos y mejor espaciado | `e26a01f` |
| 2026-09-05 | Calidad | Corrección | Fondos con degradado de card-header anulados silenciosamente | `31736df` |
| 2026-09-05 | Docentes | Función | Vista "Hoy" en el dashboard docente | `94de8bd` |
| 2026-09-05 | Dashboard | Función | Fusionar KPIs institucionales en el dashboard principal | `040ed6d` |
| 2026-09-06 | Carnet+ | Función | Vista "Hoy" con Carnet+, tareas pendientes y próximo pago | `e000eb3` |
| 2026-09-06 | Classroom | Función | Conexión de solo lectura Planificación → ZuraClass | `452cd45` |
| 2026-09-06 | General | Función | Sitio público Fase 1 por centro + fix visibilidad Homepage | `6f0e9bb` |
| 2026-09-06 | Calidad | Función | Fase 2 — modo_publico + orden de secciones; Centro de Administración unificado tipo Moodle; Cada institución ve su propio homepage en su dominio "/"; Carrusel de fotos + noticias y publicaciones | `f3caf92, 101fa63, df547a5, a9a1cf0` |
| 2026-09-06 | Estudiantes | Corrección | Mostrar el logo del tenant en la topbar de docente/padre/estudiante | `5d21e3c` |
| 2026-09-06 | General | Corrección | Destino configurable de los botones del Hero | `4e128a3` |
| 2026-09-06 | SuperAdmin | Función | Crear usuario administrador al crear una institución | `ec870cf` |
| 2026-09-06 | Marca | Corrección | Consolidar Galería y sincronizar los dos logos de marca | `31b871d` |
| 2026-09-06 | Calidad | Corrección | Minimo 5 fotos para carrusel y parrafo de descripcion | `ec567cd` |
| 2026-09-07 | Calidad | Corrección | URLs de imagenes relativas y evolucion del portal publico | `bbe885c` |
| 2026-09-07 | Servicios | Corrección | Permitir marcar el carrusel del sitio desde la pantalla de fotos | `a7209b0` |
| 2026-09-07 | Respaldo | Corrección | Activar el backup automatico real (S3 + 2 bugs que lo bloqueaban) | `6d8e07e` |
| 2026-09-07 | Planificación | Función | Vincular Plan de Clase con la capa curricular (PlanifUnidad/RA) | `23452fa` |
| 2026-09-07 | Seguridad | Corrección | SubstituteBindings antes de ResolveTenant permitia binding sin scope de tenant | `85f5a31` |
| 2026-09-08 | Calidad | Corrección | Contener el desborde del bloque de anuncios laterales | `6df2f6e` |
| 2026-09-08 | General | Función | Constructor visual de bloques del portal público (Fase 2) | `d5d350e` |
| 2026-09-09 | Calidad | Función | Completar el Centro de Administración (cobertura, módulos, dark mode, buscador) | `b4c17e3` |
| 2026-09-09 | WhatsApp | Función | Plantillas de mensajes editables por tenant (WhatsApp/email) | `07ae4eb` |
| 2026-09-10 | Matrículas | Función | Matriz de institución + preferencias de usuario (in-app/push) | `6e5f938` |
| 2026-09-10 | Multi-colegio | Función | Self-service de colores institucionales del tenant; Fase 0 -- unificar TenantFeature y ConfigInstitucional::moduloActivo | `bc7588d, 41c2213` |
| 2026-09-10 | Docentes | Función | Fase 6 -- vista "Hoy" para Docente y Padre en la app movil | `9e01165` |
| 2026-09-10 | Pagos | Corrección | Corregir 2 hallazgos reales de la auditoria Don Bosco (Seccion 6) | `7474365` |
| 2026-09-10 | Calificaciones | Corrección | Candado tecnico real para periodos cerrados (Don Bosco Seccion 4) | `6fdfa9e` |
| 2026-09-10 | Matrículas | Corrección | Validar cupo de grupo en los 4 puntos de entrada (Don Bosco Seccion 2) | `bb77b05` |
| 2026-09-11 | Pagos | Corrección | Separar exportar-calificaciones y exportar-pagos de ver-* (Don Bosco Sección 4) | `695b0b4` |
| 2026-09-11 | Calidad | Documentación | Agregar roadmaps de producto y gate de producción (sesión 2026-09-05/09-11); Reconciliar ZURAEDU_IMPLEMENTATION_ROADMAP.md -- 9 de 10 ítems ya implementados | `570b47f, f095b47` |
| 2026-09-11 | MINERD / SIGERD | Documentación | Corregir estado de tests del algoritmo MINERD de promoción (ya existían) | `3e4f7ba` |
| 2026-09-11 | Planificación | Función | Extender ZuraPlanificacionAI a la línea académica (Roadmap punto 10) | `b46cebe` |
| 2026-09-11 | Tiempo real | Documentación | Cerrar ZURAEDU_IMPLEMENTATION_ROADMAP.md -- los 10 ítems del roadmap quedan hechos | `40170a5` |
| 2026-09-11 | Matrículas | Documentación | Cerrar decisión de Reingreso — matrícula genérica ya basta | `61e17c8` |
| 2026-09-11 | Pagos | Función | Agregar RNC de la institución al recibo (cierra decisiones NCF/e-CF #2 y #3) | `a2525d1` |
| 2026-09-12 | Calificaciones | Función | Acta Final de Calificaciones y Boletin de Nota editables (Primer Ciclo) | `be4e9ad` |
| 2026-09-12 | Pagos | Documentación | Registrar categoria de contribuyente como seguimiento a la decision de RNC | `1fde114` |
| 2026-09-13 | Calificaciones | Corrección | Auditar ediciones de Completiva/Extraordinaria/Prueba Especial desde el Boletín de Nota; Auditar guardado de notas del docente (celda y masivo por período); Auditar los 6 endpoints de notas restantes (mismo gap) | `d23ad9e, d951466, fa5b5b9` |
| 2026-09-13 | Pagos | Corrección | Auditar edicion/borrado de pagos y cambios de asistencia; Auditar edicion, asignacion y revocacion de becas | `1155dca, 3186501` |
| 2026-09-13 | Matrículas | Corrección | Auditar cambio de estado, retiro y cambio de grupo | `2deefd9` |
| 2026-09-13 | Docentes | Corrección | Cerrar el bypass de periodo cerrado en el portal docente | `af57c3c` |
| 2026-09-13 | Docentes | Función | Plantilla descargable en blanco para Planes de Clase; Plantilla CSV + importar/exportar para Planificación Anual; Plantilla CSV + importar/exportar para Planificación Área Técnica | `8cfc299, d9ac3e8, 88ad3c7` |
| 2026-09-13 | Permisos | Corrección | Auditar cambio de rol, borrado, activar/desactivar y reset de contraseña | `8fe5701` |
| 2026-09-14 | Pagos | Corrección | Auditar edicion de empleado, borrado y pago mensual | `92a0bf7` |
| 2026-09-14 | Bienestar | Corrección | Auditar edicion y borrado de expedientes disciplinarios; Auditar edicion y borrado de casos | `44ec665, 8b83baa` |
| 2026-09-14 | Carnet+ | Corrección | Auditar suspension/reactivacion y borrado de carnets | `77368c9` |
| 2026-09-15 | Bienestar | Corrección | Auditar ficha medica y edicion/borrado de incidentes | `96e7e49` |
| 2026-09-15 | Estudiantes | Corrección | Auditar edicion de datos de identidad; Corregir @section('page-title') mal formado | `a7e1beb, 4c3e048` |
| 2026-09-15 | Docentes | Corrección | Auditar el borrado de evaluaciones | `b45ed23` |
| 2026-09-15 | Calidad | Corrección | Auditar borrado y cambio de visibilidad | `9db6517` |
| 2026-09-15 | MINERD / SIGERD | Corrección | Eager-load docente en bloque MINERD para evitar error 500 | `0d89c59` |
| 2026-09-16 | Calidad | Corrección | Resolver colision admin.secciones.* entre Grupo y sitio publico; Alinear .env.example, rotar logs y documentar OPcache | `d0bd5a8, 2a2e6b3` |
| 2026-09-16 | Horarios | Corrección | Usar sidebar compartido en horario y mis-estudiantes | `ec1844e` |
| 2026-09-16 | MINERD / SIGERD | Refactor | Extraer MinerdBoletinService, elimina 4 copias duplicadas | `4b3aa84` |
| 2026-09-16 | Estudiantes | Corrección | Usar PromedioEstudianteService en Acta y revision de promocion | `0695cec` |
| 2026-09-16 | General | Mantenimiento | Eliminar vista huerfana portal/encuestas/responder.blade.php | `44172c3` |
| 2026-09-16 | Classroom | Mantenimiento | Eliminar mensajesFijados() y div muerto, redundantes con ChatController | `9487d0b` |
| 2026-09-17 | Plataforma | Mantenimiento | Eliminar welcome.blade.php (scaffold de Laravel sin usar) | `1486926` |
| 2026-09-17 | Servicios | Refactor | Fusionar secciones Biblioteca y Logros en Vida Escolar | `d833c4a` |
| 2026-09-17 | Asistencia | Corrección | Conectar aprobacion de justificacion de ausencia con Asistencia | `bc27b41` |
| 2026-09-17 | Matrículas | Función | Permitir reingreso de estudiantes retirados | `4ec828d` |
| 2026-09-17 | Pagos | Función | Agregar recaudacion por concepto/grado/seccion al dashboard financiero | `b169e21` |
| 2026-09-18 | Reportes | Función | Agregar recibo PDF de suscripcion (GAP 10 del roadmap) | `8f7ba88` |
| 2026-09-18 | Docentes | Función | Seleccion real de docente en citas padre-docente | `cd84ae5` |
| 2026-09-30 | Carnet+ | Función | Control de abordaje de bus con Carnet+ (GAP 06 del roadmap) | `d02ae6d` |
| 2026-09-30 | Asistencia | Rendimiento | Eliminar N+1 en el index y corregir contador de tardanzas; Consultas de asistencia sargables y contadores con el enum real; Paginar y buscar en el servidor; arreglar enum e importacion; Quitar 50 indices redundantes e indice cubriente en asistencias | `bb4bc4e, 60b7cc5, 5d2866c, 1c00b8a` |
| 2026-09-30 | Calificaciones | Corrección | Evitar funciones con nombre dentro de la vista resumen; Quitar 'ciclo' indefinido del compact() de buildBoletinData | `cf5de77, 764f700` |
| 2026-09-30 | Calificaciones | Función | Panel de planilla anual del estudiante en la planilla academica; Boton "Abrir planilla" por asignatura y fin del N+1 en los indices | `5f1ff3e, 501e071` |
| 2026-09-30 | Gamificación | Corrección | Usar el enum real tarde/excusa en perfil, grupo, reportes, gamificacion y API | `a329218` |
| 2026-09-30 | MINERD / SIGERD | Rendimiento | ValidarNomina con conjunto hash en vez de in_array en el bucle | `ca68cb5` |
| 2026-09-30 | Pagos | Rendimiento | Resumen de admin/pagos en una sola consulta agrupada | `550c2c0` |
| 2026-09-30 | Calidad | Corrección | No usar env() fuera de config/ (se ignoraba con config:cache en produccion) | `e5e1b05` |
| 2026-09-30 | Despliegue | Documentación | Ajustes de MySQL, regla de env() y medicion de OPcache | `8266277` |
| 2026-09-30 | Migración a TypeScript | Función | Workspace TypeScript y paquete compartido @zuraedu/shared; Esqueleto web Next.js 16, README y CI del workspace TypeScript | `02a621a, afb6898` |
| 2026-09-30 | Permisos | Función | API NestJS 11 + Kysely con auth Sanctum, permisos Spatie y filtro de tenant; Cache de sesion y permisos en la API (TTL corto, configurable) | `0206b53, 46a156c` |
| 2026-09-30 | Estudiantes | Rendimiento | Indice del listado paginado de estudiantes (tenant, borrado logico, orden); Conteo del listado de estudiantes con hint del optimizador (11,7 -> 1,6 ms); Forzar el indice del listado en el conteo del paginador (sin filtros) | `13a1007, 38e4e64, 0e2f6ce` |

---

## Octubre 2026 (detalle curado)

## 2026-10-01

| Área | Tipo | Qué se hizo | Resultado / impacto | Commits | Estado |
|---|---|---|---|---|---|
| Seguridad | Corrección | Las reglas exists:/unique: ahora respetan el colegio y el borrado lógico; TrustProxies ya no confía en cualquiera (X-Forwarded-For falsificada); el docente solo puede marcar matrículas del grupo de su asignación. | Antes se aceptaban ids de otros colegios y se podía falsificar la IP; ahora no. | `f088bcd, b7a2845, 46bc47b, fe20fc9` | Hecho |
| Migración a TypeScript | Función | API TypeScript (NestJS + Kysely): matrículas con cupo, notificaciones con push y tiempo real (Reverb), foto del estudiante, límite de peticiones; web de prueba bajo /nuevo; Nginx con TLS probado; alta, edición y borrado lógico de estudiantes con la auditoría de Laravel; contrato OpenAPI; Redis opcional; pruebas e2e contra MySQL en el CI. | Esqueleto con paridad respecto a Laravel; decisión de migrar cerrada por el usuario. | `e835955, f84c24d, bcb980c, 74131af, f837bc8, c5969ee, 77ae7a9, 5b0adaf, 15f24b0, d05e2f9, 0ce7d3d, c35f5e9, ff971dc, 59681df, 7ab8155, 78b9150, 87106ad, 0e231c5` | Hecho |
| Migración a TypeScript | Documentación | Benchmark de carga concurrente Laravel vs TypeScript, evaluación de costo operativo (CPU y memoria por petición), inventario de efectos secundarios de PHP que una escritura en TypeScript no dispara y medición de lecturas. | Datos para decidir tamaño de servidor por colegio; ninguna lectura de Laravel justifica migrar solo por rendimiento. | `5d7ecd3, 7020eb9, a2aa32f, 8c0994c, 9c0c5d2, 2fa905b, 4428aa1` | Hecho |
| Despliegue | Corrección | deploy.sh y rollback.sh recargan PHP-FPM y explican los fallos; el código exige PHP 8.3+; dos despliegues en el mismo segundo ya no chocan. | Despliegue y reversión más seguros, con runbook del primer despliegue. | `5b732df, 51ede9e, e3e7739` | Hecho |
| Rendimiento | Rendimiento | Validación de nómina SIGERD sin hidratar modelos; el aviso de «sin matrícula» del listado de estudiantes usa el índice del listado. | 636 ms → 44 ms con 4.949 matrículas. | `efb0925, 1e8f127` | Hecho |
| Cierre de año | Corrección | Un estudiante sin notas ya no pasa a repetidor por defecto; queda pendiente de decisión. | Evita repetir por error a quien no tiene calificaciones. | `265b0fc` | Hecho |

## 2026-10-02

| Área | Tipo | Qué se hizo | Resultado / impacto | Commits | Estado |
|---|---|---|---|---|---|
| Calendario | Función | Avisar eventos a padres, docentes y personal (mensajería + notificación + correo) con .ics y enlace a Google Calendar; a grupos específicos y a un grado completo. | El calendario académico llega a las familias. | `9b0cea7, 8451bd7, 3321c40, faadb0c` | Hecho |
| Cierre de año | Función | Traslado automático de fin de año: los promovidos avanzan, los no promovidos repiten, el último grado egresa; el traslado manual valida el grupo y respeta la carrera. | Cierre de año ejecutado en la base real. | `30b45e2, a0db08b` | Hecho |
| Concurrencia | Corrección | Operaciones atómicas bajo concurrencia: doble clic en matrícula, alta e importación con cupo, confirmación de pagos y cuotas, ventas/recargas de cafetería, matrícula masiva. | Desaparecen avisos y cargos duplicados, ventas aprobadas de más y errores 500 por unicidad. | `98f7833, 41076b7, 1a6702f, 6aaa05b, 59dd5ec` | Hecho |
| Integraciones | Función | Integración con Odoo por centro educativo (solo agregar credenciales). | Sin probar contra un Odoo real. | `22fc2d6` | Hecho (sin probar en real) |
| Rendimiento | Pruebas | Prueba de carga en Linux (Nginx + PHP-FPM + MySQL + Redis) como flujo manual de GitHub Actions; panel del estudiante 41 → 33 consultas. | Unas 25 páginas/s en 4 vCPU. | `0bc3066, e775772, 0947a75, 72ff0e8, 52380a4, 0eadc2c` | Hecho |
| Marca | Función | Logotipo ZuraEdu y recursos (favicon, iconos, og-image, app móvil); pie «Todos los derechos reservados por ZuraEdu» en paneles, portales, acceso, PDF y correos; sin nombres de colegio escritos a mano. | Marca única y comprobada por pruebas automáticas. | `0aa436e, b103a81, f599250, db40323` | Hecho |
| Carnets | Corrección | Carnet con logo de ZuraEdu, PDF con tablas (CR80 real) y QR generado en el servidor, también en «Mi carnet». | El PDF salía casi vacío y sin QR. | `72eb9d4, 1d65252` | Hecho |

## 2026-10-03

| Área | Tipo | Qué se hizo | Resultado / impacto | Commits | Estado |
|---|---|---|---|---|---|
| Classroom | Corrección | Portal de padres (siempre 403), quiz (rutas inexistentes), API móvil, IDOR entre docentes, período cerrado, tabla de entregas y cabecera duplicada de DataTables en todo el sistema. | Flujo completo verificado en navegador real. | `2b3529f, bef4b8e, c8be421` | Hecho |
| Calificaciones | Corrección | Notas del docente: matrículas ajenas al grupo, PDF del boletín que nunca se generaba, símbolos rotos y errores de celda. | Seguridad e integridad de las notas. | `ea316c2, 8fce0bb` | Hecho |
| Respaldo | Función | Respaldo automático con hora elegida, carpeta local y Google Drive; pantalla propia del superadministrador. | Cerró un hueco: el administrador de un colegio podía bajar los datos de todos. Drive real sin probar. | `ecd2e06` | Hecho (Drive sin probar) |
| Menú y portada | Función | Menú filtrado por rol, accesos rápidos con icono, atajos de la app por rol, landing nueva y 8 guías (flyers) por rol en pantalla, impresión y PDF. | 482 enlaces del menú recorridos con 21 roles; Personal Administrativo de 16 a 10 enlaces. | `ecd2e06, 9f5b927, 8602c76` | Hecho |
| Rendimiento | Rendimiento | Excel de pagos 3 veces más rápido y PDF con tope; CSS y JavaScript del panel y del portal a archivos con versión. | Pagos: 9,7 s → 2,9 s. Páginas: admin ~255 → ~100 KB, portal ~63 → ~20 KB incrustados. | `fe9e911, 11b7346, b6f1d41, bc0c5c8, 431ffaf, 11308b0` | Hecho |
| Permisos | Corrección | Personal Administrativo puede ver estudiantes (solo lectura) y los botones de crear/editar se ocultan a quien no puede. | Dejaron de llevar a 403. | `0698167, 513843c` | Hecho |
| Respaldo | Función | Copia de los datos del propio colegio (ZIP de CSV) para Administrador y Dirección; datos sensibles solo para Dirección; auditada. | No sirve para restaurar: solo consulta y archivo. | `3530ede` | Hecho |
| Soporte y mensajería | Corrección | Chat de soporte: las respuestas del administrador no llegaban (3 causas) y ahora hay respuesta automática y número de soporte; el worker procesa todas las colas (WhatsApp y notificaciones nunca salían). | La respuesta llega al cliente en ~1 s; 387 notificaciones acumuladas se procesan. | `4a2550e, 518d2e1` | Hecho |
| Documentación | Documentación | Documento Word de la revisión integral (menús, accesos, landing, guías, respaldo, rendimiento, pruebas). | docs/ZuraEdu_Revision_Integral_2026-10-03.docx | `18daf9d, 784b6b9` | Hecho |

## 2026-10-04

| Área | Tipo | Qué se hizo | Resultado / impacto | Commits | Estado |
|---|---|---|---|---|---|
| WhatsApp | Corrección | Formulario de Twilio/Meta (campos ocultos que se pisaban), validación del Account SID, API Key (SK) con campo propio, plantilla de Twilio (Content SID) y mensajes de error en español. | La prueba de envío explica el motivo exacto del fallo. | `2e1fdbb, 3b3a7ed, eadc3dd, cc04160, d02722c` | Hecho (falta credencial real) |
| Cafetería | Corrección | Permisos propios para recargar/vender/ajustar, tope de monto, anti doble envío, auditoría; los botones no abrían el formulario (modales fuera del contenedor de Alpine); buscador de estudiante; avisos de validación en español. | Defecto de origen del módulo; ahora hay prueba en el CI que impide repetirlo en las ~300 pantallas. | `46ea8c5, b807a4d, 5d8353d` | Hecho |
| Móvil | Corrección | Botón «Entrar» visible en el celular, páginas sin desbordes de 320 a 1280 px, guía de instalación para iPhone/iPad/Chrome iOS con entrada permanente en accesos rápidos. | Panel, portales y páginas públicas sin desborde horizontal. | `c6708e4, b78e957, 9b59eef` | Hecho |
| Marca | Corrección | El acceso en el dominio de la plataforma muestra la marca ZuraEdu y no el logo del colegio por defecto. | Con el dominio propio de un colegio sigue su logo. | `bde7406, c93eaae` | Hecho |
| Rendimiento | Rendimiento | KPIs del dashboard del administrador con caché de 2 minutos. | El ranking de grupos (~85 ms) ya no se calcula en cada carga. | `b90aa8e` | Hecho |
| Operación | Operación | Túnel de pruebas (ngrok) con perfil temporal, OPcache, 4 procesos PHP, compresión y caché; cierre automático a las 8 h. | Para probar desde el celular fuera del local; sin commit (herramientas locales). | — | Hecho |

## 2026-10-05

| Área | Tipo | Qué se hizo | Resultado / impacto | Commits | Estado |
|---|---|---|---|---|---|
| Calidad | Pruebas | La comprobación de botones de Alpine fuera de su contenedor cubre todos los roles de la prueba de rutas. | Prevención de un defecto silencioso. | `33bdc2a` | Hecho |
| Detalle de pantallas | Corrección | Ficha del estudiante (error 500 con cualquier calificación), registro de calificaciones sin selección y reporte de asistencia por estudiante. | Hallados al recorrer 51 pantallas de detalle con datos reales. | `ea7a2b4` | Hecho |

## 2026-10-06

| Área | Tipo | Qué se hizo | Resultado / impacto | Commits | Estado |
|---|---|---|---|---|---|
| Calidad | Corrección | Tres vistas con error de sintaxis de Blade (editar encuesta, intento de evaluación del docente, planificaciones del hijo) y prueba que compila todas las vistas. | Editar encuesta no abría desde que se creó. | `0958833` | Hecho |
| Portal docente | Corrección | Estadísticas de asistencia (array_max no existe en PHP), rúbricas y asistente con carga diferida; prueba que detecta funciones inexistentes en las vistas. | Portal docente sin errores 500 en 57 pantallas de detalle. | `928bbb1` | Hecho |
| Documentación | Documentación | Bitácora de trabajo lista para importar en Notion: base de datos (CSV) y página (Markdown) con todo el historial, desde marzo; mayo-septiembre agrupado automáticamente por día y área desde git. | docs/bitacora/. No se pudo escribir directamente en Notion (sin conector). | `5e1deb6` | Hecho |

## Pendientes (dependen de ti)

- [ ] Poner la contraseña de aplicación de Gmail en MAIL_PASSWORD y probar el envío de correo. — *Hoy los correos fallan con 530 Authentication Required.*
- [ ] WhatsApp: Content SID de Twilio de una plantilla aprobada (o Phone Number ID de Meta). — *Sin plantilla Twilio responde «ContentSid Required».*
- [ ] Google Drive: crear credenciales de Google y conectarlas en /superadmin/respaldos. — *La conexión real no se ha probado.*
- [ ] Tarea programada del servidor llamando a schedule:run cada minuto. — *Sin ella no hay respaldo automático ni procesamiento de colas.*
- [ ] Decidir el texto de «Soporte 24/7» y «menos de 2 minutos» en la landing. — *Son promesas comerciales.*
- [ ] Al desplegar: php artisan migrate, npm run build y subir public/css y public/js. — *Incluye permisos de cafetería y respaldo.*
- [ ] Restauración por colegio (hoy solo existe la copia para consulta). — *Requiere diseño: es delicado con varios colegios.*
- [ ] Recorrer pantallas de detalle de los portales con más datos y las que no tienen modelo en la ruta. — *Cobertura parcial.*

## Lecciones que conviene recordar

- **Nunca dos corridas de pruebas a la vez** contra la misma base de datos de pruebas: dan `QueryException` falsos.
- **Un defecto silencioso no se ve en consola:** Alpine no procesa lo que queda fuera de un `x-data`; por eso hay una prueba en el CI.
- **Las pantallas de detalle no las ve `RouteSmokeTest`** (solo rutas sin parámetros): hay que recorrerlas con datos reales.
- En Blade, `{{ $x }}h@endif` (directiva pegada a una letra) **no se reconoce**; y `@json([...])` de varias líneas puede no compilar: calcular antes en `@php`.
- **Caché de rutas** en el servidor de pruebas: tras añadir una ruta hay que regenerarla o da error 500.
- Al mover CSS/JS fuera de un layout, **buscar pruebas que lean ese archivo** (ya pasó dos veces).
- Credenciales y contraseñas: **nunca en el chat ni en archivos del repositorio**; se escriben directo en la pantalla o en el `.env`.

## Documentos relacionados

- `docs/ZuraEdu_Revision_Integral_2026-10-03.docx` — revisión integral (Word).
- `docs/BACKUP_ZURAEDU.md` — respaldo (hora, carpeta local, Google Drive).
- `docs/PROCESO_MARCA_ZURAEDU.md` — proceso de la marca.
- `DEPLOY.md` — despliegue.
