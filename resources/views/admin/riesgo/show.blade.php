@extends('layouts.admin')
@section('title', 'Risk Score — ' . ($score->estudiante?->nombre_completo ?? ''))

@section('content')
@php
    $cfg    = $score->nivel_config;
    $est    = $score->estudiante;
    $nombre = $est?->nombre_completo ?? '—';
    $niveles= \App\Models\AcademicRiskScore::NIVELES;
@endphp

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('admin.riesgo.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h1 class="h5 fw-800 mb-0">Risk Score — {{ $nombre }}</h1>
</div>

<div class="row g-3">

    {{-- ── Panel principal del score ── --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:18px; overflow:hidden;">
            <div style="background:linear-gradient(135deg, {{ $cfg['color'] }}22, {{ $cfg['color'] }}08); padding: 32px 24px; text-align:center; border-bottom: 3px solid {{ $cfg['color'] }};">
                {{-- Score grande --}}
                <div style="font-size: 5rem; font-weight: 900; line-height:1; color: {{ $cfg['color'] }};">{{ $score->score }}</div>
                <div style="font-size: 1.1rem; font-weight: 800; color: {{ $cfg['color'] }}; margin-top: 6px;">{{ $cfg['label'] }}</div>
                <div class="text-muted small mt-1">Score de Riesgo Académico</div>

                {{-- Gauge visual --}}
                <div style="margin: 16px auto 0; max-width: 240px;">
                    <div style="height: 10px; border-radius: 99px; background: linear-gradient(to right, #22c55e 0%, #84cc16 20%, #f59e0b 40%, #f97316 60%, #ef4444 80%); position: relative;">
                        <div style="position:absolute; top:-4px; left: calc({{ $score->score }}% - 9px); width:18px; height:18px; border-radius:50%; background:#fff; border: 3px solid {{ $cfg['color'] }}; box-shadow: 0 2px 6px rgba(0,0,0,.2);"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-1" style="font-size:.68rem; color:#9ca3af;">
                        <span>Sin Riesgo</span><span>Crítico</span>
                    </div>
                </div>
            </div>
            <div class="card-body p-3">
                {{-- Info del estudiante --}}
                <table class="table table-sm small mb-0">
                    <tr><td class="text-muted fw-600">Matrícula</td><td class="fw-700">{{ $est?->matricula ?? '—' }}</td></tr>
                    <tr><td class="text-muted fw-600">Grupo</td><td class="fw-700">{{ $matricula?->grupo?->nombre_completo ?? '—' }}</td></tr>
                    <tr><td class="text-muted fw-600">Año Escolar</td><td class="fw-700">{{ $score->schoolYear?->nombre ?? '—' }}</td></tr>
                    <tr><td class="text-muted fw-600">Calculado</td><td class="fw-700">{{ \Carbon\Carbon::parse($score->calculado_en)->format('d/m/Y H:i') }}</td></tr>
                </table>
                <button class="btn btn-sm btn-outline-danger w-100 mt-2"
                    hx-post="{{ route('admin.riesgo.recalcular-uno', $est?->id) }}"
                    onclick="recalcularUno({{ $est?->id }})">
                    <i class="bi bi-arrow-clockwise"></i> Recalcular ahora
                </button>
            </div>
        </div>
    </div>

    {{-- ── Dimensiones --}}
    <div class="col-md-8">
        <div class="row g-3 h-100">

            @php
            $dims = [
                ['label' => 'Académico', 'key' => 'dim_academico',  'peso' => '40%', 'icon' => 'bi-book-fill',
                 'value' => $score->dim_academico,
                 'detail' => [
                     ['Materias en riesgo (< 70)', "{$score->materias_en_riesgo} de {$score->total_materias}"],
                     ['Promedio general',          $score->promedio_general !== null ? number_format($score->promedio_general,1) : '—'],
                 ]
                ],
                ['label' => 'Asistencia', 'key' => 'dim_asistencia', 'peso' => '30%', 'icon' => 'bi-calendar-check-fill',
                 'value' => $score->dim_asistencia,
                 'detail' => [
                     ['% Asistencia', $score->pct_asistencia !== null ? number_format($score->pct_asistencia,1).'%' : '—'],
                 ]
                ],
                ['label' => 'Disciplina', 'key' => 'dim_disciplina', 'peso' => '20%', 'icon' => 'bi-shield-fill-exclamation',
                 'value' => $score->dim_disciplina,
                 'detail' => [
                     ['Tardanzas',   $score->tardanzas],
                     ['Faltas leves',$score->faltas_leves],
                     ['Faltas graves',$score->faltas_graves],
                     ['Suspensiones',$score->suspensiones],
                 ]
                ],
                ['label' => 'Tendencia', 'key' => 'dim_tendencia',  'peso' => '10%', 'icon' => 'bi-graph-down',
                 'value' => $score->dim_tendencia,
                 'detail' => [
                     ['Dirección', $score->dim_tendencia <= 10 ? 'Mejorando ↑' : ($score->dim_tendencia <= 30 ? 'Estable →' : ($score->dim_tendencia <= 60 ? 'Declive leve ↓' : 'Declive severo ↘'))],
                 ]
                ],
            ];
            @endphp

            @foreach($dims as $dim)
            @php
                $v    = $dim['value'];
                $dcolor = $v > 60 ? '#ef4444' : ($v > 30 ? '#f59e0b' : '#22c55e');
                $dbg   = $v > 60 ? '#fef2f2' : ($v > 30 ? '#fffbeb' : '#f0fdf4');
            @endphp
            <div class="col-sm-6">
                <div class="card border-0 shadow-sm h-100" style="border-radius:14px; border-left: 4px solid {{ $dcolor }} !important;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-800" style="font-size:.85rem;">
                                    <i class="bi {{ $dim['icon'] }}" style="color:{{ $dcolor }}"></i>
                                    {{ $dim['label'] }}
                                </div>
                                <div class="text-muted" style="font-size:.72rem;">Peso: {{ $dim['peso'] }}</div>
                            </div>
                            <span style="font-size: 1.6rem; font-weight: 900; color: {{ $dcolor }}; line-height:1;">{{ round($v) }}</span>
                        </div>
                        {{-- Barra --}}
                        <div style="height:8px; border-radius:99px; background:#f1f5f9; overflow:hidden; margin-bottom:10px;">
                            <div style="width:{{ $v }}%; height:100%; border-radius:99px; background: {{ $dcolor }};"></div>
                        </div>
                        {{-- Detalles --}}
                        <table class="table table-sm mb-0" style="font-size:.76rem;">
                            @foreach($dim['detail'] as [$k, $va])
                            <tr>
                                <td class="text-muted ps-0 py-1">{{ $k }}</td>
                                <td class="fw-700 text-end pe-0 py-1">{{ $va }}</td>
                            </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
            @endforeach

        </div>
    </div>

    {{-- ── Historial de niveles (visual) --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-body p-3">
                <div class="fw-700 small mb-3">Escala de Riesgo</div>
                <div class="d-flex gap-2 flex-wrap">
                @foreach($niveles as $nkey => $ncfg)
                <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-pill {{ $score->nivel === $nkey ? 'fw-800' : 'opacity-50' }}"
                     style="background:{{ $ncfg['bg'] }}; border: 2px solid {{ $score->nivel === $nkey ? $ncfg['color'] : 'transparent' }};">
                    <span style="width:10px;height:10px;border-radius:50%;background:{{ $ncfg['color'] }};"></span>
                    <span style="color:{{ $ncfg['color'] }}; font-size:.82rem;">{{ $ncfg['label'] }}</span>
                    <span class="text-muted" style="font-size:.72rem;">{{ $ncfg['min'] }}–{{ $ncfg['max'] }}</span>
                </div>
                @endforeach
                </div>
            </div>
        </div>
    </div>

</div>

<script>
async function recalcularUno(id) {
    const btn = event.currentTarget;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Calculando…';
    try {
        const r = await fetch('{{ route('admin.riesgo.recalcular-uno', '__ID__') }}'.replace('__ID__', id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        });
        const d = await r.json();
        if (d.score !== undefined) {
            location.reload();
        }
    } catch(e) {
        alert('Error al recalcular');
    } finally {
        btn.disabled = false;
    }
}
</script>
@endsection
