# Arquitectura de "Sitio por Centro" — Portal Público + Constructor Visual (2026-09-05)

Auditoría de solo lectura. Diseño conceptual únicamente — nada de esto está
implementado.

## Estado actual: confirmado 100% inexistente

- `landing.blade.php` es la página de marketing del propio SaaS ZuraEdu,
  sin ninguna referencia a `Tenant`/`ConfigInstitucional` — idéntica para
  todos los centros.
- No existe ninguna ruta `/centro/{algo}` ni controlador que resuelva una
  página pública distinta por tenant.
- No existen los modelos `Noticia` ni `Galeria` (pública) — cero
  migraciones.
- `Evento` existe pero es interno (inscripción de la comunidad ya
  autenticada), sin campo de visibilidad pública.
- No existe ningún sistema de bloques/secciones/widgets configurables en
  todo el código — es una funcionalidad completamente nueva, no una mejora
  de algo parcial.

## Lo que SÍ se puede reutilizar (evitar duplicar)

- `Tenant.dominio` / `Tenant.dominio_personalizado` ya existen y ya se usan
  para resolver el tenant por subdominio/dominio propio
  (`ResolveTenant`). Una URL pública por centro puede montarse sobre esto
  directamente — **no crear un campo "slug" nuevo**.
- `ConfigInstitucional` (almacén clave-valor: `clave`, `valor`, `tipo`,
  `grupo`) sigue siendo el lugar correcto para datos simples (teléfono,
  correo, dirección, redes sociales, colores) — no debe absorber contenido
  editorial (eso es responsabilidad de las nuevas `pagina_secciones`).

## Arquitectura mínima viable propuesta (conceptual — NO crear todavía)

```
tenant
  └── pagina_secciones (NUEVA, BelongsToTenant)
        - tenant_id
        - tipo        (hero | informacion | noticias | actividades | galeria | eventos | contacto)
        - orden       (entero, para drag&drop)
        - activo      (booleano)
        - contenido   (json — estructura depende de `tipo`)
        - timestamps
```

- Una fila por bloque configurado. El campo `tipo` determina qué vista
  Blade lo renderiza (`match($seccion->tipo)`), sin motor de plantillas
  nuevo ni tabla-por-tipo-de-bloque.
- El `contenido` JSON evita tener que anticipar todas las columnas posibles
  de cada tipo de bloque por adelantado.
- La URL pública se resuelve igual que hoy resuelve `ResolveTenant`, sin
  inventar un sistema paralelo de slugs.

## Lo que esta auditoría NO evaluó (fuera de alcance, pedir aparte si hace falta)

- El editor drag&drop en sí (frontend/UX del constructor visual) — solo se
  evaluó la arquitectura de datos que lo soportaría.
- Si el constructor debe ser de bloques predefinidos configurables
  (recomendado, menor esfuerzo) o edición completamente libre tipo
  page-builder genérico (mucho mayor esfuerzo, no justificado sin evidencia
  de que algún centro lo necesite).

## Dependencias con otras partes de este roadmap

- Depende de que `ZURAEDU_PRODUCT_GAPS.md` (unificación de módulos
  activables) esté resuelto primero, si se quiere que la página pública
  muestre/oculte bloques según qué módulos tiene activos el tenant (ej. no
  mostrar "galería" si el centro no tiene ese módulo).
- No depende de ZuraPlan ni del resto del roadmap — es independiente.
