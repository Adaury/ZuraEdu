@extends('layouts.portal-estudiante')
@section('title', 'Historial Académico')
@section('activeKey', 'historial')

@section('content')
<div class="prt-page-header">
    <div>
        <h1 class="prt-page-title"><i class="bi bi-clock-history me-2"></i>Historial Académico</h1>
        <p class="prt-page-sub">Registro de todos tus años escolares</p>
    </div>
    <a href="{{ route('portal.estudiante.constancia') }}"
       style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1.1rem;border-radius:10px;background:linear-gradient(135deg,#1e3a8a,#2563eb);color:#fff;font-size:.85rem;font-weight:600;text-decoration:none;">
        <i class="bi bi-file-earmark-arrow-down"></i> Descargar Constancia
    </a>
</div>

@if($matriculas->isEmpty())
<div style="background:#fff;border-radius:16px;padding:3rem;text-align:center;box-shadow:0 2px 12px rgba(15,23,42,.07);">
    <i class="bi bi-journal-x" style="font-size:2.5rem;color:#cbd5e1;display:block;margin-bottom:.75rem;"></i>
    <p style="color:#64748b;">No se encontraron registros académicos.</p>
</div>
@else

{{-- Resumen general --}}
@php
$añosTotal  = $matriculas->count();
$notaGlobal = $matriculas->whereNotNull('nota_promedio')->avg('nota_promedio');
$activa     = $matriculas->first(fn($m) => $m['matricula']->estado === 'activa');
@endphp
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.75rem;">
    @foreach([
        ['Años cursados',   $añosTotal,                     '#6366f1','#eef2ff','bi-calendar-range-fill'],
        ['Promedio global', $notaGlobal ? number_format($notaGlobal,1).'%' : '—', '#0891b2','#ecfeff','bi-bar-chart-fill'],
        ['Año actual',      $activa ? $activa['matricula']->schoolYear?->nombre ?? '—' : 'Sin matrícula activa', '#16a34a','#f0fdf4','bi-check-circle-fill'],
    ] as [$lbl,$val,$col,$bg,$ico])
    <div style="background:#fff;border-radius:14px;padding:1.1rem 1.25rem;box-shadow:0 2px 12px rgba(15,23,42,.07);display:flex;align-items:center;gap:.9rem;">
        <div style="width:42px;height:42px;border-radius:11px;background:{{ $bg }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi {{ $ico }}" style="color:{{ $col }};font-size:1.1rem;"></i>
        </div>
        <div>
            <div style="font-size:1.25rem;font-weight:900;color:#0f172a;line-height:1.1;">{{ $val }}</div>
            <div style="font-size:.7rem;color:#64748b;font-weight:500;">{{ $lbl }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Timeline de años --}}
