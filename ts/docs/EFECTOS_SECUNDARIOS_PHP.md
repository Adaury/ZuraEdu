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
real usan Reverb (broadcasting): una API TypeScript que quiera emitirlos necesita su propia conexión a Reverb/Redis.

También se encolan jobs desde los controladores: `EnviarMensajeCircularJob`, `EnviarNotificacionJob`, `EnviarPushLoteJob`,
`ImportarCalificacionesJob`, `EnviarWhatsApp`.

## Cachés que Laravel invalida a mano

20 archivos llaman a `Cache::forget`, entre ellos `CalificacionController`, `DashboardController`, `CierreAnoController`,
`ComunicacionesController`, `UsuarioController`, `AlertaController`, `SigerdController`, `SistemaController`,
`SolicitudesAdminController` y `AuthController`. Mientras la API TypeScript no tenga cliente de Redis, **sus escrituras no
invalidan esas claves** y los paneles pueden mostrar datos viejos hasta que venza el TTL (≈300 s en el dashboard).

`EstudianteController` **no toca ninguna caché** (0 llamadas), así que crear/editar/borrar estudiantes desde TypeScript queda
en paridad exacta con PHP en este punto.

## Diferencias conocidas y deliberadas (estudiantes)

| Tema | Laravel | TypeScript | Motivo |
|---|---|---|---|
| Número de matrícula automático | `count(creados del año) + 1` | máximo existente del año + 1, con bloqueo por colegio | Laravel tiene condición de carrera y no cuenta a los borrados: tras borrar a alguien puede generar un número repetido y fallar con un 500 |
| `tutor_parentesco` / `tutor_trabajo` | regla de 150 caracteres | 50 / 100 (los de la columna) | En PHP un valor largo da un 500 de la base de datos; aquí un 400 claro |
| `nacionalidad` | `nullable` | no admite null | La columna es `NOT NULL` |
| `tutor_email` | aparece en las reglas | se rechaza | No existe como columna ni en `$fillable` |
| Foto del estudiante | se sube, se procesa y **se borra al eliminar** | no se acepta ni se borra | Depende de dónde se guarden los archivos (decisión pendiente) |
| Matricular al crear (`grupo_id`) | `Matricula::create` en el mismo request | no se acepta | Es el módulo de matrículas (cupo, eventos); se migra aparte |
| Respuesta de crear/borrar | redirección HTML | `201` con el estudiante / `204` | Es una API |
