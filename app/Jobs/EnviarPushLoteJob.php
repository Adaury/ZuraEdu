<?php

namespace App\Jobs;

use App\Services\NotificacionPreferenciaService;
use App\Services\PushNotificationService;

/**
 * Push masivo para Notificacion::enviarA(). Las filas in-app ya se
 * insertaron en el request HTTP que despachó este job (bulk insert
 * síncrono); este job SOLO manda el push, filtrando por la preferencia
 * individual de cada destinatario con una sola query para los N.
 *
 * Job separado de EnviarNotificacionJob a propósito (no una bandera de
 * modo en el mismo job): los contratos son opuestos -- uno crea la fila +
 * push + broadcast para UN usuario, este es push-only para N usuarios ya
 * insertados. Los reintentos también tienen semántica distinta: reintentar
 * un push duplicado es barato, reintentar una creación de fila no lo es.
 */
class EnviarPushLoteJob extends TenantJob
{
    public int $tries   = 2;
    public int $backoff = 15;

    /**
     * @param  array<int>  $userIds
     */
    public function __construct(
        public readonly array  $userIds,
        public readonly string $tipo,
        public readonly string $titulo,
        public readonly string $mensaje,
        public readonly array  $datos = [],
        int $tenantId = 0,
    ) {
        parent::__construct();

        if ($tenantId > 0) {
            $this->tenantId = $tenantId;
        }
    }

    public function handle(): void
    {
        // Re-chequeo: el admin pudo apagar la categoría entre el encolado
        // (dentro del request de enviarA()) y la ejecución de este job (la
        // cola 'notifications' puede tener retraso).
        if (! NotificacionPreferenciaService::pushInstitucionActivo($this->tipo)) {
            return;
        }

        $ids = NotificacionPreferenciaService::filtrarUsuariosConPush($this->userIds, $this->tipo);
        if (empty($ids)) {
            return;
        }

        try {
            PushNotificationService::sendToUsers(
                $ids,
                $this->titulo,
                $this->mensaje,
                array_merge($this->datos, ['tipo' => $this->tipo]),
            );
        } catch (\Throwable) {}
    }
}
