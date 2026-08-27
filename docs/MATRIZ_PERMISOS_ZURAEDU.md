# Matriz de Permisos — ZuraEdu / SGE

**Estado:** Auditoría completa — §2.1 a §2.6 corregidos y verificados (2026-08-25)
**Alcance de este documento:** inventario real del sistema tal como está implementado hoy, más los hallazgos de seguridad detectados. No es un documento aspiracional — cada afirmación está verificada contra el código, con archivo:línea.
**Regla seguida:** ANALIZAR → DOCUMENTAR (este archivo) → PROPONER → CORREGIR → PROBAR.

## Progreso de corrección

- ✅ **§2.1 (crítico, roles docente)** — corregido. `User::esDocente()`/`tieneRolDocente()` reemplazan las 51+27 ocurrencias de `hasRole('Docente')` en 38 archivos. Verificado con un docente real (bloquea acceso a asignaciones ajenas) y con la suite completa (29/29).
- ✅ **§2.6 (NUEVO, CRÍTICO — el hallazgo más severo de toda la auditoría, encontrado al verificar §2.1 en navegador real).** El escenario de explotación descrito en §2.1 original ("un Docente Técnico visita `/admin/calificaciones/{id}` y accede a notas ajenas") **no era alcanzable**: `EnsureAdminAccess` redirige a *cualquier* usuario `tieneRolDocente()` fuera de `/admin` hacia `/portal/docente` — así que ningún docente llega jamás a los controladores `Admin\*`. El verdadero flujo de producción para docentes es `Portal\PortalDocenteController` (91 métodos, cada uno con su propio `abort(403)` si `$asignacion->docente_id !== $docente->id` — correctamente scopeado, confirmado de nuevo en este pase). Pero al verificar esto se encontró algo peor: **`routes/web.php:322` protegía todo `/portal/docente/*` con `middleware(['auth','activo','role:Docente'])` — el rol exacto, literal, sin los otros 3.** Efecto real: un usuario con rol *Docente Académico*, *Docente Técnico* o *Docente Guía* queda **bloqueado del sistema por completo** — 403 en `/admin` (por tener uno de esos roles) y 403 en `/portal/docente` (por no ser exactamente `'Docente'`). No es un problema de exceso o defecto de permisos: es una **denegación de servicio total** para 3 de los 4 roles docente. Reproducido en navegador con un usuario de prueba real (`Docente Técnico` → "Acceso denegado — User does not have the right roles"), corregido reemplazando `role:Docente` por `can:acceso-portal-docente` (Gate nuevo: `tieneRolDocente()`), reverificado con el mismo usuario (llega correctamente a `/portal/docente/setup`) y con `docente@demo.com` (rol `Docente` normal, sigue funcionando igual — 29/29 tests, más 8/8 checks de navegador en `boletines`/`calificaciones` propios vs. ajenos por asignación).
  - **Consecuencia para el resto de esta auditoría:** todo el trabajo de §2.2/§2.3 que asignó `can:ingresar-calificaciones` (docente-capable) a rutas `Admin\*` (planes-clase, instrumentos, observaciones, tutorias, gamificación, rúbricas, `horarios/mi-horario`, y la activación de `BoletinPolicy`) sigue siendo correcto y no rompe nada, pero es **inalcanzable para docentes en la práctica** — `EnsureAdminAccess` los redirige antes de llegar ahí. No se revierte (es inofensivo y correcto si algún día se habilita acceso admin a docentes) pero no es lo que protege el uso real; lo que protege el uso real es `PortalDocenteController`, ya verificado.
