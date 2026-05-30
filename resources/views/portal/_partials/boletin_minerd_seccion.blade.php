{{--
    Partial: sección MINERD en boletín portal (estudiante / padre)
    Variables requeridas:
      $minerdData  — array con ciclo, asignaciones, evalMap, tieneEvaluaciones
      $periodos    — Collection<Periodo>
--}}
@if(!empty($minerdData) && $minerdData['tieneEvaluaciones'])
@php
    $mCiclo    = $minerdData['ciclo'];
    $mAsigs    = $minerdData['asignaciones'];
    $mEvalMap  = $minerdData['evalMap'];
    $mEsPrimer = $mCiclo === 'primer_ciclo';
    $mUmbral   = $mEsPrimer ? 2.5 : 65;

    // Promedio de una asignación en un período
    $mPromPer = function(int $asigId, $ces, int $pId) use ($mEvalMap): ?float {
        $vals = [];
        foreach ($ces as $ce) {
            $ils = $ce->indicadoresActivos ?? collect();
            if ($ils->isNotEmpty()) {
                foreach ($ils as $il) {
                    $v = $mEvalMap[$asigId]["il_{$il->id}"][$pId] ?? null;
                    if ($v !== null) $vals[] = (float)$v;
                }
            } else {
                $v = $mEvalMap[$asigId]["ce_{$ce->id}"][$pId] ?? null;
                if ($v !== null) $vals[] = (float)$v;
            }
        }
        return count($vals) ? round(array_sum($vals)/count($vals), 2) : null;
    };

    // Promedio general de una asignación (todos los períodos)
    $mPromAsig = function(int $asigId, $ces) use ($mEvalMap): ?float {
        $vals = [];
        foreach ($ces as $ce) {
            $ils = $ce->indicadoresActivos ?? collect();
            if ($ils->isNotEmpty()) {
                foreach ($ils as $il) {
                    foreach ($mEvalMap[$asigId]["il_{$il->id}"] ?? [] as $v) {
                        if ($v !== null) $vals[] = (float)$v;
                    }
                }
            } else {
                foreach ($mEvalMap[$asigId]["ce_{$ce->id}"] ?? [] as $v) {
                    if ($v !== null) $vals[] = (float)$v;
                }
            }
        }
        return count($vals) ? round(array_sum($vals)/count($vals), 2) : null;
    };

    // Clase CSS del badge de período según valor y ciclo
    $mPbCls = function($v) use ($mEsPrimer, $mUmbral): string {
        if ($v === null) return 'pb-nd';
        return (float)$v >= $mUmbral ? 'pb-ok' : 'pb-mal';
    };

    // Clase CSS del badge final (final-num)
    $mFnCls = function($v) use ($mEsPrimer, $mUmbral): string {
        if ($v === null) return 'fn-nd';
        return (float)$v >= $mUmbral ? 'fn-ok' : 'fn-mal';
    };

    // Clase CSS del sit-pill
    $mSpCls = function($v) use ($mUmbral): string {
        if ($v === null) return '';
        return (float)$v >= $mUmbral ? 'sp-ok' : 'sp-mal';
    };

    // Formatear valor
    $mFmt = function($v) use ($mEsPrimer): string {
        if ($v === null) return '—';
        return $mEsPrimer ? number_format((float)$v, 1) : number_format((float)$v, 0);
    };

    // Etiqueta de escala
    $mEscala = function($v) use ($mEsPrimer, $mUmbral): string {
        if ($v === null) return '—';
        $f = (float)$v;
        if ($mEsPrimer) return match(true) {
            $f >= 3.5 => 'Avanzado',
            $f >= 2.5 => 'Logrado',
            $f >= 1.5 => 'En proceso',
            default   => 'Inicial',
        };
        return $f >= $mUmbral ? 'Aprobado' : 'No aprobado';
    };

    // Asignaturas que tienen CE configuradas
    $mAsigsCon = $mAsigs->filter(fn($a) => ($a->asignatura->competenciasActivas ?? collect())->isNotEmpty());
@endphp

