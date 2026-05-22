@extends('layouts.admin')
@section('page-title', 'Salud Escolar')

@push('styles')
<style>
.dash-card { background:#fff; border-radius:14px; border:1px solid #e5e7eb; padding:1.25rem 1.5rem; }
.stat-icon  { width:46px; height:46px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
.stat-label { font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; }
.stat-value { font-size:1.5rem; font-weight:800; color:#111827; line-height:1.1; }

.inc-row { display:flex; align-items:center; gap:.75rem; padding:.6rem 0; border-bottom:1px solid #f3f4f6; }
.inc-row:last-child { border-bottom:none; }

.tipo-chip { display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .65rem;
             border-radius:20px; font-size:.72rem; font-weight:700; }

.bar-wrap  { display:flex; flex-direction:column; gap:.4rem; }
.bar-item  { display:flex; align-items:center; gap:.5rem; font-size:.8rem; }
.bar-track { flex:1; height:8px; border-radius:4px; background:#f1f5f9; overflow:hidden; }
.bar-fill  { height:100%; border-radius:4px; }
</style>
@endpush

@section('content')

{{-- Encabezado ──────────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 style="font-size:1.35rem;font-weight:800;color:var(--primary);margin:0;">
            <i class="bi bi-heart-pulse-fill me-2" style="color:#e11d48;"></i>Salud Escolar
        </h1>
        <p class="text-muted mb-0" style="font-size:.82rem;">Fichas médicas, incidentes y atenciones del plantel</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.salud.incidentes') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-list-ul me-1"></i>Todos los incidentes
        </a>
        <a href="{{ route('admin.salud.incidentes.crear') }}" class="btn btn-warning btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Registrar incidente
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" style="border-radius:10px;">
    <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Tarjetas resumen ─────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="dash-card d-flex align-items-center gap-3">
            <div class="stat-icon" style="background:#fee2e2;">
                <i class="bi bi-clipboard2-pulse" style="color:#dc2626;"></i>
            </div>
            <div>
                <div class="stat-label">Total Incidentes</div>
                <div class="stat-value">{{ $totalIncidentes }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dash-card d-flex align-items-center gap-3">
            <div class="stat-icon" style="background:#fef3c7;">
                <i class="bi bi-calendar-event" style="color:#d97706;"></i>
            </div>
            <div>
                <div class="stat-label">Este Mes</div>
                <div class="stat-value" style="color:#d97706;">{{ $incidentesMes }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dash-card d-flex align-items-center gap-3">
            <div class="stat-icon" style="background:#dbeafe;">
                <i class="bi bi-file-medical" style="color:#1d4ed8;"></i>
            </div>
            <div>
                <div class="stat-label">Fichas Médicas</div>
                <div class="stat-value" style="color:#1d4ed8;">{{ $totalFichas }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dash-card d-flex align-items-center gap-3">
            <div class="stat-icon" style="background:#fce7f3;">
                <i class="bi bi-bell-slash" style="color:#be185d;"></i>
            </div>
            <div>
                <div class="stat-label">Sin Notificar</div>
                <div class="stat-value" style="color:#be185d;">{{ $noNotificados }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- Distribución por tipo ──────────────────────────────────────────────── --}}
    <div class="col-12 col-md-5">
        <div class="dash-card h-100">
            <div class="fw-700 mb-3" style="font-weight:700; font-size:.9rem; color:#111827;">
                <i class="bi bi-pie-chart me-1" style="color:#7c3aed;"></i>Incidentes por Tipo
            </div>
            @php $maxTipo = $conteosTipo->max() ?: 1; @endphp
            <div class="bar-wrap">
                @foreach($tipos as $key => $ti)
                @php $cnt = $conteosTipo[$key] ?? 0; @endphp
                <div class="bar-item">
                    <div style="width:80px; font-size:.8rem; color:#374151; font-weight:600;">
                        <i class="bi {{ $ti['icon'] }} me-1" style="color:{{ $ti['color'] }};"></i>{{ $ti['label'] }}
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width:{{ $maxTipo > 0 ? round($cnt / $maxTipo * 100) : 0 }}%; background:{{ $ti['color'] }};"></div>
                    </div>
                    <div style="width:28px; text-align:right; font-weight:700; font-size:.82rem; color:#111827;">{{ $cnt }}</div>
                </div>
                @endforeach
            </div>

            @if($totalIncidentes > 0)
            <div class="d-flex flex-wrap gap-2 mt-3">
                @foreach($tipos as $key => $ti)
                @php $cnt = $conteosTipo[$key] ?? 0; $pct = round($cnt / $totalIncidentes * 100); @endphp
                @if($cnt > 0)
                <span class="tipo-chip" style="background:{{ $ti['bg'] }};color:{{ $ti['color'] }};">
                    {{ $ti['label'] }}: {{ $pct }}%
                </span>
                @endif
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Incidentes recientes ─────────────────────────────────────────────── --}}
    <div class="col-12 col-md-7">
        <div class="dash-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="fw-700" style="font-weight:700; font-size:.9rem; color:#111827;">
                    <i class="bi bi-clock-history me-1" style="color:#0891b2;"></i>Incidentes Recientes
                </div>
                <a href="{{ route('admin.salud.incidentes') }}" class="btn btn-outline-secondary btn-sm" style="font-size:.75rem;">
                    Ver todos <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            @forelse($ultimos as $inc)
            @php $ti = $inc->tipo_info; @endphp
            <div class="inc-row">
                <div style="width:32px; height:32px; border-radius:8px; background:{{ $ti['bg'] }};
                            display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="bi {{ $ti['icon'] }}" style="color:{{ $ti['color'] }}; font-size:.9rem;"></i>
                </div>
                <div style="flex:1; min-width:0;">
                    <div style="font-weight:700; font-size:.82rem; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ $inc->estudiante?->nombre_completo ?? '—' }}
                    </div>
                    <div style="font-size:.74rem; color:#6b7280;">
                        {{ $inc->fecha->format('d/m/Y') }}
                        @if($inc->hora) · {{ \Carbon\Carbon::parse($inc->hora)->format('H:i') }} @endif
                        · {{ Str::limit($inc->descripcion, 50) }}
                    </div>
                </div>
                <div class="d-flex align-items-center gap-1">
                    @if(! $inc->notificado_representante)
                    <span class="badge bg-warning text-dark" style="font-size:.68rem;" title="Representante no notificado">
                        <i class="bi bi-bell-slash"></i>
                    </span>
                    @endif
                    <a href="{{ route('admin.salud.incidentes.editar', $inc) }}"
                       class="btn btn-outline-secondary btn-sm py-0 px-2" title="Editar">
                        <i class="bi bi-pencil" style="font-size:.7rem;"></i>
                    </a>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-4">
                <i class="bi bi-clipboard2-x fs-2 d-block mb-2"></i>
                Sin incidentes registrados.
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Acciones rápidas por tipo ───────────────────────────────────────────── --}}
<div class="row g-3">
    @foreach($tipos as $key => $ti)
    <div class="col-6 col-sm-3">
        <a href="{{ route('admin.salud.incidentes', ['tipo' => $key]) }}"
           class="dash-card d-flex align-items-center gap-3 text-decoration-none"
           style="border-left:4px solid {{ $ti['color'] }};">
            <div style="width:36px; height:36px; border-radius:8px; background:{{ $ti['bg'] }};
                        display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="bi {{ $ti['icon'] }}" style="color:{{ $ti['color'] }};"></i>
            </div>
            <div>
                <div style="font-size:.73rem; color:#6b7280; font-weight:600; text-transform:uppercase; letter-spacing:.04em;">{{ $ti['label'] }}</div>
                <div style="font-size:1.25rem; font-weight:800; color:#111827;">{{ $conteosTipo[$key] ?? 0 }}</div>
            </div>
        </a>
    </div>
    @endforeach
</div>

@endsection
