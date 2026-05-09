@extends('layouts.portal')
@section('title', 'Reconocimientos — ' . $estudiante->nombres)

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'logros', 'estudiante' => $estudiante])
@endsection

@section('content')

{{-- Header --}}
<div class="mb-4 p-4" style="background:linear-gradient(135deg,#b45309,#f59e0b);border-radius:16px;position:relative;overflow:hidden;">
    <div style="position:absolute;top:-20px;right:-20px;width:120px;height:120px;background:rgba(255,255,255,.08);border-radius:50%;pointer-events:none;"></div>
    <div style="position:relative;z-index:1;">
        <h4 class="text-white fw-bold mb-1"><i class="bi bi-trophy-fill me-2"></i>Reconocimientos y Logros</h4>
        <small class="text-white opacity-75">{{ $estudiante->nombres }} {{ $estudiante->apellidos }}</small>
    </div>
</div>

@if($reconocimientos->isEmpty())
<div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-body text-center py-5">
        <i class="bi bi-trophy" style="font-size:3rem;color:#d1d5db;"></i>
        <p class="text-muted mt-3 mb-0">Aún no hay reconocimientos registrados.</p>
    </div>
</div>
@else
<div class="row g-3">
    @foreach($reconocimientos as $rec)
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;overflow:hidden;">
            <div class="card-body d-flex gap-3 align-items-start py-3 px-4">
                <div style="width:48px;height:48px;border-radius:12px;background:#fef3c7;flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-award-fill" style="color:#b45309;font-size:1.4rem;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:.85rem;font-weight:800;color:#1e293b;">{{ $rec->titulo }}</div>
                    @if($rec->tipo)
                        <span style="background:#fef9c3;color:#92400e;border-radius:99px;padding:.1rem .5rem;font-size:.7rem;font-weight:700;">{{ $rec->tipo->nombre }}</span>
                    @endif
                    @if($rec->descripcion)
                        <p style="font-size:.78rem;color:#64748b;margin-top:.35rem;margin-bottom:.25rem;">{{ $rec->descripcion }}</p>
                    @endif
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span style="font-size:.72rem;color:#94a3b8;">
                            <i class="bi bi-calendar3 me-1"></i>{{ $rec->fecha->format('d/m/Y') }}
                        </span>
                        @if($rec->entregado)
                            <span style="background:#d1fae5;color:#065f46;border-radius:99px;padding:.1rem .5rem;font-size:.68rem;font-weight:700;">
                                <i class="bi bi-check-circle-fill me-1"></i>Entregado
                            </span>
                        @else
                            <span style="background:#fef3c7;color:#92400e;border-radius:99px;padding:.1rem .5rem;font-size:.68rem;font-weight:700;">
                                Pendiente entrega
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-3 text-muted small text-end">
    {{ $reconocimientos->count() }} reconocimiento{{ $reconocimientos->count() != 1 ? 's' : '' }} en total
</div>
@endif

@endsection
