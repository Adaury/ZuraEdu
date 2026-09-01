<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\LoopsPerTenant;
use App\Models\ConfigInstitucional;
use App\Models\Notificacion;
use App\Models\Pago;
use App\Models\SchoolYear;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AlertasProximosPagos extends Command
{
    use LoopsPerTenant;

    protected $signature   = 'pagos:aviso-proximo {--dias=3 : Días de anticipación}';
    protected $description = 'Avisa a representantes y estudiantes de pagos que vencen en los próximos días';

    public function handle(): int
    {
        $totalPagos = $totalEnviados = 0;

        $this->forEachTenant(function ($tenant) use (&$totalPagos, &$totalEnviados) {
            [$p, $e] = $this->procesarTenant();
            $totalPagos    += $p;
            $totalEnviados += $e;
        });

        $this->info("Pagos próximos: {$totalPagos} | Notificaciones enviadas: {$totalEnviados}");
        return self::SUCCESS;
    }

    private function procesarTenant(): array
    {
        if (! ConfigInstitucional::moduloActivo('pagos')) {
            return [0, 0];
        }

        $sy = SchoolYear::actual();
        if (! $sy) {
            return [0, 0];
        }

        $dias        = (int) $this->option('dias');
        $fechaLimite = now()->addDays($dias)->toDateString();
        $hoy         = now()->toDateString();
        $inst        = ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pagos = Pago::with([
                'matricula.estudiante.representantes',
                'matricula.estudiante.user',
            ])
            ->where('estado', 'pendiente')
            ->whereDate('fecha_vencimiento', '>=', $hoy)
            ->whereDate('fecha_vencimiento', '<=', $fechaLimite)
            ->whereHas('matricula', fn($q) => $q->where('school_year_id', $sy->id))
            ->get();

        if ($pagos->isEmpty()) {
            return [0, 0];
        }

        $enviados = 0;

        foreach ($pagos as $pago) {
            $est         = $pago->matricula?->estudiante;
            if (! $est) continue;

            // abs(): Carbon 3 puede devolver diffIn*() con signo según dirección.
            $diasRestantes = (int) abs(Carbon::parse($pago->fecha_vencimiento)->diffInDays(now()));
            $cuando        = $diasRestantes === 0 ? 'hoy' : "en {$diasRestantes} día(s)";
            $monto         = 'RD$ ' . number_format($pago->monto, 2);
            $cacheKey      = "aviso_pago_{$pago->id}_" . now()->toDateString();

            if (cache()->has($cacheKey)) continue;
            cache()->put($cacheKey, true, now()->endOfDay());

            // Notificación interna al estudiante
            if ($est->user_id) {
                Notificacion::enviar(
                    $est->user_id,
                    'pago',
                    '⏰ Pago próximo a vencer',
                    "Tu pago de \"{$pago->concepto}\" ({$monto}) vence {$cuando}.",
                    ['url' => route('portal.estudiante.mis-pagos')]
                );
                $enviados++;
            }

            // Notificación y WhatsApp al representante
            $rep = $est->representantes->first();
            if ($rep) {
                if ($rep->user_id) {
                    Notificacion::enviar(
                        $rep->user_id,
                        'pago',
                        '⏰ Pago próximo a vencer',
                        "El pago de \"{$pago->concepto}\" de {$est->nombre_completo} ({$monto}) vence {$cuando}."
                    );
                    $enviados++;
                }

                if ($rep->telefono) {
                    WhatsAppService::send(
                        $rep->telefono,
                        "⏰ *{$inst}*\n\nEstimado representante, el pago de *{$est->nombre_completo}*:\n\n" .
                        "📋 *{$pago->concepto}*\n" .
                        "💰 Monto: *{$monto}*\n" .
                        "📅 Vence: *{$pago->fecha_vencimiento->format('d/m/Y')}* ({$cuando})\n\n" .
                        "Puede pagar desde el portal: " . config('app.url')
                    );
                }
            }
        }

        return [$pagos->count(), $enviados];
    }
}
