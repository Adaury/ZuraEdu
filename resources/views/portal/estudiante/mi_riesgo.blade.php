@extends('layouts.portal')

@section('page-title', 'Mi Situación Académica')
@section('portal-name', 'Portal del Estudiante')

@section('sidebar')
    @include('portal.estudiante._sidebar', ['activeKey' => 'mi-riesgo'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.estudiante.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.estudiante.boletin') }}" class="prt-nav-item">
        <i class="bi bi-file-earmark-text-fill"></i>Boletín
    </a>
    <a href="{{ route('portal.estudiante.mi-riesgo') }}" class="prt-nav-item active">
        <i class="bi bi-shield-fill-check"></i>Mi Estado
    </a>
    <a href="{{ route('portal.estudiante.asistencia') }}" class="prt-nav-item">
        <i class="bi bi-calendar-check"></i>Asistencia
    </a>
    <a href="{{ route('portal.estudiante.tutor-ia') }}" class="prt-nav-item">
        <i class="bi bi-robot"></i>Tutor IA
    </a>
@endsection

@section('content')

@php
    $niveles = \App\Models\AcademicRiskScore::NIVELES;
    $cfg     = $score ? $score->nivel_config : $niveles['sin_riesgo'];
    $puntos  = $score ? $score->score : null;

    $mensajes = [
        'sin_riesgo' => ['titulo' => '¡Excelente trabajo!',     'texto' => 'Tu desempeño es sobresaliente. Sigue así y alcanzarás todas tus metas académicas.'],
        'bajo'       => ['titulo' => '¡Vas muy bien!',          'texto' => 'Tu situación académica es buena. Mantén el ritmo y continúa esforzándote.'],
        'moderado'   => ['titulo' => 'Atención moderada',       'texto' => 'Hay algunas áreas donde puedes mejorar. Habla con tu docente o usa el Tutor IA para reforzar.'],
        'alto'       => ['titulo' => 'Requiere atención',       'texto' => 'Es importante que tomes acción ahora. El Tutor IA y tus docentes pueden ayudarte a mejorar.'],
        'critico'    => ['titulo' => 'Situación crítica',       'texto' => 'Necesitas apoyo inmediato. Por favor comunícate con tu coordinador o representante.'],
    ];
    $nivel   = $score ? $score->nivel : 'sin_riesgo';
    $msg     = $mensajes[$nivel];
@endphp

{{-- ── Hero ── --}}
<div style="background:linear-gradient(135deg,{{ $cfg['color'] }}22,{{ $cfg['color'] }}08);border-radius:16px;padding:1.5rem;border:2px solid {{ $cfg['color'] }}33;margin-bottom:1.25rem;">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        {{-- Gauge central --}}
        <div style="text-align:center;min-width:110px;">
            @if($puntos !== null)
            <div style="font-size:3.5rem;font-weight:900;line-height:1;color:{{ $cfg['color'] }};">{{ $puntos }}</div>
            <div style="font-size:.78rem;font-weight:700;color:{{ $cfg['color'] }};margin-top:2px;">{{ $cfg['label'] }}</div>
            @else
            <div style="font-size:2rem;font-weight:900;line-height:1;color:#9ca3af;">—</div>
            <div style="font-size:.78rem;color:#9ca3af;margin-top:2px;">Sin calcular</div>
            @endif
        </div>

        {{-- Barra gauge --}}
        <div style="flex:1;min-width:180px;">
            <div style="font-size:1rem;font-weight:800;color:#1e293b;margin-bottom:.25rem;">{{ $msg['titulo'] }}</div>
            <div style="font-size:.82rem;color:#64748b;margin-bottom:.75rem;">{{ $msg['texto'] }}</div>
            @if($puntos !== null)
            <div style="height:10px;border-radius:99px;background:linear-gradient(to right,#22c55e 0%,#84cc16 20%,#f59e0b 40%,#f97316 60%,#ef4444 80%);position:relative;">
                <div style="position:absolute;top:-4px;left:calc({{ min($puntos,99) }}% - 9px);width:18px;height:18px;border-radius:50%;background:#fff;border:3px solid {{ $cfg['color'] }};box-shadow:0 2px 6px rgba(0,0,0,.2);"></div>
            </div>
            <div class="d-flex justify-content-between mt-1" style="font-size:.65rem;color:#9ca3af;">
                <span>Sin Riesgo</span><span>Crítico</span>
            </div>
            @endif
        </div>

        {{-- Año escolar --}}
        <div style="text-align:right;min-width:100px;">
            <div class="text-muted" style="font-size:.7rem;">Año Escolar</div>
            <div style="font-size:.82rem;font-weight:700;color:#334155;">{{ $schoolYear?->nombre ?? '—' }}</div>
            @if($score)
            <div class="text-muted" style="font-size:.68rem;margin-top:4px;">
                Actualizado<br>{{ \Carbon\Carbon::parse($score->calculado_en)->diffForHumans() }}
            </div>
            @endif
        </div>
    </div>
</div>

@if(! $score)
{{-- Sin score calculado --}}
<div class="card border-0 shadow-sm text-center py-5" style="border-radius:14px;">
    <i class="bi bi-hourglass-split" style="font-size:2.5rem;color:#9ca3af;"></i>
    <div class="fw-700 mt-3" style="color:#64748b;">Tu evaluación de riesgo aún no ha sido calculada</div>
    <div class="text-muted small mt-1">El equipo docente realizará este análisis próximamente.</div>
</div>
@else

{{-- ── Dimensiones ── --}}
<div class="row g-3 mb-3">
@php
$dims = [
    [
        'label' => 'Desempeño Académico',
        'icon'  => 'bi-book-fill',
        'peso'  => '40%',
        'value' => $score->dim_academico,
        'detalle' => [
            ['Materias que necesito reforzar', "{$score->materias_en_riesgo} de {$score->total_materias}"],
            ['Promedio general', $score->promedio_general !== null ? number_format($score->promedio_general,1) : '—'],
        ],
        'tip' => 'Basado en tus notas actuales por materia.',
    ],
    [
        'label' => 'Asistencia',
        'icon'  => 'bi-calendar-check-fill',
        'peso'  => '30%',
        'value' => $score->dim_asistencia,
        'detalle' => [
            ['% de asistencia', $score->pct_asistencia !== null ? number_format($score->pct_asistencia,1).'%' : '—'],
        ],
        'tip' => 'Cuántos días has asistido respecto al total.',
    ],
    [
        'label' => 'Conducta',
        'icon'  => 'bi-shield-fill-check',
        'peso'  => '20%',
        'value' => $score->dim_disciplina,
        'detalle' => [
            ['Tardanzas',       $score->tardanzas],
            ['Observaciones leves', $score->faltas_leves],
            ['Observaciones graves', $score->faltas_graves],
            ['Suspensiones',    $score->suspensiones],
        ],
        'tip' => 'Registro disciplinario del año escolar.',
    ],
    [
        'label' => 'Tendencia',
        'icon'  => 'bi-graph-up',
        'peso'  => '10%',
        'value' => $score->dim_tendencia,
        'detalle' => [
            ['Dirección', $score->dim_tendencia <= 10 ? 'Mejorando ↑' : ($score->dim_tendencia <= 30 ? 'Estable →' : ($score->dim_tendencia <= 60 ? 'Declive leve ↓' : 'Declive severo ↘'))],
        ],
        'tip' => 'Compara tus notas de períodos anteriores.',
    ],
];
@endphp

@foreach($dims as $dim)
@php
    $v   = $dim['value'];
    $dc  = $v > 60 ? '#ef4444' : ($v > 30 ? '#f59e0b' : '#22c55e');
    $dbg = $v > 60 ? '#fef2f2' : ($v > 30 ? '#fffbeb' : '#f0fdf4');
@endphp
<div class="col-sm-6">
    <div class="card border-0 shadow-sm h-100" style="border-radius:14px;border-left:4px solid {{ $dc }} !important;background:{{ $dbg }};">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="fw-800" style="font-size:.85rem;color:#1e293b;">
                        <i class="bi {{ $dim['icon'] }}" style="color:{{ $dc }}"></i>
                        {{ $dim['label'] }}
                    </div>
                    <div class="text-muted" style="font-size:.7rem;">Peso: {{ $dim['peso'] }}</div>
                </div>
                <span style="font-size:1.5rem;font-weight:900;color:{{ $dc }};line-height:1;">{{ round($v) }}</span>
            </div>
            <div style="height:7px;border-radius:99px;background:#e2e8f0;overflow:hidden;margin-bottom:10px;">
                <div style="width:{{ $v }}%;height:100%;border-radius:99px;background:{{ $dc }};transition:width .8s;"></div>
            </div>
            <table class="table table-sm mb-1" style="font-size:.76rem;background:transparent;">
                @foreach($dim['detalle'] as [$k, $va])
                <tr>
                    <td class="text-muted ps-0 py-1" style="background:transparent;">{{ $k }}</td>
                    <td class="fw-700 text-end pe-0 py-1" style="background:transparent;">{{ $va }}</td>
                </tr>
                @endforeach
            </table>
            <div style="font-size:.68rem;color:#94a3b8;font-style:italic;">{{ $dim['tip'] }}</div>
        </div>
    </div>
</div>
@endforeach
</div>

{{-- ── Escala visual ── --}}
<div class="card border-0 shadow-sm mb-3" style="border-radius:14px;">
    <div class="card-body p-3">
        <div class="fw-700 small mb-2">Escala de riesgo</div>
        <div class="d-flex gap-2 flex-wrap">
            @foreach($niveles as $nk => $nc)
            <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-pill {{ $nivel === $nk ? 'fw-800' : 'opacity-50' }}"
                 style="background:{{ $nc['bg'] }};border:2px solid {{ $nivel === $nk ? $nc['color'] : 'transparent' }};">
                <span style="width:9px;height:9px;border-radius:50%;background:{{ $nc['color'] }};"></span>
                <span style="color:{{ $nc['color'] }};font-size:.8rem;">{{ $nc['label'] }}</span>
                <span class="text-muted" style="font-size:.68rem;">{{ $nc['min'] }}–{{ $nc['max'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ── Recomendación / CTA ── --}}
@if($score->score >= 40)
<div class="card border-0 shadow-sm" style="border-radius:14px;background:linear-gradient(135deg,#0ea5e9 0%,#6366f1 100%);color:#fff;">
    <div class="card-body p-3 d-flex align-items-center gap-3">
        <i class="bi bi-robot" style="font-size:2rem;opacity:.9;"></i>
        <div>
            <div class="fw-800" style="font-size:.9rem;">¿Necesitas apoyo?</div>
            <div style="font-size:.8rem;opacity:.9;">El Tutor IA puede ayudarte a reforzar las materias donde tienes dificultades.</div>
        </div>
        <a href="{{ route('portal.estudiante.tutor-ia') }}" class="btn btn-sm ms-auto"
           style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.4);white-space:nowrap;border-radius:99px;font-weight:700;">
            Abrir Tutor IA
        </a>
    </div>
</div>
@endif

@endif {{-- end if $score --}}

@endsection
