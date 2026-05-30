{{--
    Partial: Registro MINERD (solo lectura)
    Variables requeridas:
      $matricula    — Matricula|null
      $ciclo        — 'primer_ciclo' | 'segundo_ciclo'
      $asignaciones — Collection<Asignacion> cargada con asignatura.competenciasActivas.indicadoresActivos, docente
      $periodos     — Collection<Periodo>
      $evalMap      — [$asignacionId][$refKey][$periodoId] = valor
      $schoolYear   — SchoolYear|null
      $nombreAlumno — string (para el encabezado)
--}}

@php
    use App\Services\RegistroAcademicoService;

    $umbral    = $ciclo === 'primer_ciclo' ? 2.5 : 65;
    $esPrimer  = $ciclo === 'primer_ciclo';

    // ── Helpers de color ─────────────────────────────────────────────────
    $colorBg = function($v) use ($esPrimer): string {
        if ($v === null) return '#f1f5f9';
        $f = (float)$v;
        if ($esPrimer) return match(true) { $f>=3.5=>'#dcfce7',$f>=2.5=>'#dbeafe',$f>=1.5=>'#fef9c3',default=>'#fee2e2' };
        return match(true) { $f>=90=>'#d1fae5',$f>=65=>'#dcfce7',$f>=50=>'#fef9c3',default=>'#fee2e2' };
    };
    $colorTx = function($v) use ($esPrimer): string {
        if ($v === null) return '#94a3b8';
        $f = (float)$v;
        if ($esPrimer) return match(true) { $f>=3.5=>'#15803d',$f>=2.5=>'#1e40af',$f>=1.5=>'#854d0e',default=>'#991b1b' };
        return match(true) { $f>=90=>'#065f46',$f>=65=>'#15803d',$f>=50=>'#854d0e',default=>'#991b1b' };
    };
    $fmtVal = function($v) use ($esPrimer): string {
        if ($v === null) return '—';
        return $esPrimer ? number_format((float)$v, 1) : number_format((float)$v, 0);
    };
    $escalaLabel = function($v) use ($esPrimer): string {
        if ($v === null) return '';
        if (!$esPrimer) return '';
        return match((int)round($v)) { 1=>'Inicial',2=>'En proceso',3=>'Logrado',4=>'Avanzado',default=>'' };
    };

    // ── Calcular promedio de una materia para este estudiante ─────────────
    $promMateria = function(int $asigId, $ces) use ($evalMap): ?float {
        $vals = [];
        foreach ($ces as $ce) {
            $ils = $ce->indicadoresActivos ?? collect();
            if ($ils->isNotEmpty()) {
                foreach ($ils as $il) {
                    foreach ($evalMap[$asigId]["il_{$il->id}"] ?? [] as $v) {
                        if ($v !== null) $vals[] = (float)$v;
                    }
                }
            } else {
                foreach ($evalMap[$asigId]["ce_{$ce->id}"] ?? [] as $v) {
                    if ($v !== null) $vals[] = (float)$v;
                }
            }
        }
        return count($vals) ? round(array_sum($vals)/count($vals), 2) : null;
    };
@endphp

{{-- ── Sin matrícula activa ──────────────────────────────────────────── --}}
@if(!$matricula)
<div class="text-center py-5" style="color:#94a3b8;">
    <i class="bi bi-exclamation-circle d-block mb-2" style="font-size:2.5rem;color:#f59e0b;"></i>
    <strong style="color:#374151;">No hay matrícula activa</strong>
    <p class="mt-1 mb-0" style="font-size:.84rem;">No se encontró una matrícula para el año escolar actual.</p>
</div>
@elseif($asignaciones->isEmpty())
<div class="text-center py-5" style="color:#94a3b8;">
    <i class="bi bi-journal-x d-block mb-2" style="font-size:2.5rem;"></i>
    <strong style="color:#374151;">Sin asignaciones configuradas</strong>
    <p class="mt-1 mb-0" style="font-size:.84rem;">No hay materias asignadas para el grupo este año.</p>
</div>
@else

