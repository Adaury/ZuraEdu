# Proceso: marca ZuraEdu en todo el sistema

> Qué se pidió, cómo se hizo el paneo del sistema, qué se encontró, qué decisiones se tomaron y cómo mantenerlo.
> Fecha de la intervención: 2026-10-02. Los números de este documento salen de recorrer el código, no de estimaciones.

## 1. Qué se pidió

1. Crear un **logotipo** para ZuraEdu.
2. Ponerlo en **todo el sistema**.
3. Poner en el **pie de todas las páginas** «Todos los derechos reservados por ZuraEdu».
4. Hacer un **paneo completo** del sistema y **documentar el proceso** (este documento).

## 2. El paneo: qué hay en el sistema

Se recorrieron las 737 plantillas de `resources/views` y los recursos públicos.

| Superficie | Cantidad | Cómo se trataba antes |
|---|---|---|
| Plantillas base (`layouts/`) | 3 propias + 1 que hereda | admin, portal (de él hereda el del estudiante) y superadmin |
| Pantallas que cuelgan de una plantilla base | 472 | heredan el pie de la plantilla base |
| Pantallas HTML independientes | 30 | acceso, páginas públicas, pagos con tarjeta, encuestas, verificación… |
| Páginas de error (403, 404, 419, 500, 503) | 5 | sin pie |
| Correos | 14 | pies distintos y nombres inconsistentes |
| PDF e impresiones | 161 | cada uno con su propio pie, sin marca de la plataforma |
| Aplicación móvil | 5 íconos | un birrete con una «Z» provisional |
| Recursos de marca en `public/` | 0 | solo un `favicon.ico` genérico |

### Hallazgos del paneo (todos corregidos)

- **No existía ningún recurso de marca de ZuraEdu** (ni logo, ni íconos, ni imagen para compartir).
- **Pies de página inconsistentes**: 14 archivos tenían un «©» propio, con cinco textos distintos («ZuraEdu — Plataforma de Gestión Escolar», «Sistema SGE», el nombre del colegio, …).
- **Nombres de colegio escritos a mano que verían TODOS los centros** (error de aislamiento entre colegios, no solo estético):
  - el pie del panel de administración decía «© AprendeTicPaulino»;
  - el correo «boletín disponible» decía «Politécnico Salesiano Arquides Calderón» a las familias de cualquier colegio;
  - la pantalla del representante, la guía de ayuda, el encabezado de la planificación (administración y docente, mayúsculas) y una pantalla de boletines tenían el mismo nombre fijo;
  - un PDF de asistencia terminaba con «— AprendeTicPaulino».
- **El nombre antiguo «SGE»** seguía en 7 títulos de pestaña y en varios textos.
- Cuando un colegio **no había subido logo**, el panel mostraba una insignia de texto con las siglas «SGE».

## 3. Decisión principal: marca de la plataforma vs marca del colegio

El sistema es **multi-colegio**: cada centro tiene su propio logo y nombre (`Tenant::logo_url`, `system_logo`).
Por eso **el logo de ZuraEdu es la marca de la plataforma y no reemplaza al del colegio**:

| Lugar | Qué se ve |
|---|---|
| Barra lateral del panel y barra superior del portal | El **logo del colegio** si lo subió; si no, la **insignia de ZuraEdu** (en vez de las siglas «SGE») |
| Al pie de la barra lateral del panel | El logo blanco de ZuraEdu como plataforma que respalda al colegio |
| Pie de **todas** las páginas, PDF y correos | Logo de ZuraEdu + «© 2026 ZuraEdu. Todos los derechos reservados.» |
| Acceso (login, registro, recuperar contraseña) | Logo blanco de ZuraEdu y el texto legal; el nombre y logo del colegio siguen en su panel |
| Panel de superadministrador, página de inicio (landing), app móvil | Marca ZuraEdu completa |
| Sitios públicos de cada colegio | Se **conserva** el pie del propio colegio y se **agrega** debajo el de ZuraEdu |

El ícono de la aplicación instalable (PWA) lo genera el sistema por colegio con su color (`PwaController`): **no se tocó**.

## 4. El logotipo

**Concepto**: una **Z** geométrica coronada por un **birrete** (gorro de graduación), dentro de una insignia azul marino; el nombre «ZuraEdu» va en
trazo monolineal geométrico («Zura» en azul marino, «Edu» en azul). Mantiene la identidad que ya tenía la app móvil (azul marino `#1e3a6e`, birrete, Z).
Las letras están **dibujadas con trazos, no con una tipografía**: se ven igual en cualquier equipo, PDF o correo.

