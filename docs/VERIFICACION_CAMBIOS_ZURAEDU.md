# Verificación completa de ZuraEdu — 2026-09-04

Auditoría solicitada por el usuario siguiendo el checklist de
[`PROMPT_AUDITORIA_COMPLETA_ZURAEDU.md`](PROMPT_AUDITORIA_COMPLETA_ZURAEDU.md).
Ejecutada en 6 auditorías paralelas de solo lectura (ningún archivo fue
modificado durante esta auditoría). Cobertura: RBAC/multi-tenant/seguridad,
académico núcleo, calificaciones/evaluación, Carnet+/Classroom/portales,
finanzas/SIGERD/SuperAdmin, UI-UX/rendimiento/BD/rutas.

Clasificación usada: 🟢 existe y funciona · 🟡 existe pero incompleta ·
🟠 existe pero tiene errores · 🔴 no existe · 🔵 existe y debe mejorarse ·
⚫ no aplica.

## 1. Resumen ejecutivo

El sistema está, en conjunto, **sólidamente implementado**: RBAC con 21
roles reales, aislamiento multi-tenant en 148 modelos, 1456 rutas sin
duplicados y protegidas por grupo de middleware, cero tablas/migraciones
duplicadas, CSRF/XSS/mass-assignment/SQL-injection cubiertos, portales de
padre y estudiante con verificación real de la relación en BD (no solo por
ID de URL). Todos los hallazgos de auditorías anteriores de esta misma
sesión siguen corregidos y vigentes.

Se encontraron **4 hallazgos de severidad alta** (requieren decisión antes
de continuar agregando módulos) y **varios de severidad media/baja**
listados en la sección 3. Ninguno es una regresión de esta sesión — todos
son deuda preexistente descubierta ahora.

## 2. Hallazgos de severidad alta

### H1 — Dos implementaciones independientes de "promoción" con reglas distintas
- **Dónde**: `app/Http/Controllers/Admin/CierreAnoController.php:701`
  (regla: promedio ≥ 60, sin verificar asistencia) vs.
  `app/Services/RegistroAcademicoService.php:285-341` (regla MINERD:
  promedio ≥ 65 **y** asistencia ≥ 75%, con estado `condicionado` para
  2do ciclo).
- **Por qué importa**: ambas escriben a la misma tabla `promociones` vía
  `updateOrCreate` sobre la misma fila — la que se ejecute después pisa
  silenciosamente el resultado de la otra. La ruta B
  (`POST registro/{grupo}/calcular-promociones`) solo requiere el permiso
  `ingresar-calificaciones` (docentes/registro académico), mucho más amplio
  que el gate de Dirección que protege el cierre de año oficial (ruta A).
  Un estudiante con promedio 62 y 70% de asistencia sale "promovido" por A
  y "no_promovido" por B.
- **Pruebas**: la ruta A tiene 9 tests (`CierreAnoRegressionTest`); la ruta
  B **no tiene ningún test**.
- **Recomendación**: unificar en un solo punto de verdad (extender
  `RegistroAcademicoService::calcularPromocion()`, que tiene la lógica
  MINERD más completa, y hacer que `CierreAnoController` la use), o como
  mínimo alinear los umbrales y restringir el permiso de
  `registro.calcular-promociones` al mismo nivel que el cierre de año.

### H2 — QR de Carnet+ estático + endpoint público que filtra PII
- **Dónde**: `app/Services/CarnetQrService.php` — `qrContent()`/
  `resolverQrPermanente()` usan un token generado una sola vez, sin
  expiración ni rotación. `routes/web.php:711-714`
  (`/checkin/scan/{qrToken}`, pública, sin auth) devuelve nombre completo,
  número de carnet, tipo y grupo dado ese token
  (`CarnetCheckinController::scanPublico`).
- **Por qué importa**: el mismo token está impreso en el carnet físico. Una
  foto del carnet (una sola vez) permite consultar esos datos de forma
  indefinida sin sesión ni límite de tiempo.