{{-- ── Info encabezado ─────────────────────────────────────────────────── --}}
<div class="minerd-info-bar">
    <div class="mib-item">
        <span class="mib-label">Estudiante</span>
        <span class="mib-val">{{ $nombreAlumno }}</span>
    </div>
    <div class="mib-sep"></div>
    <div class="mib-item">
        <span class="mib-label">Grupo</span>
        <span class="mib-val">{{ $matricula->grupo?->grado?->nombre }} — Sección {{ $matricula->grupo?->seccion?->nombre }}</span>
    </div>
    <div class="mib-sep"></div>
    <div class="mib-item">
        <span class="mib-label">Ciclo</span>
        <span class="mib-val">{{ $esPrimer ? 'Primer Ciclo' : 'Segundo Ciclo' }}</span>
    </div>
    <div class="mib-sep"></div>
    <div class="mib-item">
        <span class="mib-label">Año escolar</span>
        <span class="mib-val">{{ $schoolYear?->nombre }}</span>
    </div>
</div>

{{-- ── Leyenda ─────────────────────────────────────────────────────────── --}}
@if($esPrimer)
<div class="leyenda-minerd">
    <span class="ley-tit">Escala MINERD:</span>
    <span class="ley-chip" style="background:#fee2e2;color:#991b1b;">1 — Inicial</span>
    <span class="ley-chip" style="background:#fef9c3;color:#854d0e;">2 — En proceso</span>
    <span class="ley-chip" style="background:#dbeafe;color:#1e40af;">3 — Logrado</span>
    <span class="ley-chip" style="background:#dcfce7;color:#15803d;">4 — Avanzado</span>
    <span class="ley-chip" style="background:#e0e7ff;color:#3730a3;">Aprobatorio ≥ 2.5</span>
</div>
@else
<div class="leyenda-minerd">
    <span class="ley-tit">Escala numérica:</span>
    <span class="ley-chip" style="background:#d1fae5;color:#065f46;">≥ 90 Excelente</span>
    <span class="ley-chip" style="background:#dcfce7;color:#15803d;">65–89 Bueno</span>
    <span class="ley-chip" style="background:#fef9c3;color:#854d0e;">50–64 Regular</span>
    <span class="ley-chip" style="background:#fee2e2;color:#991b1b;">&lt; 50 Insuficiente</span>
    <span class="ley-chip" style="background:#e0e7ff;color:#3730a3;">Aprobatorio ≥ 65</span>
</div>
@endif

{{-- ── Acordeón de materias ─────────────────────────────────────────────── --}}
<div class="minerd-accordion" id="mrdAccordion">

