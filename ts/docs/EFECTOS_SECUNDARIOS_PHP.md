# Efectos secundarios de PHP que una escritura en TypeScript no dispara

> Inventario hecho el 2026-10-01 leyendo el código de Laravel. **Léelo antes de migrar la escritura de un módulo.**

## Por qué existe

Cuando una escritura pasa de Laravel a TypeScript, todo lo que PHP hace *como consecuencia* de guardar un modelo
**deja de ocurrir en silencio**: los observers y los hooks del modelo (`static::saved`...) solo se ejecutan dentro de
Eloquent; los eventos y las invalidaciones de caché los llaman los controladores de PHP. La primera versión del
`PATCH /estudiantes/:id` omitía el registro de auditoría que escribe `EstudianteObserver::updated()`; las pantallas que
filtran por él no habrían visto los cambios hechos desde TypeScript. Lo detectó la lectura del observer, no una prueba.

## Procedimiento antes de migrar la escritura de un módulo

1. **Observers y hooks del modelo**: `grep -rn "::observe(" app/Providers` y `grep -rn "static::\(creating\|created\|saving\|saved\|updating\|updated\|deleting\|deleted\)" app/Models`. Ojo: un observer puede estar *importado y no registrado* (ver `MatriculaObserver`).
2. **Eventos y listeners**: `grep -rnE "::dispatch\(|event\(new " app` y `EventServiceProvider::$listen`.
3. **Cachés**: `grep -rn "Cache::forget\|Cache::tags" app`. Las claves llevan el prefijo del colegio (`t{id}_...`).
4. **Archivos**: busca `Storage::`, subidas y borrados de archivos asociados al modelo.
5. **Jobs** que el controlador encola al guardar.
6. Para cada hallazgo: reproducirlo en TypeScript con una prueba e2e, o documentar la diferencia aquí.

## Observers registrados (`app/Providers/AppServiceProvider.php:49-51`)

| Modelo | Evento | Qué hace | Estado en TypeScript |
|---|---|---|---|
| `Estudiante` | `created` | `ActivityLog` `estudiante.creado` — `Estudiante creado: Apellidos, Nombres (Matr: …)` | ✅ `POST /api/v1/estudiantes` |
| `Estudiante` | `updated` | `ActivityLog` `estudiante.actualizado` — `… \| Campos: a, b, updated_at` (columnas cambiadas, en el orden de la tabla) | ✅ `PATCH /api/v1/estudiantes/:id` |
| `Estudiante` | `deleted` | `ActivityLog` `estudiante.eliminado` — `Estudiante eliminado: Apellidos, Nombres` | ✅ `DELETE /api/v1/estudiantes/:id` |
| `Calificacion` (técnica) | `saved` | Si está publicada y `nota_final < 70`: crea una alerta académica (una por año escolar) y avisa al docente | ❌ pendiente (módulo calificaciones) |
| `CalificacionAcademica` | `saved` | `ActivityLog` `calificacion_academica.guardada` si cambió `nota_final`, `publicado` o `situacion` | ❌ pendiente (módulo calificaciones) |

Además de lo que hace el observer, `EstudianteController` audita en el propio controlador: `estudiante.editado` (solo si
cambia un campo sensible). Está reproducido y condicionado igual.

## Observer que NO está registrado: `MatriculaObserver`

`app/Observers/MatriculaObserver.php` dispararía el evento de tiempo real `DashboardActualizado` al crear una matrícula,
pero **nunca se registra**: `AppServiceProvider.php:13` lo importa (`use`) y no existe `Matricula::observe(...)`. Es código
muerto. Consecuencias:

- Una matrícula creada desde el asistente de alta de estudiantes (`EstudianteController@store` con `grupo_id`) **no
  actualiza el panel en vivo**. Es un defecto latente de Laravel, no algo a reproducir.
- Los controladores que sí lo disparan a mano son `MatriculaController` (líneas 173 y 352), `InscripcionController`,
  `SchoolYearController` y `UsuarioController`. Al migrar matrículas hay que reproducir **esos** disparos explícitos.
