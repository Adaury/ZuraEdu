<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\LoopsPerTenant;
use App\Models\OdooConexion;
use App\Services\Odoo\OdooSincronizador;
use Illuminate\Console\Command;

class OdooSincronizar extends Command
{
    use LoopsPerTenant;

    protected $signature = 'odoo:sincronizar';
    protected $description = 'Envía a Odoo los datos de cada centro con la integración activa (contactos y facturas)';

    public function handle(): int
    {
        $this->forEachTenant(function ($tenant) {
            $conexion = OdooConexion::first();   // scope de tenant

            if (! $conexion || ! $conexion->activo) {
                return;
            }

            $r = (new OdooSincronizador($conexion))->ejecutar();
            $this->info("[{$tenant->nombre_institucion}] contactos: " . json_encode($r['contactos']) . ' · facturas: ' . json_encode($r['facturas']));
            foreach (array_slice($r['errores'], 0, 5) as $e) {
                $this->warn("    {$e}");
            }
        });

        return self::SUCCESS;
    }
}
