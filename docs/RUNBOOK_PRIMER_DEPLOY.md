# Runbook: primer despliegue con `deploy.sh` (Laravel 10 → 13)

Pensado para el **primer** despliegue con `deploy.sh`. Si producción sigue en Laravel 10 (no se ha podido verificar desde el repositorio), este despliegue sube **de golpe** el
salto a Laravel 13 y todo lo posterior (migraciones, validaciones `exists:`/`unique:`, `TrustProxies`, índices, etc.). Los comandos van con `/ruta/al/proyecto` y `tu-dominio.edu.do`:
sustitúyelos. **Lo que no se puede saber desde aquí queda marcado «anota»**: son datos del servidor real.

`deploy.sh` y `rollback.sh` se prueban en cada cambio (`scripts/probar-deploy.sh`, 44 comprobaciones en un servidor simulado); aun así nunca se han ejecutado contra un servidor real.

---

## A. Antes (sin parar nada; hazlo días antes)

**1. PHP: ¿8.3 o más? (en la consola y en PHP-FPM, que pueden ser distintos)**
```bash
php -v                                                    # debe decir 8.3.x o superior
ls /etc/php/                                              # versiones instaladas
systemctl list-units --type=service | grep -i fpm         # anota el nombre del servicio (p. ej. php8.3-fpm)
grep -rn "fastcgi_pass" /etc/nginx/                       # a qué socket apunta Nginx (debe ser el de PHP 8.3)
php -m | grep -iE "pdo_mysql|redis|pcntl|posix|gd|zip|mbstring"
which php                                                 # el php que usan Supervisor y el cron debe ser el 8.3
```
Si no está PHP 8.3: instálalo (con `php8.3-fpm` y las extensiones), apunta Nginx a su socket y **pruébalo antes**. Con 8.1/8.2 `composer install` falla a mitad del despliegue, con la web en mantenimiento.

**2. Herramientas**
```bash
composer --version      # 2.x
mysql -V                # 8.0 o más
mysqldump --version     # lo usa el backup (si no está en el PATH: MYSQLDUMP_PATH en el .env)
redis-cli ping          # PONG
supervisorctl status    # deben verse sge-horizon y sge-reverb (si se llaman distinto, anótalo)
node -v                 # solo si compilas los assets en el servidor (18 o más)
```

**3. Git: árbol limpio, rama correcta y permiso de escritura (el script publica un tag)**
```bash
cd /ruta/al/proyecto
git status --porcelain            # debe salir VACÍO (si no, el script se niega a desplegar)
git branch --show-current         # master
git remote -v
git push --dry-run origin master  # comprueba que el servidor puede escribir en GitHub (deploy key con permiso de escritura)
git rev-parse --short HEAD        # ANOTA el commit actual del servidor
```

**4. Prefijos de Redis y sesión (para NO cerrar la sesión de todos)** — en el código **actual**, antes del `git pull`:
```bash
php artisan tinker --execute="echo config('cache.prefix'), PHP_EOL, config('database.redis.options.prefix'), PHP_EOL, config('session.cookie'), PHP_EOL;"
```
Copia esos tres valores **exactos** al `.env` del servidor:
```env
CACHE_PREFIX=<primer valor>
REDIS_PREFIX=<segundo valor>
SESSION_COOKIE=<tercer valor>
```
Laravel cambia el prefijo por defecto de la caché entre versiones (lo advierte el propio `.env.example`; en Laravel 13 es `<APP_NAME>_cache_`), y con `SESSION_DRIVER=redis` las sesiones se guardan con ese prefijo: si el valor nuevo no coincide con el de producción, la caché empieza vacía y los usuarios tienen que volver a iniciar sesión. Fijándolos con los valores actuales no cambia nada.

**5. Variables nuevas del `.env`**
```bash
git fetch origin
comm -13 <(grep -oE '^[A-Z_]+' .env | sort -u) <(git show origin/master:.env.example | grep -oE '^[A-Z_]+' | sort -u)
```
Muestra las variables que el `.env.example` nuevo tiene y tu `.env` no. Revísalas con `DEPLOY.md` §2 (en producción: `APP_ENV=production`, `APP_DEBUG=false`, `FORCE_HTTPS=true`, `CACHE_DRIVER=redis`, `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis`, `REVERB_*`, `HORIZON_ALLOWED_EMAILS`).
**`TRUSTED_PROXIES`:** no hace falta si Nginx está directamente de cara a Internet. **Si hay un balanceador o una CDN delante de Nginx** (Cloudflare, ALB…), define `TRUSTED_PROXIES=<sus rangos>` y configura el módulo `realip` de Nginx (`DEPLOY.md` §7); si no, Laravel verá la IP del balanceador para todos y el límite de intentos de login por IP los juntará.

**6. Logos `.svg`** (según el análisis del salto, Laravel 12+ ya no acepta `.svg` en la regla `image`: afectaría a **subir** logos nuevos en `.svg`; los ya guardados se siguen mostrando)
```bash
mysql -u USUARIO -p NOMBRE_BD -e "select id, dominio, logo from tenants where logo like '%.svg'"
```

**7. Cuántas migraciones nuevas vienen**
```bash
git diff --stat HEAD origin/master -- database/migrations | tail -1
```
En desarrollo, crear **todo** el esquema desde cero tarda unos 4 minutos; un despliegue aplica solo las nuevas. No se ha medido en producción: reserva margen.

**8. Backup de prueba y copia fuera del servidor** (no esperes a que falle el del script)
```bash
php artisan sge:backup
ls -lh storage/app/backups | tail -3        # ANOTA el archivo y comprueba que el tamaño es razonable
scp storage/app/backups/<archivo>.sql usuario@otra-maquina:/ruta/segura/
```