- ✅ **§2.2 (alto, segmentación de rutas)** — corregido para los 7 módulos de mayor riesgo: nómina (`can:gestionar-pagos`), salud/disciplina (`can:acceso-salud-disciplina`, Gate nuevo), billing (`can:acceso-billing`, Gate nuevo), registro-académico (`can:gestionar-matriculas`), guardado de notas MINERD (`can:ingresar-calificaciones`), perfiles de docentes/estudiantes (`can:gestionar-docentes`/`can:gestionar-estudiantes`). Verificado en navegador real con petición HTTP (Biblioteca bloqueada con 403 en los 5 módulos; Administrador y SuperAdmin-administrando con acceso correcto).
- ✅ **§2.2 — resto de `academico.php` (Medio-Alto, ~48 rutas)** — corregido: cursos (`can:gestionar-grupos`), materias/asignaciones (`can:gestionar-asignaciones`), registro MINERD lectura (`can:ver-calificaciones`, ya tenía `can:ingresar-calificaciones` anidado para guardar), competencias/IL (`can:gestionar-indicadores`), planes de clase e instrumentos de evaluación (`can:ingresar-calificaciones` — coincide con el scoping interno por docente que ya tenían los controladores), auditoría de calificaciones (`can:supervisar-registros`), observaciones de docentes (`can:ingresar-calificaciones`), homepage editor (`can:gestionar-configuracion`), planificación área técnica (`can:ingresar-calificaciones`), bachillerato técnico (`can:gestionar-asignaturas`).
- ✅ **`seguimiento_social.php` (Medio, PII sensible)** — corregido con `can:acceso-salud-disciplina` (mismo Gate que salud/disciplina, mismo nivel de sensibilidad).
- ✅ **`riesgo.php`** — ya estaba protegido correctamente (middleware en el constructor de `AcademicRiskController`, no en la ruta). No requería cambio.
- ✅ **Bug adicional tipo §2.4 fuera de Policies** — `AcademicAlertService.php:218` usaba `User::role(['Admin', 'Director'])`; 'Admin' no existe en BD (es 'Administrador'), así que las alertas académicas nunca llegaban a Administradores. Corregido.
- ✅ **§2.3 (medio, Policies muertas)** — resuelto, decisión distinta para cada una tras investigar si protegían algo alcanzable:
  - **`BoletinPolicy` → activada.** Su lógica ya estaba duplicada *inline* en `BoletinController::verEstudiante()`, `::pdf()` y `::pdfAnual()` (con una inconsistencia: el inline no consideraba `tutor_id`, solo asignaciones activas). Reemplazado por `$this->authorize('ver'|'pdf', $matricula)`.
  - **Gap real encontrado de paso**: `BoletinController::grupo()` y `::zipGrupo()` (vista y ZIP masivo de boletines por grupo) **no tenían ningún control de acceso por grupo** — un docente con `ver-boletines` podía pasar cualquier `grupo_id` ajeno y ver/descargar en bloque boletines de estudiantes que no enseña, saltándose el control que sí existía en las rutas por-estudiante. Corregido con un helper nuevo `puedeVerGrupo()` en el mismo controlador (mismo criterio que `BoletinPolicy`: tutor o asignación activa en ese grupo).
  - **`EstudiantePolicy` y `GrupoPolicy` → eliminadas.** Su rama de scoping para Docentes era código inalcanzable: las rutas que usan `EstudianteController`/`GrupoController` (`personas.php`, `academico.php`) están gateadas con `can:gestionar-estudiantes`/`can:gestionar-grupos`, permisos que ningún rol Docente tiene — ningún docente llega nunca a ese código, y los roles que sí llegan (Admin/Director/Coordinadores/etc.) no necesitan scoping por asignación. Se eliminaron los 2 archivos y su registro en `AuthServiceProvider::$policies` para no dejar Policies "registradas" que nadie invoca (falsa sensación de seguridad). Si en el futuro se habilita que Docentes naveguen estudiantes/grupos desde el panel admin, escribir una Policy nueva con los requisitos de ese momento.
