@extends('layouts.portal-estudiante')
@section('title', 'Recursos — ' . ($asignacion->asignatura?->nombre ?? ''))

@section('activeKey', 'recursos')

@push('styles')
<style>
.erec-card {
    border: 1px solid var(--prt-border);
    border-radius: 14px;
    padding: 1rem 1.1rem;
    transition: box-shadow .15s, border-color .15s, transform .15s;
    background: var(--prt-card);
    text-decoration: none;
    display: block; color: inherit;
}
.erec-card:hover { border-color: #c7d7ff; box-shadow: 0 4px 18px rgba(0,0,0,.08); transform: translateY(-2px); text-decoration: none; color: inherit; }
[data-theme="dark"] .erec-card { border-color: #334155; }
[data-theme="dark"] .erec-card:hover { border-color: #4f6a9a; }
.erec-type-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    font-size: .68rem; font-weight: 700; border-radius: 99px;
    padding: .18rem .55rem;
}
</style>
@endpush

@section('content')

{{-- Header --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.estudiante.dashboard') }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-folder-fill" style="color:#2563eb;"></i>
            Recursos — {{ $asignacion->asignatura?->nombre }}
        </h1>
        <div style="font-size:.75rem;color:#64748b;">
            {{ $asignacion->grupo?->nombre_completo ?? '—' }}
            @if($schoolYear) · {{ $schoolYear->nombre }} @endif
            · {{ $recursos->count() }} recurso(s)
        </div>
    </div>
    <div style="display:flex;gap:.5rem;">
        <a href="{{ route('portal.estudiante.recursos.pdf', $asignacion) }}" target="_blank"
           style="background:#991b1b;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;white-space:nowrap;">
            <i class="bi bi-file-earmark-pdf"></i>PDF
        </a>
        <a href="{{ route('portal.estudiante.recursos.excel', $asignacion) }}"
           style="background:#15803d;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;white-space:nowrap;">
            <i class="bi bi-file-earmark-excel"></i>Excel
        </a>
    </div>
</div>

{{-- Info banner --}}
<div style="background:#eff6ff;border-radius:10px;padding:.7rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#1d4ed8;display:flex;align-items:center;gap:.5rem;">
    <i class="bi bi-info-circle-fill"></i>
    Materiales compartidos por el docente de <strong>{{ $asignacion->asignatura?->nombre }}</strong>.
    Haz clic en cualquier recurso para abrirlo o descargarlo.
</div>

{{-- Grid de recursos --}}
@forelse($recursos as $rec)
@php $color = $rec->color; $icono = $rec->icono; @endphp

@if($rec->url)
<a href="{{ $rec->url }}" target="_blank" rel="noopener" class="erec-card" style="margin-bottom:.65rem;">
@elseif($rec->archivo_path)
<a href="{{ Storage::url($rec->archivo_path) }}" target="_blank" class="erec-card" style="margin-bottom:.65rem;">
@else
<div class="erec-card" style="margin-bottom:.65rem;">
@endif

    <div style="display:flex;align-items:flex-start;gap:.85rem;">
        {{-- Icono --}}
        <div style="width:46px;height:46px;border-radius:12px;background:{{ $color }}18;color:{{ $color }};display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0;">
            <i class="bi {{ $icono }}"></i>
        </div>
        {{-- Info --}}
        <div style="flex:1;min-width:0;">
            <div style="font-size:.9rem;font-weight:800;color:var(--prt-text);margin-bottom:.2rem;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                {{ $rec->titulo }}
                <span class="erec-type-badge" style="background:{{ $color }}18;color:{{ $color }};">
                    <i class="bi {{ $icono }}" style="font-size:.7rem;"></i>
                    {{ ucfirst($rec->tipo) }}
                </span>
            </div>
            @if($rec->descripcion)
            <div style="font-size:.78rem;color:var(--prt-muted);margin-bottom:.3rem;line-height:1.5;">{{ $rec->descripcion }}</div>
            @endif
            @if($rec->url)
            <div style="font-size:.7rem;color:#94a3b8;display:flex;align-items:center;gap:.3rem;">
                <i class="bi bi-link-45deg"></i>
                {{ Str::limit($rec->url, 55) }}
            </div>
            @elseif($rec->archivo_nombre)
            <div style="font-size:.7rem;color:#94a3b8;display:flex;align-items:center;gap:.3rem;">
                <i class="bi bi-paperclip"></i>
                {{ $rec->archivo_nombre }}
            </div>
            @endif
            <div style="font-size:.65rem;color:#94a3b8;margin-top:.3rem;">
                <i class="bi bi-clock me-1"></i>{{ $rec->created_at->diffForHumans() }}
            </div>
        </div>
        {{-- Flecha --}}
        <div style="color:{{ $color }};font-size:1.1rem;flex-shrink:0;align-self:center;">
            @if($rec->archivo_path)
            <i class="bi bi-download"></i>
            @else
            <i class="bi bi-box-arrow-up-right"></i>
            @endif
        </div>
    </div>

@if($rec->url)
</a>
@elseif($rec->archivo_path)
</a>
@else
</div>
@endif

@empty
<div style="text-align:center;padding:3rem 1rem;color:#9ca3af;">
    <i class="bi bi-folder-x" style="font-size:2.8rem;display:block;margin-bottom:.85rem;color:#cbd5e1;"></i>
    <div style="font-weight:700;font-size:.9rem;margin-bottom:.3rem;color:#64748b;">Sin recursos disponibles</div>
    <div style="font-size:.8rem;">El docente aún no ha subido materiales para esta materia.</div>
</div>
@endforelse

@endsection
