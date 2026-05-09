@extends('layouts.admin')
@section('page-title', 'Solicitudes de Representantes')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1e3a6e;"><i class="bi bi-send-fill me-2"></i>Solicitudes de Representantes</h4>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">Justificaciones, citas y solicitudes enviadas desde el portal del padre.</p>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-3">
    <div class="col-4 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3" style="border-radius:12px;border-top:4px solid #d97706 !important;">
            <div style="font-size:1.7rem;font-weight:900;color:#d97706;">{{ $stats['pendientes'] }}</div>
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;color:#9ca3af;letter-spacing:.05em;">Pendientes</div>
        </div>
    </div>
    <div class="col-4 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3" style="border-radius:12px;border-top:4px solid #2563eb !important;">
            <div style="font-size:1.7rem;font-weight:900;color:#2563eb;">{{ $stats['en_proceso'] }}</div>
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;color:#9ca3af;letter-spacing:.05em;">En proceso</div>
        </div>
    </div>
    <div class="col-4 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3" style="border-radius:12px;border-top:4px solid #16a34a !important;">
            <div style="font-size:1.7rem;font-weight:900;color:#16a34a;">{{ $stats['total_hoy'] }}</div>
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;color:#9ca3af;letter-spacing:.05em;">Hoy</div>
        </div>
    </div>
</div>

{{-- Filtros --}}
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3">
        <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            @foreach($estados as $key => $cfg)
            <option value="{{ $key }}" {{ request('estado') === $key ? 'selected' : '' }}>{{ $cfg['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="tipo" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todos los tipos</option>
            @foreach($tipos as $key => $label)
            <option value="{{ $key }}" {{ request('tipo') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <div class="input-group input-group-sm">
            <input type="text" name="q" class="form-control" placeholder="Buscar por asunto o representante…" value="{{ request('q') }}">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
        </div>
    </div>
    @if(request()->hasAny(['estado','tipo','q']))
    <div class="col-md-2">
        <a href="{{ route('admin.solicitudes.index') }}" class="btn btn-outline-secondary btn-sm w-100">Limpiar</a>
    </div>
    @endif
</form>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:.83rem;border-radius:10px;">
    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
</div>
@endif

{{-- Tabla --}}
<div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden;">
    <table class="table table-hover mb-0" style="font-size:.84rem;">
        <thead style="background:#f8fafc;">
            <tr>
                <th style="padding:.75rem 1rem;font-weight:700;font-size:.7rem;text-transform:uppercase;color:#6b7280;letter-spacing:.05em;">Representante</th>
                <th>Tipo</th>
                <th>Asunto</th>
                <th>Estudiante</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($solicitudes as $sol)
        @php $ec = $sol->estado_config; @endphp
        <tr>
            <td style="padding:.7rem 1rem;vertical-align:middle;">
                <div class="fw-600">{{ $sol->representante?->nombre_completo ?? '—' }}</div>
                <div style="font-size:.72rem;color:#9ca3af;">{{ $sol->representante?->telefono }}</div>
            </td>
            <td style="vertical-align:middle;">
                <span class="badge" style="background:#f1f5f9;color:#374151;font-size:.7rem;">{{ $sol->tipo_label }}</span>
            </td>
            <td style="vertical-align:middle;max-width:200px;">
                <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $sol->asunto }}</div>
            </td>
            <td style="vertical-align:middle;font-size:.78rem;color:#64748b;">
                {{ $sol->estudiante?->nombre_completo ?? '—' }}
            </td>
            <td style="vertical-align:middle;font-size:.78rem;color:#64748b;white-space:nowrap;">
                {{ $sol->created_at->format('d/m/Y') }}<br>
                <span style="color:#9ca3af;">{{ $sol->created_at->format('H:i') }}</span>
            </td>
            <td style="vertical-align:middle;">
                <span style="background:{{ $ec['bg'] }};color:{{ $ec['color'] }};border-radius:99px;font-size:.65rem;font-weight:700;padding:.2rem .6rem;">
                    {{ $ec['label'] }}
                </span>
            </td>
            <td style="vertical-align:middle;">
                <a href="{{ route('admin.solicitudes.show', $sol) }}" class="btn btn-sm btn-outline-primary" style="font-size:.75rem;">
                    Ver
                </a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center py-5 text-muted">
                <i class="bi bi-send" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                No hay solicitudes con esos criterios.
            </td>
        </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">{{ $solicitudes->links() }}</div>

@endsection
