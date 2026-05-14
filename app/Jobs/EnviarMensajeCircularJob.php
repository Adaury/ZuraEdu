<?php

namespace App\Jobs;

use App\Models\Mensaje;
use App\Models\MensajeDestinatario;
use Illuminate\Support\Facades\Cache;

class EnviarMensajeCircularJob extends TenantJob
{
    public int $tries   = 3;
    public int $backoff = 15;
    public int $timeout = 120;

    public function __construct(
        public readonly int   $mensajeId,
        public readonly array $destinatarioIds,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $mensaje = Mensaje::withoutGlobalScopes()->find($this->mensajeId);
        if (! $mensaje) return;

        $tid = $mensaje->tenant_id ?? 0;

        foreach ($this->destinatarioIds as $destId) {
            MensajeDestinatario::firstOrCreate([
                'mensaje_id'      => $this->mensajeId,
                'destinatario_id' => $destId,
            ]);
            Cache::forget("t{$tid}_user_{$destId}_msg_unread");
        }

        // Disparar notificaciones a cada destinatario
        foreach ($this->destinatarioIds as $destId) {
            EnviarNotificacionJob::dispatch(
                userId:   $destId,
                tipo:     'general',
                titulo:   'Nuevo mensaje de ' . optional($mensaje->remitente)->name,
                mensaje:  \Illuminate\Support\Str::limit($mensaje->asunto, 80),
                datos:    ['mensaje_id' => $this->mensajeId],
                tenantId: $mensaje->tenant_id,
            )->onQueue('notifications');
        }
    }
}
