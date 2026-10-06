# Bitácora de trabajo — ZuraEdu / SGE

> Del 1 al 6 de octubre de 2026 · 84 commits en `master` · CI en verde en cada entrega.

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

## 2026-10-01

| Área | Tipo | Qué se hizo | Resultado / impacto | Commits | Estado |
|---|---|---|---|---|---|
| Seguridad | Corrección | Las reglas exists:/unique: ahora respetan el colegio y el borrado lógico; TrustProxies ya no confía en cualquiera (X-Forwarded-For falsificada). | Antes se aceptaban ids de otros colegios y se podía falsificar la IP; ahora no. | `f088bcd, b7a2845, 46bc47b` | Hecho |
| Migración a TypeScript | Función | API TypeScript (NestJS + Kysely): matrículas con cupo, notificaciones con push y tiempo real (Reverb), foto del estudiante, límite de peticiones; web de prueba bajo /nuevo; Nginx con TLS probado. | Esqueleto con paridad respecto a Laravel; decisión de migrar cerrada por el usuario. | `e835955, f84c24d, bcb980c, 74131af, f837bc8, c5969ee, 77ae7a9, 5b0adaf` | Hecho |
| Migración a TypeScript | Documentación | Benchmark de carga concurrente Laravel vs TypeScript y evaluación de costo operativo (CPU y memoria por petición). | Datos para decidir tamaño de servidor por colegio. | `5d7ecd3, 7020eb9, a2aa32f, 8c0994c` | Hecho |
| Despliegue | Corrección | deploy.sh y rollback.sh recargan PHP-FPM y explican los fallos; el código exige PHP 8.3+; dos despliegues en el mismo segundo ya no chocan. | Despliegue y reversión más seguros, con runbook del primer despliegue. | `5b732df, 51ede9e, e3e7739` | Hecho |
| Rendimiento | Rendimiento | Validación de nómina SIGERD sin hidratar modelos. | 636 ms → 44 ms con 4.949 matrículas. | `efb0925` | Hecho |
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
