@echo off
REM Invocado cada minuto por una tarea de Windows Task Scheduler ("ZuraEdu Laravel Scheduler").
REM Deja que Laravel decida qué comando de app/Console/Kernel.php le toca correr en este minuto
REM (backup diario, alertas por email, recordatorios de pago, suspension automatica de tenants
REM morosos, limpieza de demos, snapshots de Horizon, etc.) — ver docs/BACKUP_ZURAEDU.md.
cd /d C:\laragon\www\sge
"C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" artisan schedule:run >> storage\logs\scheduler.log 2>&1
