<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LimpiarDemosTemporales extends Command
{
    protected $signature   = 'demo:limpiar {--force : Eliminar sin confirmación}';
    protected $description = 'Elimina tenants demo temporales cuyo período de prueba ha vencido';

    public function handle(): int
    {
        $expired = Tenant::withTrashed()
            ->where('fecha_vencimiento', '<', now()->toDateString())
            ->whereNotNull('metadatos')
            ->get()
            ->filter(fn($t) => ($t->metadatos['is_demo_temporal'] ?? false) === true);

        if ($expired->isEmpty()) {
            $this->info('No hay demos temporales vencidas.');
            return 0;
        }

        $this->info("Demos vencidas encontradas: {$expired->count()}");

        foreach ($expired as $tenant) {
            $this->line("  → Eliminando tenant_id={$tenant->id}: {$tenant->nombre_institucion}");

            DB::transaction(function () use ($tenant) {
                $tid = $tenant->id;

                // Eliminar datos en orden de dependencia
                DB::table('calificaciones')->where('tenant_id', $tid)->delete();
                DB::table('calificaciones_academicas')->where('tenant_id', $tid)->delete();
                DB::table('matriculas')->where('tenant_id', $tid)->delete();
                DB::table('asignaciones')->where('tenant_id', $tid)->delete();
                DB::table('grupos')->where('tenant_id', $tid)->delete();
                DB::table('periodos')->where('tenant_id', $tid)->delete();
                DB::table('comunicados')->where('tenant_id', $tid)->delete();
                DB::table('asistencias')->where('tenant_id', $tid)->delete();
                DB::table('alertas_sistema')->where('tenant_id', $tid)->delete();
                DB::table('notificaciones')->where('tenant_id', $tid)->delete();

                // Estudiantes y docentes
                DB::table('docentes')->where('tenant_id', $tid)->delete();
                DB::table('estudiantes')->where('tenant_id', $tid)->delete();

                // Estructura
                DB::table('asignaturas')->where('tenant_id', $tid)->delete();
                DB::table('secciones')->where('tenant_id', $tid)->delete();
                DB::table('grados')->where('tenant_id', $tid)->delete();
                DB::table('school_years')->where('tenant_id', $tid)->delete();

                // Roles de usuarios
                $userIds = DB::table('users')->where('tenant_id', $tid)->pluck('id');
                DB::table('model_has_roles')->whereIn('model_id', $userIds)->delete();
                DB::table('users')->where('tenant_id', $tid)->delete();

                // Features y tenant
                DB::table('tenant_features')->where('tenant_id', $tid)->delete();
                Tenant::withTrashed()->where('id', $tid)->forceDelete();
            });
        }

        $this->info('✅ Limpieza completada.');
        return 0;
    }
}