@foreach($asignaciones as $aIdx => $asig)
    @php
        $ces        = $asig->asignatura->competenciasActivas ?? collect();
        $asigEval   = $evalMap[$asig->id] ?? [];
        $pm         = $promMateria($asig->id, $ces);
        $aprobada   = $pm !== null ? $pm >= $umbral : null;
        $isFirst    = $aIdx === 0;
    @endphp

    <div class="mrd-card">
        {{-- Cabecera del acordeón --}}
        <button class="mrd-card-header {{ $isFirst ? 'open' : '' }}"
                onclick="toggleMrd(this)"
                aria-expanded="{{ $isFirst ? 'true' : 'false' }}">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <i class="bi bi-book-fill" style="color:#2d5aa0;font-size:.95rem;"></i>
                <span class="mrd-mat-nombre">{{ $asig->asignatura?->nombre }}</span>
                @if($asig->docente)
                <span class="mrd-docente">Prof. {{ $asig->docente->apellidos }}</span>
                @endif
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($pm !== null)
                    <span class="mrd-prom-chip"
                          style="background:{{ $colorBg($pm) }};color:{{ $colorTx($pm) }};">
                        {{ $fmtVal($pm) }}
                        @if($aprobada !== null)
                            <i class="bi bi-{{ $aprobada ? 'check-circle-fill' : 'x-circle-fill' }}" style="font-size:.7rem;"></i>
                        @endif
                    </span>
                @else
                    <span class="mrd-prom-chip" style="background:#f1f5f9;color:#94a3b8;">Sin notas</span>
                @endif
                @if($ces->isEmpty())
                    <span style="font-size:.68rem;color:#94a3b8;font-style:italic;">Sin CE</span>
                @endif
                <i class="bi bi-chevron-down mrd-chevron"></i>
            </div>
        </button>

        {{-- Cuerpo del acordeón --}}
        <div class="mrd-card-body {{ $isFirst ? '' : 'collapsed' }}">

            @if($ces->isEmpty())
            <div class="text-center py-3" style="color:#94a3b8;font-size:.83rem;">
                <i class="bi bi-info-circle me-1"></i>
                Esta materia no tiene Competencias Específicas configuradas.
            </div>
            @else

            {{-- Tabla CE / IL --}}
            <div class="mrd-tbl-wrap">
            <table class="mrd-tbl">
                <thead>
                    <tr>
                        <th class="mrd-th-ce" style="text-align:left;min-width:160px;">Competencia / Indicador</th>
                        @foreach($periodos as $p)
                            <th class="mrd-th-per">P{{ $p->numero }}</th>
                        @endforeach
                        <th class="mrd-th-prom">Prom</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($ces as $ce)
                    @php
                        $ils     = $ce->indicadoresActivos ?? collect();
                        $ceVals  = [];
                    @endphp

                    @if($ils->isNotEmpty())
                        {{-- Fila CE (cabecera del grupo) --}}
                        <tr class="mrd-tr-ce">
                            <td colspan="{{ $periodos->count() + 2 }}" class="mrd-td-ce-header">
                                <i class="bi bi-layers-fill me-1" style="font-size:.7rem;color:#2d5aa0;"></i>
                                {{ $ce->codigo ? $ce->codigo.': ' : '' }}{{ $ce->nombre }}
                            </td>
                        </tr>

                        {{-- Filas IL --}}
                        @foreach($ils as $ilIdx => $il)
                            @php
                                $ilKey  = "il_{$il->id}";
                                $ilVals = array_filter($asigEval[$ilKey] ?? [], fn($v) => $v !== null);
                                $promIl = count($ilVals) ? round(array_sum($ilVals)/count($ilVals), 2) : null;
                                if ($promIl !== null) $ceVals[] = $promIl;
                            @endphp
                            <tr class="mrd-tr-il {{ $ilIdx % 2 === 0 ? '' : 'mrd-tr-alt' }}">
                                <td class="mrd-td-il-name">
                                    <span class="mrd-il-badge">IL{{ $ilIdx + 1 }}</span>
                                    <span class="mrd-il-texto" title="{{ $il->descripcion }}">
                                        {{ $il->codigo ? $il->codigo.': ' : '' }}{{ \Illuminate\Support\Str::limit($il->descripcion ?? $il->nombre ?? '', 55) }}
                                    </span>
                                </td>
                                @foreach($periodos as $p)
                                    @php $val = $asigEval[$ilKey][$p->id] ?? null; @endphp
                                    <td class="mrd-td-val"
                                        style="background:{{ $colorBg($val) }};color:{{ $colorTx($val) }};">
                                        {{ $fmtVal($val) }}
                                        @if($esPrimer && $val !== null)
                                            <div style="font-size:.55rem;opacity:.75;line-height:1;margin-top:.1rem;">{{ $escalaLabel($val) }}</div>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="mrd-td-prom"
                                    style="background:{{ $colorBg($promIl) }};color:{{ $colorTx($promIl) }};">
                                    <strong>{{ $fmtVal($promIl) }}</strong>
                                </td>
                            </tr>
                        @endforeach

                        {{-- Fila prom CE --}}
                        @php $promCe = count($ceVals) ? round(array_sum($ceVals)/count($ceVals), 2) : null; @endphp
                        <tr class="mrd-tr-prom-ce">
                            <td class="mrd-td-prom-ce-label">Promedio CE</td>
                            <td colspan="{{ $periodos->count() }}" style="border:none;"></td>
                            <td class="mrd-td-prom"
                                style="background:{{ $colorBg($promCe) }};color:{{ $colorTx($promCe) }};font-weight:800;">
                                {{ $fmtVal($promCe) }}
                            </td>
                        </tr>

                    @else
                        {{-- CE directa (sin ILs) --}}
                        @php
                            $ceKey  = "ce_{$ce->id}";
                            $ceVals2 = array_filter($asigEval[$ceKey] ?? [], fn($v) => $v !== null);
                            $promCeD = count($ceVals2) ? round(array_sum($ceVals2)/count($ceVals2), 2) : null;
                        @endphp
                        <tr class="mrd-tr-il">
                            <td class="mrd-td-il-name">
                                <span class="mrd-il-badge" style="background:#e0e7ff;color:#3730a3;">CE</span>
                                {{ $ce->codigo ? $ce->codigo.': ' : '' }}{{ \Illuminate\Support\Str::limit($ce->nombre, 55) }}
                            </td>
                            @foreach($periodos as $p)
                                @php $val = $asigEval[$ceKey][$p->id] ?? null; @endphp
                                <td class="mrd-td-val"
                                    style="background:{{ $colorBg($val) }};color:{{ $colorTx($val) }};">
                                    {{ $fmtVal($val) }}
                                </td>
                            @endforeach
                            <td class="mrd-td-prom"
                                style="background:{{ $colorBg($promCeD) }};color:{{ $colorTx($promCeD) }};">
                                <strong>{{ $fmtVal($promCeD) }}</strong>
                            </td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
            </div>

            {{-- Pie: promedio de la materia --}}
            <div class="mrd-mat-footer">
                <span style="font-size:.78rem;color:#6b7280;font-weight:600;">Promedio de la materia:</span>
                @if($pm !== null)
                    <span class="mrd-prom-final"
                          style="background:{{ $colorBg($pm) }};color:{{ $colorTx($pm) }};">
                        {{ $fmtVal($pm) }}
                        {{ $esPrimer ? $escalaLabel($pm) : '' }}
                    </span>
                    <span class="mrd-sit-badge {{ $aprobada ? 'mrd-sit-aprobado' : 'mrd-sit-reprobado' }}">
                        <i class="bi bi-{{ $aprobada ? 'check-circle-fill' : 'x-circle-fill' }} me-1"></i>
                        {{ $aprobada ? 'Aprobado' : 'No aprobado' }}
                    </span>
                @else
                    <span style="color:#94a3b8;font-size:.78rem;">Sin notas registradas</span>
                @endif
            </div>

            @endif {{-- fin $ces->isEmpty() --}}
        </div>
    </div>
