<?php

namespace App\Jobs;

use App\Models\Grupo;
use App\Models\RendimientoCache;
use App\Models\SchoolYear;
use Illuminate\Support\Facades\Log;

class RecalcularRendimientoJob extends TenantJob
{
    public int $tries = 3;

    public function __construct(
        public readonly int  $schoolYearId,
        public readonly ?int $grupoId   = null,
        public readonly ?int $periodoId = null,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $schoolYear = SchoolYear::find($this->schoolYearId);
        if (!$schoolYear) {
            Log::warning("RecalcularRendimientoJob: SchoolYear {$this->schoolYearId} not found.");
            return;
        }

        if ($this->grupoId) {
            // Recalculate a single group
            RendimientoCache::recalcularParaGrupo($this->grupoId, $this->schoolYearId, $this->periodoId);
        } else {
            // Recalculate all active groups of the school year
            $grupos = Grupo::where('school_year_id', $this->schoolYearId)
                ->where('activo', true)
                ->pluck('id');

            foreach ($grupos as $gid) {
                RendimientoCache::recalcularParaGrupo($gid, $this->schoolYearId, $this->periodoId);
            }
        }

        Log::info("RecalcularRendimientoJob completed for schoolYear={$this->schoolYearId}, grupo={$this->grupoId}");
    }
}
