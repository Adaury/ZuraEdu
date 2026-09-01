<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\LoopsPerTenant;
use App\Mail\RecordatorioCierrePeriodo;
use App\Models\AlertaSistema;
use App\Models\CalendarioAcademico;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class AlertasEntregaNotas extends Command
{
    use LoopsPerTenant;

    protected $signature   = 'alertas:entrega-notas';
    protected $description = 'Genera alertas para eventos de entrega de notas próximos (≤ 3 días)';

    public function handle(): int
    {
        $total = 0;

        $this->forEachTenant(function ($tenant) use (&$total) {
            $total += $this->procesarTenant();
        });

        $this->info("Alertas generadas: {$total}");
        return self::SUCCESS;
    }

    private function procesarTenant(): int
    {
        $schoolYear = SchoolYear::actual();
        if (!$schoolYear) {
            return 0;
        }

        $eventos = CalendarioAcademico::where('school_year_id', $schoolYear->id)
            ->where('tipo', 'entrega_notas')
            ->where('activo', true)
            ->whereDate('fecha_inicio', '>=', now()->toDateString())
            ->whereDate('fecha_inicio', '<=', now()->addDays(3)->toDateString())
            ->get();

        if ($eventos->isEmpty()) {
            return 0;
        }

        $docentes = User::role(User::ROLES_DOCENTE)->get();
        $created  = 0;

        foreach ($eventos as $evento) {
            // abs(): Carbon 3 puede devolver diffIn*() con signo según dirección.
            $diasRestantes = (int) abs(now()->startOfDay()->diffInDays($evento->fecha_inicio));
            $cuando = $diasRestantes === 0 ? 'hoy' : "en {$diasRestantes} día(s)";

            foreach ($docentes as $docente) {
                $existe = AlertaSistema::where('tipo', 'entrega_notas')
                    ->where('referencia_tipo', CalendarioAcademico::class)
                    ->where('referencia_id', $evento->id)
                    ->where('destinatario_id', $docente->id)
                    ->exists();

                if (!$existe) {
                    AlertaSistema::create([
                        'tipo'            => 'entrega_notas',
                        'titulo'          => "Entrega de notas: {$evento->titulo}",
                        'mensaje'         => "El evento \"{$evento->titulo}\" vence {$cuando}. Asegúrate de publicar tus calificaciones a tiempo.",
                        'nivel'           => $diasRestantes === 0 ? 'danger' : 'warning',
                        'destinatario_id' => $docente->id,
                        'referencia_tipo' => CalendarioAcademico::class,
                        'referencia_id'   => $evento->id,
                        'school_year_id'  => $schoolYear->id,
                        'leida'           => false,
                        'expira_en'       => $evento->fecha_inicio->addDay(),
                    ]);

                    // Send email reminder if docente has an email address
                    if ($docente->email) {
                        try {
                            Mail::to($docente->email)
                                ->send(new RecordatorioCierrePeriodo($docente, $evento, $diasRestantes));
                        } catch (\Exception $e) {
                            $this->warn("Email no enviado a {$docente->email}: " . $e->getMessage());
                        }
                    }

                    $created++;
                }
            }
        }

        return $created;
    }
}
