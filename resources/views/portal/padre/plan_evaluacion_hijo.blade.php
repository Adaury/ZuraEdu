@extends('layouts.portal')
@section('page-title', 'Plan de Evaluación — ' . ($estudiante->nombre_completo ?? ''))
@section('portal-name', 'Portal de Representante')

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'plan-evaluacion', 'estudiante' => $estudiante])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.padre.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.padre.hijo.boletin', $estudiante) }}" class="prt-nav-item">
        <i class="bi bi-file-earmark-text-fill"></i>Boletín
    </a>
    <a href="{{ route('portal.padre.hijo.asistencia', $estudiante) }}" class="prt-nav-item">
        <i class="bi bi-clipboard-check"></i>Asistencia
    </a>
    <a href="{{ route('portal.padre.hijo.plan-evaluacion', $estudiante) }}" class="prt-nav-item active">
        <i class="bi bi-bar-chart-steps"></i>Plan Eval.
    </a>
@endsection

@push('styles')
<style>
.peval-materia {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    margin-bottom: 1.25rem;
    overflow: hidden;
}
.peval-materia-hd {
    background: linear-gradient(90deg, rgba(30,58,138,.07) 0%, transparent 100%);
    border-bottom: 1px solid #e2e8f0;
    padding: .75rem 1rem;
    display: flex; align-items: center; gap: .6rem;
}
.peval-cat-bar {
    display: flex; align-items: center; gap: .5rem; margin-bottom: .45rem;
    font-size: .78rem;
}
.peval-inst-item {
    padding: .55rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: .6rem;
    font-size: .8rem;
}
.peval-inst-item:last-child { border-bottom: none; }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.padre.hijo', $estudiante) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-bar-chart-steps" style="color:#1e3a8a;"></i>
            Plan de Evaluación
        </h1>
        <div style="font-size:.75rem;color:#64748b;">
            {{ $estudiante->nombre_completo }}
            · {{ $matricula?->grupo?->nombre_completo ?? '—' }}
            @if($schoolYear) · {{ $schoolYear->nombre }} @endif
        </div>
    </div>
</div>

@if($planesData->isEmpty())
<div class="prt-card" style="text-align:center;padding:2.5rem;">
    <i class="bi bi-bar-chart-steps" style="font-size:2rem;color:#94a3b8;display:block;margin-bottom:.75rem;"></i>
    <p style="color:#64748b;font-size:.88rem;margin:0;">
        Aún no hay planes de evaluación publicados para el grupo de
        <strong>{{ $estudiante->nombres }}</strong>.
    </p>
</div>
@else

<p style="font-size:.78rem;color:#64748b;margin-bottom:1rem;">
    <i class="bi bi-info-circle me-1"></i>
    Distribución de pesos por categoría que usa cada docente para calcular la nota en cada período.
</p>

@foreach($planesData as $row)
@php
    $asignacion = $row['asignacion'];
    $planesAsig = $row['planesAsig'];
    $instAsig   = $row['instAsig'];
@endphp
<div class="peval-materia">
    {{-- Cabecera de la materia --}}
    <div class="peval-materia-hd">
        <div style="width:32px;height:32px;background:linear-gradient(135deg,#1e3a8a,#2563eb);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-book-fill" style="color:#fff;font-size:.8rem;"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <div style="font-weight:800;font-size:.88rem;color:#1e293b;">
                {{ $asignacion->asignatura?->nombre ?? 'Materia' }}
            </div>
            <div style="font-size:.72rem;color:#64748b;">
                Docente: {{ $asignacion->docente?->nombre_completo ?? '—' }}
            </div>
        </div>
    </div>

    {{-- Períodos --}}
    @foreach($periodos as $periodo)
    @php $plan = $planesAsig[$periodo->id] ?? null; @endphp
    @if($plan)
    <div style="padding:.85rem 1rem;border-bottom:1px solid #f1f5f9;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.65rem;flex-wrap:wrap;gap:.4rem;">
            <span style="font-size:.78rem;font-weight:700;color:#1e3a8a;display:flex;align-items:center;gap:.35rem;">
                <i class="bi bi-calendar3"></i>{{ $periodo->nombre }}
            </span>
            @if($plan->observaciones)
            <span style="font-size:.7rem;color:#64748b;font-style:italic;max-width:60%;text-align:right;">
                {{ $plan->observaciones }}
            </span>
            @endif
        </div>

        {{-- Barras por categoría --}}
        @foreach($categorias as $campo => $cat)
        @php $pct = $plan->$campo ?? 0; @endphp
        @if($pct > 0)
        <div class="peval-cat-bar">
            <i class="bi {{ $cat['icon'] }}" style="color:{{ $cat['color'] }};font-size:.8rem;width:16px;text-align:center;flex-shrink:0;"></i>
            <span style="width:110px;color:#374151;flex-shrink:0;">{{ $cat['label'] }}</span>
            <div style="flex:1;background:#f1f5f9;border-radius:99px;height:8px;overflow:hidden;">
                <div style="width:{{ $pct }}%;background:{{ $cat['color'] }};height:100%;border-radius:99px;"></div>
            </div>
            <span style="font-weight:700;color:{{ $cat['color'] }};width:36px;text-align:right;flex-shrink:0;">{{ $pct }}%</span>
        </div>
        @endif
        @endforeach

        {{-- Instrumentos del período --}}
        @php $instPer = $instAsig[$periodo->id] ?? collect(); @endphp
        @if($instPer->isNotEmpty())
        <div style="margin-top:.6rem;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;overflow:hidden;">
            <div style="padding:.4rem .75rem;font-size:.7rem;font-weight:700;color:#475569;background:#f1f5f9;border-bottom:1px solid #e2e8f0;">
                <i class="bi bi-clipboard-check me-1"></i>Instrumentos ({{ $instPer->count() }})
            </div>
            @foreach($instPer as $inst)
            <div class="peval-inst-item">
                <i class="bi bi-check2-circle" style="color:#10b981;flex-shrink:0;"></i>
                <span style="flex:1;color:#1e293b;">{{ $inst->titulo }}</span>
                <span style="font-size:.68rem;color:#94a3b8;white-space:nowrap;">{{ $inst->tipo_label ?? $inst->tipo }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif
    @endforeach
</div>
@endforeach

@endif

@endsection
