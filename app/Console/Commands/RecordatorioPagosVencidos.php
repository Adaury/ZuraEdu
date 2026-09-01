<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\LoopsPerTenant;
use App\Models\ConfigInstitucional;
use App\Models\Notificacion;
use App\Models\Pago;
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class RecordatorioPagosVencidos extends Command
{
    use LoopsPerTenant;

    protected $signature   = 'pagos:recordatorio-vencidos';
    protected $description = 'Envía recordatorio por email/notificación a representantes con pagos vencidos';

    public function handle(): int
    {
        $total = 0;

        $this->forEachTenant(function ($tenant) use (&$total) {
            $total += $this->procesarTenant();
        });

        $this->info("Recordatorios enviados: {$total}");
        return self::SUCCESS;
    }

    private function procesarTenant(): int
    {
        if (! ConfigInstitucional::moduloActivo('pagos')) {
            return 0;
        }

        if (\App\Helpers\Setting::get('email_notif_pagos', '1') !== '1') {
            return 0;
        }

        $syActual = SchoolYear::actual();
        if (! $syActual) {
            return 0;
        }

        // Sincronizar vencidos primero
        $actualizados = Pago::sincronizarVencidos();
        $this->info("Pagos actualizados a vencido: {$actualizados}");

        // Obtener pagos vencidos del año actual
        $pagosVencidos = Pago::with([
                'matricula.estudiante.representantes',
                'matricula.estudiante.user',
            ])
            ->where('estado', 'vencido')
            ->whereHas('matricula', fn($m) => $m->where('school_year_id', $syActual->id))
            ->get();

        if ($pagosVencidos->isEmpty()) {
            return 0;
        }

        // Agrupar por representante para no enviar múltiples emails si tiene varios hijos
        $porRepresentante = $pagosVencidos->groupBy(function ($pago) {
            return $pago->matricula?->estudiante?->representantes->first()?->id ?? 'sin_rep';
        })->filter(fn($g, $k) => $k !== 'sin_rep');

        $enviados = 0;
        $si = ConfigInstitucional::get('nombre_institucion', config('app.name'));

        foreach ($porRepresentante as $repId => $pagosGrupo) {
            $rep = $pagosGrupo->first()?->matricula?->estudiante?->representantes->first();
            if (! $rep) continue;

            $totalDeuda = $pagosGrupo->sum('monto');
            $estudiante = $pagosGrupo->first()?->matricula?->estudiante;

            // Notificación interna si el representante tiene cuenta de usuario
            $userId = $rep->user_id;
            if ($userId) {
                Notificacion::create([
                    'user_id' => $userId,
                    'tipo'    => 'pago',
                    'titulo'  => 'Recordatorio: pagos vencidos',
                    'mensaje' => "Tiene RD$ " . number_format($totalDeuda, 2) . " en pagos vencidos. Por favor regularice su situación.",
                    'leida'   => false,
                    'datos'   => json_encode(['deuda' => $totalDeuda]),
                ]);
            }

            // WhatsApp al representante
            if ($rep->telefono) {
                WhatsAppService::send(
                    $rep->telefono,
                    "⚠️ *{$si}*\n\nEstimado representante, tiene *RD$ " . number_format($totalDeuda, 2) . "* en pagos escolares vencidos" .
                    ($estudiante ? " de *{$estudiante->nombre_completo}*" : '') .
                    ".\n\nPor favor regularice su situación ingresando al portal: " . config('app.url')
                );
            }

            // Email si el representante tiene correo
            if ($rep->email) {
                try {
                    Mail::send([], [], function ($message) use ($rep, $pagosGrupo, $totalDeuda, $estudiante, $si) {
                        $message->to($rep->email)
                            ->subject("⚠️ {$si} — Recordatorio de pagos vencidos")
                            ->html(view('emails.recordatorio-pagos', [
                                'rep'         => $rep,
                                'estudiante'  => $estudiante,
                                'pagos'       => $pagosGrupo,
                                'totalDeuda'  => $totalDeuda,
                                'si'          => $si,
                            ])->render());
                    });
                    $enviados++;
                } catch (\Throwable $e) {
                    $this->warn("Email no enviado a {$rep->email}: " . $e->getMessage());
                }
            }
        }

        return $enviados;
    }
}
