# Backup automático de ZuraEdu

Resuelve el **blocker #1 del Gate de Producción** ([[GATE_PRODUCCION_ZURAEDU]]):
"Sin backup automático de base de datos y archivos". Antes de esto, el
respaldo dependía 100% de que un administrador entrara al panel
(`/admin/sistema/backup`) y presionara el botón manualmente.

## 1. Qué se respalda

- **Base de datos completa** (`mysqldump --single-transaction --routines
  --triggers`): todos los tenants a la vez, porque ZuraEdu usa una sola BD
  compartida con `tenant_id` (no una BD por tenant) — un solo backup diario
  ya cubre centros educativos, usuarios, estudiantes, docentes, matrículas,
  calificaciones, finanzas, Carnet+, Classroom y configuraciones de todos
  los tenants. **No se generan backups separados por tenant.**
- **Archivos persistentes**: todo `storage/app/public/` comprimido en un
  `.zip` — carnets/fotos (`fotos/`), entregas de Classroom (`classroom/`,
  `entregas/`), boletines generados (`boletines/`), branding (`branding/`,
  `logos/`), planes de clase (`planes_clase/`) y archivos de sistema
  (`sistema/`).

**Explícitamente NO se respalda** (verificado antes de implementar, no se
asumió): `storage/app/imports/` e `storage/app/import_temp/` (áreas de
trabajo temporal para importaciones masivas, no datos finales) ni ningún
archivo suelto de trabajo en `storage/app/` raíz. El código fuente ya vive
en git y no necesita backup aparte.

## 2. Dónde se almacena

`storage/app/backups/` — disco `local` de Laravel (privado, **fuera** de
`storage/app/public/`, que es lo único expuesto por `storage:link`). Nunca
accesible por URL pública. La descarga/eliminación manual desde el panel ya
tenía protección contra path traversal (`BackupSecurityTest`), sin cambios.

Si se necesita almacenamiento externo (recomendado para producción real,
para no depender de un solo disco del mismo servidor): Laravel ya trae
soporte S3 configurado en `config/filesystems.php` (`disks.s3`). Para
activarlo, definir en el `.env` real del servidor (nunca en el repositorio):

```
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=...
AWS_BUCKET=...
```

y copiar los archivos de `storage/app/backups/` a ese disco después de cada
corrida (o cambiar `BACKUP_DISCO=s3`, lo cual requiere adaptar
`BackupService` para escribir directo al disco S3 en vez de al filesystem
local — **no implementado todavía**, fuera del alcance de esta tarea).

## 3. Cuándo se ejecuta

Diariamente vía el scheduler de Laravel (`app/Console/Kernel.php`), a la
hora definida en `BACKUP_HORA` (`.env`, por defecto `02:30`).

**Importante sobre la hora**: `config('app.timezone')` de este proyecto es
`UTC` (no se modificó — está fuera del alcance de esta tarea tocar el
timezone global de la app). El scheduler de Laravel usa esa zona horaria,
igual que todos los demás `$schedule->command()` ya existentes en este
`Kernel.php`. `02:30` en el schedule es **02:30 UTC**, no hora de Santo
Domingo. Si se requiere que corra a las 02:30 hora de Santo Domingo
(UTC-4), poner `BACKUP_HORA=06:30` en el `.env` real del servidor.

El servidor necesita el cron de Laravel corriendo (una sola entrada, si no
existe ya de antes por las otras tareas programadas de este `Kernel.php`):

