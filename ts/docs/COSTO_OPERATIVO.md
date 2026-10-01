# Costo operativo: cuánto consume el sistema y qué cambia con TypeScript (2026-10-01)

Evaluación de **CPU y memoria por petición**, proyectada a colegios reales. Se mide, no se estima: cada cifra de la sección 2
viene de una corrida reproducible; lo que NO se midió (tráfico real, precios) está marcado como **SUPUESTO**.

## 1. Cómo se midió

- Base sintética `sge_bench` (4.950 estudiantes, 742 mil asistencias, 49,5 mil pagos; **436 MB** con un año de datos).
- **Un solo proceso** de cada lado, una conexión, 12 s por escenario, con un usuario real de cada rol (admin, docente, padre,
  estudiante). PHP 8.3 con OPcache + `config:cache` + `route:cache` (lo que hace `deploy.sh`); Node en producción.
- El CPU se lee del propio proceso de la aplicación y de `mysqld` antes y después de la carga (no se infiere de la latencia).
  El CPU de `mysqld` en reposo es 1–4 ms/s: no distorsiona.
- Memoria: RSS de cada proceso. **Límites:** máquina de desarrollo con Windows (el RSS incluye librerías compartidas, así que
  en Linux suele salir menor), MySQL en el mismo equipo, sin TLS ni red. Úsese como magnitud relativa, no como cotización.
- Reproducible con `ts/bench/` (generador de carga) y el script de medición descrito abajo.

## 2. Mediciones

### CPU por petición (ms, aplicación + MySQL)

| Petición | App | MySQL | Total | Latencia p50 | Tamaño |
|---|---|---|---|---|---|
| Portal padre (dashboard) | 39,1 | 10,1 | **49** | 50 ms | 72 KB |
| Portal estudiante (dashboard) | 55,2 | 14,1 | **69** | 70 ms | 110 KB |
| Portal docente (dashboard) | 171,6 | 22,4 | **194** | 191 ms | 1,6 MB |
| Admin · listado de estudiantes | 46,9 | 24,1 | **71** | 72 ms | 292 KB |
| Admin · dashboard | 153,0 | **485,2** | **638** | 637 ms | 255 KB |
| Admin · pagos | 84,5 | **488,6** | **573** | 555 ms | 322 KB |
| Boletín / notas / asistencia en **PDF** (por estudiante) | ≈ 275–345 | ≈ 7–9 | **≈ 285–355** | 283–354 ms | — |
| API listado, **Laravel** (ruta equivalente) | 14,3 | 15,5 | 30 | 28 ms | 2 KB |
| API listado, **TypeScript** (caché de auth 30 s) | 0,7 | 1,7 | **2,4** | 2,4 ms | 2 KB |
| API listado, **TypeScript** (sin caché de auth) | 1,5 | 2,3 | **3,8** | 3,7 ms | 2 KB |

### Memoria de los procesos que el sistema real mantiene encendidos

| Proceso | RSS en reposo |
|---|---|
| Worker de PHP (web) | ≈ 70 MB cada uno (pico 80) |
| Worker de cola (cada uno de los de Horizon) | 78 MB (28–30 MB privados) |
| Reverb (tiempo real) | 74 MB |
| Redis | 33 MB |
| **API TypeScript (una instancia)** | **210 MB** |
| MySQL (buffer pool de desarrollo: 128 MB) | 412 MB |

Horizon declara **6 supervisores con mínimo 1 proceso** (`config/horizon.php`): al menos 6 workers + el maestro siempre
encendidos, aunque no haya tráfico (≈ 550 MB de RSS, ≈ 210 MB privados). **Esto no desaparece con una API en TypeScript.**

## 3. Lo que muestran los datos

1. **En la parte de la aplicación, TypeScript gasta 10–20 veces menos CPU** (14,3 ms frente a 0,7–1,5 ms). En el total (con
   MySQL) la brecha real, a consultas iguales, es de **≈ 4 a 7 veces**.
2. **Parte de la diferencia de MySQL no es del lenguaje.** El `count(*)` de `Estudiante::paginate` sin sugerencia de índice
   cuesta ≈ 11 ms de MySQL (el hallazgo de la Fase 0: 11,7 ms → 1,6 ms con `INDEX(...)`); mi ruta de prueba de PHP no la
   aplicaba y el controlador real sí. Comparar la columna «App» es lo justo para el lenguaje.
3. **Lo más caro del sistema es el SQL de dos pantallas, y ningún lenguaje lo arregla:** dashboard y pagos gastan ≈ 0,5 s de CPU
   de MySQL **por cada vista** (agregados sobre 49,5 mil notas y pagos). Una página normal cuesta 50–70 ms.
4. **El PDF es el siguiente costo:** ≈ 0,3 s de CPU cada uno, 5–7 veces una página. Un ZIP de boletines de **todo** un colegio de
   5.000 estudiantes ≈ 5.000 × 0,35 s ≈ **29 minutos de CPU** (estimación por multiplicación; conviene encolarlo fuera de hora).
5. **El costo fijo (memoria) lo dominan MySQL, Horizon y Reverb**, no el servidor web: ≈ 1,4–1,8 GB antes del primer usuario.
6. **La API TypeScript añade 210 MB fijos** (≈ 3 workers de PHP). Para un colegio pequeño, tenerla encendida cuesta más RAM de la
   que ahorra en CPU.

## 4. Proyección a un colegio real

