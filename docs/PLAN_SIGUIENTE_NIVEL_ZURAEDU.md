# Plan de siguiente nivel — ZuraEdu

Basado en el veredicto del gate de producción
([[GATE_PRODUCCION_ZURAEDU]]): ZuraEdu puede entrar a **staging**
resolviendo primero los 3 blockers de esa etapa (backup automático,
confirmar `QUEUE_CONNECTION` real, procedimiento mínimo de deploy con
rollback), pero **no a producción con datos reales** hasta cerrar también
los warnings de observabilidad y auditoría. Este documento es un plan —
no se implementó nada de esto todavía.

## FASE 1 — Staging

Objetivo: tener un entorno que se comporte como producción, sin datos
reales de un centro, para validar el proceso de deploy y detectar
problemas antes de que importen.

- Resolver los 3 blockers del gate: backup diario de BD programado
  (`$schedule->command()`), confirmar que `QUEUE_CONNECTION` en el
  `.env` real NO sea `sync`, definir un procedimiento de deploy documentado
  (aunque sea manual) con backup previo y forma conocida de revertir.
- Levantar el servidor de staging con Reverb/Redis/Horizon corriendo de
  verdad (no `artisan serve`).
- Correr `RouteSmokeTest` (ya existe) como smoke test después de cada
  deploy a staging, no solo dentro de PHPUnit.
- Cargar datos de un tenant de prueba (no un centro real) y ejercitar los
  flujos completos: matrícula, calificaciones, boletines, pagos, Carnet+,
  Classroom, notificaciones WhatsApp.

## FASE 2 — Pruebas de carga

Objetivo: confirmar los límites reales de la arquitectura antes de
comprometerse con un centro real.

- Simular un tenant con varios cientos/miles de estudiantes y medir
  tiempos de respuesta en dashboard, listados, generación de reportes
  PDF/Excel de grupo completo y consolidados institucionales.
- Confirmar si los reportes pesados (hoy síncronos) necesitan moverse a
  cola antes de un centro con matrícula grande.
- Probar concurrencia real: varios docentes capturando calificaciones al
  mismo tiempo, varios escaneos de Carnet+ simultáneos en el kiosco.

## FASE 3 — Hardening

Objetivo: cerrar los warnings de seguridad/configuración identificados en
el gate antes de exponer el sistema a un centro real.

- Fijar `SESSION_SECURE_COOKIE=true`, expiración de tokens de Sanctum,
  `CORS` restringido al dominio real de la app móvil.
- Agregar auditoría (`ActivityLog`) a login/logout y a cambios de
  rol/permiso.
- Agregar un comando de verificación de integridad de datos (matrículas
  huérfanas, calificaciones sin asignación válida).
- Resolver el permiso de `Caja/Finanzas` sobre `gestionar-estudiantes`
  (separar lectura de eliminación).

## FASE 4 — Primer centro piloto

Objetivo: operar con un centro educativo real, acompañado de cerca.

- Definir con el usuario las dos decisiones pendientes (Reingreso,
  promedio por período del portal de padres) antes de que un padre o
  registrador las necesite en producción.
- Monitoreo manual activo durante las primeras semanas (sin
  observabilidad automatizada todavía — ver Fase 7).
- Backup verificado funcionando de verdad (no solo programado — confirmar
  que corre y que el archivo generado es restaurable).

## FASE 5 — SaaS multi-tenant real

Objetivo: incorporar centros adicionales con confianza en el aislamiento.

- Repetir la prueba de aislamiento Centro A vs. Centro B del gate con
  datos reales de dos tenants activos, no solo revisión de código.
- Confirmar que el proceso de onboarding de un nuevo tenant (creación,
  planes, feature flags) es reproducible sin intervención manual ad-hoc.

## FASE 6 — Mobile / API

Objetivo: dar cobertura de pruebas real a la superficie que hoy consume
la app móvil, identificada como área sin tests en el gate.

- Agregar tests de regresión para los endpoints de `routes/api.php` más
  allá del único que ya tiene (`/api/v1/carnet/scan`).
- Confirmar límites de rate limiting apropiados para uso real desde la
  app, no solo desde pruebas manuales.

## FASE 7 — Escalabilidad

Objetivo: preparar la infraestructura para más de un servidor de
aplicación.

- Migrar `CACHE_DRIVER`/`SESSION_DRIVER` de `file` a `redis` en
  producción real (ya identificado en el gate como necesario para
  multi-servidor).
- Agregar índices compuestos `(tenant_id, columna_filtro)` en las tablas
  de mayor volumen si las pruebas de carga (Fase 2) muestran que hacen
  falta.
- Agregar observabilidad real: error-tracking (Sentry/Bugsnag/Flare),
  notificación de jobs fallidos en Horizon, healthchecks de Reverb/Redis/
  BD/almacenamiento.

## FASE 8 — Enterprise

Objetivo: documentación y procesos que permitan crecer sin depender de
una sola persona.

- Reescribir `README.md` con instalación, configuración, arquitectura y
  modelo de roles reales de ZuraEdu (hoy es el genérico de Laravel).
- Automatizar CI/CD (correr la suite de tests + `RouteSmokeTest` en cada
  push, deploy automatizado con rollback).
- Documentación de onboarding para un desarrollador nuevo, independiente
  del desarrollador original.

---

Ninguna de estas fases se implementó — es un plan a ejecutar por etapas,
con aprobación explícita del usuario antes de empezar cada una, siguiendo
el mismo patrón de esta sesión (auditar → decidir → corregir → verificar →
commitear con aprobación explícita).
