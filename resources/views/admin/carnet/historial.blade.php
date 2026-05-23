@extends('layouts.admin')
@section('page-title', 'Carnet+ — Historial')

@section('content')

<x-breadcrumb :items="[
    ['label' => 'Dashboard',  'url' => route('admin.dashboard')],
    ['label' => 'Carnet+',    'url' => route('admin.carnet.index')],
    ['label' => 'Historial'],
]" />

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <h4 class="fw-bold mb-0" style="color:var(--primary)">
        <i class="bi bi-clock-history me-2"></i>Historial de Accesos
    </h4>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.carnet.historial.pdf', request()->all()) }}" target="_blank"
           class="btn btn-outline-danger btn-sm"><i class="bi bi-file-pdf me-1"></i>PDF</a>
        <a href="{{ route('admin.carnet.historial.excel', request()->all()) }}"
           class="btn btn-outline-success btn-sm"><i class="bi bi-file-excel me-1"></i>Excel</a>
    </div>
</div>

{{-- Filtros --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <form class="d-flex gap-2 flex-wrap align-items-center">
            <input type="date" name="fecha" class="form-control form-control-sm" style="max-width:160px;"
                   value="{{ request('fecha', today()->toDateString()) }}">
            <select name="tipo_evento" class="form-select form-select-sm" style="max-width:150px;">
                <option value="">Todos los eventos</option>
                @foreach(\App\Models\CarnetAcceso::TIPOS_EVENTO as $val => $lbl)
                <option value="{{ $val }}" {{ request('tipo_evento')==$val ? 'selected':'' }}>{{ $lbl }}</option>
                @endforeach
            </select>
            <select name="estado" class="form-select form-select-sm" style="max-width:160px;">
                <option value="">Todos los estados</option>
                @foreach(\App\Models\CarnetAcceso::ESTADOS as $val => $info)
                <option value="{{ $val }}" {{ request('estado')==$val ? 'selected':'' }}>{{ $info['label'] }}</option>
                @endforeach
            </select>
            <input type="text" name="search" class="form-control form-control-sm" style="max-width:180px;"
                   placeholder="Buscar nombre..." value="{{ request('search') }}">
            <button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filtrar</button>
            @if(request()->hasAny(['fecha','tipo_evento','estado','search']))
            <a href="{{ route('admin.carnet.historial') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
            @endif
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="font-size:.8rem;padding:.7rem 1rem;">Persona</th>
                        <th style="font-size:.8rem;">Grupo</th>
                        <th style="font-size:.8rem;">Evento</th>
                        <th style="font-size:.8rem;">Estado</th>
                        <th style="font-size:.8rem;">Zona</th>
                        <th style="font-size:.8rem;">Hora</th>
                        <th style="font-size:.8rem;">Dispositivo</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($accesos as $acc)
                @php $badge = $acc->estado_badge; @endphp
                <tr>
                    <td style="padding:.6rem 1rem;">
                        <div class="fw-semibold" style="font-size:.88rem;">
                            {{ $acc->carnet?->nombre_completo ?? '—' }}
                        </div>
                        <div class="text-muted" style="font-size:.74rem;">
                            {{ $acc->carnet?->numero_carnet ?? '' }}
                        </div>
                    </td>
                    <td style="font-size:.83rem;">{{ $acc->carnet?->matricula?->grupo?->nombre_completo ?? '—' }}</td>
                    <td>
                        <span class="badge rounded-pill px-2 py-1" style="background:#e0e7ff;color:#3730a3;font-size:.75rem;">
                            {{ \App\Models\CarnetAcceso::TIPOS_EVENTO[$acc->tipo_evento] ?? $acc->tipo_evento }}
                        </span>
                    </td>
                    <td>
                        <span class="badge rounded-pill px-2 py-1 bg-{{ $badge['color'] }}" style="font-size:.75rem;">
                            <i class="{{ $badge['icon'] }} me-1"></i>{{ $badge['label'] }}
                        </span>
                    </td>
                    <td style="font-size:.82rem;">{{ $acc->zona?->nombre ?? '—' }}</td>
                    <td style="font-size:.82rem;font-variant-numeric:tabular-nums;">{{ $acc->hora }}</td>
                    <td style="font-size:.76rem;color:#9ca3af;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $acc->dispositivo ?? '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-clock-history" style="font-size:2.5rem;opacity:.2;display:block;margin-bottom:.5rem;"></i>
                        No hay registros para los filtros seleccionados.
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($accesos->hasPages())
    <div class="card-footer bg-white border-top-0 py-2">{{ $accesos->links() }}</div>
    @endif
</div>

@endsection
