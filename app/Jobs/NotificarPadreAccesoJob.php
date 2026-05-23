<?php

namespace App\Jobs;

use App\Models\CarnetAcceso;
use App\Models\CarnetIdentidad;
use App\Models\Matricula;
use App\Models\User;

class NotificarPadreAccesoJob extends TenantJob
{
    public int $tries   = 3;
    public int $backoff = 15;

    public function __construct(
        public readonly int    $carnetId,
        public readonly string $tipoEvento,
        public readonly string $estado,
        public readonly string $hora,
        int $tenantId = 0,
    ) {
        parent::__construct();
        if ($tenantId > 0) $this->tenantId = $tenantId;
    }

    public function handle(): void
    {
        $carnet = CarnetIdentidad::withoutTenant()
            ->with(['user', 'matricula.estudiante'])
            ->find($this->carnetId);

        if (! $carnet) return;

        $nombre = $carnet->user?->name ?? 'El estudiante';
        $tipo   = $this->tipoEvento === 'entrada' ? 'ingresó' : 'salió';

        $titulo  = "Carnet+ — {$tipo} a las {$this->hora}";
        $mensaje = "{$nombre} {$tipo} del centro a las {$this->hora}.";

        // Notificar al representante/padre via el sistema de notificaciones existente
        if ($carnet->matricula_id) {
            $matricula = Matricula::withoutTenant()
                ->with('estudiante')
                ->find($carnet->matricula_id);

            if ($matricula && $matricula->estudiante_id) {
                // Buscar representantes vinculados al estudiante
                $representantes = \App\Models\Representante::withoutTenant()
                    ->where('tenant_id', $this->tenantId)
                    ->whereHas('estudiantes', fn($q) => $q->where('estudiantes.id', $matricula->estudiante_id))
                    ->with('user')
                    ->get();

                foreach ($representantes as $rep) {
                    if (! $rep->user_id) continue;
                    dispatch(new EnviarNotificacionJob(
                        userId:   $rep->user_id,
                        tipo:     'carnet_acceso',
                        titulo:   $titulo,
                        mensaje:  $mensaje,
                        datos:    [
                            'tipo_evento' => $this->tipoEvento,
                            'estado'      => $this->estado,
                            'hora'        => $this->hora,
                        ],
                        tenantId: $this->tenantId,
                    ));
                }
            }
        }
    }
}