- ✅ **§2.4 (bajo, bug de nombre de rol)** — corregido, incluida la ocurrencia adicional en `AcademicAlertService.php:218`.
- ✅ **§2.5 (NUEVO, alto — no estaba en el informe original) — middleware `role:` usado directamente en producción, no solo en teoría.** El "Hallazgo adicional" de la sección de arriba (que `role:` no pasa por `Gate::before`, rompiendo el bypass de `super_admin` administrando un tenant) no era solo un riesgo teórico: **12 rutas ya lo usaban así en producción**, 9 en `routes/admin/*.php` y 3 en `routes/api.php` (app móvil). Todas corregidas reemplazando `role:` por `can:` + un `Gate::define()` nuevo en `AuthServiceProvider.php` (`solo-administrador`, `acceso-direccion`, `acceso-direccion-coordinacion`, `acceso-sigerd`, `acceso-docente-api`):
  - `cierre_ano.php`, `kpis.php`, `reportes.php` (dashboard ejecutivo) → `acceso-direccion` (Administrador\|Director).
  - `pagos.php` (config pasarelas Stripe/CardNet — **el módulo que el informe original citaba como "buena plantilla"**), `sistema.php` (backup, config institucional, demo/trial, login-config, WhatsApp) → `solo-administrador`.
  - `importaciones.php` (hub principal), `sistema.php` (landing/login público) → `acceso-direccion-coordinacion`.
  - `sigerd.php` (grupo principal + subgrupo de configuración) → `acceso-sigerd` / `acceso-direccion`.
  - **`routes/api.php` (app móvil), 3 rutas** (`api.docente.*`, `mis-evaluaciones`, `mis-reuniones`) — tenían el mismo bug que §2.1 pero en la capa API: solo comprobaban el rol literal `'Docente'`, dejando fuera a Docente Académico/Técnico/Guía (que además, por usar `role:`, tampoco recibían el bypass de `super_admin`). Corregido con `acceso-docente-api` (`tieneRolDocente()` + Administrador/Director).
- ✅ **Resto de módulos Bajo/Medio de §2.2** — corregidos con permisos existentes o Gates nuevos, agrupando por audiencia real (criterio del asistente, a confirmar por el usuario si algo no encaja en el uso diario):
  - **Permiso exacto ya existente:** `biblioteca.php` → `gestionar-biblioteca`; `becas.php` → `gestionar-pagos` (naturaleza financiera); `pre-matriculas.php` → `gestionar-matriculas`.
  - **`ver-servicios`** (Administrador, Biblioteca, Director, Recepción — coincide con la descripción del propio informe para Recepción: "servicios carnet/QR/entrada-salida"): `carnet.php`, `transporte.php`, `cafeteria.php`, `equipos.php`, `galeria.php`, `eventos.php`, `recursos.php`, `inventario.php`.
  - **`gestionar-asignaciones`** (confirmado exacto vía los flags `$isAdmin||$isDir||$isCoord` que ya usa el sidebar para `classroom`, `resources/views/layouts/admin.blade.php:2903`): `classroom.php`, y la parte de gestión/configuración de `horarios.php` + `scheduling.php`.
  - **`ingresar-calificaciones`** (audiencia docente-capable + Admin/Coordinadores/Director — coincide con el scoping interno que ya tenían varios de estos controladores): `tutorias.php`, `gamificacion.php`, `proyectos.php`, `reconocimientos.php`, `rubricas.php`, y la parte de vista propia (`mi-horario`/`horario-docente`) de `horarios.php`.
  - **`acceso-direccion`** (Gate nuevo, Administrador\|Director): `avisos-emergencia.php` (alcance masivo, alto impacto si se usa mal).
  - **`acceso-direccion-coordinacion`** (Gate nuevo): `reuniones.php` (actas formales), `encuestas.php`.
  - **`supervisar-registros`**: `solicitudes.php` (las 3 bandejas: representantes, estudiantes, docentes).
  - **Revisados y confirmados sin necesidad de `can:` de ruta** — ambos siguen el mismo patrón que `riesgo.php` (autorización dentro del controlador, no en la ruta), y en ambos casos es el diseño correcto, no un gap:
    - `comunicaciones.php` — mensajería interna; por diseño cualquier usuario del panel debe poder escribirle a un colega de su tenant. `ComunicacionesController::show()`/`destroy()`/`descargarAdjunto()` ya verifican remitente/destinatario antes de dar acceso a un mensaje individual. Único detalle menor (no bloqueante): la pestaña "circulares" de `index()` lista el *asunto* de todas las circulares del tenant sin filtrar por destinatario (el cuerpo sigue protegido por `show()`) — parece intencional (transparencia institucional), se deja así salvo que se pida lo contrario.
    - `soporte.php` — tickets internos; `TicketController::esAdmin()` (Administrador/Director/Coordinador Académico) ya gatea listas Excel/PDF, asignación y ver/responder tickets ajenos; cualquier otro rol solo puede crear/ver/responder los suyos y cerrar los ya resueltos. Completo.
    - `comunicados` (en `sistema.php`) — ✅ auditado y corregido (2026-08-27). Ver actualización más abajo.