**9. Permiso para recargar PHP-FPM sin contraseña** (el script lo hace solo; sin esto solo avisa y tendrás que hacerlo a mano)
```bash
sudo visudo -f /etc/sudoers.d/zuraedu-deploy
# usuario-que-despliega ALL=(root) NOPASSWD: /usr/bin/systemctl reload php8.3-fpm
sudo -n systemctl reload php8.3-fpm && echo "recarga sin contraseña OK"
```

**10. Ventana.** Avisa a los usuarios y elige un horario de poco tráfico. Mientras dura el despliegue la aplicación muestra «mantenimiento».

---

## B. El día del despliegue

**11. Abre una sesión que sobreviva a un corte de SSH**
```bash
tmux new -s deploy        # si se corta la conexión: tmux attach -t deploy
```

**12. Variables y ejecución**
```bash
cd /ruta/al/proyecto
export DEPLOY_HEALTH_URL=https://tu-dominio.edu.do/health
export PHP_FPM_SERVICE=php8.3-fpm        # el nombre que anotaste en el paso 1
./deploy.sh                               # o ./deploy.sh --sin-assets si public/build ya viene compilado y no hay Node
```
**Anota** el tag `deploy-AAAAMMDD-HHMMSS` y el archivo de backup que imprime. Verás los pasos `[1/9]` … `[9/9]`.

Si **falla el backup**, el script sale de mantenimiento solo y no toca nada. Si falla **cualquier paso posterior**, la aplicación **se queda en mantenimiento a propósito** y el script te dice en qué paso fue y qué hacer (ver sección D).

---

## C. Justo después (≈ 15 minutos)

**13. Estado del sistema**
```bash
curl -s -o /dev/null -w "%{http_code}\n" https://tu-dominio.edu.do/health     # 200
php artisan about | head -25            # Laravel 13.x, Environment production, drivers de caché/cola/sesión
php artisan horizon:status              # "Horizon is running"
supervisorctl status                    # sge-horizon y sge-reverb en RUNNING
systemctl is-active php8.3-fpm          # active
tail -n 80 storage/logs/laravel.log     # sin errores nuevos
```

**14. Pruebas en el navegador**, con un usuario real de cada rol principal (administrador, docente, padre/estudiante): iniciar sesión, ver el dashboard, listar estudiantes, matricular a un estudiante de prueba (usa las validaciones nuevas), abrir un PDF (boletín) y comprobar la campanita de notificaciones en tiempo real.

**15. Comprobar el índice del listado de estudiantes y `TRUSTED_PROXIES`** — comandos exactos en `DEPLOY.md` §8b («Índice del listado de estudiantes») y §7 («IP real del cliente»).

**16. El planificador:** `crontab -l` debe tener `* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1` (`DEPLOY.md` §5).

**17. Vigila 24–48 horas:** `tail -f storage/logs/laravel.log`, `php artisan horizon:status`, y los avisos de los usuarios.

---

## D. Si algo falla

| Situación | Qué hacer |
|---|---|
| Falló el **backup** (`[2/9]`) | Nada: el script ya salió de mantenimiento y no tocó el código. Arregla `mysqldump`/permisos/espacio y repite. |
| Falló **`git push` del tag** (`[3/9]`) | Falta permiso de escritura (paso 3). La aplicación sigue en mantenimiento: arregla las credenciales, repite `./deploy.sh`. |
| Falló **`composer install`** (`[5/9]`) | Casi siempre es la versión de PHP (paso 1). Corrige y repite `./deploy.sh`. |
| Falló una **migración** (`[7/9]`) | **MySQL no deshace los cambios de esquema a medias.** Mira `php artisan migrate:status` antes de repetir. Si la base quedó inconsistente: `./rollback.sh <tag> --restaurar-bd=<backup>`. |
| No se pudo recargar **PHP-FPM** | El script **avisa y sigue**. Ejecuta **ya** `sudo systemctl reload php8.3-fpm`: con `opcache.validate_timestamps=0` el servidor sigue sirviendo el código anterior hasta que lo hagas. |
| La aplicación sale de mantenimiento pero algo está mal | Decide **en los primeros minutos** (cuantos menos datos nuevos se escriban, menos se pierden al restaurar). |

**Revertir:**
```bash
git tag --list 'deploy-*' --sort=-creatordate | head          # el tag que anotaste
./rollback.sh deploy-AAAAMMDD-HHMMSS                           # SOLO el código (la base de datos no se toca)
./rollback.sh deploy-AAAAMMDD-HHMMSS --restaurar-bd=storage/app/backups/<archivo>.sql   # además, restaura la BD (pide escribir CONFIRMAR)
```
`rollback.sh` recarga PHP-FPM igual que `deploy.sh`, deja el repositorio en *detached HEAD* sobre el tag (es lo esperado) y **no revierte migraciones**.
**Ojo:** si ya se migró y se revierte solo el código sin restaurar la BD, el código viejo corre sobre un esquema nuevo y puede fallar. Restaurar la BD sobrescribe todo lo escrito desde el backup: por eso el despliegue mantiene la aplicación en mantenimiento mientras ocurre.

---

## Lo que este runbook NO cubre

- **La API TypeScript y la web nueva (`/nuevo`):** `deploy.sh` no las toca y no hacen falta para que funcione lo demás. Su despliegue (compilar, gestor de procesos, Nginx) está en `ts/deploy/nginx/README.md`.
- **El primer despliegue desde cero** de un servidor nuevo: ver `DEPLOY.md` §1.
