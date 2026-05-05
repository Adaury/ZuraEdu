# Guía de Despliegue — EduCore PSAC

## Requisitos del servidor
- PHP 8.1+
- MySQL 8.0+
- Composer
- Node.js 18+ (para compilar assets)

---

## 1. Configuración inicial

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan optimize
php artisan storage:link
```

---

## 2. Variables .env críticas para producción

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.edu.do

QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=correo@tudominio.edu.do
MAIL_PASSWORD=app-password-aqui
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tudominio.edu.do

GEMINI_API_KEY=tu-clave-aqui
```

---

## 3. Scheduler (Tareas programadas)

Agrega esta línea al **crontab del servidor** (`crontab -e`):

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

### Tareas que corren automáticamente:
| Hora | Tarea |
|------|-------|
| 06:00 diario | `alertas:rendimiento` — detecta notas < 60 y alerta coordinadores |
| 07:00 diario | `alertas:entrega-notas` — alerta sobre fechas de cierre próximas |
| Cada 5 min | `queue:work` — procesa cola de emails |
| Domingo 00:00 | `queue:prune-failed` — limpia jobs fallidos > 7 días |
| Domingo 03:00 | `session:flush` — limpia sesiones expiradas |

---

## 4. Queue Worker (procesador de emails)

### En servidor Linux/producción:
```bash
# Con Supervisor (recomendado)
# /etc/supervisor/conf.d/educore-worker.conf
[program:educore-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/proyecto/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/ruta/proyecto/storage/logs/worker.log
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start educore-worker:*
```

### En Laragon (desarrollo local):
```bash
php artisan queue:work --tries=3
```

---

## 5. Permisos de carpetas

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 6. Comandos útiles post-deploy

```bash
php artisan optimize:clear     # limpiar todo el caché
php artisan optimize           # reconstruir caché
php artisan queue:restart      # reiniciar workers después de deploy
php artisan alertas:rendimiento --force  # forzar regeneración de alertas
```
