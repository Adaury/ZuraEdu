@extends('layouts.admin')
@section('page-title', 'Dashboard Ejecutivo')

@push('styles')
<style>
.period-pill { border:1.5px solid #e2e8f0; border-radius:99px; padding:.3rem .85rem; font-size:.75rem; font-weight:600; color:#374151; text-decoration:none; display:inline-block; transition:all .15s; }
.period-pill:hover { background:#eff6ff; border-color:#2563eb; color:#2563eb; }
.period-pill.active { background:#2563eb; border-color:#2563eb; color:#fff; }
[data-theme="dark"] .period-pill { color:#94a3b8; border-color:#334155; }
[data-theme="dark"] .period-pill:hover { background:#1e3a6e; border-color:#3b82f6; color:#93c5fd; }
[data-theme="dark"] .period-pill.active { background:#2563eb; color:#fff; }
</style>
@endpush

@section('content')

{{-- ── Header ──────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(135deg,#0f1f3d,#1e3a6e);">
    <div class="card-body py-3 px-4 text-white">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div style="width:48px;height:48px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-bar-chart-line-fill" style="font-size:1.4rem;"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0">Dashboard Ejecutivo</h5>
                    <p class="mb-0" style="font-size:.83rem;opacity:.85;">
                        Resumen institucional — {{ $schoolYear?->nombre ?? 'Año actual' }}
                    </p>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <span style="font-size:.72rem;opacity:.7;">Generado: {{ now()->format('d/m/Y H:i') }}</span>
                <a href="{{ route('admin.ejecutivo.excel', request()->query()) }}"
                   class="btn btn-sm" style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.25);font-size:.75rem;">
                    <i class="bi bi-file-earmark-excel me-1"></i>Excel
                </a>
                <a href="{{ route('admin.ejecutivo.pdf', request()->query()) }}" target="_blank"
                   class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);font-size:.75rem;">
                    <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ── Filtro por período ─────────────────────────────────────────── --}}
@if($periodos->isNotEmpty())
<div class="mb-3 d-flex gap-2 flex-wrap align-items-center">
    <span class="text-muted" style="font-size:.78rem;font-weight:600;">Período:</span>
    <a href="{{ request()->url() }}" class="period-pill {{ !$periodoId ? 'active' : '' }}">Anual</a>
    @foreach($periodos as $p)
    <a href="{{ request()->url() }}?periodo_id={{ $p->id }}"
       class="period-pill {{ $periodoId == $p->id ? 'active' : '' }}">
        {{ $p->nombre }}
    </a>
    @endforeach
</div>
@endif

{{-- ── Mount point React ──────────────────────────────────────────── --}}
<div id="ejecutivo-react">
    {{-- Skeleton mientras carga React --}}
    <div class="row g-3 mb-4">
        @for($i = 0; $i < 6; $i++)
        <div class="col-6 col-md-4 col-xl-2">
            <div style="height:100px;border-radius:16px;background:linear-gradient(90deg,#e2e8f0 25%,#f1f5f9 50%,#e2e8f0 75%);background-size:200% 100%;animation:shimmer 1.4s infinite;"></div>
        </div>
        @endfor
    </div>
    <div class="row g-3 mb-4">
        <div class="col-lg-8"><div style="height:320px;border-radius:16px;background:#f8fafc;animation:shimmer 1.4s infinite;background:linear-gradient(90deg,#e2e8f0 25%,#f1f5f9 50%,#e2e8f0 75%);background-size:200% 100%;"></div></div>
        <div class="col-lg-4"><div style="height:320px;border-radius:16px;background:linear-gradient(90deg,#e2e8f0 25%,#f1f5f9 50%,#e2e8f0 75%);background-size:200% 100%;animation:shimmer 1.4s infinite;"></div></div>
    </div>
</div>

<style>
@keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
</style>

@push('scripts')
<script>
window.__EJECUTIVO_DATA__ = @json([
    'totalEstudiantes'      => $totalEstudiantes,
    'totalDocentes'         => $totalDocentes,
    'promedioInstitucional' => $promedioInstitucional,
    'tasaAprobacion'        => $tasaAprobacion,
    'pctAsistencia'         => $pctAsistencia,
    'statsPagos'            => $statsPagos,
    'asistenciaMes'         => $asistenciaMes,
    'promediosPorGrado'     => $promediosPorGrado,
    'matriculasPorGrado'    => $matriculasPorGrado,
    'tendenciaAsistencia'   => $tendenciaAsistencia,
    'distribucionDesempeno' => $distribucionDesempeno,
    'topGrupos'             => $topGrupos->load('grupo.grado', 'grupo.seccion'),
    'bottomGrupos'          => $bottomGrupos->load('grupo.grado', 'grupo.seccion'),
    'promediosPorAsignatura'=> $promediosPorAsignatura,
    'riesgoData'            => [
        'totalEnRiesgo'  => $riesgoData['totalEnRiesgo'],
        'riesgoPorGrado' => $riesgoData['riesgoPorGrado'],
    ],
    'statsDocentes'    => $statsDocentes,
    'comparativa'      => $comparativa,
    'preMatriculaStats'=> $preMatriculaStats,
    'disciplinaPorTipo'=> $disciplinaPorTipo,
    'schoolYear'       => $schoolYear ? ['nombre' => $schoolYear->nombre] : null,
]);
</script>
@vite('resources/js/ejecutivo.jsx')
@endpush

@endsection