- ✅ Verificado tras todo el lote: `php artisan route:list` carga sin errores (930 rutas admin, 124 API) y la suite completa sigue en 29/29.

### `comunicados` (sistema.php) — auditado y corregido (2026-08-27)

Único módulo que había quedado marcado "no revisado" en el barrido original. `ComunicadoController` no tenía ningún control de acceso — ni `can:` en la ruta, ni Policy, ni `esAdmin()` interno (a diferencia de `comunicaciones.php`/`soporte.php`, que sí lo tienen). Efecto real: cualquier rol que llegara a `/admin` (Secretaría, Recepción, Biblioteca, Caja, etc.) podía crear/editar/eliminar comunicados institucionales, incluyendo el envío masivo real de notificaciones y correos a "todos" los usuarios del tenant.

**Corregido:** rutas de gestión (`index`, `create`, `store`, `edit`, `update`, `destroy`, `dashboard`, `lista-pdf`, `lista-excel`) protegidas con `can:acceso-direccion-coordinacion` (Gate ya existente, Administrador/Director/Coordinador Académico/1/2 — mismo criterio que `reuniones.php`/`encuestas.php`, comunicación institucional formal). `comunicados/mis` (bandeja personal) y `comunicados/{id}/pdf` (descarga individual) quedaron sin cambios, abiertos a cualquier rol admin — mismo criterio de "transparencia institucional" ya documentado para `comunicaciones.php`.

**Hallazgo adicional durante la corrección:** el sidebar tenía dos versiones inconsistentes entre sí — el menú combinado (Admin/Director/Coordinador) ya mostraba "Gestionar Comunicados" solo a esos 3 roles, pero los sidebars **exclusivos** de Secretaría y de Registrador Académico mostraban un enlace de gestión completa sin ninguna condición (`layouts/admin.blade.php`, antes en las líneas ~2384 y ~2491). Decisión del usuario: el alcance correcto es solo Admin/Director/Coordinador — se corrigieron esos 2 enlaces para apuntar a `comunicados.mis` en vez de `comunicados.index`, igual que el resto de roles sin ese permiso.

Verificado: `route:list` carga las 19 rutas de `comunicados` sin error, sidebars sin enlaces rotos, suite completa 69/69 sin regresiones.

### Regresión encontrada y corregida al implementar la recomendación #3 de `AUDITORIA_DON_BOSCO_ZURAEDU.md` (2026-08-26)

Al activar `BoletinPolicy` (§2.3 de este documento), `ver()` quedó restringido a `hasRole(['Administrador', 'Director'])` hardcodeado — pero el código original que reemplazaba (`BoletinController::puedeVerTodo()`) permitía también a Coordinador Académico/Primer/Segundo Ciclo, Secretaría, Secretaria Docente, Personal Administrativo y Encargado de Área. Efecto real: **cualquiera de esos roles, aunque tuviera el permiso `ver-boletines` asignado en Spatie, recibía 403 al intentar ver un boletín** — encontrado al probar en navegador el split de `ver-boletines`/`imprimir-boletines` con una cuenta `Encargado de Área` real. Corregido: `ver()` ahora permite a cualquier usuario con `ver-boletines` que no sea docente (los docentes siguen scopeados a sus propios estudiantes) — esto además cubre correctamente a Registrador Académico/Encargado de Registro Académico, que tienen el permiso pero nunca estuvieron en la lista hardcodeada original tampoco.

### Recomendación #3 de la auditoría Don Bosco implementada: separar `imprimir-boletines` de `ver-boletines`

El permiso `imprimir-boletines` existía en Spatie, asignado a roles, pero no se usaba en ningún lado del código (`ver-boletines` gateaba ver, imprimir PDF y exportar ZIP por igual). Corregido en `routes/admin/academico.php`: `boletines.zip`, `boletines.pdf` y `boletines.pdf-anual` ahora requieren además `can:imprimir-boletines`, anidado dentro del grupo `can:ver-boletines` existente. Efecto real verificado en navegador: un rol con solo `ver-boletines` (ej. Encargado de Área) ve el boletín pero recibe 403 al intentar descargar el PDF o el ZIP; un rol con ambos permisos (Administrador, Coordinadores, Secretaría, Registrador Académico, Personal Administrativo, Secretaria Docente) sigue pudiendo hacer ambas cosas sin cambios.

