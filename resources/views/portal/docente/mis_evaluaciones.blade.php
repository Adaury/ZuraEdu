@extends('layouts.portal')

@section('title', 'Mis Evaluaciones de Desempeño')

@section('role', 'docente')

@section('activeKey', 'mis-evaluaciones')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'mis-evaluaciones'])
@endsection

@section('content')

{{-- Encabezado --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.dashboard') }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">Mis Evaluaciones de Desempeño</h1>
        <div style="font-size:.75rem;color:#64748b;">{{ $docente->nombre_completo ?? auth()->user()->name }}</div>
    </div>
</div>

@if($evaluaciones->isEmpty())
<div style="background:#fff;border-radius:14px;border:1px dashed #cbd5e1;padding:3rem;text-align:center;color:#94a3b8;">
    <i class="bi bi-clipboard2-check" style="font-size:2.5rem;margin-bottom:.75rem;display:block;"></i>
    <div style="font-weight:600;color:#64748b;margin-bottom:.35rem;">Sin evaluaciones registradas</div>
    <div style="font-size:.82rem;">Las evaluaciones de desempeño que te realicen aparecerán aquí.</div>
</div>
@else

{{-- Resumen promedio general --}}
@php
    $promedioGeneral = round($evaluaciones->avg('promedio'), 2);
    $nivelGeneral = $evaluaciones->first()->nivelDesempeno();
    $criteriosLabels = $evaluaciones->first()->criterios();
@endphp
<div style="background:linear-gradient(135deg,#1e3a8a 0%,#4f46e5 100%);border-radius:14px;padding:1.2rem 1.5rem;color:#fff;margin-bottom:1.25rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
    <div style="width:56px;height:56px;border-radius:50%;background:rgba(255,255,255,.18);border:2px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">
        <i class="bi bi-clipboard2-check-fill"></i>
    </div>
    <div style="flex:1;">
        <div style="font-size:.75rem;color:rgba(255,255,255,.75);margin-bottom:.15rem;">Promedio General de Desempeño</div>
        <div style="font-size:1.75rem;font-weight:900;line-height:1;">{{ $promedioGeneral }}<span style="font-size:.9rem;font-weight:500;"> / 5</span></div>
        <div style="font-size:.78rem;color:rgba(255,255,255,.8);margin-top:.2rem;">{{ $evaluaciones->count() }} evaluación{{ $evaluaciones->count() !== 1 ? 'es' : '' }} registrada{{ $evaluaciones->count() !== 1 ? 's' : '' }}</div>
    </div>
    <div style="background:rgba(255,255,255,.15);border-radius:10px;padding:.55rem 1rem;text-align:center;flex-shrink:0;">
        <div style="font-size:.85rem;font-weight:800;">{{ $nivelGeneral['label'] }}</div>
        <div style="font-size:.65rem;color:rgba(255,255,255,.75);">Nivel de desempeño</div>
    </div>
</div>

{{-- Lista de evaluaciones --}}
<div style="display:flex;flex-direction:column;gap:.85rem;">
    @foreach($evaluaciones as $ev)
    @php $nivel = $ev->nivelDesempeno(); @endphp
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05);">
        {{-- Header --}}
        <div style="padding:.85rem 1.25rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;">
            <div>
                <div style="font-weight:700;color:#1e293b;font-size:.9rem;">
                    Período: {{ $ev->periodo_evaluado }}
                </div>
                <div style="font-size:.75rem;color:#64748b;margin-top:.1rem;">
                    Evaluado por: {{ $ev->evaluador?->name ?? '—' }}
                    · {{ $ev->created_at->format('d/m/Y') }}
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:.75rem;">
                <div style="text-align:center;">
                    <div style="font-size:1.4rem;font-weight:900;color:#1e293b;">{{ number_format($ev->promedio_calculado, 1) }}</div>
                    <div style="font-size:.65rem;color:#64748b;">/ 5.0</div>
                </div>
                <span style="background:{{ $nivel['color'] }};color:{{ $nivel['text'] }};border-radius:99px;padding:.2rem .75rem;font-size:.72rem;font-weight:700;">
                    {{ $nivel['label'] }}
                </span>
            </div>
        </div>

        {{-- Criterios --}}
        <div style="padding:1rem 1.25rem;">
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.6rem;">
                @foreach($ev->criterios() as $crit)
                @php
                    $val = $ev->{$crit['key']};
                    $pct = $val * 20;
                    $barColor = $val >= 4 ? '#22c55e' : ($val >= 3 ? '#3b82f6' : ($val >= 2 ? '#f59e0b' : '#ef4444'));
                @endphp
                <div style="background:#f8fafc;border-radius:8px;padding:.6rem .75rem;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.3rem;">
                        <div style="font-size:.72rem;color:#64748b;">{{ $crit['label'] }}</div>
                        <div style="font-size:.8rem;font-weight:700;color:#1e293b;">{{ $val }}/5</div>
                    </div>
                    <div style="background:#e2e8f0;border-radius:99px;height:5px;overflow:hidden;">
                        <div style="width:{{ $pct }}%;height:100%;background:{{ $barColor }};border-radius:99px;transition:width .4s;"></div>
                    </div>
                </div>
                @endforeach
            </div>

            @if($ev->observaciones)
            <div style="margin-top:.85rem;padding:.75rem 1rem;background:#eff6ff;border-radius:8px;border-left:3px solid #3b82f6;">
                <div style="font-size:.68rem;font-weight:700;color:#2563eb;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem;">Observaciones</div>
                <div style="font-size:.82rem;color:#374151;">{{ $ev->observaciones }}</div>
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection
