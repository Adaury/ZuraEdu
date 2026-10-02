# Prueba de carga en Linux (Nginx + PHP-FPM)

Se lanza **a mano** desde GitHub: pestaña *Actions* → *Prueba de carga (Linux)* → *Run workflow*
(o `gh workflow run carga.yml -f usuarios=1500 -f fpm_hijos=16 -f segundos=90`). No corre sola y no toca producción ni ninguna base real.

Monta un servidor Linux descartable con Nginx, PHP 8.3-FPM (`pm=static`), MySQL 8 y Redis, siembra los datos de demostración
(`php artisan db:seed`: 360 estudiantes, calificaciones, asistencias, pagos), crea una cuenta y una sesión por persona
(`preparar.php`) y mide tres escenarios con `carga.py`:

| Escenario | Qué responde |
|---|---|
| `escalera-N` (N = 8…256 usuarios que piden sin parar) | Cuántas páginas por segundo aguanta el servidor y dónde empieza a esperar |
| `realista-N` (N usuarios, pausa de 10–30 s entre páginas) | Cómo se comporta con personas reales; ¿se sostienen N usuarios? |
| `rafaga-N` (N usuarios piden a la vez) | Qué pasa con una avalancha (p. ej. toda la escuela entrando a la vez) |

El resumen (tabla + CPU + estado de PHP-FPM + MySQL + errores) queda en el *Summary* de la ejecución y como artefacto `carga-resultados`.

## Cómo leer los resultados
- **OK/s** de la escalera: la capacidad. Cuando sube poco al duplicar usuarios, ya se saturó; ahí crece la latencia (p95) en vez del rendimiento.
- **Rechazadas / timeouts / 5xx > 0**: el servidor no dio abasto. **Redir. (sesión) > 0**: las sesiones fabricadas no valieron (no es culpa del servidor).
- **Saturación**: con `pm=static` y socket unix, «max children reached» y «listen queue» de PHP-FPM siempre marcan 0 (no sirven). La señal real es **CPU al 100 %** y la **latencia que crece en proporción a los usuarios** mientras OK/s no sube.
- **Servidor p50/p95** es lo que midió Nginx (`request_time`); el p50/p95 del generador incluye además la espera para conectar.

## Límites
El runner público es chico (4 vCPU) y el generador corre en la misma máquina que el servidor: las cifras son un **piso conservador**, no la
capacidad de un servidor dedicado. Sirven para comparar cambios (más/menos procesos, optimizaciones) y para detectar errores bajo carga.

`preparar.php` modifica usuarios: solo corre sobre la base `sge_carga` (se niega con cualquier otro nombre).
