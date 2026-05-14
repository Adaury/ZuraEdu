<?php

namespace App\Console\Commands;

use App\Models\Notificacion;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class AlertasCumpleanos extends Command
{
    protected $signature   = 'alertas:cumpleanos {--force : Enviar aunque ya se haya enviado hoy}';
    protected $description = 'Felicita a estudiantes que cumplen años hoy y notifica a sus docentes y representantes';

    public function handle(): int
    {
        $sy = SchoolYear::actual();
        if (! $sy) {
            $this->warn('Sin año escolar activo.');
            return self::SUCCESS;
        }

        $hoy   = now()->format('m-d');
        $today = now()->toDateString();

        // Estudiantes activos cuyo cumpleaños es hoy
        $matriculas = Matricula::with([
                'estudiante.representantes',
                'estudiante.user',
                'grupo.asignaciones.docente.user',
            ])
            ->where('school_year_id', $sy->id)
            ->where('estado', 'activa')
            ->whereHas('estudiante', fn($q) =>
                $q->whereRaw("DATE_FORMAT(fecha_nacimiento, '%m-%d') = ?", [$hoy])
            )
            ->get();

        if ($matriculas->isEmpty()) {
            $this->info('Sin cumpleaños hoy.');
            return self::SUCCESS;
        }

        $enviados = 0;
        $inst     = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        foreach ($matriculas as $mat) {
            $est   = $mat->estudiante;
            $edad  = $est->fecha_nacimiento?->age ?? '';
            $nombre = $est->nombre_completo;

            // --- Notificación al estudiante (si tiene usuario) ---
            if ($est->user_id) {
                $cacheKey = "cumple_{$est->id}_{$today}";
                if ($this->option('force') || ! cache()->has($cacheKey)) {
                    Notificacion::enviar(
                        $est->user_id,
                        'cumpleanos',
                        '🎂 ¡Feliz cumpleaños!',
                        "¡{$inst} te desea un feliz cumpleaños! Que tengas un excelente día."
                    );
                    cache()->put($cacheKey, true, now()->endOfDay());
                    $enviados++;
                }
            }

            // --- WhatsApp al representante ---
            $rep = $est->representantes->first();
            if ($rep?->telefono) {
                $cacheKeyRep = "cumple_rep_{$est->id}_{$today}";
                if ($this->option('force') || ! cache()->has($cacheKeyRep)) {
                    $edadStr = $edad ? " ({$edad} año" . ($edad === 1 ? '' : 's') . ")" : '';
                    WhatsAppService::send(
                        $rep->telefono,
                        "🎂 *{$inst}*\n\nEstimado representante, hoy {$nombre}{$edadStr} celebra su cumpleaños.\n\n¡Felicitaciones de parte de todo el equipo educativo! 🎉"
                    );
                    cache()->put($cacheKeyRep, true, now()->endOfDay());
                }
            }

            // --- Notificación a docentes del grupo ---
            $docenteUserIds = $mat->grupo?->asignaciones
                ->pluck('docente.user_id')
                ->filter()
                ->unique();

            foreach ($docenteUserIds ?? [] as $uid) {
                $cacheKeyDoc = "cumple_doc_{$uid}_{$est->id}_{$today}";
                if ($this->option('force') || ! cache()->has($cacheKeyDoc)) {
                    Notificacion::enviar(
                        $uid,
                        'cumpleanos',
                        '🎂 Cumpleaños: ' . $est->nombre,
                        "Hoy {$nombre} cumple años. ¡Recuérdalo en clase! 🎉"
                    );
                    cache()->put($cacheKeyDoc, true, now()->endOfDay());
                }
            }

            $this->line(" ✓ {$nombre}");
        }

        $this->info("Cumpleaños procesados: {$matriculas->count()} | Notificaciones enviadas: {$enviados}");
        return self::SUCCESS;
    }
}
