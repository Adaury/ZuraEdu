# ZuraEdu — Evolución de Producto (2026-09-05)

Documento de visión del "Prompt Maestro" de evolución hacia una plataforma
tipo Moodle (facilidad administrativa) + EduPage (experiencia diaria),
adaptada al contexto dominicano, sin copiar código, diseño ni branding de
ninguno de los dos. Basado en auditoría real de código — ver
`ZURAEDU_PRODUCT_GAPS.md` para el detalle completo por componente.

## Diagnóstico honesto antes de proponer nada

ZuraEdu **ya es** técnicamente mucho de lo que este prompt pide, solo que
no se percibe así porque las piezas están repartidas:

- Multi-tenancy real y aislado: ✅ ya existe.
- RBAC granular a nivel de backend: ✅ ya existe.
- Módulos activables por centro: ✅ existe, pero en dos sistemas que no se
  hablan entre sí.
- Planificación docente con jerarquía Plan→Unidad→Actividad: ✅ existe,
  pero en 3 sistemas paralelos sin conectar a ZuraClass.
- Dashboards diferenciados por rol: ✅ existe la lógica condicional; falta
  el enfoque "Hoy" consolidado.
- IA que genera borrador sin publicar automático: ✅ ya es el patrón real.

**Lo que genuinamente NO existe** es más acotado de lo que el prompt
asume: un portal público configurable por centro, un constructor visual de
bloques, y la conexión entre planificación y ZuraClass.

## El concepto: de "aplicación administrativa" a "sistema operativo del centro"

La diferencia no está en agregar más módulos — ZuraEdu ya tiene más
módulos que la mayoría de sistemas comparables en el mercado dominicano
(ver benchmark previo de esta sesión). La diferencia está en **conectar lo
que ya existe** para que cada rol viva su día dentro de una sola
experiencia, en vez de navegar entre módulos independientes:

```
CENTRO EDUCATIVO
   ↓
IDENTIDAD INSTITUCIONAL       (🟢 ya existe — ConfigInstitucional)
   ↓
PÁGINA / PORTAL PÚBLICO       (🔴 no existe — ver ZURAEDU_TENANT_SITE_ARCHITECTURE.md)
   ↓
MÓDULOS                       (🟡 existe, fragmentado — ver ZURAEDU_PRODUCT_GAPS.md)
   ↓
USUARIOS / ROLES / PERMISOS   (🟢 ya existe, sólido)
   ↓
EXPERIENCIA PERSONALIZADA     (🟡 existe la lógica por rol, falta la vista "Hoy" — ver ZURAEDU_ROLE_EXPERIENCE.md)
```

## Qué significa "extraer conceptos sin copiar" en la práctica

- **De Moodle**: la idea de un centro de administración único y
  autoservicio (no el código, no el diseño) — ZuraEdu ya tiene el CRUD,
  falta el hub de navegación (pendiente de documentar en un roadmap futuro).
- **De EduPage**: la idea de que el día a día del docente vive en una sola
  pantalla ("Hoy") en vez de repartido en menús — ZuraEdu ya tiene los
  datos, falta la vista consolidada (`ZURAEDU_ROLE_EXPERIENCE.md`).
- Ninguno de los dos tiene el enfoque MINERD-específico ni el modelo SaaS
  multi-tenant con billing propio que ZuraEdu ya tiene — esas son ventajas
  reales de ZuraEdu que no hay que diluir al "parecerse" a otros productos.

## Documentos de este roadmap

1. `ZURAEDU_PRODUCT_GAPS.md` — matriz de estado por componente.
2. `ZURAEDU_ROLE_EXPERIENCE.md` — dashboard dinámico y vista "Hoy" por rol.
3. `ZURAPLAN_ARCHITECTURE.md` — las 3 líneas de planificación existentes y
   la decisión de unificarlas o solo conectarlas a ZuraClass.
4. `ZURAEDU_TENANT_SITE_ARCHITECTURE.md` — portal público + constructor
   visual (lo único genuinamente nuevo de todo este roadmap).
5. `ZURAEDU_IMPLEMENTATION_ROADMAP.md` — fases, prioridades y el primer
   desarrollo recomendado.

El centro de administración unificado tipo Moodle (mencionado arriba como
concepto) no tiene todavía un documento de arquitectura propio — queda
pendiente de escribir si se decide abordarlo.
