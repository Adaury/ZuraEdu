<?php

namespace App\Jobs;

use App\Events\NotificationCreated;
use App\Models\Notificacion;
use App\Services\NotificacionPreferenciaService;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Cache;

class EnviarNotificacionJob extends TenantJob
{
    public int $tries   = 3;
    public int $backoff = 10;

    public function __construct(
        public readonly int    $userId,
        public readonly string $tipo,
        public readonly string $titulo,
        public readonly string $mensaje,
        public readonly array  $datos  = [],
        int $tenantId = 0,
    ) {
        parent::__construct();

        // Permite sobrescribir tenant_id cuando se despacha desde otro job
        if ($tenantId > 0) {
            $this->tenantId = $tenantId;
        }
    }

    public function handle(): void
    {
        // Gate 1 replicado (ver Notificacion::enviar()) -- NO es redundante:
        // NotificarPadreAccesoJob y EnviarMensajeCircularJob despachan este
        // job directo, sin pasar por Notificacion::enviar(); para ellos
        // este es el ÚNICO gate. El tenant ya está bindeado aquí por
        // ResolveTenantForJob (middleware de TenantJob), así que
        // Setting::get() dentro del servicio lee el tenant correcto.
        if (! NotificacionPreferenciaService::inAppActivo($this->tipo)) {
            return;
        }

        $notif = Notificacion::withoutTenant()->create([
            'tenant_id' => $this->tenantId,
            'user_id'   => $this->userId,
            'tipo'      => $this->tipo,
            'titulo'    => $this->titulo,
            'mensaje'   => $this->mensaje,
            'datos'     => $this->datos ?: null,
            'leida'     => false,
        ]);

        Cache::forget("user_{$this->userId}_notif_unread");

        // Gate 2 replicado.
        if (NotificacionPreferenciaService::pushActivo($this->userId, $this->tipo)) {
            try {
                PushNotificationService::sendToUser(
                    $this->userId,
                    $this->titulo,
                    $this->mensaje,
                    array_merge($this->datos, ['tipo' => $this->tipo]),
                );
            } catch (\Throwable) {}
        }

        try {
            NotificationCreated::dispatch(
                $notif->user_id,
                $notif->tipo,
                $notif->titulo,
                $notif->mensaje,
                $notif->datos['url'] ?? null,
                \App\Models\Notificacion::ICONOS[$notif->tipo] ?? 'bi-bell',
            );
        } catch (\Throwable) {}
    }
}
