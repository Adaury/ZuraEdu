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
