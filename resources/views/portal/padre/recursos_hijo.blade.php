@extends('layouts.portal')
@section('page-title', 'Recursos — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal del Representante')

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'recursos'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.padre.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.padre.hijo', $estudiante) }}" class="prt-nav-item">
        <i class="bi bi-person-fill"></i>Hijo
    </a>
    <a href="{{ route('portal.padre.hijo.recursos', [$estudiante, $asignacion]) }}" class="prt-nav-item active">
        <i class="bi bi-folder-fill"></i>Recursos
    </a>
@endsection

@push('styles')
<style>
.prec-card {
    border: 1px solid var(--prt-border);
    border-radius: 14px;
    padding: 1rem 1.1rem;
    transition: box-shadow .15s, border-color .15s, transform .15s;
    background: var(--prt-card);
    text-decoration: none;
    display: block; color: inherit;
}
.prec-card:hover { border-color: #c7d7ff; box-shadow: 0 4px 18px rgba(0,0,0,.08); transform: translateY(-2px); text-decoration: none; color: inherit; }
[data-theme="dark"] .prec-card { border-color: #334155; }
[data-theme="dark"] .prec-card:hover { border-color: #4f6a9a; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.padre.hijo', $estudiante) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-folder-fill" style="color:#2563eb;"></i>
            Recursos — {{ $asignacion->asignatura?->nombre }}
        </h1>
        <div style="font-size:.75rem;color:#64748b;">
            {{ $estudiante->nombre_completo }}
            · {{ $matricula->grupo?->nombre_completo ?? '—' }}
            @if($schoolYear) · {{ $schoolYear->nombre }} @endif
            · {{ $recursos->count() }} recurso(s)
        </div>
    </div>
    <div style="display:flex;gap:.5rem;flex-shrink:0;">
        <a href="{{ route('portal.padre.hijo.recursos.pdf', [$estudiante, $asignacion]) }}" target="_blank"
           style="background:#dc2626;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-file-earmark-pdf-fill"></i>PDF
        </a>
        <a href="{{ route('portal.padre.hijo.recursos.excel', [$estudiante, $asignacion]) }}"
           style="background:#15803d;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-file-earmark-excel-fill"></i>Excel
        </a>
    </div>
</div>

<div style="background:#eff6ff;border-radius:10px;padding:.7rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#1d4ed8;display:flex;align-items:center;gap:.5rem;">
    <i class="bi bi-info-circle-fill"></i>
    Materiales compartidos por el docente de <strong>{{ $asignacion->asignatura?->nombre }}</strong> para <strong>{{ $estudiante->nombre_completo }}</strong>.
</div>

@forelse($recursos as $rec)
@php $color = $rec->color; $icono = $rec->icono; @endphp

@if($rec->url)
<a href="{{ $rec->url }}" target="_blank" rel="noopener" class="prec-card" style="margin-bottom:.65rem;">
@elseif($rec->archivo_path)
<a href="{{ Storage::url($rec->archivo_path) }}" target="_blank" class="prec-card" style="margin-bottom:.65rem;">
@else
<div class="prec-card" style="margin-bottom:.65rem;">
@endif

    <div style="display:flex;align-items:flex-start;gap:.85rem;">
        <div style="width:46px;height:46px;border-radius:12px;background:{{ $color }}18;color:{{ $color }};display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0;">
            <i class="bi {{ $icono }}"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <div style="font-size:.9rem;font-weight:800;color:var(--prt-text);margin-bottom:.2rem;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                {{ $rec->titulo }}
                <span style="background:{{ $color }}18;color:{{ $color }};border-radius:99px;padding:.18rem .55rem;font-size:.68rem;font-weight:700;display:inline-flex;align-items:center;gap:.3rem;">
                    <i class="bi {{ $icono }}" style="font-size:.7rem;"></i>{{ ucfirst($rec->tipo) }}
                </span>
            </div>
            @if($rec->descripcion)
            <div style="font-size:.78rem;color:var(--prt-muted);margin-bottom:.3rem;line-height:1.5;">{{ $rec->descripcion }}</div>
            @endif
            @if($rec->url)
            <div style="font-size:.7rem;color:#94a3b8;display:flex;align-items:center;gap:.3rem;">
                <i class="bi bi-link-45deg"></i>{{ Str::limit($rec->url, 55) }}
            </div>
            @elseif($rec->archivo_nombre)
            <div style="font-size:.7rem;color:#94a3b8;display:flex;align-items:center;gap:.3rem;">
                <i class="bi bi-paperclip"></i>{{ $rec->archivo_nombre }}
            </div>
            @endif
            <div style="font-size:.65rem;color:#94a3b8;margin-top:.3rem;">
                <i class="bi bi-clock me-1"></i>{{ $rec->created_at->diffForHumans() }}
            </div>
        </div>
        <div style="color:{{ $color }};font-size:1.1rem;flex-shrink:0;align-self:center;">
            @if($rec->archivo_path)
            <i class="bi bi-download"></i>
            @else
            <i class="bi bi-box-arrow-up-right"></i>
            @endif
        </div>
    </div>

@if($rec->url || $rec->archivo_path)
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
