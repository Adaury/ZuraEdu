<?php

namespace App\Console\Commands;

use App\Models\ConfigInstitucional;
use App\Models\Notificacion;
use App\Models\Pago;
use App\Models\Representante;
use App\Models\SchoolYear;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class RecordatorioPagosVencidos extends Command
{
    protected $signature   = 'pagos:recordatorio-vencidos';
    protected $description = 'Envía recordatorio por email/notificación a representantes con pagos vencidos';

    public function handle(): int
    {
        if (! ConfigInstitucional::moduloActivo('pagos')) {
            $this->info('Módulo de pagos inactivo. Nada que hacer.');
            return self::SUCCESS;
        }

        if (\App\Helpers\Setting::get('email_notif_pagos', '1') !== '1') {
            $this->info('Notificaciones de pagos desactivadas. Nada que hacer.');
            return self::SUCCESS;
        }

        $syActual = SchoolYear::actual();
        if (! $syActual) {
            $this->warn('Sin año escolar activo.');
            return self::SUCCESS;
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
            $this->info('Sin pagos vencidos. No se envían recordatorios.');
            return self::SUCCESS;
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

        $this->info("Recordatorios enviados: {$enviados}");
        return self::SUCCESS;
    }
}
