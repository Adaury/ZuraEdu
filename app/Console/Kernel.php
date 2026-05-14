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
        // ── Alertas de ausencias repetidas (diario 05:30) ────────────────────
        $schedule->command('alertas:ausencias')->dailyAt('05:30');

        // ── Alertas de riesgo académico (notas < 60 publicadas) ───────────────
        $schedule->command('alertas:rendimiento')->dailyAt('06:00');

        // ── Alertas de baja académica y baja asistencia ───────────────────────
        $schedule->command('alertas:academicas')->dailyAt('06:30');

        // ── Alertas de entrega de notas (eventos próximos ≤ 3 días) ──────────
        $schedule->command('alertas:entrega-notas')->dailyAt('07:00');

        // ── Procesar cola de emails pendientes ────────────────────────────────
        $schedule->command('queue:work --stop-when-empty --tries=3')
                 ->everyFiveMinutes()
                 ->withoutOverlapping();

        // ── Métricas de Horizon (gráficas del dashboard) ─────────────────────
        $schedule->command('horizon:snapshot')->everyFiveMinutes();

        // ── Limpiar trabajos fallidos con más de 7 días ───────────────────────
        $schedule->command('queue:prune-failed --hours=168')->weekly();

        // ── Recordatorio semanal de pagos vencidos (lunes 08:00) ─────────────
        $schedule->command('pagos:recordatorio-vencidos')->weeklyOn(1, '08:00');

        // ── Limpiar sesiones expiradas ────────────────────────────────────────
        $schedule->command('session:flush')->weeklyOn(0, '03:00');

        // ── Limpiar tenants demo temporales vencidos (cada hora) ─────────────
        $schedule->command('demo:limpiar --force')->hourly();

        // ── Verificar pagos: suspender vencidos, reactivar pagados (diario) ───
        $schedule->command('tenants:verificar-pagos')->dailyAt('02:00');
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
