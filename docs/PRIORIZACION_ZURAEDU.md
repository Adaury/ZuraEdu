# Priorización de Producto — ZuraEdu (2026-09-05)

Fase 6 (scoring) y Fase 13 (Top 20) del roadmap. Escala 1-5 por dimensión,
basada en la evidencia de `MATRIZ_GAPS_PRODUCTO_ZURAEDU.md` — no son
puntuaciones intuitivas, cada una tiene una razón anotada.

## Escala

1 = irrelevante/trivial · 3 = moderado · 5 = crítico/máximo.
**Esfuerzo**: 1 = horas · 3 = días · 5 = semanas (refactor grande).

## Matriz de puntuación

| # | Iniciativa | Comercial | Operativo | Académico | Padres | Riesgo | Esfuerzo | Prioridad |
|---|---|---|---|---|---|---|---|---|
| 1 | Tests algoritmo MINERD (GAP 02) | 2 | 3 | 5 | 2 | 5 | 2 | **P0** |
| 2 | Carnet+ WhatsApp + paridad API (GAP 03) | 3 | 3 | 1 | 5 | 2 | 1 | **P0** |
| 3 | Conectar justificación→Asistencia (GAP 09) | 2 | 4 | 4 | 4 | 3 | 2 | **P0** |
| 4 | Dashboard financiero por concepto/grado (GAP 05) | 3 | 5 | 1 | 1 | 1 | 3 | **P1** |
| 5 | Decisión de negocio: Reingreso (GAP 04) | 2 | 4 | 3 | 1 | 2 | — | **P1** (decisión, no código) |
| 6 | Decisión de negocio: alcance real de NCF/e-CF (GAP 01) | 5 | 3 | 1 | 3 | 4 | — | **P1** (decisión, no código) |
| 7 | Citas padre-docente con agenda real (GAP 08) | 2 | 3 | 1 | 4 | 1 | 3 | **P2** |
| 8 | Carnet+ ↔ Transporte (GAP 06) | 3 | 3 | 1 | 4 | 2 | 3 | **P2** |
| 9 | Historial de facturas SaaS (GAP 10) | 3 | 2 | 1 | 1 | 2 | 2 | **P2** |
| 10 | Unificar ZuraAI + medición de costo (GAP 07) | 4 | 2 | 2 | 1 | 3 | 4 | **P3** |
| 11 | Observer en `Pago` (auditoría) | 1 | 3 | 1 | 1 | 3 | 1 | P2 |
| 12 | Auditoría login/logout y cambios de rol | 1 | 3 | 1 | 1 | 3 | 2 | P2 |
| 13 | Adjuntar evidencia en justificación de ausencia | 1 | 2 | 1 | 2 | 1 | 1 | P3 |
| 14 | Unificar Equipos + Inventario | 1 | 3 | 1 | 1 | 1 | 4 | P3 |
| 15 | Centralizar generación de documentos/certificados | 1 | 2 | 1 | 1 | 1 | 4 | P3 |
| 16 | Extender granularidad de permisos a más recursos | 2 | 2 | 1 | 1 | 3 | 3 | P2 |
| 17 | Multi-campus (⚠️ REQUIERE VALIDACIÓN de demanda) | 3 | 2 | 1 | 1 | 1 | 5 | P3 (no iniciar sin validar) |
| 18 | Conciliación bancaria automática (⚠️ REQUIERE VALIDACIÓN) | 2 | 3 | 1 | 1 | 1 | 4 | P3 (no iniciar sin validar) |
| 19 | Menú/alérgenos de comedor (⚠️ REQUIERE VALIDACIÓN) | 1 | 2 | 1 | 2 | 2 | 4 | P3 (no iniciar sin validar) |
| 20 | Analítica SaaS (MRR/churn) | 2 | 2 | 1 | 1 | 1 | 3 | P3 |

**Cómo se calculó "Prioridad"**: no es un promedio simple — P0 exige
Esfuerzo ≤2 Y (Riesgo≥4 O Padres≥5); P1 exige Comercial≥4 O ser una
decisión de negocio bloqueante; P2 es impacto moderado con esfuerzo
manejable; P3 es esfuerzo alto y/o impacto disperso, o depende de una
validación externa pendiente.

## P0 — hacer primero (impacto/riesgo alto, esfuerzo bajo)

1. Tests del algoritmo MINERD de promoción.
2. Carnet+: agregar WhatsApp al job existente + corregir la API para que
   dispare el mismo job que el kiosco admin.
3. Conectar la aprobación de justificación de ausencia con el registro
   real de `Asistencia`.

## P1 — decisiones de negocio que bloquean todo lo demás en su área

4. Decidir el flujo de Reingreso.
5. Decidir el alcance real de NCF: ¿queda en "solo registrar" (ya
   implementado) o se invierte en integrar un proveedor e-CF certificado?
   Esto determina si el dashboard financiero (P1 #4 de esta lista) debe
   incluir estado de comprobantes fiscales o no.
6. Dashboard financiero por concepto/grado/sección.

## P2 — mejoras de valor claro, esfuerzo moderado

7. Citas padre-docente con agenda real (slots de horario).
8. Carnet+ ↔ Transporte escolar.
9. Historial de facturas del billing SaaS.
11-12. Observer en `Pago` + auditoría de login/roles (ambas bajo esfuerzo,
   conviene agrupar en un solo esfuerzo de "auditoría transversal").
16. Extender el patrón `ver-X`/`gestionar-X` (ya usado en estudiantes) a
   otros recursos sensibles.

## P3 — esfuerzo alto, o pendiente de validación externa

10. Unificar ZuraAI (refactor de 4 controladores — planificar aparte, no
    mezclarlo con quick wins).
14-15. Unificar Equipos/Inventario y centralizar documentos — deuda de
    diseño real pero sin urgencia de negocio.
17-19. Multi-campus, conciliación bancaria, menú/alérgenos — **no iniciar
    sin confirmar demanda real primero** (ver `DECISIONES_PRODUCTO_ZURAEDU.md`).
20. Analítica SaaS — más valiosa cuando haya volumen de tenants.
