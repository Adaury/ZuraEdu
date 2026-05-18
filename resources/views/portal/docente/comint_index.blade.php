@extends('layouts.portal')

@section('page-title', 'Comunicados Internos — Portal Docente')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'comint'])
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4 py-4" style="max-width:860px;">

    {{-- Cabecera --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        <div style="width:46px;height:46px;background:linear-gradient(135deg,#1e3a6e,#3b82f6);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-envelope-paper-fill" style="color:#fff;font-size:1.25rem;"></i>
        </div>
        <div>
            <h4 class="mb-0 fw-bold" style="color:#1e3a6e;">Comunicados Internos</h4>
            <p class="mb-0 text-muted" style="font-size:.82rem;">Comunicaciones oficiales del centro dirigidas al personal</p>
        </div>
        @php
            $sinLeer = $comunicados->filter(fn($c) => $c->lecturas->isEmpty())->count();
        @endphp
        @if($sinLeer > 0)
        <span class="ms-auto badge rounded-pill" style="background:#ef4444;font-size:.78rem;padding:.35rem .7rem;">
            {{ $sinLeer }} sin leer
        </span>
        @endif
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:10px;border:none;background:#dcfce7;color:#166534;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @forelse($comunicados as $com)
    @php $leido = $com->lecturas->isNotEmpty(); @endphp
    <div class="card border-0 mb-3 comint-card {{ $leido ? 'leido' : 'no-leido' }}"
         style="border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.07);overflow:hidden;transition:box-shadow .2s;"
         id="comint-{{ $com->id }}">

        {{-- Barra lateral de estado --}}
        <div style="display:flex;">
            <div class="comint-stripe" style="width:5px;flex-shrink:0;background:{{ $leido ? '#22c55e' : '#3b82f6' }};"></div>
            <div class="card-body py-3 px-4" style="flex:1;">
                <div class="d-flex align-items-start gap-3">

                    {{-- Ícono --}}
                    <div style="width:40px;height:40px;background:{{ $leido ? '#f0fdf4' : '#eff6ff' }};border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
                        <i class="bi {{ $leido ? 'bi-envelope-open-fill' : 'bi-envelope-fill' }}"
                           style="color:{{ $leido ? '#22c55e' : '#3b82f6' }};font-size:1.1rem;"></i>
                    </div>

                    {{-- Contenido --}}
                    <div style="flex:1;min-width:0;">
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <h6 class="mb-0 fw-{{ $leido ? 'normal' : 'bold' }}" style="color:{{ $leido ? '#374151' : '#1e3a6e' }};font-size:.95rem;">
                                {{ $com->titulo }}
                            </h6>
                            @if(!$leido)
                            <span style="background:#3b82f6;color:#fff;border-radius:99px;font-size:.6rem;padding:.15rem .45rem;font-weight:700;white-space:nowrap;">NUEVO</span>
                            @endif
                            <span class="ms-auto text-muted" style="font-size:.75rem;white-space:nowrap;">
                                <i class="bi bi-calendar3 me-1"></i>{{ $com->published_at->format('d/m/Y H:i') }}
                            </span>
                        </div>

                        <p class="mb-2" style="font-size:.82rem;color:#6b7280;line-height:1.5;">
                            {{ Str::limit(strip_tags($com->cuerpo), 200) }}
                        </p>

                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <span style="font-size:.75rem;color:#9ca3af;">
                                <i class="bi bi-person-fill me-1"></i>{{ $com->autor?->name ?? '—' }}
                            </span>

                            {{-- Acuse de recibo --}}
                            @if($leido)
                            <span style="font-size:.75rem;color:#22c55e;font-weight:600;">
                                <i class="bi bi-check-circle-fill me-1"></i>Leído el {{ $com->lecturas->first()?->leido_at?->format('d/m/Y H:i') }}
                            </span>
                            @else
                            <form method="POST" action="{{ route('portal.docente.comint.leer', $com) }}" class="d-inline"
                                  onsubmit="comintMarcar(event, this, {{ $com->id }})">
                                @csrf
                                <button type="submit" class="btn btn-sm"
                                        style="background:#3b82f6;color:#fff;border:none;border-radius:8px;font-size:.75rem;padding:.3rem .75rem;font-weight:600;transition:background .15s;">
                                    <i class="bi bi-check2 me-1"></i>Acuse de Recibo
                                </button>
                            </form>
                            @endif

                            {{-- Expandir texto completo --}}
                            @if(strlen(strip_tags($com->cuerpo)) > 200)
                            <button class="btn btn-link p-0" style="font-size:.75rem;color:#6366f1;"
                                    data-bs-toggle="collapse" data-bs-target="#body-{{ $com->id }}">
                                <i class="bi bi-chevron-down me-1"></i>Ver completo
                            </button>
                            @endif
                        </div>

                        @if(strlen(strip_tags($com->cuerpo)) > 200)
                        <div class="collapse mt-3" id="body-{{ $com->id }}">
                            <div style="background:#f8faff;border-radius:8px;padding:1rem;font-size:.83rem;color:#374151;line-height:1.7;border:1px solid #e0e7ff;">
                                {!! nl2br(e(strip_tags($com->cuerpo))) !!}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-5" style="color:#9ca3af;">
        <i class="bi bi-inbox" style="font-size:3rem;"></i>
        <p class="mt-3 mb-0">No hay comunicados internos publicados.</p>
    </div>
    @endforelse

    {{ $comunicados->links() }}
</div>

<style>
.comint-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.13) !important; }
.comint-card.leido { opacity: .85; }
</style>

<script>
function comintMarcar(e, form, id) {
    e.preventDefault();
    const card = document.getElementById('comint-' + id);
    fetch(form.action, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
    }).then(r => r.json()).then(data => {
        if (!data.ok) return;
        // Visual: marcar como leído sin recargar
        const stripe = card.querySelector('.comint-stripe');
        const icon   = card.querySelector('.bi-envelope-fill');
        const badge  = card.querySelector('.badge');
        const btn    = form.closest('.d-inline');

        if (stripe)  stripe.style.background = '#22c55e';
        if (icon) { icon.className = 'bi bi-envelope-open-fill'; icon.style.color = '#22c55e'; }
        if (badge)   badge.remove();
        if (btn)     btn.innerHTML = '<span style="font-size:.75rem;color:#22c55e;font-weight:600;"><i class="bi bi-check-circle-fill me-1"></i>Acuse registrado</span>';

        card.classList.add('leido');
        card.classList.remove('no-leido');

        // Actualizar contador en sidebar
        const sbBadge = document.querySelector('.comint-badge-sb');
        if (sbBadge) {
            const n = parseInt(sbBadge.textContent) - 1;
            n > 0 ? sbBadge.textContent = n : sbBadge.remove();
        }
    });
}
</script>
@endsection