### Hallazgo adicional durante la corrección de §2.2

El middleware `role:` de Spatie **no es interceptado por `Gate::before`** — solo lo intercepta `can:` (que pasa por el sistema de Gates de Laravel). Usar `role:` directamente en una ruta rompe la función "SuperAdmin administra un tenant" para ese módulo específico, sin dar ningún error visible (simplemente el SuperAdmin recibe 403 en ese módulo aunque en todos los demás tenga bypass total). Además, `role:Rol1,Rol2` es un error común: la coma separa el argumento de **guard de autenticación**, no una lista de roles — el separador correcto para múltiples roles es `|`. Ambos problemas se evitan definiendo un `Gate::define()` en `AuthServiceProvider` y usando `can:` en la ruta, incluso para checks que no involucran el modelo de permisos de Spatie. **Regla para todo trabajo futuro de rutas: nunca usar middleware `role:` directamente — siempre `can:` con un permiso Spatie o un Gate definido.**

---

## 1. Roles y permisos reales (Spatie Permission)

21 roles, 25 permisos. La matriz completa e interactiva (con búsqueda) vive en `/admin/ayuda/roles` dentro del propio sistema, con datos en vivo desde la base de datos — no se duplica aquí para evitar que este documento quede desactualizado. Resumen narrativo por rol:

| Rol | Alcance previsto |
|---|---|
| **super_admin** | Plataforma completa — todos los tenants, planes, suscripciones, auditoría global. No tiene permisos Spatie asignados: un `Gate::before` en `AuthServiceProvider.php` le da acceso total sin depender de la tabla de permisos. |
| **Administrador** | Su institución completa: usuarios, año escolar, grupos, docentes, estudiantes, matrículas, asignaturas, períodos, calificaciones, asistencia, indicadores, boletines, configuración, pagos, biblioteca. |
| **Director** | Igual que Administrador excepto `gestionar-usuarios` y `gestionar-configuracion`. |
| **Coordinador Académico / Primer Ciclo / Segundo Ciclo** | Supervisión académica de su ciclo: grupos, docentes, estudiantes, matrículas, calificaciones, asistencia, indicadores, boletines, reportes. Los tres roles tienen permisos Spatie idénticos — se diferencian por el dashboard/sidebar contextual, no por el conjunto de permisos. |
| **Registrador Académico / Encargado de Registro Académico** | Matrícula (pre-inscripción, inscripción, reinscripción, estado), expediente, estructura académica, historial académico, documentos oficiales (boletines/récords/certificados/actas), reportes, SIGERD. Confirmado que **no** ve SuperAdmin, SaaS, Horizon, DevOps (`resources/views/layouts/admin.blade.php:2152-3406`). |
| **Docente / Docente Académico / Docente Técnico / Docente Guía** | Solo sus asignaciones (cadena Docente → Asignación → Grupo → Estudiante). Docente Guía suma `ver-estadisticas` para su sección guía. **Ver hallazgo crítico §3.1 — esta separación en 4 roles es la causa raíz del bug de mayor severidad encontrado.** |
| **Secretaría / Secretaria Docente** | Estudiantes, matrículas, calificaciones/asistencia (solo lectura), boletines (lectura e impresión). |
| **Personal Administrativo** | Lectura de calificaciones/asistencia/boletines, impresión de boletines, estadísticas, supervisión de registros, reportes institucionales. |
| **Caja / Finanzas** | Estudiantes (lectura), pagos (gestión y consulta), reportes institucionales. Sin acceso académico. |
| **Biblioteca** | Solo gestión de biblioteca y servicios. |
| **Recepción** | Estudiantes, matrículas, servicios (carnet/QR/entrada-salida). Sin notas, sin configuración académica, sin finanzas. |
| **Estudiante** | Solo su propia información — nunca resuelve por parámetro de ruta, siempre por `auth()->user()->estudiante` (`PortalEstudianteController.php`, confirmado inmune a IDOR). |
| **Representante (Padre)** | Solo sus hijos — verificado en **38/38** métodos de `PortalPadreController.php` con `$representante->estudiantes()->where('estudiante_id', $estudiante->id)->exists()` antes de proceder. |