- **No** se corrigió registrando el observer: duplicaría el evento donde los controladores ya lo emiten.

## Hooks del modelo (`static::…`)

| Modelo | Hook | Efecto |
|---|---|---|
| `EvaluacionDocente` | `saving` | Calcula `promedio` a partir de las puntuaciones |
| `FotoAlbum` | `deleting` | **Borra el archivo** de la foto del disco `public` |
| `PlantillaComunicacion` | `saved` / `deleted` | Invalida la caché de plantillas del colegio |
| `TicketSoporte` | `creating` | Asigna `sla_vencimiento_at` según la prioridad |

## Eventos y dónde se disparan

| Evento | Se dispara desde |
|---|---|
| `AsistenciaRegistrada` | `Portal/PortalDocenteController` |
| `CalificacionesPublicadas` | `Admin/CalificacionController` |
| `GradePublished` | `Admin/CalificacionAcademicaController`, `Admin/CalificacionController` |
| `CarnetEscaneado` | `Admin/CarnetCheckinController` |
| `EstudianteEscaneadoQr` | `AsistenciaQrController` |
| `DashboardActualizado` | `Admin/MatriculaController`, `InscripcionController`, `SchoolYearController`, `UsuarioController` |
| `PagoConfirmado` | `Admin/PagoController`, `CardNetController`, `PagoStripeController`, `WebhookStripeController` |
| `NotificationCreated` | `Models/Notificacion`, `Jobs/EnviarNotificacionJob` |
| `MessageSent` | `Portal/ClassroomChatController` |
| `NuevoMaterialPublicado`, `TaskCreated` | `Portal/ClassroomDocenteController` |
| `StudentConnected`, `TaskDelivered` | `Portal/ClassroomEstudianteController` |
| `NuevoMensajeTenantChat` | `Admin/TenantChatController` |
| `SupportAdminReply`, `SupportMessageReceived` | `SupportChatController` |
| `AnuncioTenant`, `ClassroomMeetingUpdated`, `NewClassroomMessage`, `NotificacionEnviada` | no se encontró disparo con `::dispatch(` ni `event(new` — revisar (puede usar `broadcast()` u otra forma) |

**El único evento con listener** es `PagoConfirmado` → `NotificarPagoConfirmado`
(`app/Providers/EventServiceProvider.php`), más `Registered` (verificación de correo, del framework). Los eventos de tiempo
real usan Reverb (broadcasting). La API TypeScript ya puede emitirlos: `ReverbPublisher` (`src/realtime/reverb.publisher.ts`)
publica por HTTP firmado (protocolo Pusher) con los mismos nombres de evento y canal que Laravel (`private-tenant.{id}`…);
probado contra un Reverb real (`test/reverb.e2e-spec.ts`). Cada módulo migrado debe llamarlo para los eventos que su controlador
de PHP despacha.

También se encolan jobs desde los controladores: `EnviarMensajeCircularJob`, `EnviarNotificacionJob`, `EnviarPushLoteJob`,
`ImportarCalificacionesJob`, `EnviarWhatsApp`.

## Cachés que Laravel invalida a mano

20 archivos llaman a `Cache::forget`, entre ellos `CalificacionController`, `DashboardController`, `CierreAnoController`,
`ComunicacionesController`, `UsuarioController`, `AlertaController`, `SigerdController`, `SistemaController`,
`SolicitudesAdminController` y `AuthController`. La API TypeScript ya tiene `LaravelCache` (`src/redis/laravel-cache.ts`:
`olvidar`, `olvidarPorPrefijo`, `olvidarMejorEsfuerzo`), pero **cada módulo migrado debe llamarlo** para las claves que su
controlador de PHP olvida; si no, los paneles muestran datos viejos hasta que venza el TTL (≈300 s en el dashboard).
Claves lógicas de Laravel (`t12_dashboard_…`); el prefijo físico `REDIS_PREFIX + CACHE_PREFIX` lo añade `LaravelCache`.
Usar `olvidarMejorEsfuerzo` DESPUÉS de confirmar la transacción: la escritura ya es válida aunque Redis falle.

