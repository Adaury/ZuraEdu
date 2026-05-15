@extends('layouts.portal-estudiante')
@section('title', 'Comunicados')

@section('activeKey', 'comunicados')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
    <h2 style="font-size:1rem;font-weight:800;margin:0;">
        <i class="bi bi-megaphone-fill me-2" style="color:#3b82f6;"></i>Noticias del Centro
    </h2>
    <div class="d-flex align-items-center gap-2">
        <span style="font-size:.78rem;color:var(--prt-muted);">{{ $comunicados->total() }} comunicado(s)</span>
        <a href="{{ route('portal.estudiante.comunicados.pdf') }}" target="_blank" class="btn btn-danger btn-sm" style="font-size:.75rem;">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('portal.estudiante.comunicados.excel') }}" class="btn btn-success btn-sm" style="font-size:.75rem;">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
    </div>
</div>

@forelse($comunicados as $com)
<div class="prt-card" style="margin-bottom:.85rem;">
    <div class="prt-card-body" style="padding:.9rem 1.1rem;">
        <div style="font-size:.9rem;font-weight:700;color:#1e293b;margin-bottom:.3rem;">{{ $com->titulo }}</div>
        <div style="font-size:.8rem;color:#374151;line-height:1.65;margin-bottom:.5rem;white-space:pre-line;">{{ $com->cuerpo }}</div>
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.3rem;">
            <span style="font-size:.72rem;color:#9ca3af;">
                <i class="bi bi-calendar3 me-1"></i>{{ $com->published_at?->format('d/m/Y') }}
                &nbsp;·&nbsp; {{ $com->autor?->name ?? 'Administración' }}
            </span>
            <span style="background:#eff6ff;color:#3b82f6;border-radius:20px;padding:.15rem .6rem;font-size:.68rem;font-weight:700;">
                {{ ucfirst($com->tipo_destinatarios ?? 'general') }}
            </span>
        </div>
    </div>
</div>
@empty
<div class="prt-card">
    <div class="prt-card-body" style="text-align:center;padding:2.5rem;color:var(--prt-muted);">
        <i class="bi bi-megaphone" style="font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.4;"></i>
        No hay comunicados disponibles.
    </div>
</div>
@endforelse

<div class="mt-3 d-flex justify-content-center">
    {{ $comunicados->links() }}
</div>

@endsection