```
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

## 4. Cuánto tiempo se conserva

**Se verificó antes de implementar** que no existía ninguna política de
retención previa (el botón manual del panel nunca borraba nada solo; el
único borrado era manual desde la UI). Se implementó como propuesta inicial,
configurable:

- `BACKUP_RETENCION_DIAS=7` (`.env`) — se conservan los backups (BD y
  archivos) de los últimos 7 días; los más viejos se eliminan automáticamente
  después de cada corrida **exitosa**. Si una corrida falla, no se aplica
  retención (para no perder backups válidos anteriores mientras el problema
  no esté resuelto).
- La retención solo borra archivos que calzan con el patrón
  `backup_*.sql` / `files_*.zip` dentro de `storage/app/backups/` — nunca
  toca otro archivo, y nunca los backups ya existentes antes de esta
  implementación (se verificó explícitamente que no se borra nada al
  desplegar este cambio).

## 5. Cómo verificar un backup

Cada corrida (manual o automática) queda registrada en la tabla
`backup_runs` (no es específica de un tenant — es a nivel de plataforma) con
fecha de inicio/fin, duración, tamaños de archivo, y si falló, en qué etapa
y con qué mensaje. El panel `/admin/sistema/backup` muestra "Último backup
exitoso" tomando este registro.

Verificación automática que ya hace el propio comando antes de marcar una
corrida como exitosa: el archivo debe existir y pesar más que
`config('backup.tamano_minimo_bytes')` (100 bytes) — un dump vacío o
truncado por un error de `mysqldump` nunca se registra como éxito.

**No se implementó** una restauración de prueba automática en un entorno de
staging (pedido como "si es viable sin riesgo" en el prompt original) — este
proyecto no tiene todavía un entorno de staging separado (ver blocker #2 del
Gate de Producción, deployment). Queda como paso manual: ver sección 6.

## 6. Cómo restaurar

**Nunca restaurar sobre producción para probar.** Para restaurar la base de
datos en un entorno de prueba/staging:

```bash
mysql -h HOST -u USUARIO -p NOMBRE_BD < backup_2026-09-04_02-30-00.sql
```

Para restaurar los archivos, descomprimir el `.zip` correspondiente dentro
de `storage/app/public/` del entorno destino y correr `php artisan
storage:link` si el symlink no existe.

## 7. Qué ocurre si falla

El comando (`sge:backup`) nunca oculta un error:

- Se registra en `storage/logs/backup.log` (canal `backup`, dedicado —
  igual patrón que el canal `horario` ya existente en
  `config/logging.php` — con líneas `BACKUP STARTED` / `BACKUP DATABASE
  SUCCESS` / `BACKUP FILES SUCCESS` / `RETENTION SUCCESS` / `BACKUP
  COMPLETE`, o `BACKUP FAILED` con fecha, etapa donde falló y mensaje.
  **Nunca se registra la contraseña de BD** (va al subproceso `mysqldump`
  por variable de entorno, nunca por argumento de línea de comandos ni por
  log).
- Se registra en `backup_runs` con `estado = 'fallido'`, la etapa
  (`backup_bd`, `backup_archivos`, `retencion`, etc.) y el mensaje.
- El comando retorna código de salida distinto de cero (`Command::FAILURE`),
  así que cualquier supervisor de cron/CI que revise el exit code lo detecta.
- **Notificación**: se reutiliza el mecanismo de logging de Laravel ya
  declarado en este proyecto (`config/logging.php` ya trae un canal `slack`
  nativo de Laravel, hoy inactivo por falta de `LOG_SLACK_WEBHOOK_URL`). No
  se construyó un sistema de notificación nuevo — los canales de
  notificación reales del proyecto (`Notificacion`, `WhatsAppService`) son
  para usuarios/representantes de un tenant específico, no para alertar a
  un administrador de plataforma sobre un fallo de infraestructura, así que
  no aplican aquí. Para recibir un aviso por Slack cuando falle un backup,
  basta con definir `LOG_SLACK_WEBHOOK_URL` en el `.env` real y agregar
  `'backup'` al array `channels` del canal `stack`, o cambiar
  `LOG_CHANNEL=backup` puntualmente — no requiere tocar código.

## 8. Cómo configurar almacenamiento externo

Ver sección 2. No se generaron ni inventaron credenciales de ningún tipo.

## 9. Qué necesita configurar el servidor

- `mysqldump` disponible en el `PATH` del usuario que corre el cron (mismo
  requisito que ya tenía el botón manual del panel — no es nuevo).
- Extensión `ZipArchive` de PHP habilitada (verificada: disponible en el
  PHP 8.3.31 usado por este proyecto).
- El cron de `schedule:run` corriendo cada minuto (sección 3).
- Espacio en disco suficiente para `retención_dias` × tamaño de un backup
  completo (BD + archivos).

## 10. Cómo probarlo en staging

```bash
php artisan sge:backup                # corrida completa (BD + archivos)
php artisan sge:backup --sin-archivos # solo BD, más rápido para pruebas
```

Confirmar después: el archivo aparece en `storage/app/backups/`, la fila
nueva en `backup_runs` tiene `estado = 'exitoso'`, y `/admin/sistema/backup`
muestra la fecha como "Último backup exitoso".

## Configuración (`.env` / `config/backup.php`)

| Variable | Por defecto | Qué controla |
|---|---|---|
| `BACKUP_ENABLED` | `true` | Si el schedule registra la tarea diaria |
| `BACKUP_HORA` | `02:30` | Hora diaria (UTC — ver sección 3) |
| `BACKUP_RETENCION_DIAS` | `7` | Días que se conservan los backups |
| `BACKUP_INCLUIR_ARCHIVOS` | `true` | Si también respalda `storage/app/public` |
| `BACKUP_DISCO` | `local` | Reservado para uso futuro con disco externo |

## Decisiones tomadas durante la auditoría previa a implementar

- Se usó `app/Console/Kernel.php` (arquitectura clásica), **no**
  `bootstrap/app.php`→`withSchedule()` (estilo Laravel 11+), porque este
  proyecto (Laravel 13.29.0) sigue registrando `App\Console\Kernel`
  explícitamente en `bootstrap/app.php` y ya tiene 14 tareas programadas ahí
  — se siguió la convención existente en vez de introducir una arquitectura
  paralela.
- El comando se llamó `sge:backup` (no `zuraedu:backup`) para seguir el
  prefijo `sge:` ya usado por el único otro comando de mantenimiento a nivel
  de plataforma (`sge:saneamiento`), en vez del prefijo de ejemplo del
  pedido original.
- El respaldo de archivos usa `ZipArchive` (extensión nativa de PHP) en vez
  de invocar `zip`/`tar` por shell, para no agregar una segunda dependencia
  de binario externo además de `mysqldump`.