| Color | Valor | Uso |
|---|---|---|
| Azul marino | `#1e3a6e` | insignia, «Zura» |
| Azul | `#2563eb` | «Edu» |
| Celeste | `#7dd3fc` | birrete |

### Archivos (`public/brand/`)

| Archivo | Para qué |
|---|---|
| `zuraedu-logo.svg` / `.png` (1200 px) | logo horizontal en fondo claro |
| `zuraedu-logo-blanco.svg` / `.png` | logo horizontal en fondo oscuro |
| `zuraedu-icono.svg`, `zuraedu-icono-blanco.svg` | solo la insignia |
| `zuraedu-logo-300.png` | tamaño pequeño para correos y PDF |
| `favicon.svg`, `favicon-16/32/48.png`, `public/favicon.ico` | pestaña del navegador |
| `apple-touch-icon.png` (180), `zuraedu-icono-192.png`, `zuraedu-icono-512.png` | pantalla de inicio / PWA |
| `og-image.png` (1200×630) | vista previa al compartir el enlace |
| `mobile/assets/*` (icon, adaptive-icon, splash-icon, notification-icon, favicon) | app móvil |

### Cómo regenerarlos

Todo sale de **un solo diseño** en `scripts/marca/generar-logos.cjs`. Para cambiar colores o forma, se edita ese archivo y:

```
node scripts/marca/generar-logos.cjs                 # escribe public/brand y mobile/assets
node scripts/marca/generar-logos.cjs <carpeta>       # modo prueba: escribe solo en esa carpeta
```

## 5. Cómo se usa (para quien cree páginas nuevas)

Configuración y texto legal en un solo lugar: `config/brand.php` y `App\Support\Marca`.
El pie muestra «© 2026» el primer año y «© 2026–2028» los siguientes.

| Dónde | Qué poner |
|---|---|
| Pantalla que hereda `layouts.admin` / `layouts.portal` / `layouts.superadmin` | Nada: el pie ya está en la plantilla base |
| Pantalla HTML independiente | `@include('partials.marca.head')` dentro de `<head>` y `<x-marca.pie />` antes de `</body>` |
| PDF o impresión (dompdf) | `@include('partials.marca.pie-pdf')` antes de `</body>` |
| Correo | `@include('partials.marca.pie-correo')` como una fila más de la tabla |
| Un logo suelto | `<x-marca.logo variante="color" :alto="32" />` (`color`, `blanco`, `icono`, `icono-blanco`) |

`<x-marca.pie>` acepta `tono="auto"` (por defecto: el texto hereda el color de la página y el logo va sobre una pastilla blanca, así se ve bien sobre
cualquier fondo), `claro` u `oscuro`.

### Por qué el pie de los PDF va «al fondo» y no «al final del texto»

Se probó con el motor real (dompdf): un documento que cabe **justo** en una página pasaba a **dos** con un pie en el flujo (agregaba una página casi vacía).
Con el pie posicionado al fondo de la página (`position:absolute; bottom:0`) sigue siendo una. Hay una prueba automática que lo comprueba.
Este pie **no** se puso en cinco diseños de página completa o formato físico (carnets, diploma, certificado de proyecto): ahí no corresponde.

## 6. Qué se cambió

| Superficie | Cambio |
|---|---|
| `layouts/admin` | íconos de marca; insignia de ZuraEdu si el colegio no tiene logo; logo blanco al pie de la barra lateral; pie unificado (reemplaza «© AprendeTicPaulino») |
| `layouts/portal` (y el del estudiante, que hereda) | íconos de marca sin pisar los de la PWA por colegio; insignia de ZuraEdu si no hay logo; pie |
| `layouts/superadmin` | logo real en vez de las letras «ZE»; pie |
| Acceso (4 pantallas) | logo blanco y texto legal unificado en el pie del panel |
| Páginas públicas, de error y demás (28) | ícono de pestaña y pie (tono según el fondo de cada página) |
| Página de inicio (landing) | logo real en la barra y el pie; texto legal unificado; imagen para compartir |
| PDF e impresiones | pie al fondo en 156 plantillas (161 menos 5 excluidas) |
| Correos (14) | pie con logo PNG y texto legal; se quitaron los «©» propios; el de boletín ya no nombra a un colegio fijo |
| App móvil | 5 íconos nuevos (hace falta una nueva compilación para verlos) |
| Títulos de pestaña | «— SGE» → «— ZuraEdu» |
| Nombres de colegio fijos | sustituidos por el nombre del colegio actual en 6 lugares |

