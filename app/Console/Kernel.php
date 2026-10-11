<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ── Academic Risk Score: recálculo diario (antes solo con el botón admin) ──
        $schedule->command('riesgo:calcular')->dailyAt('05:00')->withoutOverlapping(60);

        // ── Alertas de ausencias repetidas (diario 05:30) ────────────────────
        $schedule->command('alertas:ausencias')->dailyAt('05:30');

        // ── Alertas de riesgo académico (notas < 60 publicadas) ───────────────
        $schedule->command('alertas:rendimiento')->dailyAt('06:00');

        // ── Alertas de baja académica y baja asistencia ───────────────────────
        $schedule->command('alertas:academicas')->dailyAt('06:30');

        // ── Alertas de entrega de notas (eventos próximos ≤ 3 días) ──────────
        $schedule->command('alertas:entrega-notas')->dailyAt('07:00');

        // ── Cumpleaños de estudiantes (diario 07:30) ──────────────────────────
        $schedule->command('alertas:cumpleanos')->dailyAt('07:30');

        // ── Procesar cola de emails pendientes ────────────────────────────────
        // Todas las colas: antes solo «default», y los WhatsApp («whatsapp») y las notificaciones («notifications») se
        // acumulaban sin enviarse. Con Horizon activo ambos conviven sin problema (cada trabajo lo toma uno solo).
        $schedule->command('queue:work --queue=default,notifications,whatsapp,emails,classroom,sigerd --stop-when-empty --max-time=50 --tries=3')
                 ->everyMinute()
                 ->withoutOverlapping(10);

        // ── Odoo: enviar contactos/facturas de los centros con la integración activa ──
        $schedule->command('odoo:sincronizar')->hourly()->withoutOverlapping(30);

        // ── Métricas de Horizon (gráficas del dashboard) ─────────────────────
        $schedule->command('horizon:snapshot')->everyFiveMinutes();

        // ── Limpiar trabajos fallidos con más de 7 días ───────────────────────
        $schedule->command('queue:prune-failed --hours=168')->weekly();

        // ── Aviso de pagos próximos a vencer (diario 08:00, 3 días antes) ────
        $schedule->command('pagos:aviso-proximo --dias=3')->dailyAt('08:00');

        // ── Recordatorio semanal de pagos vencidos (lunes 08:00) ─────────────
        $schedule->command('pagos:recordatorio-vencidos')->weeklyOn(1, '08:00');

        // ── Limpiar sesiones expiradas ────────────────────────────────────────
        $schedule->command('session:flush')->weeklyOn(0, '03:00');

        // ── Limpiar tenants demo temporales vencidos (cada hora) ─────────────
        $schedule->command('demo:limpiar --force')->hourly();

        // ── Verificar pagos: suspender vencidos, reactivar pagados (diario) ───
        $schedule->command('tenants:verificar-pagos')->dailyAt('02:00');

        // ── SIGERD: validación semanal y notificación al registrador (viernes 09:00) ──
        $schedule->command('sigerd:validar')->weeklyOn(5, '09:00');

        // ── Backup automático diario de BD + archivos (ver docs/BACKUP_ZURAEDU.md) ──
        // La hora, la frecuencia y los destinos los elige el superadministrador en su pantalla (tabla backup_configuracion); sin esa
        // tabla todavía (p. ej. durante una migración) se usan los valores de config/backup.php.
        if (config('backup.enabled', true)) {
            try {
                $bk = \App\Models\BackupConfiguracion::actual();
            } catch (\Throwable) {
                $bk = null;
            }
            $hora = $bk?->hora ?: config('backup.hora', '02:30');
            $evento = $schedule->command('sge:backup')->timezone($bk?->zona_horaria ?: 'UTC');
            ($bk && $bk->frecuencia === 'semanal') ? $evento->weeklyOn($bk->dia_semana, $hora) : $evento->dailyAt($hora);
            $evento->when(fn () => $bk ? (bool) $bk->activo : true)->withoutOverlapping()->onOneServer();
        }

        // Latido: la pantalla de respaldos avisa si el programador de tareas (cron) dejó de correr, que es lo que hace que el respaldo
        // automático de verdad se ejecute.
        $schedule->call(fn () => \Illuminate\Support\Facades\Cache::put('scheduler_latido', now()->getTimestamp(), 3600))->everyMinute()->name('scheduler-latido');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