---

## 2. Hallazgos de seguridad (por severidad)

### 2.1 — CRÍTICO: comparación de rol por string exacto `'Docente'` ignora los otros 3 roles docente

**Causa raíz única, ~50 ocurrencias en ~25 archivos.** El catálogo de roles tiene 4 variantes con el mismo propósito (`Docente`, `Docente Académico`, `Docente Técnico`, `Docente Guía`), pero gran parte del código solo comprueba `$user->hasRole('Docente')` literal.

**Consecuencia según dónde ocurre — dos direcciones opuestas:**

- **`app/Policies/AsignacionPolicy.php:28,44,64,80`** (`verCalificaciones`, `ingresarCalificaciones`, `verAsistencia`, `ingresarAsistencia`) y **`app/Http/Controllers/Admin/CalificacionController.php:42`** (`docenteActual()`) y **`app/Http/Controllers/Admin/AsistenciaController.php:35`**: si el usuario es "Docente Técnico"/"Docente Académico"/"Docente Guía", el check falla, el código cae al `return true` / trata al usuario como admin → **acceso sin restricción para ver e ingresar notas/asistencia de CUALQUIER asignación del tenant, no solo las propias.**
  - *Escenario de explotación:* un profesor con rol "Docente Técnico" (uso previsto — es el rol del Área Técnica) visita `/admin/calificaciones/{id}` con el ID de una clase ajena y puede modificar notas de estudiantes que no le corresponden.
- **`app/Policies/BoletinPolicy.php:33`, `EstudiantePolicy.php:27`, `GrupoPolicy.php:17,26`**: mismo patrón, pero aquí el fallback es `return false` → **bug funcional inverso**: un Docente Técnico/Académico/Guía legítimo queda bloqueado de ver boletines, perfiles o grupos de sus propios cursos.

**Fix propuesto (no aplicado todavía):** reemplazar `$user->hasRole('Docente')` por `$user->hasAnyRole(['Docente','Docente Académico','Docente Técnico','Docente Guía'])`, o mejor, por `$user->docente !== null` (verificar el registro de dominio en vez del nombre de rol) en los ~50 sitios afectados. Requiere una pasada dedicada dado el volumen — no se debe mezclar con otros fixes de esta auditoría.

### 2.2 — ALTO: middleware genérico `admin.access` sin segmentar por módulo en ~30 de 42 archivos de rutas

Todas las rutas `/admin/*` comparten `EnsureAdminAccess`, que solo verifica "¿el usuario tiene alguno de los 13 roles admin-capable?" — no distingue módulo. La restricción real depende de que cada archivo de rutas agregue su propio `can:`/`role:`, y la mayoría no lo hace (~810 rutas revisadas en 44 archivos):

| Módulo | Rutas sin `can:`/`role:` propio | Riesgo | Qué expone |
|---|---|---|---|
| `nomina.php` | 20 | Alto | Salarios y nómina de docentes, visible/editable por cualquiera de los 13 roles admin-capable (incl. Biblioteca, Recepción) |
| `salud.php` | 12 | Alto | Fichas médicas de estudiantes (PII sensible) |
| `disciplina.php` | 6 | Alto | Expedientes disciplinarios |
| `billing.php` | 5 | Alto | Checkout/gestión de la suscripción SaaS del tenant |
| `registro_academico.php` | 12 | Alto | Baja, reactivación y traslado de matrícula — abierto a los 13 roles, no solo a Registro Académico |
| `personas.php` (bloque `perfiles/*`) | ~13 | Alto | Certificado de notas, certificado de conducta, historial académico |
| `academico.php` (bloque `registro/*`) | 9 | Alto | `registro.guardar`/`guardar-lote` permite **guardar notas MINERD directamente** sin `can:` |
| `academico.php` (resto: cursos, competencias, planes de clase, instrumentos, auditoría de notas, observaciones) | ~48 | Medio-Alto | CRUD académico y auditoría de calificaciones |
| `seguimiento_social.php`, `riesgo.php` | 16 | Medio | Casos de seguimiento social y riesgo académico |
| ~15 módulos menores (becas, cafetería, carnet, equipos, horarios, inventario, proyectos, transporte, tutorías, reuniones, solicitudes, soporte…) | ~200 | Bajo-Medio | Sin segmentar por rol, pero de menor sensibilidad |

