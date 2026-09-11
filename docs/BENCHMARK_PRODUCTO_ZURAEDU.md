# Benchmark Competitivo — ZuraEdu vs. EduPage / PowerSchool / Alma (2026-09-05)

Fase 4 del roadmap. Referencia conceptual pedida explícitamente: EduPage,
PowerSchool, Alma. Fuentes: fetch directo a edupage.org y búsqueda web
sobre el mercado de SIS 2026, ambas realizadas en esta sesión — citadas al
final. Comparación cruzada contra el inventario real de ZuraEdu (ver
`MATRIZ_GAPS_PRODUCTO_ZURAEDU.md`), no contra impresiones.

## Resumen por competidor

- **PowerSchool**: analítica potente + IA, pensado para distritos grandes
  de EE. UU. Fuerte en reportería a escala, boundary verification, asset
  tracking. No es el comparable más relevante para RD (mercado/escala
  distintos).
- **Alma**: SIS moderno, interfaz limpia, portal familiar sin "bloat"
  enterprise. Comparable más cercano en filosofía de producto a ZuraEdu.
- **EduPage**: SIS europeo maduro, el más detallado de los tres para esta
  comparación (fetch completo de su lista de módulos).

## Tabla comparativa (solo diferencias reales, ya cruzadas contra el código)

| Función | EduPage | ZuraEdu | Estado tras esta auditoría |
|---|---|---|---|
| Sustituciones docentes automáticas | Sí | Sí (`Suplencia`/`SuplenciaService`) | Ya existe, no repetir |
| Horario con generador automático | Sí | Sí (backtracking + validación) | ZuraEdu por encima del estándar regional |
| Libro de clases digital (asistencia+notas+tema del día) | Sí | Parcial — asistencia y notas sí, "tema de la clase del día" ligado al horario no confirmado | REQUIERE VALIDACIÓN si se necesita |
| Evaluación por competencias/estándares | Sí | Sí, y ajustado al currículo MINERD específicamente | 🔵 diferenciador — más específico que un genérico |
| Control de acceso con tarjeta/tag (RFID) | Sí | No (usa QR — Carnet+) | Decisión tecnológica distinta, no una carencia |
| Reserva de citas padre-maestro | Sí | Parcial (ver GAP 08) | Existe la base, falta agenda real |
| Justificación digital de inasistencia | Sí | Parcial (ver GAP 09) | Existe el envío, falta conectar aprobación |
| Pagos: importación/conciliación bancaria automática | Sí | No | Confirmado ausente |
| Comedor: menú/alérgenos/recetas | Sí | No (ZuraEdu resuelve el cobro/prepago, no el menú) | Categoría de producto distinta — REQUIERE VALIDACIÓN de demanda real antes de construir |
| Vista consolidada multi-escuela (aScOrbit, para redes/distritos) | Sí | No | Relevante solo si se vende a redes con varios campus — REQUIERE VALIDACIÓN |
| Derechos de usuario granulares por módulo | Sí | Empezando (ver-estudiantes vs. gestionar-estudiantes, commit `a8d8c50`) | Patrón a extender a más recursos |
| App móvil | Sí | Sí, más amplia (79 pantallas vs. lo descrito para EduPage) | 🔵 diferenciador de amplitud, con deuda de consistencia UX |
| Certificados/logros publicados en la web | Sí | Parcial (certificados sí, publicación pública no confirmada) | Bajo impacto, no priorizar |
| IA integrada en el flujo académico | No mencionado en EduPage | Sí (ZuraAI, 4 puntos de entrada) | 🔵 diferenciador potencial si se unifica (GAP 07) |
| Multi-tenant SaaS con billing propio | No aplica (EduPage es un producto, no un SaaS white-label por tenant en el mismo sentido) | Sí, con Stripe + transferencia + onboarding self-service | 🔵 diferenciador — ZuraEdu es SaaS multi-institución desde el diseño |

## Lo que el mercado 2026 espera de un SIS "de clase mundial" (hallazgo de búsqueda general, no específico de un producto)

Los sistemas líderes en 2026 se diferencian por: **Cloud + IA + mobile-first**,
y por ofrecer **acceso API e integraciones pre-armadas** con Google
Workspace, Microsoft 365 y software de contabilidad, para no convertirse en
una isla de datos.

- **Cloud**: ZuraEdu ya es 100% SaaS multi-tenant — cumplido.
- **IA**: ZuraEdu ya tiene IA integrada (ZuraAI) — cumplido en presencia,
  pendiente en madurez (GAP 07).
- **Mobile-first**: app con 79 pantallas — cumplido en amplitud, pendiente
  en consistencia.
- **API/integraciones externas (Google Workspace, Microsoft 365,
  contabilidad)**: **no evaluado en esta auditoría** — REQUIERE
  VALIDACIÓN si hay demanda real de integrar con esas plataformas antes de
  construir algo especulativo.

## Conclusión del benchmark

ZuraEdu no está "por detrás" de estos productos en cobertura de módulos —
al contrario, en varias áreas (evaluación por competencias ajustada a
MINERD, IA integrada, SaaS multi-tenant con billing propio) va adelante
por estar diseñado específicamente para el contexto dominicano/RD, algo
que un producto genérico importado no ofrece. Las brechas reales son
puntuales y ya están listadas en la matriz de gaps — no hay que rediseñar
el producto, hay que cerrar 8-10 huecos concretos y decidir 2-3 cosas de
negocio (fiscal, multi-campus, integraciones externas).

## Fuentes citadas (obligatorio incluir)

- [Comprobantes Fiscales Electrónicos e-CF — DGII](https://dgii.gov.do/cicloContribuyente/facturacion/comprobantesFiscales/Paginas/comprobantesFiscalesElectronicos.aspx)
- [Facturación Electrónica en RD 2026: Guía Completa](https://gestiondo.com.do/finanzas-legalidad/facturacion-electronica-rd-fechas-prorroga-obligatoriedad/)
- [Facturador Gratuito de Facturación Electrónica — DGII](https://dgii.gov.do/cicloContribuyente/facturacion/comprobantesFiscalesElectronicosE-CF/Paginas/facturador-gratuito.aspx)
- [Best School Management Software for K-12 Schools (2026) — The Principal Center](https://www.principalcenter.com/best-school-management-software/)
- [edupage.org](https://www.edupage.org/) (fetch directo de su listado de módulos)
