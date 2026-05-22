@extends('layouts.portal')

@section('title', 'Reconocimientos — ' . $estudiante->nombres)

@section('role', 'padre')

@section('activeKey', 'reconocimientos')

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'reconocimientos', 'estudiante' => $estudiante])
@endsection

@section('content')

{{-- Encabezado --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.padre.hijo', $estudiante) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">Reconocimientos</h1>
        <div style="font-size:.75rem;color:#64748b;">{{ $estudiante->nombre_completo }}</div>
    </div>
</div>

{{-- Banner --}}
<div style="background:linear-gradient(135deg,#78350f 0%,#d97706 100%);border-radius:14px;padding:1.2rem 1.5rem;color:#fff;margin-bottom:1.25rem;display:flex;align-items:center;gap:1rem;">
    <div style="width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">
        <i class="bi bi-trophy-fill"></i>
    </div>
    <div style="flex:1;">
        <div style="font-size:1rem;font-weight:800;">
            {{ $reconocimientos->count() }} reconocimiento{{ $reconocimientos->count() !== 1 ? 's' : '' }}
        </div>
        <div style="font-size:.75rem;color:rgba(255,255,255,.8);">
            @if($reconocimientos->isEmpty())
                Aún no hay reconocimientos registrados.
            @else
                Diplomas y premios obtenidos por {{ $estudiante->nombres }}.
            @endif
        </div>
    </div>
</div>

@if($reconocimientos->isEmpty())
<div style="text-align:center;padding:3rem 1rem;color:#94a3b8;">
    <i class="bi bi-trophy" style="font-size:2.5rem;margin-bottom:.75rem;display:block;"></i>
    <div style="font-weight:600;color:#64748b;margin-bottom:.35rem;">Sin reconocimientos</div>
    <div style="font-size:.82rem;">Los diplomas y premios de su representado aparecerán aquí.</div>
</div>
@else
<div style="display:flex;flex-direction:column;gap:.75rem;">
    @foreach($reconocimientos as $r)
    @php
        $colores = [
            'excelencia' => ['#fef3c7','#d97706','#92400e'],
            'deportivo'  => ['#dcfce7','#16a34a','#14532d'],
            'arte'       => ['#fce7f3','#db2777','#831843'],
            'liderazgo'  => ['#ede9fe','#7c3aed','#4c1d95'],
            'ciencias'   => ['#dbeafe','#2563eb','#1e3a8a'],
            'civico'     => ['#fff7ed','#ea580c','#7c2d12'],
        ];
        $tipo = strtolower($r->tipo?->nombre ?? '');
        $color = collect($colores)->first(fn($v, $k) => str_contains($tipo, $k)) ?? ['#f8fafc','#64748b','#1e293b'];
    @endphp
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;display:flex;box-shadow:0 1px 3px rgba(0,0,0,.05);">
        <div style="width:5px;background:{{ $color[1] }};flex-shrink:0;"></div>
        <div style="padding:1rem 1.25rem;flex:1;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;flex-wrap:wrap;">
                <div>
                    <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.25rem;">
                        @if($r->tipo)
                        <span style="background:{{ $color[0] }};color:{{ $color[2] }};border-radius:99px;padding:.15rem .6rem;font-size:.68rem;font-weight:700;">
                            {{ $r->tipo->icono ?? '🏆' }} {{ $r->tipo->nombre }}
                        </span>
                        @endif
                        @if($r->entregado)
                        <span style="background:#dcfce7;color:#166534;border-radius:99px;padding:.15rem .6rem;font-size:.68rem;font-weight:600;">
                            <i class="bi bi-check-circle-fill"></i> Entregado
                        </span>
                        @else
                        <span style="background:#fef9c3;color:#854d0e;border-radius:99px;padding:.15rem .6rem;font-size:.68rem;font-weight:600;">
                            <i class="bi bi-clock"></i> Pendiente
                        </span>
                        @endif
                    </div>
                    <div style="font-weight:700;color:#1e293b;font-size:.9rem;">{{ $r->titulo }}</div>
                    @if($r->descripcion)
                    <div style="font-size:.78rem;color:#64748b;margin-top:.2rem;">{{ $r->descripcion }}</div>
                    @endif
                    @if($r->emitidoPor)
                    <div style="font-size:.72rem;color:#94a3b8;margin-top:.25rem;">Emitido por: {{ $r->emitidoPor->name }}</div>
                    @endif
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <div style="font-size:.75rem;font-weight:600;color:#475569;">{{ $r->fecha->format('d/m/Y') }}</div>
                    @if($r->entregado && $r->fecha_entrega)
                    <div style="font-size:.7rem;color:#16a34a;">Entregado {{ $r->fecha_entrega->format('d/m/Y') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection
