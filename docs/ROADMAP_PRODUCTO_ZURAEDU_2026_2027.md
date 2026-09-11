# Roadmap de Producto — ZuraEdu 2026-2027

Documento maestro de la fase de auditoría + análisis + priorización +
arquitectura pedida explícitamente el 2026-09-05, **sin ningún cambio de
código, migración, ruta, permiso ni commit** — cumplido según regla
explícita del pedido. Los otros 5 documentos de este roadmap contienen el
detalle; este es el mapa completo y la decisión de por dónde empezar.

**Documentos de este roadmap**:
1. `MATRIZ_GAPS_PRODUCTO_ZURAEDU.md` — inventario + los 10 gaps re-auditados + módulos adicionales.
2. `BENCHMARK_PRODUCTO_ZURAEDU.md` — comparación contra EduPage/PowerSchool/Alma y el mercado 2026.
3. `PRIORIZACION_ZURAEDU.md` — matriz de puntuación 1-5 y clasificación P0-P3.
4. `ARQUITECTURA_EVOLUCION_ZURAEDU.md` — mapa de dependencias + plan técnico P0/P1.
5. `DECISIONES_PRODUCTO_ZURAEDU.md` — qué NO construir y decisiones de negocio pendientes.
6. Este documento — roadmap por fases y resumen ejecutivo.

---

## Fase 7 — Roadmap por fases

El orden **no sigue una plantilla genérica** (fundamentos→cumplimiento→
padres→finanzas→operaciones→IA→escalabilidad) — se derivó del mapa de
dependencias real (`ARQUITECTURA_EVOLUCION_ZURAEDU.md`), donde los 3 P0
resultaron ser independientes entre sí y de esfuerzo bajo, así que van
juntos primero sin importar el orden interno.

### FASE 0 — Cierre de riesgos ya identificados (independientes, esfuerzo bajo)
- Tests del algoritmo MINERD de promoción.
- Carnet+: WhatsApp + paridad admin/API.
- Conectar justificación de ausencia con el registro real de asistencia.

*Por qué primero*: los tres son extensiones de código que ya existe y
funciona parcialmente — no requieren ninguna decisión de negocio previa,
no tienen dependencias entre sí, y dos de los tres cierran un riesgo real
(uno académico — decide repitencia; uno de confianza — el padre cree que
algo se aprobó y no pasa nada).

### FASE 1 — Decisiones de negocio bloqueantes
- Definir el flujo de Reingreso.
- Decidir el alcance real de NCF/e-CF (¿el MVP ya implementado basta, o
  se invierte en un proveedor certificado?) — incluye levantar la
  categoría de contribuyente real de cada tenant.

*Por qué aquí y no antes/después*: no son tareas técnicas, pero bloquean
cualquier trabajo adicional en Matrícula/Registro y en Pagos/Finanzas
respectivamente. Conviene resolverlas mientras se ejecuta la Fase 0, no
después.

### FASE 2 — Finanzas
- Dashboard financiero por concepto/grado/sección.
- Historial de facturas del billing SaaS.

*Depende de*: la decisión de Fase 1 sobre NCF, para saber si el dashboard
debe incluir estado de comprobantes fiscales.

### FASE 3 — Experiencia de padres
- Citas padre-docente con agenda real (cruce contra `HorarioDetalle`).
- Carnet+ ↔ Transporte escolar.

*Por qué después de Finanzas y no antes*: ambas son mejoras de
experiencia con buen impacto en padres pero sin urgencia de riesgo o
cumplimiento — tiene sentido que compitan por prioridad después de cerrar
lo que sí tiene riesgo/plazo (Fases 0-2).

### FASE 4 — Plataforma / IA
- Unificar ZuraAI en un servicio central con medición de uso por tenant.

*Por qué al final*: es el ítem de mayor esfuerzo (refactor de 4
controladores) y su valor principal es habilitar un modelo de precios de
IA — más relevante cuando ya haya una base de tenants pagando lo
suficiente como para que el costo de Gemini importe de verdad.

