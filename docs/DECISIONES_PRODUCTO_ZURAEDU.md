# Decisiones de Producto — ZuraEdu (2026-09-05)

Fase 12 (obligatoria): qué NO construir, y por qué. Más las decisiones de
negocio pendientes que bloquean trabajo técnico. Nada aquí es una
recomendación de "no hacerlo nunca" — es "no hacerlo ahora, y por qué".

## Qué NO debemos construir (todavía)

| Iniciativa | Por qué no ahora |
|---|---|
| **e-CF real (integración con proveedor certificado DGII)** | Requiere una decisión comercial (qué proveedor, costo, quién lo paga) antes de cualquier línea de código — construir sin eso es trabajo desechable. El MVP de "registrar y mostrar" (ya implementado) cubre la necesidad inmediata sin ese riesgo. |
| **GPS/tracking en vivo de transporte** | Fuera de alcance realista para el problema real confirmado (que el padre sepa que el hijo subió al bus) — control de abordaje puntual con Carnet+ ya lo resuelve sin la complejidad/costo de tracking continuo. |
| **Tabla `CitaPadreDocente` nueva y paralela** | Ya existe `SolicitudRepresentante` con el tipo `cita_docente` y todo el flujo de aprobación — crear un modelo paralelo duplicaría lógica que ya funciona. Extender, no crear. |
| **Event/Listener nuevo para notificaciones de Carnet+** | Ya existe `NotificarPadreAccesoJob` haciendo ese trabajo — solo le falta el canal WhatsApp. Construir infraestructura nueva encima de una que ya funciona es complejidad innecesaria. |
| **Separar Equipos e Inventario en más módulos** | Es al revés: son dos sistemas que ya deberían unificarse, no fragmentarse más. |
| **Multi-campus** | Sin demanda confirmada de ningún cliente real — construirlo especulativamente es el mayor riesgo de esfuerzo desperdiciado de todo este roadmap (esfuerzo 5/5 en la matriz de priorización). **REQUIERE VALIDACIÓN** antes de considerarlo siquiera. |
| **Conciliación bancaria automática** | Depende del formato de exportación de cada banco dominicano (no confirmado, varía por banco) — construir contra un formato asumido podría no servir para el banco real que use cada centro. **REQUIERE VALIDACIÓN.** |
| **Gestión de menú/alérgenos de comedor** | El dolor real confirmado en Cafetería es el cobro/prepago, ya resuelto y sólido. Menú/alérgenos es una categoría de producto distinta (seguridad alimentaria, no financiera) sin señal de demanda de ningún cliente. **REQUIERE VALIDACIÓN.** |
| **Analítica SaaS (MRR/churn) ahora mismo** | Más valiosa cuando haya volumen real de tenants pagando — con pocos tenants, un dashboard de MRR no aporta información que Dirección no sepa ya de memoria. |
| **Integraciones con Google Workspace/Microsoft 365** | Mencionado como expectativa general del mercado 2026, pero no evaluado si algún cliente real de ZuraEdu lo necesita. **REQUIERE VALIDACIÓN** antes de invertir esfuerzo. |

## Decisiones de negocio pendientes (bloquean trabajo técnico en su área)

### 1. Reingreso como flujo distinto de matrícula nueva — **CERRADA (2026-09-11)**
- **Decisión**: la matrícula genérica actual basta. No se construye ningún
  flujo de "Reingreso" diferenciado.
- **Validación con el usuario**: confirmado que hoy Secretaría/Registro no
  lleva ningún control especial — matriculan al estudiante que regresa
  como lo harían con cualquiera.
- **Verificación técnica de que el flujo actual ya soporta esto bien** (sin
  cambios de código): al retirar una matrícula
  (`MatriculaController.php:355`) solo cambia `Matricula.estado`, el
  registro `Estudiante` no se toca y sigue `activo`.
  `MatriculaController::create()` arma la lista de estudiantes a
  matricular con `Estudiante::activos()->whereNotIn('id', $enrolledIds)`
  filtrado por año escolar — un estudiante retirado en un año anterior
  aparece normalmente para matricularlo de nuevo, sobre el mismo registro
  (su historial de calificaciones/asistencia/boletines queda intacto, no
  se duplica). Si alguien intentara crearlo como estudiante nuevo por
  error, `cedula` tiene `unique:estudiantes,cedula` y lo bloquea.

### 2. Alcance real de NCF/e-CF
- **Qué falta decidir**: ¿el MVP de "solo registrar y mostrar" (ya
  implementado) es suficiente para el negocio, o hace falta invertir en
  una integración real con un proveedor certificado por la DGII?
- **REQUIERE VALIDACIÓN**: (a) qué categoría de contribuyente (pequeño/
  mediano/grande) aplica a cada centro tenant real — cambia la urgencia
  del plazo del 15/11/2026; (b) si hay presupuesto/apetito para contratar
  un proveedor e-CF certificado.

### 3. Categoría de contribuyente por tenant (afecta la decisión #2)
- No hay ningún campo en el sistema que registre esto hoy — ni siquiera
  el RNC del centro está almacenado. Antes de decidir la estrategia fiscal
  hace falta este dato básico por cada institución real.

## Nota sobre "REQUIERE VALIDACIÓN"

Cada vez que este documento (o los otros 5 de este roadmap) marca algo
así, significa: no hay evidencia en el código ni en una fuente externa
confirmada de que sea un requisito real — es una posibilidad razonable,
no una conclusión. No se debe construir nada marcado así sin antes
conseguir esa confirmación (de un cliente real, de un contador, de
Dirección, etc.).
