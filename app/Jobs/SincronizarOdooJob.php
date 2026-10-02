<?php

namespace App\Jobs;

use App\Models\OdooConexion;
use App\Services\Odoo\OdooSincronizador;

/** Envía a Odoo los datos de UN centro (el tenant lo restaura TenantJob). Una sola vez por intento: si falla, el motivo queda en la conexión. */
class SincronizarOdooJob extends TenantJob
{
    public int $tries = 1;
    public int $timeout = 600;

    public function handle(): void
    {
        $conexion = OdooConexion::first();   // scope de tenant: solo la de este centro

        if ($conexion && $conexion->activo) {
            (new OdooSincronizador($conexion))->ejecutar();
        }
    }
}