- **Detalle adicional**: ya existe un token dinámico de corta vida
  (`generarTokenDinamico()`/`resolverTokenDinamico()`, TTL 300s) construido
  para esto pero **nunca conectado** al flujo real de escaneo — es código
  muerto hoy.
- **Recomendación**: usar el token dinámico en el endpoint público, o
  reducir la respuesta a solo válido/inválido sin nombre ni grupo.

### H3 — Sub-recursos admin protegidos solo por el gate genérico, no por permiso específico
- **Dónde**: `routes/admin/sistema.php` — `school-years`, `periodos`
  (incluye cerrar/reabrir), `areas`/`especialidades`, `malla-curricular`,
  `sistema/actividad` (log de auditoría), `sistema/estadisticas`,
  `sistema/reporte-ejecutivo`/`reporte-anual`/`ficha-institucional`; y
  `routes/admin/reportes.php:57-77` — `alertas/generar-*` y `calendario/*`.
  Ninguno tiene `can:` en la ruta ni `authorize()` en el controlador.
- **Por qué importa**: cualquier rol admin-capaz (incluyendo Biblioteca o
  Recepción, que en `RolesSeeder` NO tienen `gestionar-school-years` ni
  `gestionar-periodos`) puede, escribiendo la URL directamente, cerrar/
  reabrir un período académico, eliminar un año escolar, borrar eventos del
  calendario institucional, disparar generación masiva de alertas, o leer
  el log de auditoría completo del tenant. El permiso
  `gestionar-school-years` existe en el seeder pero no se usa en ninguna
  ruta.
- **Recomendación**: agregar `can:gestionar-school-years` /
  `can:gestionar-periodos` / permisos equivalentes a estas rutas.

### H4 — Tailwind CSS vía CDN "Play" en producción
- **Dónde**: `resources/views/layouts/admin.blade.php:155` y
  `resources/views/landing.blade.php:18` —
  `<script src="https://cdn.tailwindcss.com"></script>`, sin `defer`,
  bloqueante.
- **Por qué importa**: es exactamente el script que Tailwind documenta como
  "no apto para producción" — recompila todo el CSS de utilidades en el
  navegador en cada carga de página. Como la app no es SPA (cada módulo es
  una recarga completa), esto se paga en cada clic de menú — coincide
  directamente con el síntoma de lentitud que motivó esta sección del
  checklist.
- **Recomendación**: compilar Tailwind vía PostCSS + Vite (el proyecto ya
  usa Vite) y servir un `.css` estático — no requiere infraestructura
  nueva.

## 3. Hallazgos de severidad media

- **PromedioEstudianteService no se usa en 6 controladores / 15 sitios**
  (`Portal/PortalDocenteController.php:4506`,
  `Admin/PerfilEstudianteController.php` ×7,
  `Admin/ReportesController.php` ×5,
  `Admin/PerfilDocenteController.php:251`,
  `PortalRepresentanteController.php:95`) — reimplementan
  `avg('nota_final')` directo sobre `CalificacionAcademica`, sin el
  fallback a notas técnicas que el servicio centralizado sí tiene. Un
  estudiante 100% del área técnica probablemente ve el promedio en blanco
  en el perfil de estudiante (admin), reportes de grupo, portal de padres y
  la vista del docente por asignación.
- **Sin protección contra doble-escaneo en Carnet+**
  (`CarnetCheckinController::scan()`, `CarnetApiController::scan()`) — dos
  escaneos seguidos del mismo QR generan dos entradas y dos notificaciones
  WhatsApp duplicadas al padre.
- **Reingreso no existe como flujo distinto** — un estudiante que regresa
  se re-matricula por el flujo genérico, sin ningún flag o historial que
  reconozca que es un reingreso vs. matrícula nueva.

## 4. Hallazgos de severidad baja / notas para discusión

- Carnet+ (control de entrada/salida física) está desconectado del módulo
  de Asistencia académica — son sistemas paralelos; puede ser diseño
  intencional (seguridad física ≠ asistencia académica) pero no está
  documentado como tal.
- SIGERD solo exporta (CSV/Excel/PDF) y valida — no hay importación ni
  sincronización automática con el sistema real del MINERD. Consistente con
  el nombre del servicio (`SigerdExportService`), pero vale confirmar si es
  la expectativa real del negocio.
