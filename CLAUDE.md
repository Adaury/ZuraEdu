# ZuraEdu / SGE — Instrucciones del proyecto

## Verificar antes de crear o modificar

Antes de crear, modificar o eliminar cualquier funcionalidad, primero
comprobar qué existe ya: rutas, controladores, modelos, migraciones,
tablas/relaciones, middleware, Policies/Gates, permisos (Spatie), vistas,
servicios, APIs. No asumir que algo existe (o no existe) por el nombre de
un archivo — comprobarlo leyendo el código.

- Si una funcionalidad ya existe y funciona: no tocarla innecesariamente.
- Si existe pero está incompleta: completarla, no reescribirla desde cero.
- Si existe pero tiene errores: corregirla en el sitio donde está.
- Si no existe: decirlo explícitamente antes de implementarla.

No dupliques funcionalidades ni crees código innecesario. Al reportar un
hallazgo o un fix, sé concreto: qué archivo, qué línea, qué ruta, qué
permiso/rol lo protege — "ya está implementado" sin esa evidencia no basta.

## Multi-tenant y permisos — no confiar en el cliente

Este proyecto es multi-tenant (`tenant_id` + `BelongsToTenant`). Cualquier
query, policy o endpoint nuevo debe quedar aislado por tenant y no confiar
únicamente en un ID recibido del navegador (params de ruta, body, query) —
verificar siempre la relación real (ej. padre→hijo, docente→asignación)
contra la base de datos, no solo el ID.

## Migraciones

No modificar una migración antigua que ya se haya ejecutado (en local,
staging o producción) para resolver un cambio estructural. Si hace falta
cambiar la estructura: crear una migración nueva.

## Operaciones destructivas

No ejecutar `migrate:fresh`, `db:wipe`, `DROP DATABASE`/`DROP TABLE`, ni
borrar datos o código existente masivamente sin autorización explícita del
usuario para esa acción concreta.

## Idioma

Responder siempre en español.

## Auditoría completa

Cuando el usuario pida explícitamente una auditoría/radiografía completa
del sistema (no una tarea puntual), usar el checklist detallado en
[`docs/PROMPT_AUDITORIA_COMPLETA_ZURAEDU.md`](docs/PROMPT_AUDITORIA_COMPLETA_ZURAEDU.md)
— cubre módulo por módulo (académico, Carnet+, Classroom, portales,
finanzas, SIGERD, SuperAdmin, rendimiento, seguridad, base de datos, rutas)
con una clasificación obligatoria (🟢 existe y funciona / 🟡 incompleta /
🟠 con errores / 🔴 no existe / 🔵 debe mejorarse) y un reporte final
estructurado.