**SUPUESTOS (no hay registros de tráfico real; hay que calibrarlos con los accesos de un colegio en producción):**
poblaciones de 500 / 1.500 / 5.000 estudiantes (familias ≈ 80 %, docentes ≈ 1 por 25, 5–15 administrativos); ventana de 15 minutos;
solo peticiones dinámicas (los archivos estáticos los sirve Nginx); personal administrativo = 50 % listados, 25 % dashboard,
25 % pagos; no más del 60 % de CPU; precios de referencia **US$ 6 por vCPU y US$ 2 por GB al mes** (orden de magnitud: ajustar con
el proveedor). El dimensionamiento es para el **pico**, que dura minutos.

- **Hora pico normal:** estudiantes 10 %, familias 5 %, docentes 60 %, administrativos 100 %, 2 páginas/min cada uno.
- **Pico de publicación de notas:** estudiantes 30 %, familias 40 %, docentes 30 %, administrativos 100 %, 1,5 páginas/min.

| Colegio | Perfil | Activos | Petic./s | Núcleos (60 %) | Workers PHP | RAM total | Servidor | US$/mes |
|---|---|---|---|---|---|---|---|---|
| Pequeño (500) | hora pico | 87 | 2,9 | 0,47 | 1 | 1,5 GB | 2 vCPU / 2 GB | 16 |
| Pequeño (500) | publicación de notas | 321 | 8,0 | 0,88 | 1 | 1,5 GB | 2 vCPU / 2 GB | 16 |
| Mediano (1.500) | hora pico | 254 | 8,5 | 1,28 | 2 | 1,6 GB | 2 vCPU / 2 GB | 16 |
| Mediano (1.500) | publicación de notas | 956 | 23,9 | 2,54 | 3 | 1,6 GB | 4 vCPU / 2 GB | 28 |
| Grande (5.000) | hora pico | 835 | 27,8 | 4,05 | 4 | 2,1 GB | 5 vCPU / 3 GB | 36 |
| Grande (5.000) | publicación de notas | 3.175 | 79,4 | 8,31 | 8 | 2,4 GB | 9 vCPU / 3 GB | 60 |

(Memoria fija incluida: SO + Nginx 512 MB, Redis 33, Reverb 74, Horizon 546, MySQL con buffer pool = 1,5 × datos del año, mínimo 256 MB.)

### ¿Cuánto ahorraría pasar lecturas a TypeScript?

- **Costo fijo de tenerla encendida:** +210 MB ≈ **US$ 0,41 al mes** de RAM.
- **Cota superior de ahorro** si TODAS las páginas de estudiante y familia se sirvieran desde TypeScript (sin contar el CPU de
  renderizar la web Next.js, que se comería parte del ahorro):

| Colegio | Perfil | Núcleos antes → después | Ahorro (US$/mes) |
|---|---|---|---|
| Pequeño | hora pico | 0,47 → 0,28 | ≈ 1 |
| Pequeño | publicación de notas | 0,88 → 0,29 | ≈ 4 |
| Mediano | hora pico | 1,28 → 0,71 | ≈ 3 |
| Mediano | publicación de notas | 2,54 → 0,78 | ≈ 11 |
| Grande | hora pico | 4,05 → 2,14 | ≈ 12 |
| Grande | publicación de notas | 8,31 → 2,44 | ≈ 35 |

## 5. Conclusiones y recomendaciones

1. **El costo de infraestructura de un colegio es pequeño con cualquiera de los dos stacks** (US$ 16–60 al mes en el pico, con los
   supuestos de arriba). La diferencia posible por migrar a TypeScript es de **US$ 1–35 al mes**; el costo real de la migración es de
   **mantenimiento**: dos stacks, dos despliegues y reglas de negocio duplicadas que se desalinean (por eso existen las pruebas de
   paridad). Con estos datos, **migrar no se justifica por costo**.
2. **Lo que sí rinde, sin reescribir nada, en este orden:**
   1. **Dashboard y pagos del administrador** (≈ 0,5 s de CPU de MySQL por vista): cachear o preagregar. Es la mayor palanca por
      petición del sistema.
   2. **PDF y ZIP masivos** (≈ 0,3 s cada PDF): siempre por cola, con límite de concurrencia y fuera del horario pico; escalonar la
      publicación de notas evita el pico de 8 núcleos.
   3. **Horizon en colegios pequeños:** 6 supervisores con mínimo 1 proceso fijan ≈ 550 MB; con 1–2 supervisores se ahorran ≈ 300 MB
      (la diferencia entre un servidor de 2 GB y uno de 4 GB).
   4. **MySQL:** el buffer pool de producción debe ser ≥ 1,5 × los datos del año (≈ 0,65 GB para 5.000 estudiantes).
3. **Cuándo cambiaría la conclusión:** si un colegio supera varios miles de usuarios simultáneos de forma sostenida, o si se vende
   como servicio con muchos colegios en un mismo servidor (ahí la brecha de 4–7× por núcleo sí se multiplica por colegio).

## 6. Lo que esta evaluación NO cubre

- **Tráfico real:** los perfiles son supuestos. Calibrarlos con los registros de acceso de un colegio en producción.
- **Un solo proceso y una sola máquina de desarrollo con Windows;** no se probó PHP-FPM ni un servidor Linux con varios workers.
- **Solo peticiones dinámicas:** sin archivos estáticos, WebSockets (Reverb sostenido), exportaciones de Excel, importaciones ni el
  planificador (`schedule:run`).
- **CPU de la web Next.js** (renderizado en servidor): no existe todavía; las cifras de TypeScript son solo de la API.
- **Precios:** orden de magnitud, no cotización.
