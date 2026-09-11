# Arquitectura de Evolución — ZuraEdu (2026-09-05)

Fase 8 (mapa de dependencias) y Fase 9 (plan técnico para P0/P1). Solo
diseño — nada de esto se implementó. Todos los nombres de clases/tablas
son propuestas conceptuales, a validar antes de escribir una sola
migración.

## Fase 8 — Mapa de dependencias real

Construido a partir del código auditado, no de la intuición.

```
CarnetAcceso (ya existe)
  ├─→ NotificarPadreAccesoJob (ya existe, falta WhatsApp) ── P0 #2
  │      └─→ WhatsAppService (ya existe, patrón a reutilizar)
  └─→ Transporte (RutaTransporte/EstudianteRuta, ya existen) ── P2 #8
         └─→ requiere: nuevo tipo_evento + validar asignación a ruta
              antes de crear el registro

Asistencia (ya existe, ya tiene estado='justificado')
  ├─→ SolicitudRepresentante tipo justificacion_ausencia (ya existe)
  │      └─→ SolicitudesAdminController::responder() (ya existe)
  │            └─→ FALTA: actualizar Asistencia al aprobar ── P0 #3
  └─→ RegistroAcademicoService::calcularPromocion() (ya existe, 0 tests)
         └─→ REQUIERE: plan de pruebas ── P0 #1
              (no depende de nada más — se puede hacer ya)

Pago (ya existe, ya tiene numero_comprobante_fiscal desde commit e177ff1)
  ├─→ PagoController::dashboard() (ya existe, básico)
  │      └─→ FALTA: breakdown por concepto/grado/sección ── P1 #6
  ├─→ Sin Observer (auditoría) ── P2 #11
  └─→ Decisión de negocio: NCF/e-CF real ── P1 #5
         └─→ SI se decide integrar un proveedor: bloquea diseño de
             PagoController y de la vista de recibo hasta tener el
             proveedor elegido (no iniciar sin esa decisión)

SolicitudRepresentante tipo cita_docente (ya existe, sin docente_id)
  └─→ HorarioDetalle (ya existe, del docente)
         └─→ FALTA: cruce de disponibilidad real ── P2 #7

BillingController (ya existe: checkout/transferencia/webhooks)
  └─→ Stripe ya genera invoices internamente
         └─→ FALTA: exponerlas en UI ── P2 #9

ZuraAI (4 controladores independientes, ya existen)
  └─→ FALTA: servicio central + medición de uso por tenant ── P3 #10
         └─→ Bloquea: cualquier plan de precios "IA ilimitada vs. básica"
```

**Lectura del mapa**: los 3 P0 son genuinamente independientes entre sí —
no hay razón técnica para no hacerlos en paralelo o en cualquier orden.
Los P1 de decisión de negocio (Reingreso, alcance NCF) no bloquean el
código de los P0, pero sí bloquean CUALQUIER trabajo adicional en sus
áreas respectivas — por eso están en P1 y no más abajo.

## Fase 9 — Plan técnico por iniciativa (P0/P1 únicamente)

### P0 #1 — Tests del algoritmo MINERD
- **Archivos afectados**: ninguno de producción. Solo
  `tests/Feature/RegistroAcademicoServicePromocionTest.php` (nuevo).
- **Fixtures necesarias**: `SchoolYear`, `Grado` (con `ciclo` primer/segundo),
  `Seccion`, `Grupo`, `Matricula`, `Asignacion`, notas vía
  `CalificacionAcademica`/`Calificacion` según ciclo, `Asistencia`.
- **Sin nuevas tablas ni migraciones.**

### P0 #2 — Carnet+ WhatsApp + paridad API
- **Archivo modificado**: `app/Jobs/NotificarPadreAccesoJob.php` (agregar
  llamada a `WhatsAppService::send()`).
- **Archivo modificado**: `app/Http/Controllers/Api/CarnetApiController.php`
  método `scan()` (agregar el `dispatch(new NotificarPadreAccesoJob(...))`
  que ya existe en el flujo admin-web).
- **Sin nuevas tablas.** Verificar primero por qué el comentario en esa
  línea decía "no debe generar dos WhatsApp" — puede haber una razón de
  diseño que se perdió; confirmar antes de tocar.

### P0 #3 — Conectar justificación de ausencia con Asistencia
- **Archivo modificado**: `app/Http/Controllers/Admin/SolicitudesAdminController.php`
  método `responder()` — cuando `tipo === 'justificacion_ausencia'` y
  `estado === 'aprobada'`, actualizar la(s) fila(s) de `Asistencia`
  correspondientes (por `estudiante_id` + fecha) a
  `estado='justificado'` + copiar `justificacion`/`justificacion_tipo`.
- **Sin nuevas tablas.** Validar primero cómo se relaciona
  `SolicitudRepresentante.fecha_evento` con las filas de `Asistencia` a
  actualizar (¿una fecha = un registro por asignatura, o uno por día?) —
  confirmar antes de escribir el update.

### P1 #4 — Dashboard financiero ampliado
- **Archivo modificado**: `PagoController::dashboard()` — agregar
  agrupaciones por `concepto`, y por grupo→grado/sección (join ya
  existente en otros métodos del mismo controlador).
- **Posible tabla nueva**: ninguna necesaria para el MVP (todo es
  agregación sobre `pagos` existente). Proyección de cobranza (fase
  avanzada) sí podría requerir una vista materializada o un job
  programado si el volumen lo justifica — no construir hasta confirmar
  que el MVP de agrupación no es suficiente.

### P1 #5 y #6 — Decisiones de negocio (Reingreso, NCF/e-CF)
- No hay plan técnico hasta que se tome la decisión. Documentar en
  `DECISIONES_PRODUCTO_ZURAEDU.md` qué información hace falta para
  decidir cada una.