- Ambigüedad de nombre: "Curso" se usa para dos conceptos distintos
  (sinónimo de `Grupo` en el wizard académico, y "Curso Técnico" dentro de
  Bachillerato Técnico) — no es un bug, pero es fuente de confusión.
- Responsive design con cobertura de `@media` notablemente escasa en
  `layouts/superadmin.blade.php` (1 breakpoint) comparado con el resto.
- Búsqueda de estudiantes/docentes por nombre usa `LIKE '%term%'` sin
  índice — imperceptible hoy, se notará si un tenant crece a miles de
  estudiantes.
- Validación de subida de archivos: 18 puntos de subida detectados, no se
  verificó whitelist de extensión/mimetype 1:1 en cada uno (no confirmado
  como vulnerabilidad, solo no verificado exhaustivamente).
- Cobertura de tests desigual en el núcleo académico: CRUD de Estudiantes,
  Docentes, Grupos, Matrículas y SchoolYear/Periodo no tienen test de
  regresión dedicado (aparecen indirectamente en otros tests).

## 5. Todo lo demás — 🟢 confirmado con evidencia

Verificado con evidencia real (archivo, ruta, permiso, rol, test), no por
el nombre de un archivo:

- **RBAC**: 21 roles reales, 8 Gates, cobertura `can:`/`permission:` en
  39/39 archivos de rutas admin.
- **Multi-tenant**: 148 modelos con `BelongsToTenant`; las 4 excepciones
  (`Tenant`, `Subscription`, `TenantFeature`, `SupportSession`) verificadas
  como justificadas caso por caso.
- **Seguridad transversal**: CSRF sin excepciones, XSS (`SanitizeInput`)
  activo, cero mass-assignment sin `$fillable`, cero SQL injection por
  interpolación.
- **Académico núcleo**: centros/niveles/grados/secciones/períodos,
  estudiantes, expediente estudiantil (5 tabs reales: representantes,
  conducta, salud, seguimiento, reconocimientos), matrícula (con
  `lockForUpdate` para concurrencia), traslado (con test de regresión),
  docentes, asignaturas, horarios (con validador de integridad dedicado y
  8 tests), asistencia manual.
- **Calificaciones**: competencias/indicadores/evaluaciones MINERD,
  recuperación (`FINAL = P + R`, topada correctamente), boletines (12 + 10
  tests de regresión, bloqueo por deuda), actas, certificaciones,
  observaciones docente.
- **Carnet+**: check-in/check-out, notificación WhatsApp a padres,
  historial, reportes, autorización por permiso `ver-servicios`, canal de
  broadcasting corregido en la sesión anterior.
- **Classroom**: aulas virtuales, entregas (con validación de tipo de
  archivo), GradeSync (bug de sobrescritura ya corregido), duplicar aula,
  videollamada real (Jitsi, sala con sufijo aleatorio), chat (IDOR ya
  corregido).
- **Portal de padres**: relación representante↔hijo verificada en BD en
  los ~40 métodos revisados, sin una sola excepción encontrada.
- **Portal del estudiante**: diseño que elimina la clase de bug IDOR
  (resuelve el estudiante desde el usuario autenticado, no desde un ID de
  URL, en casi todas las rutas).
- **Finanzas**: pagos, cuotas, becas, bloqueo de boletines por deuda,
  saldo consolidado, idempotencia de Stripe/CardNet.
- **SIGERD**: exportación, validación, historial de envíos, sanitización
  CSV injection.
- **SuperAdmin SaaS**: gestión de tenants, suscripciones/planes, ~30
  feature flags, impersonación auditada en ActivityLog.
- **UI/UX**: 7+ dashboards contextuales reales por rol (no el mismo
  dashboard reciclado), modo oscuro completo.
- **Base de datos**: 236 migraciones, cero duplicadas, FKs indexadas
  automáticamente por InnoDB, webhooks de pago protegidos por firma pese a
  no tener middleware de ruta.
- **Rutas**: 1456 rutas, cero nombres duplicados, cero pares URI+método
  duplicados, protección consistente por grupo de middleware.

