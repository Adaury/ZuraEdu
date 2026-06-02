# Guía de Despliegue — SGE PSAC / ZuraEdu

## Requisitos del servidor

| Componente | Versión mínima |
|---|---|
| PHP | 8.1+ (extensiones: pdo_mysql, redis, pcntl, posix, gd, zip) |
| MySQL | 8.0+ |
| Redis | 6.0+ |
| Node.js | 18+ (solo si compilas assets en el servidor) |
| Composer | 2.x |
| Supervisor | cualquier versión estable |

---

## 1. Instalación inicial (primer despliegue)

```bash
# 1. Instalar dependencias PHP
composer install --no-dev --optimize-autoloader

# 2. Crear y configurar el .env
cp .env.example .env
# → Editar .env con los datos del servidor (ver sección 2)

# 3. Generar clave de aplicación
php artisan key:generate

# 4. Permisos de carpetas
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 5. Enlace de almacenamiento
php artisan storage:link

# 6. Migraciones y seeders base
php artisan migrate --force
php artisan db:seed --force

# 7. Crear SuperAdmin de plataforma
php artisan superadmin:crear \
    --name="Super Admin" \
    --email=admin@zuraedu.com \
    --password=Admin123*

# 8. Limpiar y optimizar caché
php artisan optimize:clear
php artisan optimize
```

> **Nota:** Si los assets ya vienen compilados en `public/build/` (incluidos en el ZIP), no necesitas ejecutar `npm run build`. Si no están, ejecuta: `npm ci && npm run build`

---

## 2. Variables .env críticas para producción

```env
APP_NAME="SGE PSAC"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.edu.do
FORCE_HTTPS=true

APP_PRODUCT_NAME="SGE PSAC"

# ── Institución ───────────────────────────────────────────────────────────
SCHOOL_NAME="Centro Educativo PSAC"
SCHOOL_NIVEL="Nivel Secundario"
SCHOOL_CODIGO="12345"

# ── Base de datos ─────────────────────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sge_psac
DB_USERNAME=sge_user
DB_PASSWORD=contraseña-segura-aqui

# ── Cola y caché ──────────────────────────────────────────────────────────
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# ── Reverb (WebSocket realtime) ───────────────────────────────────────────
BROADCAST_DRIVER=reverb
REVERB_APP_ID=sge_psac
REVERB_APP_KEY=clave-aleatoria-segura
REVERB_APP_SECRET=secreto-aleatorio-seguro
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=https
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=tu-dominio.edu.do
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=https

# ── Horizon ───────────────────────────────────────────────────────────────
HORIZON_PREFIX=sge_horizon:
HORIZON_ALLOWED_EMAILS=admin@tu-dominio.edu.do

# ── Email ─────────────────────────────────────────────────────────────────
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=correo@tu-dominio.edu.do
MAIL_PASSWORD=app-password-aqui
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tu-dominio.edu.do
MAIL_FROM_NAME="${APP_NAME}"

# ── IA — Google Gemini (opcional) ─────────────────────────────────────────
# Obtener en: https://aistudio.google.com/app/apikey
GEMINI_API_KEY=

# ── Push notifications móviles (Expo) ────────────────────────────────────
# Obtener en: expo.dev → Settings → Access Tokens
EXPO_ACCESS_TOKEN=

# ── Pagos (Stripe, opcional) ──────────────────────────────────────────────
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

---

## 3. Usuarios y credenciales de acceso

### Usuarios del sistema (creados por los seeders)

| Rol | Email | Contraseña | Acceso |
|-----|-------|-----------|--------|
| Administrador | `admin@sge.test` | `Admin2030!` | Panel admin principal |
| Encargado Registro Académico | `registro@demo.com` | `Registroadmin` | Módulo registro académico |
| SuperAdmin plataforma | `admin@zuraedu.com` | `Admin123*` | Panel `/superadmin` |

### Docentes de demostración

| Email | Contraseña | Nombre |
|-------|-----------|--------|
| `ana.garcia@sge.test` | `Docente2030!` | Ana María García Rosario |
| `carlos.rodriguez@sge.test` | `Docente2030!` | Carlos Rodríguez Marte |
| `elena.martinez@sge.test` | `Docente2030!` | Elena Martínez Taveras |
| `jose.perez@sge.test` | `Docente2030!` | José Manuel Pérez Bautista |
| `maria.sanchez@sge.test` | `Docente2030!` | María Sánchez Féliz |
| `roberto.torres@sge.test` | `Docente2030!` | Roberto Torres Núñez |
| `carmen.lopez@sge.test` | `Docente2030!` | Carmen López De la Rosa |
| `miguel.gonzalez@sge.test` | `Docente2030!` | Miguel Ángel González Suriel |

### Usuarios demo (portal público de demostración)

| Rol | Email | Contraseña |
|-----|-------|-----------|
| Administrador | `admin@demo.com` | `123456` |
| Docente | `docente@demo.com` | `123456` |
| Representante | `padre@demo.com` | `123456` |
| Estudiante | `estudiante@demo.com` | `123456` |

> **Importante en producción:** Cambiar las contraseñas de `admin@sge.test` y `admin@zuraedu.com` inmediatamente después del primer login. Los usuarios `@demo.com` son solo para demostración pública.

---

## 4. Multi-tenant — primer tenant (institución)

Después del primer deploy, crear el tenant desde el panel SuperAdmin:

```
https://tu-dominio.edu.do/superadmin/tenants/create
```

O por Tinker:

```bash
php artisan tinker
```
```php
\App\Models\Tenant::create([
    'nombre'     => 'Centro Educativo PSAC',
    'subdominio' => 'psac',
    'plan'       => 'enterprise',
    'activo'     => true,
]);
```

---

## 5. Scheduler (crontab)

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

| Intervalo | Tarea |
|---|---|
| 06:00 diario | `alertas:rendimiento` — notas < 60 → alerta |
| 07:00 diario | `alertas:entrega-notas` — fechas de cierre próximas |
| Cada 5 min | `horizon:snapshot` — métricas para gráficas |
| Domingo 00:00 | `horizon:clear-metrics` / `queue:prune-failed` |
| Domingo 03:00 | `session:flush` |

---

## 6. Supervisor — Horizon + Reverb

Crear `/etc/supervisor/conf.d/sge.conf`:

```ini
[program:sge-horizon]
process_name=%(program_name)s
command=php /ruta/al/proyecto/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/ruta/al/proyecto/storage/logs/horizon.log
stopwaitsecs=3600

