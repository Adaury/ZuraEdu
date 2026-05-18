@extends('layouts.portal')

@section('page-title', 'Situación Académica — ' . ($estudiante->nombre_completo ?? ''))
@section('portal-name', 'Portal del Representante')

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'riesgo', 'estudiante' => $estudiante])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.padre.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.padre.hijo', $estudiante) }}" class="prt-nav-item">
        <i class="bi bi-person-fill"></i>Resumen
    </a>
    <a href="{{ route('portal.padre.hijo.riesgo', $estudiante) }}" class="prt-nav-item active">
        <i class="bi bi-shield-fill-check"></i>Situación
    </a>
    <a href="{{ route('portal.padre.hijo.asistencia', $estudiante) }}" class="prt-nav-item">
        <i class="bi bi-calendar-check"></i>Asistencia
    </a>
    <a href="{{ route('portal.padre.tutor-ia') }}" class="prt-nav-item">
        <i class="bi bi-robot"></i>Tutor IA
    </a>
@endsection

@section('content')

@php
    $niveles = \App\Models\AcademicRiskScore::NIVELES;
    $cfg     = $score ? $score->nivel_config : $niveles['sin_riesgo'];
    $puntos  = $score ? $score->score : null;
    $nivel   = $score ? $score->nivel : 'sin_riesgo';

    $mensajes = [
        'sin_riesgo' => ['titulo' => 'Excelente desempeño',      'texto' => 'Su representado/a tiene un rendimiento sobresaliente. No hay señales de riesgo académico.'],
        'bajo'       => ['titulo' => 'Desempeño satisfactorio',  'texto' => 'El desempeño es bueno. Le recomendamos mantener el seguimiento habitual.'],
        'moderado'   => ['titulo' => 'Atención moderada',        'texto' => 'Existen algunas áreas que requieren atención. Le sugerimos conversar con el docente o usar el Tutor IA.'],
        'alto'       => ['titulo' => 'Requiere intervención',    'texto' => 'El rendimiento de su representado/a necesita atención pronta. Comuníquese con la coordinación.'],
        'critico'    => ['titulo' => 'Situación crítica',        'texto' => 'Es urgente que se comunique con el coordinador académico para definir un plan de apoyo.'],
    ];
    $msg = $mensajes[$nivel];
@endphp

{{-- ── Encabezado del estudiante ── --}}
<div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
    <div style="width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,#8b5cf6,#6366f1);display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:900;color:#fff;flex-shrink:0;">
        {{ strtoupper(substr($estudiante->nombres ?? '?', 0, 1)) }}
    </div>
    <div>
        <div class="fw-800" style="font-size:1rem;color:#1e293b;">{{ $estudiante->nombre_completo }}</div>
        <div class="text-muted small">{{ $matricula?->grupo?->nombre_completo ?? 'Sin grupo asignado' }} · {{ $schoolYear?->nombre ?? '' }}</div>
    </div>
    <a href="{{ route('portal.padre.hijo', $estudiante) }}" class="btn btn-sm btn-outline-secondary ms-auto">
        <i class="bi bi-arrow-left"></i> Volver al resumen
    </a>
</div>

{{-- ── Hero score ── --}}
<div style="background:linear-gradient(135deg,{{ $cfg['color'] }}22,{{ $cfg['color'] }}08);border-radius:16px;padding:1.5rem;border:2px solid {{ $cfg['color'] }}33;margin-bottom:1.25rem;">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div style="text-align:center;min-width:110px;">
            @if($puntos !== null)
            <div style="font-size:3.5rem;font-weight:900;line-height:1;color:{{ $cfg['color'] }};">{{ $puntos }}</div>
            <div style="font-size:.78rem;font-weight:700;color:{{ $cfg['color'] }};margin-top:2px;">{{ $cfg['label'] }}</div>
            @else
            <div style="font-size:2rem;font-weight:900;line-height:1;color:#9ca3af;">—</div>
            <div style="font-size:.78rem;color:#9ca3af;margin-top:2px;">Sin calcular</div>
            @endif
        </div>

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

        @if($score)
        <div style="text-align:right;min-width:100px;">
            <div class="text-muted" style="font-size:.7rem;">Última actualización</div>
            <div style="font-size:.8rem;font-weight:700;color:#334155;">
                {{ \Carbon\Carbon::parse($score->calculado_en)->format('d/m/Y') }}
            </div>
            <div class="text-muted" style="font-size:.68rem;">
                {{ \Carbon\Carbon::parse($score->calculado_en)->diffForHumans() }}
            </div>
        </div>
        @endif
    </div>
</div>

