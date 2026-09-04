# Prompt maestro — Verificación completa de ZuraEdu

Uso: pegar este prompt completo en una sesión nueva (o invocarlo explícitamente)
cuando se quiera una radiografía exhaustiva de todo el sistema, módulo por
módulo, antes de seguir agregando funcionalidades. No es una regla que se
aplique en cada tarea pequeña — para eso está la regla condensada en
`CLAUDE.md` ("Verificar antes de crear o modificar"). Este documento es el
checklist completo para cuando se pide explícitamente una auditoría total.

---

Actúa como AUDITOR SENIOR, ARQUITECTO DE SOFTWARE Y QA ENGINEER especializado
en Laravel, MySQL, APIs REST, RBAC, sistemas educativos SaaS y aplicaciones
multi-tenant.

Tu misión NO es comenzar creando código. PRIMERO debes VERIFICAR
exhaustivamente el estado actual del proyecto ZuraEdu y determinar qué
existe, qué funciona, qué está incompleto, qué está defectuoso y qué falta.

## 1. Regla principal — no duplicar

Antes de crear, modificar o eliminar cualquier cosa:

1. Inspecciona el código existente.
2. Inspecciona las rutas.
3. Inspecciona controladores.
4. Inspecciona modelos.
5. Inspecciona migraciones.
6. Inspecciona tablas y relaciones.
7. Inspecciona middleware.
8. Inspecciona Policies/Gates.
9. Inspecciona permisos y roles.
10. Inspecciona vistas.
11. Inspecciona componentes.
12. Inspecciona servicios.
13. Inspecciona APIs.
14. Inspecciona dashboards.
15. Inspecciona menús.
16. Inspecciona JavaScript.
17. Inspecciona configuración.
18. Inspecciona pruebas existentes.

No crees una función que ya exista. Si existe pero está incompleta,
MEJÓRALA. Si existe pero tiene errores, CORRÍGELA. Si existe y funciona
correctamente, NO LA MODIFIQUES innecesariamente.

## 2. Clasificación obligatoria

Cada funcionalidad debe clasificarse utilizando exactamente uno de estos
estados:

- 🟢 EXISTE Y FUNCIONA
- 🟡 EXISTE PERO ESTÁ INCOMPLETA
- 🟠 EXISTE PERO TIENE ERRORES
- 🔴 NO EXISTE
- 🔵 EXISTE Y DEBE MEJORARSE
- ⚫ NO APLICA

No marques una funcionalidad como existente solamente porque encuentres un
archivo con un nombre parecido. Debes comprobar que realmente funcione.

## 3. Verificación funcional

Para cada módulo verifica: ¿existe? ¿tiene interfaz? ¿tiene rutas? ¿tiene
controlador? ¿tiene modelo? ¿tiene migración? ¿tiene tablas? ¿tiene
relaciones? ¿tiene validaciones? ¿tiene permisos? ¿tiene protección
multi-tenant? ¿funciona realmente? ¿tiene errores? ¿tiene pruebas? ¿tiene
problemas de rendimiento? ¿tiene problemas de seguridad?

## 4. Roles y permisos

Verifica que ZuraEdu tenga permisos reales por rol. Roles principales:
SuperAdministrador, Administrador, Registro Académico, Coordinador
Académico, Docente Académico, Docente Técnico, Docente Guía, Finanzas/Caja,
Secretaría, Recepción/Carnet+, Padre/Madre/Tutor, Estudiante.

**Importante**: no basta con ocultar botones o menús. Debes verificar:
Middleware, Policies, Gates, Spatie Permission si está instalado,
autorización en rutas, autorización en API, controladores, acceso directo
mediante URL, acceso mediante solicitudes HTTP, restricción por tenant_id,
restricción por propietario de los datos. Un usuario NO debe poder acceder
escribiendo manualmente una URL que no le corresponde.

## 5. Multi-tenant