### FASE 5 — Deuda técnica y auditoría transversal (sin urgencia de negocio)
- Observer en `Pago` + auditoría de login/logout y cambios de rol.
- Unificar Equipos + Inventario.
- Centralizar generación de documentos/certificados.
- Extender el patrón `ver-X`/`gestionar-X` a más recursos.

### Pendiente de validación externa antes de siquiera planificar
Multi-campus, conciliación bancaria automática, menú/alérgenos de
comedor, integraciones con Google Workspace/Microsoft 365, analítica SaaS
— ver `DECISIONES_PRODUCTO_ZURAEDU.md` para el detalle de qué falta
confirmar en cada caso.

---

## Fase 11 — Nivel de madurez del producto (0-100)

Escala: 0-40 incipiente · 41-70 competitivo · 71-90 avanzado · 91-100 líder.

### Puntuación: **79/100 — Avanzado**

Justificación por componente (no es un número inflado — cada parte se
sostiene con evidencia de las auditorías anteriores):

| Componente | Peso | Nota | Razón |
|---|---|---|---|
| Núcleo académico (horarios, calificaciones, MINERD) | 25% | 85 | Generador de horarios y MINERD por encima del estándar regional; calificaciones con deuda de diseño conocida y ya mitigada en 14/15 sitios |
| Experiencia de usuario (portales, Carnet+, ZuraClass, app móvil) | 25% | 78 | Amplia cobertura (79 pantallas, ~30 grupos de API), varios flujos "parciales" recién descubiertos que ya tienen la mitad construida |
| Operaciones (finanzas, biblioteca, cafetería, transporte, disciplina, salud) | 20% | 80 | Disciplina y Salud resultaron más completos de lo estimado; Biblioteca/Transporte son el punto más débil de este bloque |
| Plataforma SaaS (SuperAdmin, billing, onboarding, multi-tenant) | 15% | 82 | Onboarding self-service y billing con Stripe real ya son de nivel comercial; falta historial de facturas y analítica de negocio |
| Cumplimiento y confianza (NCF, auditoría, tests de riesgo académico) | 10% | 55 | El punto más débil: NCF apenas MVP, cero tests en el algoritmo de mayor riesgo, auditoría de seguridad con huecos conocidos |
| IA | 5% | 65 | Presente en 4 puntos, funcional, pero fragmentado y sin visibilidad de costo |

**Lectura**: ZuraEdu no necesita "ponerse al día" con el mercado — necesita
cerrar un número acotado y conocido de brechas de cumplimiento/confianza
(el componente más débil, con diferencia) antes de venderse con total
seguridad a instituciones grandes o exigir un salto a "líder".

---

# RESUMEN EJECUTIVO

## Estado actual de ZuraEdu

**Nivel actual: 79/100 — Avanzado.**

### Fortalezas
1. Núcleo académico (horarios + MINERD) por encima del estándar del
   mercado dominicano, con evaluación por competencias ya ajustada al
   currículo real — algo que un SIS genérico importado no ofrece.
2. Plataforma SaaS multi-tenant con billing real (Stripe + transferencia)
   y onboarding self-service — nivel comercial ya alcanzado, no un
   prototipo.
3. Amplitud de cobertura funcional confirmada en esta auditoría: varios
   módulos que se creían ausentes o básicos (Disciplina, Salud, citas
   padre-docente, justificación de ausencias) resultaron estar más
   avanzados de lo evaluado inicialmente — el producto es más maduro de
   lo que la primera evaluación reflejó.
4. App móvil con cobertura amplia (79 pantallas, ~30 grupos de API) —
   backend ya expone prácticamente todo lo que tiene el panel admin.
5. IA integrada de forma nativa en 4 puntos del producto (aunque
   fragmentada) — pocos competidores de este segmento la tienen tan
   distribuida en el flujo diario.

### Debilidades
1. Cumplimiento fiscal (NCF/e-CF) apenas en MVP de registro manual — el
   componente de menor puntuación de todo el sistema.
2. El único algoritmo que decide si un estudiante repite el año
   (`calcularPromocion` MINERD) tiene cero tests.
