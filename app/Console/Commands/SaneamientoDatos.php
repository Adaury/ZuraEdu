<?php

namespace App\Console\Commands;

use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Detecta (y opcionalmente corrige) inconsistencias de datos acumuladas por
 * uso operativo: matrículas huérfanas, períodos duplicados/mal creados y
 * pagos "fantasma" sin matrícula real asociada.
 *
 * Por defecto solo REPORTA — nada se modifica sin pasar --fix. Los pagos
 * huérfanos son el único caso que --fix elimina automáticamente (no tienen
 * ninguna matrícula/estudiante real detrás, así que no representan una deuda
 * cobrable); todo lo demás requiere revisión manual porque tocar calificaciones,
 * períodos o matrículas reales sin contexto humano es demasiado riesgoso.
 */
class SaneamientoDatos extends Command
{
    protected $signature   = 'sge:saneamiento {--tenant= : ID de un tenant específico (por defecto todos)} {--fix : Aplicar las correcciones seguras (elimina pagos huérfanos)}';
    protected $description = 'Reporta inconsistencias de datos (matrículas huérfanas, períodos duplicados, pagos fantasma) y opcionalmente corrige las seguras';

    public function handle(): int
    {
        $fix = $this->option('fix');
        $tenantId = $this->option('tenant');

        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No se encontró ningún tenant con ese criterio.');
            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            $this->line('');
            $this->info("=== Tenant [{$tenant->id}] {$tenant->nombre_institucion} ===");

            $this->revisarPeriodosDuplicados($tenant->id);
            $this->revisarPeriodosFechasInvertidas($tenant->id);
            $this->revisarMatriculasHuerfanas($tenant->id);
            $this->revisarPagosFantasma($tenant->id, $fix);
            $this->revisarGruposInflados($tenant->id);
        }

        $this->line('');
        if (! $fix) {
            $this->warn('Modo reporte — nada se modificó. Corre con --fix para eliminar los pagos huérfanos confirmados.');
        }

        return self::SUCCESS;
    }

    private function revisarPeriodosDuplicados(int $tenantId): void
    {
        $dups = Periodo::where('tenant_id', $tenantId)
            ->select('school_year_id', 'numero', DB::raw('COUNT(*) as total'))
            ->groupBy('school_year_id', 'numero')
            ->having('total', '>', 1)
            ->get();

        if ($dups->isEmpty()) return;

        $this->warn("  Períodos duplicados (mismo año escolar + número):");
        foreach ($dups as $d) {
            $this->line("    school_year_id={$d->school_year_id} numero={$d->numero} → {$d->total} registros — revisar manualmente cuál conservar (tienen calificaciones asociadas).");
        }
    }

    private function revisarPeriodosFechasInvertidas(int $tenantId): void
    {
        $invalidos = Periodo::where('tenant_id', $tenantId)
            ->whereNotNull('fecha_inicio')->whereNotNull('fecha_fin')
            ->whereColumn('fecha_fin', '<', 'fecha_inicio')
            ->get();

        if ($invalidos->isEmpty()) return;

        $this->warn("  Períodos con fecha_fin anterior a fecha_inicio:");
        foreach ($invalidos as $p) {
            $this->line("    [{$p->id}] {$p->nombre}: {$p->fecha_inicio} → {$p->fecha_fin} — corregir fechas manualmente.");
        }
    }

    private function revisarMatriculasHuerfanas(int $tenantId): void
    {
        $huerfanas = Matricula::where('tenant_id', $tenantId)
            ->whereNotIn('grupo_id', Grupo::where('tenant_id', $tenantId)->pluck('id'))
            ->get();

        if ($huerfanas->isEmpty()) return;

        $this->warn("  Matrículas apuntando a un grupo que ya no existe: " . $huerfanas->count());
        foreach ($huerfanas->take(10) as $m) {
            $this->line("    matricula_id={$m->id} estudiante_id={$m->estudiante_id} grupo_id={$m->grupo_id} (inexistente)");
        }
        if ($huerfanas->count() > 10) $this->line('    ...');
    }

    private function revisarPagosFantasma(int $tenantId, bool $fix): void
    {
        $matriculaIds = Matricula::where('tenant_id', $tenantId)->pluck('id');

        $fantasma = Pago::where('tenant_id', $tenantId)
            ->whereNotNull('matricula_id')
            ->whereNotIn('matricula_id', $matriculaIds)
            ->get();

        if ($fantasma->isEmpty()) return;

        $this->warn("  Pagos 'fantasma' (matricula_id no corresponde a ninguna matrícula real): " . $fantasma->count());
        foreach ($fantasma->take(10) as $p) {
            $this->line("    pago_id={$p->id} matricula_id={$p->matricula_id} monto={$p->monto} estado={$p->estado}");
        }
        if ($fantasma->count() > 10) $this->line('    ...');

        if ($fix) {
            $ids = $fantasma->pluck('id');
            Pago::whereIn('id', $ids)->delete();
            $this->info("    → eliminados " . $ids->count() . " pagos huérfanos.");
        }
    }

    private function revisarGruposInflados(int $tenantId): void
    {
        $grupos = Grupo::where('tenant_id', $tenantId)
            ->with(['grado', 'seccion'])
            ->withCount('matriculas')
            ->withCount(['matriculas as matriculas_activas_count' => fn ($q) => $q->where('estado', 'activa')])
            ->get()
            ->filter(fn ($g) => $g->matriculas_count !== $g->matriculas_activas_count);

        if ($grupos->isEmpty()) return;

        $this->warn("  Grupos con estudiantes retirados/transferidos que antes se contaban como matriculados (ya corregido en el código, dato informativo):");
        foreach ($grupos as $g) {
            $nombre = trim(($g->grado?->nombre ?? '?') . ' ' . ($g->seccion?->nombre ?? '?'));
            $this->line("    [{$g->id}] {$nombre}: {$g->matriculas_count} total vs {$g->matriculas_activas_count} activas");
        }
    }
}