**Lo que sí está bien y sirve de plantilla:** `pagos.php` (3 grupos: `can:ver-pagos`, `can:gestionar-pagos`, `role:Administrador`), el CRUD principal de `personas.php` (docentes/estudiantes/grupos/matrículas con `can:gestionar-*`), y el grupo `/superadmin/*` (100% bajo `['auth','super_admin']`, sin excepciones coladas).

### 2.3 — MEDIO: 3 de 4 Policies registradas son código muerto de seguridad

`AuthServiceProvider.php` registra `EstudiantePolicy`, `GrupoPolicy` y `BoletinPolicy`, pero **ningún controlador las invoca** (`grep -rn "authorize(" app/Http/Controllers` → 0 resultados para estas 3). Están bien escritas (scoping correcto por asignación docente), pero no protegen nada en producción — dan una falsa sensación de seguridad. Solo `AsignacionPolicy` se usa de verdad (12 invocaciones reales en `AsistenciaController.php` y `CalificacionController.php`).

### 2.4 — BAJO: bug latente de nombre de rol en Policies

`GrupoPolicy.php:17,22` y `BoletinPolicy.php:29` comparan contra `hasRole(['Admin', 'Director'])` — el rol real en BD es **"Administrador"**, no "Admin". Sin efecto práctico hoy porque estas policies no se invocan (§2.3), pero quedaría activo y bloquearía al Administrador real el día que alguien las active.

---

## 3. Lo que ya está bien (confirmado, no tocar)

- **Portales de Docente, Padre y Estudiante** (`PortalDocenteController`, `PortalPadreController`, `PortalEstudianteController`) — scoping por relación verificado método por método (91 métodos revisados), sin IDOR encontrado.
- **7 dashboards contextuales reales** (`DashboardController.php:57-65`, patrón `match(true)`): admin, caja, secretaria, coordinador, registro, biblioteca, recepcion — con overlay adicional para Docente.
- **Menú del sidebar** protegido por flags PHP calculados por rol (`$isAdmin`, `$isDocente`, `$isRegistro`, etc.) + `@if()` — funciona, aunque es más frágil que usar `@can()`/`@role()` de Blade porque depende de nombres de rol como string.
- **Registro Académico** correctamente aislado del panel SuperAdmin/SaaS/Horizon/DevOps, tanto en menú como en dashboard.
- **`super_admin` vía `Gate::before`** — corregido hoy mismo (antes de esta auditoría), verificado en navegador real.

---

## 4. Próximos pasos (Fase "PROPONER" — pendiente de aprobación, nada aplicado)

Orden sugerido por impacto/riesgo, a confirmar contigo antes de tocar código:

1. **Fix del anti-patrón `hasRole('Docente')`** (§2.1) — el de mayor severidad real (acceso no autorizado a notas/asistencia ajenas). Requiere revisar los ~50 sitios uno por uno para decidir si el fix correcto es `hasAnyRole([...4 roles])` o `$user->docente !== null` según el contexto de cada archivo.
2. **Segmentar por `can:`/`role:` los módulos de riesgo Alto de §2.2** (nomina, salud, disciplina, billing, registro_academico, perfiles, registro/guardar) — son los que exponen datos sensibles a roles que no deberían tenerlos.
3. **Decidir el destino de las 3 Policies muertas** (§2.3): o se activan con `$this->authorize()` en los controladores correspondientes, o se documentan como redundantes y se elimina la ambigüedad (sin borrarlas sin confirmar contigo primero).
4. **Corregir el bug de nombre de rol `'Admin'` → `'Administrador'`** (§2.4) — trivial, pero debe ir junto con el punto 3 para no activar una policy rota.
5. **Módulos de riesgo Medio/Bajo de §2.2** — el resto de academico.php, seguimiento_social, riesgo, y los ~15 módulos menores.

Ninguno de estos pasos se ha ejecutado. A la espera de tu confirmación sobre por dónde empezar.