`EstudianteController` **no toca ninguna caché** (0 llamadas), así que crear/editar/borrar estudiantes desde TypeScript queda
en paridad exacta con PHP en este punto.

## Asistencia del docente (inventario previo a migrarla — 2026-10-01)

Endpoints: `PortalDocenteController::guardarAsistencia` (lote, `POST /portal/docente/asignacion/{asignacion}/asistencia`) y
`asistenciaRapidaGuardar` (uno a uno, `POST /portal/docente/asistencia-rapida/guardar`). Un `upsert` por
`(matricula_id, asignacion_id, fecha)`, y además:

| Efecto | Dónde | Notas para reproducirlo en TypeScript |
|---|---|---|
| Aviso de ausencia al representante | `notificarAusencia()` si el estado es `ausente` | Notificación in-app (`Notificacion::enviar`, respeta preferencias y cola `notifications`) **y WhatsApp** (`WhatsAppService::sendAbsence`) por cada representante. |
| Alerta de asistencia crítica | `verificarAlertasAsistencia()` (solo en el lote) | Con ≥5 registros y 70 % ≤ asistencia < 75 %: `AlertaSistema::firstOrCreate` + notificación a los representantes. Cuenta como presente `presente`, `tarde` y `excusa`. |
| Evento Reverb | `AsistenciaRegistrada` (`ShouldBroadcastNow`, canal privado `docente.{user_id}`) | Solo en el lote, dentro de `try/catch` que lo traga. |
| Auditoría | **ninguna** | `ActivityLog` solo existe al justificar (`justificarAsistencia`). Cambiar presente→ausente no deja rastro. |
| Cachés | ninguna | — |

Hallazgo corregido en Laravel (no es una diferencia deliberada): los `matricula_id` venían del navegador y **no se comprobaba que
pertenecieran al grupo de la asignación**; un docente podía marcar a cualquier estudiante del colegio y disparar el aviso
por WhatsApp a su representante. Ahora ambos endpoints lo verifican contra la BD (`AsistenciaDocenteGrupoTest`).
**La versión TypeScript debe hacer la misma comprobación** (relación real matrícula→grupo→asignación→docente, no solo el ID).

**Por qué no se migra todavía**: reproducir los avisos exige WhatsApp, notificaciones con cola y Reverb desde TypeScript, y
nada de eso existe aún en la API (solo Redis). Migrar solo el `upsert` dejaría a los padres sin aviso de ausencia, que es
una regresión visible. Orden sugerido: (1) cola/notificaciones en TS, (2) Reverb, (3) esta escritura.

## Notificaciones desde TypeScript (2026-10-01)

`NotificacionesService.enviar()` (`src/notificaciones/`) reproduce `Notificacion::enviar()` / `EnviarNotificacionJob`: gate in-app de la
institución (`notif_inapp_{categoría}`, la categoría `sistema` no se apaga), fila en `notificaciones`, borrado de la caché
`user_{id}_notif_unread` de Laravel, push a Expo (institución `notif_push_{cat}` Y usuario `notif_push_prefs[cat]`) y el evento
`notification.created` por Reverb a `private-user.{id}`. El catálogo (ICONOS, TIPO_CATEGORIA, CATEGORIAS) se compara en una prueba con
`app/Models/Notificacion.php`, así que cambiarlo en PHP sin actualizar TypeScript rompe las pruebas.

Diferencias deliberadas: el push y el evento en tiempo real van **en segundo plano** (PHP los saca del request con la cola; Expo puede
tardar 8 s); el destinatario se valida contra la BD (usuario del mismo colegio) antes de insertar; la hora del evento es UTC.
No cubre: notificaciones masivas (`enviarA`, push por lotes con `EnviarPushLoteJob`) ni el WhatsApp.