Verifica que cada centro educativo esté correctamente aislado. Comprobar:
tenant_id, usuarios, estudiantes, docentes, cursos, secciones, asignaturas,
calificaciones, asistencia, matrícula, finanzas, comunicaciones, archivos,
reportes. Un centro educativo nunca debe poder consultar información de
otro centro.

Prueba especialmente consultas: `index()`, `show()`, `edit()`, `update()`,
`destroy()`, APIs, búsquedas, reportes, exportaciones.

## 6. Módulos académicos

Verifica: Centros educativos, Niveles, Grados, Secciones, Períodos
académicos, Estudiantes, Expediente estudiantil, Matrícula, Reingreso,
Traslado, Docentes, Asignaturas, Cursos, Horarios, Asistencia,
Calificaciones, Competencias, Evaluaciones, Registro académico, Boletines,
Actas, Historial académico, Certificaciones, Promoción, Reportes.

## 7. Registro de calificaciones

Verifica especialmente el sistema de calificaciones. No asumas que una
tabla genérica de notas es suficiente. Comprobar: competencias,
indicadores, períodos, evaluaciones, puntuaciones, ponderaciones,
recuperación, calificación final, promedio, promoción, observaciones.
Verifica que los cálculos coincidan con la lógica definida para el Registro
Oficial Dominicano. Si existe una implementación anterior, analízala antes
de modificarla.

## 8. Carnet+ y asistencia

Verifica: Carnet digital, QR, Identificación del estudiante, Entrada,
Salida, Asistencia, Inasistencia, Tardanza, Justificación, Notificación a
padres, Historial, Reportes, Control mediante dispositivo/kiosco,
Seguridad del QR, Prevención de duplicación de registros.

## 9. Classroom

Verifica: Cursos, Actividades, Tareas, Archivos, Entrega de estudiantes,
Corrección docente, Calificación, Comentarios, Recursos, Chat,
Notificaciones, Videoconferencia, Historial.

## 10. Portal de padres

Verifica que el padre/tutor solamente pueda visualizar sus hijos. Debe
poder consultar: Boletín, Calificaciones, Asistencia, Actividades, Tareas,
Horario, Docentes, Comunicaciones, Notificaciones, Estado académico. Debe
existir una comprobación real de relación PADRE → HIJO. Nunca confiar
únicamente en el ID enviado desde el navegador.

## 11. Portal del estudiante

Verifica: Perfil, Horario, Asistencia, Calificaciones, Actividades, Tareas,
Materiales, Comunicaciones, Notificaciones. El estudiante solamente puede
acceder a sus propios datos.

## 12. Finanzas

Verifica: cuentas por cobrar, pagos, facturación, recibos, balances,
deudas, historial, validaciones, reportes. También verifica si existe
alguna regla relacionada con entrega de boletines, impresión de documentos,
restricciones por deuda. Si existe, verifica que esté correctamente
implementada y protegida.

## 13. SIGERD

Verifica qué existe actualmente relacionado con SIGERD. NO inventes una
integración. Determina si existe: módulo SIGERD, importación, exportación,
generación de archivos, sincronización, mapeo de datos, validación,
historial de envíos, errores, logs. Clasifica claramente lo que realmente
existe.

## 14. SuperAdministrador SaaS

Verifica: centros educativos, activación/desactivación, suscripciones,
planes, estado de pago, usuarios, límites, estadísticas, configuración
global. El SuperAdministrador debe poder administrar la plataforma SaaS sin
acceder innecesariamente a información académica privada de cada centro.

## 15. Interfaz y experiencia de usuario

Audita: Dashboard, Sidebar, Menús, Responsive, Modo oscuro, Tablas,
Formularios, Modales, Alertas, Loading, Paginación, Búsquedas, Filtros.
Verifica especialmente que cada rol tenga su propio dashboard y menú. NO
reutilizar automáticamente el dashboard de Administrador para todos los
roles.

## 16. Rendimiento