<div class="bol-section">
    {{-- Encabezado --}}
    <div class="bol-section-hd" style="background:linear-gradient(90deg,rgba(109,40,217,.07) 0%,transparent 100%);">
        <i class="bi bi-table" style="color:#7c3aed;font-size:1rem;"></i>
        <span class="title">Registro MINERD — Competencias Específicas</span>
        <span style="font-size:.68rem;font-weight:700;background:#ede9fe;color:#5b21b6;
                     padding:.15rem .55rem;border-radius:20px;margin-left:.4rem;">
            {{ $mEsPrimer ? 'Primer Ciclo · Escala 1–4' : 'Segundo Ciclo · 0–100' }}
        </span>
        <span class="count">{{ $mAsigsCon->count() }} materia(s)</span>
    </div>

    {{-- Leyenda --}}
    <div style="padding:.45rem 1.1rem;font-size:.7rem;color:#6b7280;
                border-bottom:1px solid var(--prt-border);display:flex;flex-wrap:wrap;gap:.4rem;align-items:center;">
        <span style="font-weight:700;">Escala:</span>
        @if($mEsPrimer)
            <span style="background:#fee2e2;color:#991b1b;padding:.1rem .4rem;border-radius:4px;font-weight:700;">1 Inicial</span>
            <span style="background:#fef9c3;color:#92400e;padding:.1rem .4rem;border-radius:4px;font-weight:700;">2 En proceso</span>
            <span style="background:#dbeafe;color:#1e40af;padding:.1rem .4rem;border-radius:4px;font-weight:700;">3 Logrado</span>
            <span style="background:#dcfce7;color:#15803d;padding:.1rem .4rem;border-radius:4px;font-weight:700;">4 Avanzado</span>
            <span style="color:#94a3b8;">· Aprobatorio ≥ 2.5</span>
        @else
            <span class="pb-ok pb" style="display:inline-block;min-width:auto;padding:.1rem .45rem;">≥ {{ $mUmbral }}</span>
            <span style="font-size:.7rem;">Aprobado</span>
            <span class="pb-mal pb" style="display:inline-block;min-width:auto;padding:.1rem .45rem;">< {{ $mUmbral }}</span>
            <span style="font-size:.7rem;">No aprobado</span>
        @endif
    </div>

    {{-- Filas por asignatura --}}
    @forelse($mAsigsCon as $asig)
        @php
            $ces = $asig->asignatura->competenciasActivas ?? collect();
            $pm  = $mPromAsig($asig->id, $ces);
        @endphp
        <div class="mat-row">
            <div class="mat-name">
                {{ $asig->asignatura?->nombre }}
                @if($asig->docente)
                    <div class="mat-sub">Prof. {{ $asig->docente->apellidos }}</div>
                @endif
            </div>
            <div class="per-badges">
                @foreach($periodos as $p)
                    @php $vp = $mPromPer($asig->id, $ces, $p->id); @endphp
                    <div class="pb {{ $mPbCls($vp) }}">
                        <span class="pb-lbl">P{{ $p->numero }}</span>
                        {{ $mFmt($vp) }}
                    </div>
                @endforeach
            </div>
            <div class="final-wrap">
                <div class="final-num {{ $mFnCls($pm) }}">{{ $mFmt($pm) }}</div>
                <span class="sit-pill {{ $mSpCls($pm) }}">{{ $mEscala($pm) }}</span>
            </div>
        </div>
    @empty
        <div style="padding:1.5rem;text-align:center;color:#94a3b8;font-size:.83rem;">
            <i class="bi bi-info-circle me-1"></i>
            Las materias de este grupo no tienen Competencias Específicas configuradas.
        </div>
    @endforelse

    {{-- Pie: promedio general MINERD --}}
    @php
        $mPromsGral = $mAsigsCon->map(fn($a) => $mPromAsig($a->id, $a->asignatura->competenciasActivas ?? collect()))->filter();
        $mTotalGral = $mPromsGral->count() ? round($mPromsGral->avg(), 2) : null;
    @endphp
    @if($mTotalGral !== null)
    <div style="padding:.65rem 1.1rem;border-top:2px solid var(--prt-border);
                background:var(--prt-card);display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
        <span style="font-size:.78rem;font-weight:700;color:var(--prt-muted);">Promedio MINERD:</span>
        <span style="font-size:1.05rem;font-weight:900;
                     color:{{ $mTotalGral >= $mUmbral ? '#15803d' : '#dc2626' }};">
            {{ $mFmt($mTotalGral) }}
        </span>
        <span class="sit-pill {{ $mSpCls($mTotalGral) }}">{{ $mEscala($mTotalGral) }}</span>
        <span style="margin-left:auto;font-size:.7rem;color:#94a3b8;">
            {{ $mAsigsCon->count() }} materia(s) con evaluación
        </span>
    </div>
    @endif
</div>
@endif