@if(! $score)
<div class="card border-0 shadow-sm text-center py-5" style="border-radius:14px;">
    <i class="bi bi-hourglass-split" style="font-size:2.5rem;color:#9ca3af;"></i>
    <div class="fw-700 mt-3" style="color:#64748b;">La evaluación de riesgo aún no ha sido calculada</div>
    <div class="text-muted small mt-1">El equipo académico realizará este análisis próximamente.</div>
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
            ['Materias con dificultad', "{$score->materias_en_riesgo} de {$score->total_materias}"],
            ['Promedio general', $score->promedio_general !== null ? number_format($score->promedio_general,1) : '—'],
        ],
        'tip' => 'Basado en las notas acumuladas por materia.',
    ],
    [
        'label' => 'Asistencia',
        'icon'  => 'bi-calendar-check-fill',
        'peso'  => '30%',
        'value' => $score->dim_asistencia,
        'detalle' => [
            ['% de asistencia', $score->pct_asistencia !== null ? number_format($score->pct_asistencia,1).'%' : '—'],
        ],
        'tip' => 'Porcentaje de días asistidos respecto al total.',
    ],
    [
        'label' => 'Conducta',
        'icon'  => 'bi-shield-fill-check',
        'peso'  => '20%',
        'value' => $score->dim_disciplina,
        'detalle' => [
            ['Tardanzas',            $score->tardanzas],
            ['Observaciones leves',  $score->faltas_leves],
            ['Observaciones graves', $score->faltas_graves],
            ['Suspensiones',         $score->suspensiones],
        ],
        'tip' => 'Registro disciplinario acumulado en el año.',
    ],
    [
        'label' => 'Tendencia',
        'icon'  => 'bi-graph-up',
        'peso'  => '10%',
        'value' => $score->dim_tendencia,
        'detalle' => [
            ['Dirección', $score->dim_tendencia <= 10 ? 'Mejorando ↑' : ($score->dim_tendencia <= 30 ? 'Estable →' : ($score->dim_tendencia <= 60 ? 'Declive leve ↓' : 'Declive severo ↘'))],
        ],
        'tip' => 'Compara el rendimiento entre períodos anteriores.',
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

{{-- ── CTA según nivel ── --}}
@if($score->score >= 60)
<div class="card border-0 shadow-sm mb-3" style="border-radius:14px;background:linear-gradient(135deg,#f97316,#ef4444);color:#fff;">
    <div class="card-body p-3 d-flex align-items-center gap-3">
        <i class="bi bi-telephone-fill" style="font-size:1.8rem;opacity:.9;"></i>
        <div>
            <div class="fw-800" style="font-size:.9rem;">Recomendamos contactar a la institución</div>
            <div style="font-size:.8rem;opacity:.9;">El nivel de riesgo es alto. Comuníquese con el coordinador académico para coordinar un plan de apoyo.</div>
        </div>
    </div>
</div>
@elseif($score->score >= 40)
<div class="card border-0 shadow-sm mb-3" style="border-radius:14px;background:linear-gradient(135deg,#8b5cf6,#6366f1);color:#fff;">
    <div class="card-body p-3 d-flex align-items-center gap-3">
        <i class="bi bi-robot" style="font-size:1.8rem;opacity:.9;"></i>
        <div>
            <div class="fw-800" style="font-size:.9rem;">El Tutor IA puede ayudar</div>
            <div style="font-size:.8rem;opacity:.9;">Hay áreas de mejora. El Tutor IA puede orientar a su representado/a con las materias que necesita reforzar.</div>
        </div>
        <a href="{{ route('portal.padre.tutor-ia') }}" class="btn btn-sm ms-auto"
           style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.4);white-space:nowrap;border-radius:99px;font-weight:700;">
            Abrir Tutor IA
        </a>
    </div>
</div>
@endif

{{-- ── Accesos rápidos ── --}}
<div class="row g-2">
    <div class="col-6 col-md-3">
        <a href="{{ route('portal.padre.hijo.boletin', $estudiante) }}" class="card border-0 shadow-sm text-center p-3 text-decoration-none" style="border-radius:12px;">
            <i class="bi bi-file-earmark-text-fill" style="font-size:1.4rem;color:#6366f1;"></i>
            <div class="small fw-700 mt-1" style="color:#334155;">Ver Boletín</div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('portal.padre.hijo.asistencia', $estudiante) }}" class="card border-0 shadow-sm text-center p-3 text-decoration-none" style="border-radius:12px;">
            <i class="bi bi-calendar-check-fill" style="font-size:1.4rem;color:#0ea5e9;"></i>
            <div class="small fw-700 mt-1" style="color:#334155;">Asistencia</div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('portal.padre.hijo.observaciones', $estudiante) }}" class="card border-0 shadow-sm text-center p-3 text-decoration-none" style="border-radius:12px;">
            <i class="bi bi-chat-square-text-fill" style="font-size:1.4rem;color:#f59e0b;"></i>
            <div class="small fw-700 mt-1" style="color:#334155;">Observaciones</div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('portal.padre.hijo.conducta', $estudiante) }}" class="card border-0 shadow-sm text-center p-3 text-decoration-none" style="border-radius:12px;">
            <i class="bi bi-stars" style="font-size:1.4rem;color:#22c55e;"></i>
            <div class="small fw-700 mt-1" style="color:#334155;">Conducta</div>
        </a>
    </div>
</div>

@endif {{-- end if $score --}}

@endsection
