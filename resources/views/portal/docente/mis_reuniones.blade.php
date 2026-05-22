@extends('layouts.portal')

@section('title', 'Mis Reuniones')

@section('role', 'docente')

@section('activeKey', 'mis-reuniones')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'mis-reuniones'])
@endsection

@section('content')

{{-- Encabezado --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.dashboard') }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">Mis Reuniones</h1>
        <div style="font-size:.75rem;color:#64748b;">Actas y convocatorias donde participas</div>
    </div>
</div>

{{-- Contadores por estado --}}
@php
    $programadas = $reuniones->where('estado', 'programada')->count();
    $realizadas  = $reuniones->where('estado', 'realizada')->count();
    $canceladas  = $reuniones->where('estado', 'cancelada')->count();
@endphp
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.65rem;margin-bottom:1.25rem;">
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:.85rem 1rem;text-align:center;">
        <div style="font-size:1.4rem;font-weight:900;color:#2563eb;">{{ $programadas }}</div>
        <div style="font-size:.7rem;color:#64748b;font-weight:600;">Programadas</div>
    </div>
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:.85rem 1rem;text-align:center;">
        <div style="font-size:1.4rem;font-weight:900;color:#16a34a;">{{ $realizadas }}</div>
        <div style="font-size:.7rem;color:#64748b;font-weight:600;">Realizadas</div>
    </div>
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:.85rem 1rem;text-align:center;">
        <div style="font-size:1.4rem;font-weight:900;color:#dc2626;">{{ $canceladas }}</div>
        <div style="font-size:.7rem;color:#64748b;font-weight:600;">Canceladas</div>
    </div>
</div>

@if($reuniones->isEmpty())
<div style="background:#fff;border-radius:14px;border:1px dashed #cbd5e1;padding:3rem;text-align:center;color:#94a3b8;">
    <i class="bi bi-journal-text" style="font-size:2.5rem;margin-bottom:.75rem;display:block;"></i>
    <div style="font-weight:600;color:#64748b;margin-bottom:.35rem;">Sin reuniones registradas</div>
    <div style="font-size:.82rem;">Las reuniones en las que participes aparecerán aquí.</div>
</div>
@else
<div style="display:flex;flex-direction:column;gap:.75rem;">
    @foreach($reuniones as $r)
    @php
        $badgeColors = [
            'programada' => ['#dbeafe','#1e40af'],
            'realizada'  => ['#dcfce7','#166534'],
            'cancelada'  => ['#fee2e2','#991b1b'],
        ];
        $bc = $badgeColors[$r->estado] ?? ['#f1f5f9','#374151'];
        $esMiReunion = $r->convocante_id === auth()->id();
        $acuerdosCumplidos = $r->acuerdos->where('cumplido', true)->count();
        $acuerdosTotal     = $r->acuerdos->count();
    @endphp
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05);">
        {{-- Header --}}
        <div style="padding:.9rem 1.1rem;display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;flex-wrap:wrap;">
            <div style="flex:1;">
                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.3rem;">
                    <span style="background:{{ $bc[0] }};color:{{ $bc[1] }};border-radius:99px;padding:.15rem .65rem;font-size:.68rem;font-weight:700;">
                        {{ $r->estadoLabel() }}
                    </span>
                    <span style="background:#f1f5f9;color:#475569;border-radius:99px;padding:.15rem .65rem;font-size:.68rem;font-weight:600;">
                        {{ $r->tipoLabel() }}
                    </span>
                    @if($esMiReunion)
                    <span style="background:#ede9fe;color:#5b21b6;border-radius:99px;padding:.15rem .65rem;font-size:.65rem;font-weight:600;">
                        <i class="bi bi-person-fill"></i> Convocante
                    </span>
                    @endif
                </div>
                <div style="font-weight:700;color:#1e293b;font-size:.9rem;">{{ $r->titulo }}</div>
                <div style="display:flex;align-items:center;gap:1rem;margin-top:.35rem;flex-wrap:wrap;">
                    <div style="font-size:.76rem;color:#64748b;display:flex;align-items:center;gap:.3rem;">
                        <i class="bi bi-calendar3"></i>
                        {{ $r->fecha->format('d/m/Y \a \l\a\s H:i') }}
                    </div>
                    @if($r->lugar)
                    <div style="font-size:.76rem;color:#64748b;display:flex;align-items:center;gap:.3rem;">
                        <i class="bi bi-geo-alt"></i> {{ $r->lugar }}
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Agenda + Acuerdos --}}
        @if($r->agenda || $acuerdosTotal > 0)
        <div style="padding:.75rem 1.1rem;border-top:1px solid #f1f5f9;display:flex;gap:1rem;flex-wrap:wrap;">
            @if($r->agenda)
            <div style="flex:1;min-width:180px;">
                <div style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.25rem;">Agenda</div>
                <div style="font-size:.78rem;color:#374151;">{{ Str::limit($r->agenda, 120) }}</div>
            </div>
            @endif
            @if($acuerdosTotal > 0)
            <div style="flex-shrink:0;text-align:center;">
                <div style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.25rem;">Acuerdos</div>
                <div style="font-size:1.1rem;font-weight:800;color:#1e293b;">{{ $acuerdosCumplidos }}/{{ $acuerdosTotal }}</div>
                <div style="font-size:.65rem;color:#16a34a;">cumplidos</div>
            </div>
            @endif
        </div>
        @endif
    </div>
    @endforeach
</div>
@endif

@endsection
