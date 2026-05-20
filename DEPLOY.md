# Guía de Despliegue — ZuraEdu / EduSGE

## Requisitos del servidor

| Componente | Versión mínima |
|---|---|
| PHP | 8.1+ (con extensiones: pdo_mysql, redis, pcntl, posix) |
| MySQL | 8.0+ |
| Redis | 6.0+ |
| Node.js | 18+ (compilar assets) |
| Supervisor | cualquier versión estable |

---

## 1. Instalación inicial

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=RolesSeeder        # roles y permisos base
php artisan db:seed --class=SuperAdminSeeder   # primer superadmin
php artisan storage:link
php artisan optimize
npm ci && npm run build
```

---

## 2. Variables .env críticas para producción

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.edu.do

# ── Base de datos ──────────────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zuraedu
DB_USERNAME=zuraedu_user
DB_PASSWORD=password-seguro-aqui

# ── Cola y caché ──────────────────────────────────────────────────
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=tu-password-redis   # null si sin contraseña

# ── Reverb (WebSocket realtime) ───────────────────────────────────
BROADCAST_DRIVER=reverb
REVERB_APP_ID=zuraedu_sge
REVERB_APP_KEY=zuraedu_realtime_key         # clave aleatoria segura
REVERB_APP_SECRET=zuraedu_realtime_secret   # secreto aleatorio seguro
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=https    # https en producción con SSL
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=tu-dominio.edu.do
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=https

# ── Horizon ───────────────────────────────────────────────────────
HORIZON_PREFIX=zuraedu_horizon:
HORIZON_ALLOWED_EMAILS=admin@tudominio.edu.do

# ── Email ─────────────────────────────────────────────────────────
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=correo@tudominio.edu.do
MAIL_PASSWORD=app-password-aqui
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tudominio.edu.do

# ── IA ────────────────────────────────────────────────────────────
GEMINI_API_KEY=tu-clave-aqui

# ── Push notifications (Expo) ─────────────────────────────────────
EXPO_ACCESS_TOKEN=tu-expo-access-token   # expo.dev → Account → Access Tokens
```

---

## 3. Multi-tenant — primer tenant (institución)

Tras el primer deploy, crear el tenant desde el panel SuperAdmin:

```
https://tu-dominio.edu.do/superadmin/tenants/create
```

O por Tinker:

```bash
php artisan tinker
```
```php
$tenant = \App\Models\Tenant::create([
    'nombre'    => 'Nombre del Centro Educativo',
    'subdominio' => 'micentro',   // acceso en micentro.tu-dominio.edu.do
    'plan'      => 'enterprise',
    'activo'    => true,
]);
```

### Variables de entorno por tenant

No se usan archivos `.env` separados. Cada tenant lleva su configuración en la tabla `tenants` (columna `settings` JSON). Los valores sobreescriben la configuración global vía `BelongsToTenant` trait y el middleware `ResolveTenant`.

---

## 4. Scheduler (crontab)

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

### Tareas programadas activas:
| Intervalo | Tarea |
|---|---|
| 06:00 diario | `alertas:rendimiento` — notas < 60 → alerta |
| 07:00 diario | `alertas:entrega-notas` — fechas de cierre próximas |
| Cada 5 min | `horizon:snapshot` — métricas para gráficas en `/horizon` |
| Domingo 00:00 | `horizon:clear-metrics` / `queue:prune-failed` |
| Domingo 03:00 | `session:flush` |

---

## 5. Supervisor — Horizon + Reverb

Crear el archivo `/etc/supervisor/conf.d/zuraedu.conf`:

```ini
; ── Laravel Horizon (gestiona TODOS los queue workers) ────────────────────
[program:zuraedu-horizon]
process_name=%(program_name)s
command=php /ruta/al/proyecto/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/ruta/al/proyecto/storage/logs/horizon.log
stopwaitsecs=3600

; ── Laravel Reverb (servidor WebSocket) ───────────────────────────────────
[program:zuraedu-reverb]
process_name=%(program_name)s
command=php /ruta/al/proyecto/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/ruta/al/proyecto/storage/logs/reverb.log
```

Activar:
```bash
supervisorctl reread
supervisorctl update
supervisorctl start zuraedu-horizon zuraedu-reverb
supervisorctl status
```

### Queues gestionadas por Horizon:
| Cola | Propósito |
|---|---|
| `notifications` | Notificaciones en-app + push móvil + broadcast |
| `emails` | Envío de correos |
| `pdfs` | Generación de PDFs |
| `classroom` | Eventos de ZuraClass |
| `whatsapp` | Mensajes WhatsApp |
| `exports` | Exportaciones Excel/CSV |
| `sigerd` | Integración SIGERD/MINERD |
| `default` | Jobs sin cola específica |

---

## 6. Nginx — proxy para Reverb (WebSocket)

Agregar dentro del bloque `server` en la config de Nginx:

```nginx
# WebSocket — Reverb
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

> **SSL**: Si el sitio usa HTTPS, Reverb debe configurarse con `REVERB_SCHEME=https` y certificado SSL, o usar Nginx como terminador SSL con el proxy anterior.

---

## 7. Push notifications móviles (Expo)

El backend envía push notifications a través de la API de Expo. No requiere Firebase ni APNs directamente — Expo actúa como intermediario.

### 7.1 Obtener token de acceso

1. Ir a [expo.dev](https://expo.dev) → Settings → Access Tokens
2. Crear token con permiso `Push notifications`
3. Agregar al `.env`: `EXPO_ACCESS_TOKEN=...`

### 7.2 Flujo del sistema

```
App móvil                   Backend Laravel              Expo Push API
─────────                   ───────────────              ─────────────
POST /api/push-token  ──►   Guarda token en              
  { token, role }           push_tokens table            
                                                         
Evento en backend  ──►      NotificacionCreada job  ──►  POST /v2/push/send
                            (cola: notifications)        { to: expoPushToken }
```

### 7.3 Formato del payload

```json
{
  "to": "ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]",
  "title": "Título de la notificación",
  "body": "Cuerpo del mensaje",
  "data": {
    "tipo": "nueva_nota | nuevo_mensaje | comunicado | tarea | ...",
    "id": 123
  }
}
```

El campo `data.tipo` es interpretado por el deeplink handler de la app (`hooks/usePushNotifications.ts`) para navegar a la pantalla correcta según el rol del usuario.

---

## 8. Permisos de carpetas

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 9. Deploy checklist

```bash
# 1. Bajar el sitio (opcional)
php artisan down

# 2. Actualizar código
git pull origin master

# 3. Dependencias
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 4. Base de datos
php artisan migrate --force

# 5. Limpiar y reconstruir caché
php artisan optimize:clear
php artisan optimize

# 6. Reiniciar workers y WebSocket
supervisorctl restart zuraedu-horizon zuraedu-reverb

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

## 11. Desarrollo local (Laragon)

Levanta los tres procesos en terminales separadas:

```bash
# Terminal 1 — Servidor web (Laragon lo hace automáticamente)

# Terminal 2 — Queue workers
php artisan horizon

# Terminal 3 — WebSocket
php artisan reverb:start
```

> Variables `.env` locales para desarrollo con Laragon:
> ```env
> APP_URL=http://sge.test
> REVERB_HOST=localhost
> REVERB_PORT=8080
> REVERB_SCHEME=http
> VITE_REVERB_HOST=localhost
> VITE_REVERB_SCHEME=http
> BROADCAST_DRIVER=reverb
> QUEUE_CONNECTION=redis
> ```
