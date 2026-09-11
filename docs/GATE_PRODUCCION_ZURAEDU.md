# Gate de producción — ZuraEdu (2026-09-04)

Auditoría de solo lectura pedida explícitamente por el usuario ("NO CÓDIGO
NUEVO EN ESTA ETAPA") para determinar si ZuraEdu está lista para
pre-producción / SaaS profesional. Ejecutada en 5 líneas de revisión
paralelas sobre el estado real del repositorio, después de cerrar la
auditoría completa de 26 secciones y sus 19 hallazgos corregidos (ver
[[project_auditoria_completa_2026_09_04]] y
[[project_auditoria_medios_bajos_2026_09_04]]). Ningún archivo de código
fue modificado durante este gate.

Estados: 🟢 PASS · 🟡 WARNING · 🔴 BLOCKER · ⚫ PENDIENTE DE DECISIÓN.

## Matriz final

| # | Área | Estado | Riesgo | Evidencia | Acción |
|---|------|--------|--------|-----------|--------|
| 1 | Arquitectura — escala | 🟡 WARNING | Índices sin componer con tenant_id; `GrupoController` sin paginar; reportes pesados síncronos | `2026_04_29_100003_add_tenant_id_to_core_tables.php`; `GrupoController.php`; `ReportesController.php` | Índices compuestos y colas para reportes cuando el volumen lo justifique — no urgente hoy |
| 2 | Multi-tenant — aislamiento | 🟢 PASS | Ningún FAIL encontrado en estudiantes/docentes/matrículas/calificaciones/asistencia/Classroom/Carnet+/finanzas; los 20 usos de `withoutTenant()`/`withoutGlobalScopes()` están justificados | Verificado por 2 líneas de revisión independientes | Ninguna |
| 3 | RBAC — matriz de roles | 🟡 WARNING | `Caja/Finanzas` tiene `gestionar-estudiantes` completo (incluye eliminar) pese a que el comentario del seeder dice "solo lectura en práctica"; Spatie no permite separar crear/editar/eliminar en un solo permiso | `database/seeders/RolesSeeder.php` | Separar el permiso o ajustar el rol antes de dar ese acceso a producción con datos reales |
| 4 | Seguridad — gate final | 🟢 PASS | Sin CRÍTICO ni ALTO nuevo. Sanctum sin expiración de token y `SESSION_SECURE_COOKIE=false` por defecto son MEDIO (config, no código) | `config/sanctum.php`; `config/session.php` | Fijar expiración de token y `SESSION_SECURE_COOKIE=true` en el `.env` real de producción |
| 5 | Subida de archivos | 🟢 PASS | Los 18 puntos de subida (incluido el RCE corregido en la ronda anterior) siguen con whitelist + límite de tamaño correctos; nada nuevo sin cubrir | Matriz completa verificada por el fork | Ninguna |
| 6 | Base de datos | 🟡 WARNING | FKs/constraints/unicidad correctos, cero duplicados — pero una migración ya ejecutada (`2026_03_17_000300_add_ciclo_to_grados_and_system_settings.php`) fue modificada después en vez de crear una nueva; sin comando de integridad de datos | Historial de git, commit `86c39b2` | Documentar la excepción o crear una migración correctiva nueva a futuro; agregar un comando de chequeo de integridad |
| 7 | Rendimiento | 🟡 WARNING | `Grado::orderBy()->get()` repetido sin cache en 10+ controladores (impacto bajo, catálogo pequeño) | `AcademicoController`, `EstudianteController`, `GrupoController`, etc. | Cachear cuando se optimice — no bloqueante |
| 8 | Colas / Redis / Reverb | 🟢 PASS | Los 6 jobs reales extienden `TenantJob` sin excepción; eventos usan `ShouldBroadcastNow`/`ShouldBroadcast` apropiadamente según si son tiempo-crítico | `app/Jobs/*`, `app/Events/*` | Ninguna (ver #12 configuración para el riesgo de `QUEUE_CONNECTION`) |
| 9 | Backups y recuperación | 🔴 **BLOCKER** | Sin backup automático programado — el respaldo de BD depende 100% de que un humano entre al panel y haga clic; archivos subidos (carnets, entregas, comprobantes) sin ningún respaldo | `app/Console/Kernel.php` (sin `$schedule->command('backup...')`); `BackupController.php` (solo bajo demanda) | Agregar un `$schedule` diario de backup de BD antes de operar con datos reales de un centro |
| 10 | Logs y auditoría | 🟡 WARNING | Estudiantes y calificaciones sí tienen auditoría (Observers); login/logout y cambios de rol/permiso NO dejan ningún rastro | `AuthController.php` (sin logging); `UsuarioController.php:60,95` (`assignRole`/`syncRoles` sin registro) | Agregar `ActivityLog::registrar()` a login/logout y a cambios de rol antes de operar con múltiples administradores |
| 11 | Monitoreo | 🟡 WARNING | Sin integración de error-tracking (Sentry/Bugsnag/Flare); Horizon sin notificación de fallos; sin healthcheck activo de Reverb/Redis/BD/almacenamiento | `composer.json` (sin paquete de error-tracking) | Aceptable para un piloto acompañado; necesario antes de producción sin supervisión directa |
| 12 | Testing — cobertura por riesgo | 🟡 WARNING | 176/176 pasando, pero autenticación (login/logout/rate-limit) y la superficie API móvil (más allá de un endpoint) no tienen ningún test propio; CRUD core (Estudiantes/Docentes/Grupos/Matrículas) sin test dedicado — brecha ya conocida y aceptada | Ver tabla de cobertura en el reporte del fork | Agregar test de autenticación como mínimo antes de producción |
| 13 | Configuración de producción | 🟡 WARNING | `CACHE_DRIVER`/`SESSION_DRIVER=file` por defecto (no comparten entre servidores); `QUEUE_CONNECTION=sync` por defecto (ya reportado en #1); CORS con `allowed_origins: ['*']` | `.env.example`; `config/cors.php` | Es responsabilidad del `.env` REAL de producción sobreescribir estos valores — no es un bug de código, pero el valor por defecto es el incorrecto para producción y es fácil olvidarlo |
| 14 | Deployment | 🔴 **BLOCKER** | Cero automatización de despliegue: sin `deploy.sh`, sin Dockerfile, sin `.github/workflows/`. Todo el código va directo a `master`, sin rama de staging separada. Sin estrategia de rollback ni backup pre-deploy documentados. `RouteSmokeTest` existe pero solo corre en PHPUnit, no como smoke test post-deploy real | `git branch -a`; ausencia de archivos de CI/CD | Definir un procedimiento mínimo de deploy (aunque sea manual y documentado) con backup previo y rollback conocido, antes de tocar un servidor real |
| 15 | Documentación | 🟡 WARNING | `README.md` es el genérico de Laravel sin modificar — cero información específica de ZuraEdu. `docs/` solo tiene documentos de auditoría, ninguno de instalación/arquitectura/onboarding | `README.md` (66 líneas, contenido stock) | No bloquea que el propio equipo actual opere el sistema, pero bloquea que alguien nuevo lo levante sin ayuda directa |
| 16 | Decisiones pendientes | ⚫ PENDIENTE | Ver detalle abajo | — | Esperar decisión del usuario |

## 16. Decisiones pendientes (no resueltas a propósito)

### Reingreso como flujo distinto de matrícula nueva
- **Qué decisión falta**: si un estudiante que se fue y regresa debe tener un flujo diferenciado (flag, historial de por qué se fue, prellenado del expediente anterior) o si la matrícula genérica actual es suficiente.
- **Qué módulos afecta**: Matrícula, Expediente del estudiante, posiblemente reportes MINERD que distingan reingreso de ingreso nuevo.
- **Qué datos necesita**: definición de negocio de qué distingue un reingreso (¿tiempo fuera del sistema? ¿motivo de salida? ¿se conserva el mismo número de matrícula?).
- **Riesgo si se implementa mal**: duplicar lógica de matrícula sin necesidad real, o construir un flujo que no capture lo que Dirección realmente necesita reportar.

### Promedio "por período" en el portal de padres
- **Qué decisión falta**: cómo debe calcularse el promedio por período para estudiantes académicos, dado que `CalificacionAcademica` guarda las notas en columnas fijas por competencia/período (`comp1_p1`..`comp4_p4`), no en una fila por período como `Calificacion` (técnica).
- **Qué módulos afecta**: `PortalRepresentanteController.php:95` (insignia "promedio por período" del portal de padres) — único de los 15 sitios de `PromedioEstudianteService` que sigue sin corregir.
- **Qué datos necesita**: confirmar si el promedio por período debe promediar las 4 competencias de ese período específico, y si eso coincide con cómo se calcula en el boletín oficial (para no mostrar un número distinto al padre que al boletín impreso).
- **Riesgo si se implementa mal**: mostrar una nota incorrecta a un padre — impacto de confianza alto aunque el riesgo técnico sea bajo.

## Veredicto

Ver el reporte ejecutivo entregado al usuario para el estado final,
blockers, warnings, fortalezas y próximos pasos recomendados.