3. Varias funcionalidades están "a medias" de forma no evidente hasta
   auditar el código: notificaciones de Carnet+, citas padre-docente y
   justificación de ausencias tienen la mitad construida pero no
   conectada — riesgo de que alguien intente "reconstruirlas desde cero"
   sin saber que ya existe la mitad.
4. Auditoría de seguridad inconsistente: `Pago` sin Observer, login/roles
   sin registro — huecos conocidos y ya priorizados, pero abiertos.
5. Fragmentación en dos áreas: ZuraAI (4 integraciones separadas) y
   Equipos/Inventario (2 sistemas paralelos para un concepto similar).

## TOP 5 prioridades

1. ~~Tests del algoritmo MINERD de promoción.~~ **CERRADO 2026-09-11** — ya existían (commit `af24ee1`, verificado 12/12 pasando), ver nota abajo.
2. Carnet+: agregar WhatsApp + corregir paridad admin/API.
3. Conectar la justificación de ausencia con el registro real de asistencia.
4. Decidir Reingreso y el alcance real de NCF/e-CF (dos decisiones de
   negocio, no técnicas, que bloquean trabajo futuro en sus áreas).
5. Dashboard financiero consolidado por concepto/grado/sección.

## PRÓXIMO DESARROLLO RECOMENDADO

**CERRADO (2026-09-11)**: al retomar este ítem se descubrió que
`tests/Feature/RegistroAcademicoServicePromocionTest.php` ya existía
(commit `af24ee1`, 2026-09-04) y cubre los 11 casos propuestos abajo casi
punto por punto. Se corrió la suite completa y pasan los 12 tests (16
aserciones) contra el código actual del método. No se modificó el
algoritmo ni el test. Con este ítem cerrado, el siguiente desarrollo
recomendado pasa a ser la vista "Hoy" para Docente
(`docs/ZURAEDU_IMPLEMENTATION_ROADMAP.md`).

**Tests del algoritmo MINERD de promoción (`RegistroAcademicoService::calcularPromocion`).** *(sección original, dejada como referencia del análisis de riesgo)*

### RAZÓN
Es la única lógica de negocio de alto impacto en todo el sistema (de las
más de 20 áreas auditadas en esta sesión) que decide un resultado
irreversible para una persona real — si un estudiante repite el año — sin
ninguna red de seguridad. Es además el ítem de menor esfuerzo de toda la
lista P0 (2/5): no requiere ninguna decisión de negocio previa, no
modifica el algoritmo, y no tiene dependencias con ningún otro trabajo
pendiente.

### RIESGO SI NO SE HACE
Un cambio futuro al código (un refactor, una migración de la escala de
calificaciones, un ajuste al cálculo de asistencia) podría alterar
silenciosamente quién es promovido y quién no, sin que ningún test lo
detecte — y el efecto solo se vería meses después, al cierre del año
escolar, cuando ya sea demasiado tarde para corregirlo antes de que
afecte a estudiantes reales.

### BENEFICIO ESPERADO
Una vez escritos los 11 casos de prueba propuestos en
`MATRIZ_GAPS_PRODUCTO_ZURAEDU.md` (GAP 02), el algoritmo de promoción
queda con la misma cobertura de confianza que ya tiene el resto del
núcleo académico — y el equipo puede tocar código relacionado
(calificaciones, asistencia, escalas) con la certeza de que un test
avisará si algo rompe la regla de promoción real.

### RECONCILIACIÓN CON EL OTRO ROADMAP (docs/ZURAEDU_IMPLEMENTATION_ROADMAP.md)
Existía un segundo roadmap de producto de la misma sesión que recomendaba
como primer desarrollo la vista "Hoy" para Docente. Se había decidido que
este ítem (tests de promoción) iba primero por ser corrección/cumplimiento
sobre un resultado irreversible para un estudiante real. Al resultar que
ya estaba cerrado, la vista "Hoy" para Docente pasa a ser el próximo
desarrollo recomendado en orden global.

---

**Regla final cumplida**: este documento y los otros 5 son análisis y
diseño únicamente. No se implementó, migró, ni commiteó nada. La
implementación empieza solo cuando el usuario apruebe este roadmap,
iniciativa por iniciativa.