[program:sge-reverb]
process_name=%(program_name)s
command=php /ruta/al/proyecto/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/ruta/al/proyecto/storage/logs/reverb.log
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start sge-horizon sge-reverb
supervisorctl status
```

### Colas gestionadas por Horizon

| Cola | Propósito |
|---|---|
| `notifications` | Notificaciones en-app + push móvil |
| `emails` | Envío de correos |
| `pdfs` | Generación de PDFs |
| `classroom` | Eventos de ZuraClass |
| `whatsapp` | Mensajes WhatsApp |
| `exports` | Exportaciones Excel/CSV |
| `sigerd` | Integración SIGERD/MINERD |
| `default` | Jobs sin cola específica |

---

## 7. Nginx — proxy WebSocket para Reverb

Agregar dentro del bloque `server`:

```nginx
location /app {
    proxy_pass             http://127.0.0.1:8080;
    proxy_http_version     1.1;
    proxy_set_header       Upgrade $http_upgrade;
    proxy_set_header       Connection "upgrade";
    proxy_set_header       Host $host;
    proxy_set_header       X-Real-IP $remote_addr;
    proxy_read_timeout     60s;
    proxy_send_timeout     60s;
}
```

---

## 8. Permisos de carpetas

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 9. Checklist de deploy (actualizaciones)

```bash
# 1. Modo mantenimiento
php artisan down

# 2. Subir código (descomprimir ZIP o git pull)
# git pull origin master

# 3. Dependencias
composer install --no-dev --optimize-autoloader

# 4. Base de datos
php artisan migrate --force

# 5. Limpiar y reconstruir caché
php artisan optimize:clear
php artisan optimize

# 6. Reiniciar workers y WebSocket
supervisorctl restart sge-horizon sge-reverb

# 7. Subir el sitio
php artisan up
```

---

## 10. Monitoreo

| URL | Descripción |
|---|---|
| `/horizon` | Panel Horizon — queues, workers, métricas, failed jobs |
| `/health` | JSON con estado de DB, Redis y Horizon |
| `/superadmin` | Panel SuperAdmin — tenants, planes, feature flags |

---

## 11. Cómo comprimir el proyecto para envío

Ejecutar desde el directorio **padre** de la carpeta `sge`:

```bash
# Linux / Mac
tar -czf sge.tar.gz \
    --exclude="sge/vendor" \
    --exclude="sge/node_modules" \
    --exclude="sge/storage/logs" \
    --exclude="sge/.env" \
    sge/

# Windows (PowerShell con 7-Zip)
7z a sge.zip sge\ -xr!vendor -xr!node_modules -xr!"storage\logs" -x!.env
```

> La carpeta `public/build/` **debe incluirse** en el ZIP para no necesitar compilar assets en el servidor.

---

## 12. Desarrollo local (Laragon)

```bash
# Terminal 1 — Queue workers
php artisan horizon

# Terminal 2 — WebSocket
php artisan reverb:start
```

Variables `.env` para Laragon:

```env
APP_URL=http://sge.test
BROADCAST_DRIVER=reverb
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
VITE_REVERB_HOST=localhost
VITE_REVERB_SCHEME=http
```