@endforeach

</div>

{{-- ── Resumen general ───────────────────────────────────────────────── --}}
@php
    $resumen = $asignaciones->map(function($asig) use ($evalMap, $promMateria) {
        $ces = $asig->asignatura->competenciasActivas ?? collect();
        return $promMateria($asig->id, $ces);
    })->filter();
    $promGeneral  = $resumen->count() ? round($resumen->avg(), 2) : null;
    $aprobGeneral = $promGeneral !== null ? $promGeneral >= $umbral : null;
@endphp

@if($promGeneral !== null)
<div class="mrd-resumen">
    <div class="mrd-res-item">
        <div class="mrd-res-label">Promedio general</div>
        <div class="mrd-res-val"
             style="background:{{ $colorBg($promGeneral) }};color:{{ $colorTx($promGeneral) }};">
            {{ $fmtVal($promGeneral) }}
        </div>
    </div>
    <div class="mrd-res-item">
        <div class="mrd-res-label">Materias con nota</div>
        <div class="mrd-res-val" style="background:#e0e7ff;color:#3730a3;">
            {{ $resumen->count() }} / {{ $asignaciones->count() }}
        </div>
    </div>
    @php
        $aprobadas  = $asignaciones->filter(fn($a) => ($promMateria($a->id, $a->asignatura->competenciasActivas??collect())) >= $umbral)->count();
        $reprobadas = $resumen->filter(fn($v) => $v < $umbral)->count();
    @endphp
    <div class="mrd-res-item">
        <div class="mrd-res-label">Aprobadas</div>
        <div class="mrd-res-val" style="background:#dcfce7;color:#15803d;">{{ $aprobadas }}</div>
    </div>
    @if($reprobadas > 0)
    <div class="mrd-res-item">
        <div class="mrd-res-label">Por mejorar</div>
        <div class="mrd-res-val" style="background:#fee2e2;color:#991b1b;">{{ $reprobadas }}</div>
    </div>
    @endif
</div>
@endif

@endif {{-- fin @if(!$matricula) --}}