## Matrículas: `POST /api/v1/matriculas` (2026-10-01)

Reproduce `MatriculaController@store` (permiso `gestionar-matriculas`): bloqueo del grupo, cupo (solo cuentan las matrículas `activa`),
`numero_orden` (cuenta TODAS las del grupo), matrícula `activa`; después `DashboardActualizado` (`private-tenant.{id}`, evento
`dashboard.updated`, `{tipo: 'nueva_matricula', datos: {grupo_id}}`) y la notificación «✅ Matrícula confirmada» (tipo `general`) al usuario del
estudiante y de cada representante. Los mensajes de error de cupo y de «ya matriculado» son idénticos a los de Laravel.

Diferencias deliberadas (TypeScript es MÁS estricto o más trazable):

| Tema | Laravel | TypeScript |
|---|---|---|
| Validación de ids | `exists:tabla,id`: **no filtra por colegio ni por borrado lógico** → acepta un estudiante/año de otro colegio y un estudiante borrado | año, estudiante y grupo deben existir en ESTE colegio y no estar borrados (422) |
| Grupo y año | no comprueba que el grupo sea del año indicado | 422 si el grupo es de otro año |
| Auditoría | ninguna (y `matriculas` no guarda quién matriculó) | `activity_logs`: `matricula.creada` |
| Evento del dashboard | `ShouldBroadcastNow`: bloquea la respuesta hasta que Reverb contesta | en segundo plano (`ReverbPublisher.emitir`) |
| Notificaciones | un fallo corta el resto del bloque (un único `try/catch`) | un fallo por destinatario no afecta a los demás |
| Códigos | todo es 422 de validación | 422 referencia inválida; 409 ya matriculado o sin cupo |

**Lección (la cazó la prueba de concurrencia: 8 matrículas a un grupo de 3 entraban las 8, todas con orden 1):** el `for update` del grupo
tiene que ser la PRIMERA sentencia de la transacción. MySQL (REPEATABLE READ) fija la instantánea en la primera lectura normal; si antes
se lee otra tabla, tras conseguir el bloqueo los `count` siguen viendo la instantánea vieja. Laravel lo cumple sin proponérselo (su primera
sentencia es `lockForUpdate`). **Toda escritura futura con "bloquear, contar, insertar" debe hacer lo mismo.**

No cubre: matrícula masiva (`storeMasivo`), cambio de grupo, cambio de estado ni baja (siguen en Laravel).

## Diferencias conocidas y deliberadas (estudiantes)

| Tema | Laravel | TypeScript | Motivo |
|---|---|---|---|
| Número de matrícula automático | `count(creados del año) + 1` | máximo existente del año + 1, con bloqueo por colegio | Laravel tiene condición de carrera y no cuenta a los borrados: tras borrar a alguien puede generar un número repetido y fallar con un 500 |
| `tutor_parentesco` / `tutor_trabajo` | regla de 150 caracteres | 50 / 100 (los de la columna) | En PHP un valor largo da un 500 de la base de datos; aquí un 400 claro |
| `nacionalidad` | `nullable` | no admite null | La columna es `NOT NULL` |
| `tutor_email` | aparece en las reglas | se rechaza | No existe como columna ni en `$fillable` |
| Foto del estudiante | se sube, se procesa y **se borra al eliminar** | **no se acepta** (la subida sigue en Laravel); **sí se borra al eliminar** si `FOTOS_PUBLICAS_DIR` está configurada, con defensa contra rutas fuera de la carpeta | Decisión (2026-10-01): los archivos viven en el disco de Laravel; la API TypeScript corre en el mismo servidor (Nginx), así que puede borrarlos |
| Matricular al crear (`grupo_id`) | `Matricula::create` en el mismo request | no se acepta | Es el módulo de matrículas (cupo, eventos); se migra aparte |
| Respuesta de crear/borrar | redirección HTML | `201` con el estudiante / `204` | Es una API |