## 6. Recomendación de orden de corrección

Por severidad e impacto/esfuerzo:

1. H4 (Tailwind CDN) — el más barato de arreglar y con el mayor impacto en
   la experiencia percibida (probablemente la causa raíz de "se siente
   lento").
2. H3 (permisos faltantes en sub-recursos admin) — barato, cierra un hueco
   de autorización real.
3. H1 (doble implementación de promoción) — requiere una decisión de
   negocio (qué regla es la correcta: 60 sin asistencia, o 65+75%
   asistencia+condicionado) antes de tocar código.
4. H2 (QR estático + endpoint público) — requiere decidir el approach
   (token dinámico vs. respuesta reducida) y probar con el kiosco/app real.
5. Hallazgos de severidad media (PromedioEstudianteService no consolidado,
   dedupe de escaneo, Reingreso).
6. Hallazgos de severidad baja, a criterio.

No se modificó ningún archivo durante esta auditoría — es puramente de
lectura, tal como pide la regla de "primero AUDITA, después CORRIGE" del
checklist.

---

# Re-verificación — 2026-10-06

Actualización de la auditoría del 2026-09-04. Solo lectura: **no se modificó
ningún archivo de código** (únicamente se añadió esta sección).
Alcance de esta pasada: re-comprobar en el código actual cada hallazgo
anterior (H1–H4 y medios), análisis automático de las 1525 rutas
(`php artisan route:list --json`), aislamiento multi-tenant (modelos,
`DB::table`, `withoutTenant`), seguridad transversal (subidas, rutas
públicas, webhooks) y estado de migraciones. **No** se revisó de nuevo
módulo por módulo todo el dominio académico, finanzas, SIGERD ni Classroom:
para eso sigue vigente la auditoría del 2026-09-04, salvo lo indicado abajo.

## 1. Estado de los hallazgos anteriores

| ID | Hallazgo | Estado hoy | Evidencia |
|----|----------|-----------|-----------|
| H4 | Tailwind por CDN en producción | 🟢 Corregido | `resources/views/layouts/admin.blade.php:52` y `landing.blade.php:20` ya no cargan el CDN; CSS compilado en `resources/css/admin.css` y `landing.css` |
| H3 | Sub-recursos admin sin permiso específico | 🟢 Corregido | `routes/admin/sistema.php:69` (`can:gestionar-school-years`), `:76` (`can:gestionar-periodos`, incluye cerrar/reabrir); alertas y calendario protegidos en `routes/admin/reportes.php` |
| H2 | QR estático + endpoint público que filtra PII | 🟢 Corregido | `CarnetCheckinController::scanPublico` solo devuelve `valido`, `numero_carnet`, `tipo`; ruta con `throttle:30,1` |
| — | Doble escaneo Carnet+ | 🟢 Corregido | `CarnetApiController.php:~190` usa `CarnetAcceso::recienteParaCarnet()` y responde `duplicado: true`; el controlador web también (`CarnetCheckinController.php:94`) |
| — | Reingreso sin flujo propio | 🟡 Parcial | `MatriculaController.php:104-124,290-295` ya lista candidatos a reingreso con fecha/motivo de baja; no se verificó historial dedicado |
| — | `PromedioEstudianteService` no usado | 🔵 Mejorado, no completo | Ya lo usan 10 archivos; quedan `avg('nota_final')` directos en `PortalRepresentanteController.php:95`, `PortalEstudianteController.php:2009,2015`, `AcademicRiskScoreService.php:187` y 4 en `SistemaController.php` |
| H1 | Dos reglas de promoción distintas | 🟠 **Sigue abierto** | `CierreAnoController.php:790` promueve con `>= 60` sin asistencia; `RegistroAcademicoService.php:19-20` usa 65 + 75 % de asistencia. Mitigado: `calcular-promociones` ahora exige `can:acceso-direccion` (`routes/admin/academico.php:~200`), pero las dos reglas siguen escribiendo en `promociones`. Requiere decisión de negocio |

## 2. Hallazgos nuevos

### N1 — CRÍTICO (🟢 CORREGIDO 2026-10-06): "Limpiar datos" borraba los datos de TODOS los colegios
- **Dónde**: `app/Http/Controllers/Admin/SistemaController.php:288-361`
  (`limpiarDatos`), ruta `POST admin/sistema/limpiar-datos`
  (`routes/admin/sistema.php:171`, gate `can:solo-administrador`), botón en
  `resources/views/admin/sistema/index.blade.php:664`.
- **Qué hace**: `DB::table($tabla)->truncate()` sobre `calificaciones`,
  `calificaciones_academicas`, `asistencias`, `matriculas`, `asignaciones`,
  `grupos`, `horarios`, `estudiantes`, etc., con `FOREIGN_KEY_CHECKS=0`.
  `TRUNCATE` ignora el global scope de tenant. El borrado de usuarios
  (`DB::table('estudiantes')->pluck('user_id')`) tampoco filtra por tenant.
- **Impacto**: el Administrador de **un** colegio que escriba `CONFIRMAR`
  borra los datos académicos y estudiantes de **todos** los colegios de la
  plataforma, y deja la integridad referencial desactivada si algo falla a
  mitad (no hay transacción ni `try/finally` que reactive el chequeo).
- **Pruebas**: ninguna (`grep` en `tests/` sin resultados).
- **No ejecutado** durante la auditoría (regla de no destrucción).
- **Corrección aplicada**: `limpiarDatos` ahora borra con `where tenant_id = tenant_id()` dentro de `DB::transaction`, restaura `FOREIGN_KEY_CHECKS` en `finally`, limita el pivote `estudiante_representante` (sin `tenant_id`) por los estudiantes del colegio y filtra los usuarios por tenant. Prueba: `tests/Feature/LimpiarDatosAislamientoTenantTest.php` (3 pruebas: dos colegios, scope `todo` y `estudiantes`, y confirmación obligatoria). Limitación conocida: con las claves foráneas desactivadas, tablas hijas no listadas (p. ej. `promociones`) del mismo colegio pueden quedar huérfanas, igual que antes.
- **Propuesta original**: sustituir `truncate()` por `->where('tenant_id',
  tenant_id())->delete()` dentro de una transacción, en orden de
  dependencias, y filtrar los usuarios por tenant; añadir prueba con dos
  tenants. Alternativa: retirar la función y dejarla solo para SuperAdmin.

### N2 — Medio (🟢 CORREGIDO 2026-10-06, salvo el último punto): rutas públicas con detalles a revisar
**Corrección aplicada**: `/demo/{rol}` con `throttle:20,1` (`routes/web.php`);
`CardNetController::notify` registra solo `OrderId` y `ResponseCode`, y
`CardNetService::verifyNotification` ya no escribe la firma esperada en el
log; `admin/tenant-chat/clear` exige `can:acceso-direccion`. Prueba:
`tests/Feature/AuditoriaN2RutasPublicasTest.php` (cubre chat y límite del
demo; el log de CardNet no tiene prueba). **Sigue pendiente**: confirmar que
los usuarios demo viven en un tenant aislado sin datos reales.
Descripción original:
- `GET /demo/{rol}` (`routes/web.php:88`) inicia sesión sin contraseña como
  docente/padre/estudiante demo, **sin `throttle`** (el `/demo` sí lo tiene).
  Depende de `Setting demo_activo`; `.env.production` tiene
  `DEMO_MODE_ENABLED=true`. Confirmar que los usuarios demo pertenecen a un
  tenant aislado y sin datos reales.
- `POST /cardnet/notify` (`CardNetController.php:37`) escribe en el log
  `$request->all()` completo antes de verificar la firma: contenido no
  autenticado en logs. La firma sí se verifica después.
- `DELETE admin/tenant-chat/clear` (`TenantChatController.php:27`) borra todo
  el chat del tenant y solo exige el acceso admin genérico.

### N3 — Bajo (🟢 CORREGIDO 2026-10-06): consultas `DB::table` sin `tenant_id` explícito
**Corrección aplicada**: las 7 consultas de `RendimientoController` y las 2 de
`RendimientoCache::recalcularParaGrupo` filtran ahora por
`tenant_id = tenant_id()` en la tabla base; además `RendimientoController`
solo acepta un `grupo_id` del navegador si es un grupo del colegio. Prueba:
`tests/Feature/RendimientoAislamientoTenantTest.php` — con dos colegios y el
mismo `school_year_id`, el promedio por área solo cuenta las notas del
colegio actual; verificada: **falla sin la corrección** (cuenta 2 notas en
vez de 1) y pasa con ella. Descripción original:
`RendimientoController.php:203-277` (3 consultas de promedio por área) y
`RendimientoCache.php:57-71` filtran por `school_year_id`/`grupo_id`, que son
IDs propios del tenant, así que hoy no cruzan datos; pero no tienen
defensa en profundidad como sí la tienen `KpiDashboardService.php:221,250`.
Recomendado añadir `where tenant_id`.

## 3. Verificado sin hallazgos (🟢)

- **Rutas**: 1525 rutas, 0 duplicadas por URI+método, 0 nombres duplicados.
  Solo 40 son públicas (login, registro/inscripción con throttle, webhooks
  Stripe/CardNet con firma, galería, sitio, health). De las rutas
  `/admin/*`, solo **68** dependen únicamente del gate genérico (antes eran
  decenas de sub-recursos); las sensibles (tickets, mensajes, riesgo)
  autorizan dentro del controlador (`abort(403)` y filtros por `tenant_id`).
- **Multi-tenant**: de 168 modelos, 13 no usan `BelongsToTenant`:
  `Tenant`, `Plan`, `Module`, `Subscription`, `TenantFeature`,
  `SupportSession` (globales o de plataforma, justificados) y
  `BackupConfiguracion`, `DeviceToken`, `EncuestaInteres`,
  `InsigniaEstudiante`, `PuntoEstudiante`, `MensajeDestinatario`,
  `SupportMessage` — **revisados caso por caso el 2026-10-06, ver
  apartado 3.1**: ninguno cruza datos entre colegios. Los 27 usos de `withoutGlobalScope(s)/withoutTenant` están
  en login, jobs, SuperAdmin y servicios de Carnet+ con filtro posterior.
- **Seguridad**: 0 modelos con `$guarded = []`; las subidas revisadas
  (`GaleriaController`, `HomepageController`, `PublicacionController`,
  `AuthApiController::uploadAvatar`) validan con `image|max:`; no se halló
  `eval/unserialize/exec` en `app/`.
- **Migraciones**: 255 archivos; `migrate:status` sin pendientes.
- **Webhooks**: Stripe con tabla de idempotencia
  (`stripe_webhook_events`); CardNet con verificación de firma.

### 3.1 Modelos sin `BelongsToTenant` — revisión (2026-10-06)

Solo lectura; sin cambios de código. Resultado: 🟢 los 7 son seguros hoy.

| Modelo | Por qué no lleva el trait | Cómo queda aislado (evidencia) |
|--------|---------------------------|--------------------------------|
| `PuntoEstudiante` | Hijo de `Matricula` (`matricula_id`) | Todas las consultas (`GamificacionController`, `Api/GamificacionApiController`, portales docente/estudiante/padre) filtran por `matricula_id` obtenido de `Matricula`, que sí tiene scope; las cuentas globales usan `whereHas('matricula')`, que aplica el scope. Los `create` con `matricula_id` del navegador validan con `exists:matriculas,id`, que es consciente del colegio (`TenantPresenceVerifier`), y los de docente además comprueban que la matrícula sea de su grupo |
| `InsigniaEstudiante` | Igual que el anterior | Mismo patrón; solo se escribe con `firstOrCreate` por `matricula_id` ya resuelto |
| `MensajeDestinatario` | Hijo de `Mensaje` (que sí tiene el trait) | Se lee por `destinatario_id = auth()->id()` o por `mensaje_id` de un `Mensaje` con scope; los destinatarios al enviar (`ComunicacionesController`, `Api/MensajesApiController`, `MensajesPortalController`) validan `exists:users,id`, consciente del colegio |
| `SupportMessage` | Hijo de `SupportSession` | Solo se alcanza vía sesión. Las acciones admin comparan `tenant_id` de la sesión con el del usuario (`SupportChatController.php:139,150,184`) y el listado usa `delTenant()`. El acceso público usa un token de 40 caracteres aleatorios (`SupportSession::iniciar`) |
| `DeviceToken` | Pertenece a un usuario (`user_id`) | Registro y baja siempre con `$request->user()->id` (`AuthApiController.php:95,109`); `PushNotificationService` busca por id de usuario |
| `EncuestaInteres` | Captación de clientes de la plataforma, no de un colegio | Escritura pública con throttle (`routes/web.php`); ningún código la lee, solo avisa por WhatsApp al equipo de ZuraEdu. Guarda nombre, teléfono e IP de interesados |
| `BackupConfiguracion` | El respaldo cubre la base compartida completa | Solo la usan el comando de respaldo, el `Kernel` y `SuperAdmin\RespaldoController` (rutas de superadministrador); la migración lo documenta |

Observaciones menores (no son hallazgos): `DeviceToken` no impide que el mismo
token de dispositivo esté registrado por dos usuarios (p. ej. dispositivo
compartido) y `PushNotificationService` enviaría a ambos; y `EncuestaInteres`
conserva datos personales de interesados sin pantalla ni política de
retención.

## 4. Pruebas realizadas

- Análisis estático de rutas y código descrito arriba.
- Suite `php artisan test` (BD `sge_test`, no toca `sge`): ver resultado en
  la sección 5.

## 5. Resultado de la suite de pruebas

Suite completa `php artisan test` (BD `sge_test`) ejecutada tras la corrección de N1 (commit f76be66): **970 pruebas pasadas, 0 fallidas, 3876 aserciones, 1130,9 s**. Incluye las 3 pruebas nuevas de `LimpiarDatosAislamientoTenantTest`.

Segunda ejecución completa tras corregir N2 (commit 4b47353): **972 pruebas pasadas, 0 fallidas, 3879 aserciones, 1053,8 s**. Suma las 2 pruebas de `AuditoriaN2RutasPublicasTest`.

Tercera ejecución completa tras corregir N3 (commit dcca9e5): **973 pruebas pasadas, 0 fallidas, 3882 aserciones, 1178,8 s**. Suma la prueba de `RendimientoAislamientoTenantTest`.

Cuarta ejecución completa, tras añadir los videos, los iconos de redes y la validación de URLs (commit cdef267 más el arreglo de `tests/TestCase.php`): **980 pruebas pasadas, 2 omitidas, 0 fallidas, 3917 aserciones, 1345,3 s**. Las 2 omitidas son los casos «sin videos» de `LandingVideosTest`, que no aplican mientras haya videos en `public/videos`.

Una ejecución intermedia de esta misma fecha se cortó tras 620 pruebas con `Maximum execution time of 600 seconds exceeded`. No era una prueba fallida: flujos como `BoletinController`, `ExportacionMasivaController` y `RespaldoColegioService` llaman a `set_time_limit(300|600)` y, al correr todas las pruebas en un solo proceso, ese límite seguía vigente entre pruebas; si la suite iba lenta, PHP abortaba la prueba en curso. Arreglado en `tests/TestCase.php`, que reinicia el límite (`set_time_limit(0)`) al empezar cada prueba.

## 6. Orden de corrección recomendado

1. **N1** (`limpiarDatos`) — corregir antes de cualquier otro cambio; es
   pérdida de datos entre clientes.
2. **H1** — decidir la regla oficial de promoción (60 sin asistencia vs.
   65 + 75 % + condicionado) y unificar en `RegistroAcademicoService`.
3. **N2** — throttle en `/demo/{rol}`, no loguear el IPN sin firmar,
   restringir `tenant-chat/clear`.
4. ~~Confirmar los 7 modelos sin `BelongsToTenant` y añadir `tenant_id` en
   los `DB::table` de N3~~ — hecho (apartado 3.1 y corrección de N3).
5. Completar la migración a `PromedioEstudianteService`.