## 7. Verificación

Pruebas automáticas (`tests/Feature/MarcaZuraEduEstaticoTest.php` y `MarcaZuraEduPaginasTest.php`):

- **Guardia**: recorre las 737 plantillas y falla si una **página HTML completa** no trae el pie de marca (salvo las 5 excluidas, listadas en la prueba). Así nadie puede crear una página nueva sin pie.
- **Guardia**: falla si una vista contiene el nombre de un colegio escrito a mano o «SGE» en un título.
- Recursos: existen y tienen las medidas correctas (12 imágenes), los SVG son válidos y el `.ico` es válido con tres tamaños.
- PDF: el pie no cambia la paginación y lleva texto y logo; correo: logo PNG por URL absoluta.
- Páginas reales: acceso, error 404, panel de administración, portal del estudiante, panel de superadministrador y correo base.
- Un colegio **con** logo propio lo conserva; **sin** logo ve la insignia de ZuraEdu.

## 8. Límites y pendientes

- **No se revisó cada una de las 189 páginas con un navegador.** Se comprobaron con pruebas que renderizan páginas reales, con la guardia sobre todas las plantillas y con una imagen del pie de PDF. Conviene una pasada visual de las pantallas de acceso y del panel.
- **App móvil**: los íconos nuevos se ven al compilar una versión nueva (EAS); las instalaciones actuales conservan el anterior.
- **Correos**: el logo es un PNG por URL absoluta, así que `APP_URL` debe ser público y correcto. Algunos clientes de correo piden «mostrar imágenes».
- **Decisión legal**: se aplicó «Todos los derechos reservados por ZuraEdu» también a las páginas públicas de cada colegio, junto al pie del propio colegio. Si el colegio debe conservar la titularidad del contenido de su sitio, conviene revisarlo con quien lleve lo legal y, en ese caso, cambiar el texto en `config/brand.php` (`derechos`).
- Quedan 2 plantillas sin pie de marca por ser diseños de página completa (diploma y certificado de proyecto); los 3 PDF de carnets llevan el logo dentro de su diseño (sección 9).

## 9. Segunda ronda: carnets y pantallas de acceso (2026-10-02)

**Pantallas de acceso.** El login mostraba el logo del Politécnico Salesiano Arquides Calderón: era el valor `hp_logo_path` del tenant 1 (demo) en
`config_institucional`, que las pantallas de acceso usan como logo del colegio. Se vació ese valor (el archivo sigue en `storage/app/public/branding/`;
para restaurarlo basta volver a subirlo desde Admin → Sistema) y, en las 4 pantallas de acceso, cuando el colegio no tiene logo se muestra la
**insignia de ZuraEdu** en vez de siglas («SGE» por defecto). También desaparece del sitio público de ese tenant, que usa el mismo valor.

**Carnets.** Se les puso el logo blanco de ZuraEdu, y al verificarlos con el motor real de PDF aparecieron defectos que ya existían:

| Defecto previo | Efecto | Corrección |
|---|---|---|
| Diseño con `display:flex` y degradados (dompdf no los soporta) | texto blanco sobre fondo blanco: el carnet salía casi vacío | tablas y colores sólidos |
| Papel de 226.77 × 141.73 pt **con** `landscape` (80 × 50 mm, y dompdf además intercambia ancho y alto con un arreglo) | papel vertical más chico que el carnet: 2.ª página | CR80 real 242.65 × 153.02 pt, sin `landscape` |
| QR pedido a `quickchart.io` y `enable_remote=false` | **cuadro en blanco en lugar del QR** en el carnet impreso | QR generado en el servidor (`CarnetQrService::qrDataUri`, librería `chillerlan/php-qrcode`) |
| Hoja por grupo con `flex` | tarjetas apiladas en una columna y encabezado invisible | cuadrícula de 3 por fila |

Un solo parcial (`admin/carnet/_cara.blade.php`) dibuja la cara del carnet para el PDF individual y el masivo. Pruebas: `CarnetPdfMarcaTest`.
Las pantallas web «Mi carnet» (estudiante, docente, padre) siguen pidiendo el QR a quickchart.io desde el navegador; no se tocaron.

