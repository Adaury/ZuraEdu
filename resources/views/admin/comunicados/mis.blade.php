@extends('layouts.admin')
@section('page-title', 'Mis Comunicados')
@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-megaphone-fill me-2"></i>Comunicados
        </h4>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">Avisos institucionales dirigidos a usted</p>
    </div>
</div>

@if($comunicados->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-megaphone" style="font-size:2.5rem;opacity:.3;display:block;margin-bottom:.75rem;"></i>
        No hay comunicados publicados para usted por el momento.
    </div>
</div>
@else
<div class="row g-3">
    @foreach($comunicados as $c)
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-left:4px solid var(--primary) !important;">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                    <h5 class="fw-bold mb-0" style="color:var(--primary);font-size:1rem;">{{ $c->titulo }}</h5>
                    <span class="text-muted" style="font-size:.75rem;white-space:nowrap;">
                        {{ $c->published_at?->format('d/m/Y H:i') }}
                    </span>
                </div>
                <p class="text-muted mb-2" style="font-size:.77rem;">
                    <i class="bi bi-person me-1"></i>{{ $c->autor?->name ?? '—' }}
                </p>
                <div style="font-size:.88rem;color:#374151;line-height:1.6;" class="comunicado-body">{!! $c->cuerpo !!}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>
<div class="mt-3">{{ $comunicados->links() }}</div>
@endif
@endsection