<div style="position:relative;">
    {{-- línea vertical --}}
    <div style="position:absolute;left:21px;top:0;bottom:0;width:2px;background:linear-gradient(180deg,#3b82f6,#e2e8f0);border-radius:99px;"></div>

    @foreach($matriculas as $row)
    @php
    $m        = $row['matricula'];
    $nota     = $row['nota_promedio'];
    $asist    = $row['pct_asistencia'];
    $asigs    = $row['asignaturas'];
    $esActiva = $m->estado === 'activa';

    $notaColor = is_null($nota) ? '#94a3b8' : ($nota >= 70 ? '#16a34a' : ($nota >= 60 ? '#d97706' : '#dc2626'));
    $notaBg    = is_null($nota) ? '#f8fafc'  : ($nota >= 70 ? '#f0fdf4'  : ($nota >= 60 ? '#fffbeb'  : '#fef2f2'));
    @endphp
    <div style="display:flex;gap:1.25rem;margin-bottom:1.25rem;position:relative;">
        {{-- Círculo del timeline --}}
        <div style="flex-shrink:0;width:44px;height:44px;border-radius:50%;background:{{ $esActiva ? 'linear-gradient(135deg,#1e3a8a,#3b82f6)' : '#f1f5f9' }};border:3px solid {{ $esActiva ? '#2563eb' : '#e2e8f0' }};display:flex;align-items:center;justify-content:center;z-index:1;">
            <i class="bi {{ $esActiva ? 'bi-star-fill' : 'bi-check2' }}" style="color:{{ $esActiva ? '#fff' : '#94a3b8' }};font-size:.9rem;"></i>
        </div>

        {{-- Card --}}
        <div style="flex:1;background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(15,23,42,.07);overflow:hidden;{{ $esActiva ? 'border:2px solid #3b82f6;' : '' }}">
            <div style="padding:.9rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;">
                <div>
                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;">
                        {{ $m->schoolYear?->nombre ?? 'Año escolar' }}
                    </div>
                    <div style="font-size:1rem;font-weight:700;color:#0f172a;">
                        {{ $m->grupo?->grado?->nombre ?? '—' }}
                        @if($m->grupo?->seccion) · Sección {{ $m->grupo->seccion->nombre }} @endif
                    </div>
                </div>
                @if($esActiva)
                <span style="padding:.25rem .7rem;border-radius:99px;background:#dbeafe;color:#1e40af;font-size:.72rem;font-weight:700;">Año actual</span>
                @elseif($m->estado === 'completada' || $m->estado === 'graduado')
                <span style="padding:.25rem .7rem;border-radius:99px;background:#d1fae5;color:#065f46;font-size:.72rem;font-weight:700;">Completado</span>
                @else
                <span style="padding:.25rem .7rem;border-radius:99px;background:#f1f5f9;color:#64748b;font-size:.72rem;font-weight:700;">{{ ucfirst($m->estado) }}</span>
                @endif
            </div>
            <div style="padding:.9rem 1.25rem;display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;">
                {{-- Promedio --}}
                <div style="text-align:center;">
                    <div style="font-size:1.4rem;font-weight:900;color:{{ $notaColor }};line-height:1;">
                        {{ $nota ? number_format($nota,1) : '—' }}
                    </div>
                    <div style="font-size:.68rem;color:#94a3b8;font-weight:500;margin-top:.15rem;">Promedio</div>
                    @if($nota)
                    <div style="height:4px;border-radius:99px;background:#f1f5f9;margin-top:.4rem;overflow:hidden;">
                        <div style="height:4px;width:{{ min($nota,100) }}%;background:{{ $notaColor }};border-radius:99px;"></div>
                    </div>
                    @endif
                </div>
                {{-- Asistencia --}}
                <div style="text-align:center;">
                    <div style="font-size:1.4rem;font-weight:900;color:{{ $asist ? ($asist>=75?'#16a34a':'#d97706') : '#94a3b8' }};line-height:1;">
                        {{ $asist ? $asist.'%' : '—' }}
                    </div>
                    <div style="font-size:.68rem;color:#94a3b8;font-weight:500;margin-top:.15rem;">Asistencia</div>
                    @if($asist)
                    <div style="height:4px;border-radius:99px;background:#f1f5f9;margin-top:.4rem;overflow:hidden;">
                        <div style="height:4px;width:{{ min($asist,100) }}%;background:{{ $asist>=75?'#16a34a':'#d97706' }};border-radius:99px;"></div>
                    </div>
                    @endif
                </div>
                {{-- Materias --}}
                <div style="text-align:center;">
                    <div style="font-size:1.4rem;font-weight:900;color:#6366f1;line-height:1;">{{ $asigs ?: '—' }}</div>
                    <div style="font-size:.68rem;color:#94a3b8;font-weight:500;margin-top:.15rem;">Asignaturas</div>
                </div>
            </div>
            @if($esActiva)
            <div style="padding:.6rem 1.25rem;background:#eff6ff;border-top:1px solid #dbeafe;display:flex;gap:.5rem;flex-wrap:wrap;">
                <a href="{{ route('portal.estudiante.boletin') }}"
                   style="display:inline-flex;align-items:center;gap:.3rem;padding:.3rem .75rem;border-radius:8px;background:#fff;border:1.5px solid #93c5fd;color:#1e40af;font-size:.75rem;font-weight:600;text-decoration:none;">
                    <i class="bi bi-file-earmark-text-fill"></i> Ver boletín
                </a>
                <a href="{{ route('portal.estudiante.asistencia') }}"
                   style="display:inline-flex;align-items:center;gap:.3rem;padding:.3rem .75rem;border-radius:8px;background:#fff;border:1.5px solid #93c5fd;color:#1e40af;font-size:.75rem;font-weight:600;text-decoration:none;">
                    <i class="bi bi-clipboard-check"></i> Ver asistencia
                </a>
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
