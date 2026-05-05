@extends('layouts.admin')
@section('page-title', 'Ranking Académico')

@push('styles')
<style>
    .podium-card {
        border-radius: 16px;
        text-align: center;
        padding: 1.5rem 1rem 1.25rem;
        box-shadow: 0 4px 20px rgba(0,0,0,.10);
        transition: transform .2s;
        position: relative;
    }
    .podium-card:hover { transform: translateY(-3px); }

    .podium-1 { background: linear-gradient(135deg,#fef3c7,#fbbf24); border: 2px solid #fbbf24; }
    .podium-2 { background: linear-gradient(135deg,#f1f5f9,#94a3b8); border: 2px solid #94a3b8; }
    .podium-3 { background: linear-gradient(135deg,#fef2e7,#cd7c2c); border: 2px solid #cd7c2c; }

    .podium-medal {
        font-size: 2.5rem;
        display: block;
        margin-bottom: .5rem;
        line-height: 1;
    }
    .podium-avatar {
        width: 72px; height: 72px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; font-weight: 900;
        margin: 0 auto .75rem;
        color: #fff;
        box-shadow: 0 4px 16px rgba(0,0,0,.18);
    }
    .podium-1 .podium-avatar { background: #d97706; }
    .podium-2 .podium-avatar { background: #64748b; }
    .podium-3 .podium-avatar { background: #92400e; }

    .podium-pos {
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-bottom: .3rem;
        opacity: .7;
    }
    .podium-name {
        font-weight: 800;
        font-size: .92rem;
        line-height: 1.3;
        color: #1e293b;
        margin-bottom: .4rem;
    }
    .podium-prom {
        font-size: 1.6rem;
        font-weight: 900;
        color: #1e293b;
    }
    .podium-sub { font-size: .72rem; color: #64748b; margin-top: .2rem; }

    /* Ranking table */
    .rank-table th {
        background: var(--primary);
        color: #fff;
        font-weight: 600;
        font-size: .8rem;
        padding: .65rem .75rem;
        white-space: nowrap;
    }
    .rank-table td {
        vertical-align: middle;
        padding: .6rem .75rem;
        font-size: .86rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .rank-table tbody tr:hover td { background: #f0f4ff; }

    .badge-nota {
        font-size: .82rem;
        font-weight: 800;
        padding: .35em .75em;
        border-radius: 20px;
    }
    .nota-ex  { background: #dcfce7; color: #15803d; }
    .nota-bu  { background: #dbeafe; color: #1d4ed8; }
    .nota-pr  { background: #fef3c7; color: #92400e; }
    .nota-in  { background: #fee2e2; color: #991b1b; }
    .nota-nu  { background: #f3f4f6; color: #6b7280; }

    .pos-badge {
        width: 36px; height: 36px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800;
        font-size: .85rem;
        margin: 0 auto;
    }
    .pos-gold   { background: #fbbf24; color: #78350f; }
    .pos-silver { background: #94a3b8; color: #1e293b; }
    .pos-bronze { background: #cd7c2c; color: #fff; }
    .pos-rest   { background: #e2e8f0; color: #475569; }

    /* Alert for at-risk students */
    .riesgo-alert {
        border-radius: 10px;
        border-left: 4px solid #ef4444;
        background: #fff5f5;
        padding: .85rem 1rem;
        margin-bottom: 1rem;
    }

    .filter-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,.06);
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
    }

    [data-theme="dark"] .podium-name { color: #e2e8f0; }
    [data-theme="dark"] .podium-prom { color: #e2e8f0; }
    [data-theme="dark"] .podium-sub { color: #94a3b8; }
    [data-theme="dark"] .rank-table td { border-bottom-color: #334155; }
    [data-theme="dark"] .rank-table tbody tr:hover td { background: #1a2640; }
    [data-theme="dark"] .riesgo-alert { background: #1c0000; border-left-color: #ef4444; }
    [data-theme="dark"] .filter-card { background: #1e293b; box-shadow: none; }
    [data-theme="dark"] .pos-rest { background: #334155; color: #94a3b8; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.82rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.calificaciones.index') }}" class="text-decoration-none">Calificaciones</a></li>
        <li class="breadcrumb-item active">Ranking</li>
    </ol>
</nav>

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-1" style="color:var(--primary);">
            <i class="bi bi-trophy me-2"></i>Ranking Académico
        </h4>
        <p class="text-muted mb-0" style="font-size:.85rem;">
            {{ $schoolYear->nombre }} — Clasificación por promedio de calificaciones
        </p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('admin.calificaciones.ranking') }}" id="filter-form">
    <div class="filter-card d-flex flex-wrap gap-3 align-items-end">
        <div>
            <label class="form-label fw-semibold mb-1" style="font-size:.8rem;">Grupo / Sección</label>
            <select name="grupo_id" class="form-select form-select-sm" style="min-width:200px;"
                    onchange="document.getElementById('filter-form').submit()">
                <option value="">— Seleccionar grupo —</option>
                @foreach($grupos as $g)
                <option value="{{ $g->id }}" {{ $g->id == $grupoId ? 'selected' : '' }}>
                    {{ $g->nombre_completo }}
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label fw-semibold mb-1" style="font-size:.8rem;">Período (opcional)</label>
            <select name="periodo_id" class="form-select form-select-sm" style="min-width:160px;"
                    onchange="document.getElementById('filter-form').submit()">
                <option value="">Todos los períodos</option>
                @foreach($periodos as $per)
                <option value="{{ $per->id }}" {{ $per->id == $periodoId ? 'selected' : '' }}>
                    {{ $per->nombre }}
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary btn-sm px-4">
                <i class="bi bi-funnel me-1"></i>Filtrar
            </button>
        </div>
        @if(request('grupo_id'))
        <div class="ms-auto">
            <a href="{{ route('admin.calificaciones.ranking.pdf', ['grupo_id' => request('grupo_id'), 'periodo_id' => request('periodo_id')]) }}"
               target="_blank" class="btn btn-danger btn-sm">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
            </a>
            <a href="{{ route('admin.calificaciones.ranking.excel', ['grupo_id' => request('grupo_id'), 'periodo_id' => request('periodo_id')]) }}"
               class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
            </a>
        </div>
        @endif
    </div>
</form>

@php
    // Normalize ranking to a plain array (controller may return Collection or array)
    $rankingArr = is_array($ranking) ? $ranking : $ranking->values()->toArray();
    // Add posicion if not already set
    foreach($rankingArr as $i => &$rr) {
        if (!isset($rr['posicion'])) $rr['posicion'] = $i + 1;
    }
    unset($rr);
@endphp

@if(empty($rankingArr))
<div class="alert alert-info rounded-3">
    <i class="bi bi-info-circle me-2"></i>Selecciona un grupo para ver el ranking académico.
</div>
@else

{{-- At-risk alert --}}
@php
    $enRiesgo = array_filter($rankingArr, function($r) { return $r['promedio'] !== null && $r['promedio'] < 70; });
@endphp
@if(count($enRiesgo) > 0)
<div class="riesgo-alert">
    <div class="fw-bold text-danger mb-1">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ count($enRiesgo) }} estudiante(s) con promedio inferior a 70
    </div>
    <div style="font-size:.83rem;color:#7f1d1d;">
        @foreach($enRiesgo as $r)
            <span class="badge me-1" style="background:#fee2e2;color:#991b1b;font-weight:600;">
                {{ $r['matricula']->estudiante->nombre_completo }}
                ({{ number_format($r['promedio'], 1) }})
            </span>
        @endforeach
    </div>
</div>
@endif

{{-- Podium for top 3 --}}
@if(count($rankingArr) >= 3)
<div class="row g-3 mb-4 justify-content-center">
    @php
        $top = array_slice($rankingArr, 0, 3);
        // Display order: 2nd, 1st, 3rd (visual podium layout)
        $podiumOrder = [1, 0, 2]; // indices in $top
        $podiumConfig = [
            ['label' => '1er Lugar', 'medal' => '🥇', 'class' => 'podium-1', 'col' => 'col-lg-3 col-md-4'],
            ['label' => '2do Lugar', 'medal' => '🥈', 'class' => 'podium-2', 'col' => 'col-lg-3 col-md-4'],
            ['label' => '3er Lugar', 'medal' => '🥉', 'class' => 'podium-3', 'col' => 'col-lg-3 col-md-4'],
        ];
    @endphp

    {{-- 2nd place left --}}
    @if(isset($top[1]))
    @php $r = $top[1]; $conf = $podiumConfig[1]; @endphp
    <div class="{{ $conf['col'] }}">
        <div class="podium-card {{ $conf['class'] }}">
            <span class="podium-medal">{{ $conf['medal'] }}</span>
            <div class="podium-avatar">
                {{ strtoupper(substr($r['matricula']->estudiante->nombre_completo ?? 'E', 0, 2)) }}
            </div>
            <div class="podium-pos">{{ $conf['label'] }}</div>
            <div class="podium-name">{{ $r['matricula']->estudiante->nombre_completo }}</div>
            <div class="podium-prom">{{ $r['promedio'] !== null ? number_format($r['promedio'], 1) : '—' }}</div>
            <div class="podium-sub">{{ $r['materias'] }} materia(s) evaluada(s)</div>
        </div>
    </div>
    @endif

    {{-- 1st place center --}}
    @if(isset($top[0]))
    @php $r = $top[0]; $conf = $podiumConfig[0]; @endphp
    <div class="{{ $conf['col'] }}">
        <div class="podium-card {{ $conf['class'] }}" style="transform:scale(1.05);margin-top:-8px;">
            <span class="podium-medal">{{ $conf['medal'] }}</span>
            <div class="podium-avatar" style="width:84px;height:84px;font-size:1.7rem;">
                {{ strtoupper(substr($r['matricula']->estudiante->nombre_completo ?? 'E', 0, 2)) }}
            </div>
            <div class="podium-pos">{{ $conf['label'] }}</div>
            <div class="podium-name" style="font-size:1rem;">{{ $r['matricula']->estudiante->nombre_completo }}</div>
            <div class="podium-prom" style="font-size:2rem;">{{ $r['promedio'] !== null ? number_format($r['promedio'], 1) : '—' }}</div>
            <div class="podium-sub">{{ $r['materias'] }} materia(s) evaluada(s)</div>
        </div>
    </div>
    @endif

    {{-- 3rd place right --}}
    @if(isset($top[2]))
    @php $r = $top[2]; $conf = $podiumConfig[2]; @endphp
    <div class="{{ $conf['col'] }}">
        <div class="podium-card {{ $conf['class'] }}">
            <span class="podium-medal">{{ $conf['medal'] }}</span>
            <div class="podium-avatar">
                {{ strtoupper(substr($r['matricula']->estudiante->nombre_completo ?? 'E', 0, 2)) }}
            </div>
            <div class="podium-pos">{{ $conf['label'] }}</div>
            <div class="podium-name">{{ $r['matricula']->estudiante->nombre_completo }}</div>
            <div class="podium-prom">{{ $r['promedio'] !== null ? number_format($r['promedio'], 1) : '—' }}</div>
            <div class="podium-sub">{{ $r['materias'] }} materia(s) evaluada(s)</div>
        </div>
    </div>
    @endif
</div>
@endif

{{-- Full ranking table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header border-0 pb-0 pt-3 px-4" style="background:transparent;">
        <h6 class="fw-bold mb-0" style="color:var(--primary);">
            <i class="bi bi-list-ol me-2"></i>Clasificación Completa
            @if($periodoId)
                — {{ $periodos->firstWhere('id', $periodoId)?->nombre }}
            @else
                — Todos los períodos
            @endif
        </h6>
    </div>
    <div class="card-body p-0 pt-2">
        <div class="table-responsive">
            <table class="table rank-table mb-0">
                <thead>
                    <tr>
                        <th style="width:60px;text-align:center;">Pos.</th>
                        <th>Estudiante</th>
                        <th style="width:120px;text-align:center;">Promedio</th>
                        <th style="width:100px;text-align:center;">Materias</th>
                        <th style="width:80px;text-align:center;">Indicador</th>
                        <th style="width:90px;text-align:center;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rankingArr as $r)
                    @php
                        $nota = $r['promedio'];
                        if ($nota === null)          { $notaClass = 'nota-nu'; $ind = '—'; }
                        elseif ($nota >= 90)         { $notaClass = 'nota-ex'; $ind = 'Excelente'; }
                        elseif ($nota >= 75)         { $notaClass = 'nota-bu'; $ind = 'Bueno'; }
                        elseif ($nota >= 70)         { $notaClass = 'nota-pr'; $ind = 'En proceso'; }
                        else                         { $notaClass = 'nota-in'; $ind = 'Insuficiente'; }

                        $posCls = match(true) {
                            $r['posicion'] == 1 => 'pos-gold',
                            $r['posicion'] == 2 => 'pos-silver',
                            $r['posicion'] == 3 => 'pos-bronze',
                            default             => 'pos-rest',
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="pos-badge {{ $posCls }}">{{ $r['posicion'] }}</div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:32px;height:32px;border-radius:50%;background:var(--primary);color:#fff;
                                            display:flex;align-items:center;justify-content:center;
                                            font-size:.75rem;font-weight:800;flex-shrink:0;">
                                    {{ strtoupper(substr($r['matricula']->estudiante->nombre_completo ?? 'E', 0, 2)) }}
                                </div>
                                <span class="fw-semibold">{{ $r['matricula']->estudiante->nombre_completo }}</span>
                            </div>
                        </td>
                        <td style="text-align:center;">
                            <span class="badge-nota {{ $notaClass }}">
                                {{ $nota !== null ? number_format($nota, 1) : '—' }}
                            </span>
                        </td>
                        <td style="text-align:center;color:#64748b;">
                            {{ $r['materias'] }}
                        </td>
                        <td style="text-align:center;">
                            <span class="badge {{ $notaClass }}" style="font-size:.72rem;border-radius:20px;">
                                {{ $ind }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            @if($periodos->isNotEmpty())
                            <a href="{{ route('admin.boletines.ver', [$r['matricula']->id, $periodos->last()->id]) }}"
                               class="btn btn-outline-primary btn-sm" style="font-size:.72rem;padding:.25rem .6rem;border-radius:20px;">
                                <i class="bi bi-file-earmark-text me-1"></i>Boletín
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endif

<div class="mt-3">
    <a href="{{ route('admin.calificaciones.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Volver
    </a>
</div>

@endsection