Investiga por qué la aplicación puede sentirse lenta al cambiar de
pestaña, abrir módulos, cerrar sesión, cargar tablas, buscar estudiantes,
cargar dashboards. Revisa: consultas N+1, eager loading, índices, consultas
duplicadas, cache, Redis, colas, jobs, Livewire/AJAX, JavaScript, archivos
grandes, consultas innecesarias. NO agregues Redis, Horizon, WebSockets u
otra tecnología solamente porque exista en el proyecto — primero determina
si realmente es necesaria (para este repo estas tres YA están instaladas
y en uso; el punto aplica a no añadir infraestructura nueva sin necesidad
real).

## 17. Seguridad

Audita: autenticación, autorización, CSRF, XSS, SQL Injection, Mass
Assignment, validación, subida de archivos, acceso a archivos, sesiones,
APIs, IDs manipulables, IDOR, tenant isolation, permisos, logs.

## 18. Base de datos

Verifica: tablas duplicadas, migraciones duplicadas, columnas duplicadas,
relaciones incorrectas, claves foráneas, índices, nombres inconsistentes,
datos huérfanos, migraciones ejecutadas, migraciones pendientes.

**Muy importante**: no modificar una migración antigua que ya haya sido
ejecutada en producción para solucionar un cambio estructural. Si se
necesita cambiar la estructura: CREAR UNA NUEVA MIGRACIÓN.

## 19. Rutas

Ejecuta y analiza `php artisan route:list`. Busca: rutas duplicadas, rutas
sin middleware, rutas con permisos incorrectos, rutas accesibles por roles
incorrectos, rutas sin controlador, endpoints innecesarios.

## 20-23. Pruebas y trazabilidad

Para cada hallazgo, documentar con el detalle exacto: migración,
middleware, permiso, rol, vista/componente. Ejemplo:

- Ruta: `routes/web.php`
- Controlador: `app/Http/Controllers/Academic/StudentController.php`
- Método: `index()`
- Vista: `resources/views/academic/students/index.blade.php`
- Permiso: `students.view`
- Rol: `registro_academico`

## 24. Reporte de cambios

Crear `docs/VERIFICACION_CAMBIOS_ZURAEDU.md` indicando: archivos creados,
archivos modificados, migraciones creadas, funcionalidades corregidas,
funcionalidades nuevas, permisos agregados, pruebas realizadas, errores
encontrados, errores solucionados.

## 25. Regla de no destrucción

NO ejecutar sin autorización explícita: `migrate:fresh`, `db:wipe`, `DROP
DATABASE`, `DROP TABLE`, eliminación masiva de datos, comandos
destructivos. No eliminar código existente solamente porque parezca
antiguo — primero determinar si está siendo utilizado.

## 26. Resultado final

Al terminar entregar:

1. Resumen ejecutivo.
2. Funcionalidades existentes.
3. Funcionalidades incompletas.
4. Funcionalidades con errores.
5. Funcionalidades faltantes.
6. Funcionalidades mejoradas.
7. Problemas de seguridad.
8. Problemas de multi-tenant.
9. Problemas de rendimiento.
10. Problemas de base de datos.
11. Problemas de permisos.
12. Pruebas realizadas.
13. Archivos modificados.
14. Archivos nuevos.
15. Ubicación exacta de cada función.
16. Recomendaciones.
17. Estado final del proyecto.

## Regla más importante

NO decir simplemente "ya está implementado". Se debe demostrar indicando:
qué existe, dónde existe, cómo funciona, qué permiso lo protege, qué rol
puede utilizarlo, qué tabla utiliza, qué ruta utiliza, qué prueba lo
validó. Y si no existe, decir claramente 🔴 NO EXISTE, y posteriormente
implementarlo.

No duplicar funcionalidades. No crear código innecesario. No romper
funcionalidades existentes. No modificar migraciones ya ejecutadas sin
justificación.

**Primero AUDITA. Después CORRIGE. Después IMPLEMENTA. Después PRUEBA.
Finalmente DOCUMENTA.**
