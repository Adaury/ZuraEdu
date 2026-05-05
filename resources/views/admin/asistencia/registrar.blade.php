@extends('layouts.admin')
@section('page-title', 'Registrar Asistencia')

@push('styles')
<style>
    /* ── Estado button tiles ──────────────────────────────────── */
    .estado-tile {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        width: 66px;
        height: 52px;
        border-radius: 10px;
        border: 2px solid transparent;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .02em;
        cursor: pointer;
        transition: all .15s;
        user-select: none;
        background: #f3f4f6;
        color: #6b7280;
    }
    .estado-tile i { font-size: 1.1rem; }
    .estado-tile:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,0,0,.12); }

    /* Checked states */
    .estado-tile.activo-presente {
        background: #dcfce7; color: #15803d;
        border-color: #16a34a; box-shadow: 0 2px 8px rgba(22,163,74,.2);
    }
    .estado-tile.activo-ausente {
        background: #fee2e2; color: #991b1b;
        border-color: #dc2626; box-shadow: 0 2px 8px rgba(220,38,38,.2);
    }
    .estado-tile.activo-tarde {
        background: #fef3c7; color: #92400e;
        border-color: #d97706; box-shadow: 0 2px 8px rgba(217,119,6,.2);
    }
    .estado-tile.activo-excusa {
        background: #dbeafe; color: #1d4ed8;
        border-color: #2563eb; box-shadow: 0 2px 8px rgba(37,99,235,.2);
    }
    .estado-tile.activo-retiro {
        background: #f3e8ff; color: #6b21a8;
        border-color: #7c3aed; box-shadow: 0 2px 8px rgba(124,58,237,.2);
    }

    /* ── Student row ──────────────────────────────────────────── */
    .estudiante-row {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: .65rem 1rem;
        margin-bottom: .45rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: background .12s;
    }
    .estudiante-row:hover { background: #f8faff; }
    .est-num {
        font-size: .78rem;
        color: #2563eb;
        font-weight: 700;
        min-width: 28px;
        text-align: center;
    }
    [data-theme="dark"] .est-num { color: #93c5fd !important; }
    .est-nombre {
        flex: 1;
        font-weight: 700;
        font-size: .9rem;
        color: #1d4ed8;
        min-width: 0;
    }
    [data-theme="dark"] .est-nombre { color: #93c5fd !important; }
    .est-nombre small {
        display: block;
        font-weight: 700;
        font-size: .76rem;
        color: #2563eb;
        font-family: monospace;
    }
    [data-theme="dark"] .est-nombre small { color: #93c5fd !important; }
    .asist-badge {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        font-size: .7rem;
        font-weight: 700;
        padding: .18rem .5rem;
        border-radius: 12px;
        white-space: nowrap;
    }
    .asist-ok   { background: #dcfce7; color: #15803d; }
    .asist-warn { background: #fef3c7; color: #92400e; }
    .asist-risk { background: #fee2e2; color: #991b1b; }
    .tiles-group {
        display: flex;
        gap: .4rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    /* ── Summary bar ──────────────────────────────────────────── */
    .summary-counter {
        display: flex;
        gap: .6rem;
        flex-wrap: wrap;
        align-items: center;
    }
    .cnt-pill {
        display: flex; align-items: center; gap: .4rem;
        padding: .35rem .8rem;
        border-radius: 20px;
        font-size: .82rem;
        font-weight: 700;
    }
    .cnt-p { background: #dcfce7; color: #15803d; }
    .cnt-a { background: #fee2e2; color: #991b1b; }
    .cnt-t { background: #fef3c7; color: #92400e; }
    .cnt-e { background: #dbeafe; color: #1d4ed8; }
    .cnt-r { background: #f3e8ff; color: #6b21a8; }

    /* ── Quick actions ────────────────────────────────────────── */
    .quick-btn { font-size: .78rem; padding: .28rem .7rem; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.82rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.asistencia.index') }}" class="text-decoration-none">Asistencia</a></li>
        <li class="breadcrumb-item active">Registrar</li>
    </ol>
</nav>

<form method="POST" action="{{ route('admin.asistencia.guardar', $asignacion->id) }}" id="form-asistencia">
@csrf

{{-- ── Top action bar ─────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3" style="background:linear-gradient(135deg,var(--primary),#2a4f96);">
    <div class="card-body py-3 px-4 text-white">
        <div class="row align-items-center g-2">
            <div class="col-md-7">
                <h5 class="fw-bold mb-1">{{ optional($asignacion->asignatura)->nombre }}</h5>
                <div class="d-flex flex-wrap gap-3" style="font-size:.82rem;opacity:.88;">
                    <span><i class="bi bi-people me-1"></i>{{ optional($asignacion->grupo)->nombre_completo }}</span>
                    <span><i class="bi bi-person-badge me-1"></i>{{ optional($asignacion->docente)->nombre_completo ?? 'Sin docente' }}</span>
                    <span><i class="bi bi-people-fill me-1"></i>{{ $matriculas->count() }} estudiantes</span>
                </div>
            </div>
            <div class="col-md-5 text-md-end">
                <div class="d-flex flex-wrap gap-2 justify-content-md-end align-items-center">
                    <div>
                        <label class="text-white-50 me-2" style="font-size:.78rem;font-weight:600;">FECHA</label>
                        <input type="date"
                               name="fecha"
                               id="input-fecha"
                               value="{{ $fecha }}"
                               class="form-control form-control-sm d-inline-block"
                               style="width:155px;background:#fff;border:0;font-weight:600;"
                               onchange="window.location.href='{{ route('admin.asistencia.registrar', $asignacion->id) }}?fecha='+this.value">
                    </div>
                    <a href="{{ route('admin.asistencia.lista-blanco', $asignacion) }}" target="_blank"
                       class="btn btn-outline-light btn-sm">
                        <i class="bi bi-printer me-1"></i>Lista en blanco
                    </a>
                    <button type="submit" class="btn btn-light fw-bold px-4">
                        <i class="bi bi-floppy me-2"></i>Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Summary counters (auto-update via JS) ──────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="summary-counter">
                <span class="cnt-pill cnt-p"><i class="bi bi-check-circle-fill"></i><span id="cnt-presente">0</span> Presentes</span>
                <span class="cnt-pill cnt-a"><i class="bi bi-x-circle-fill"></i><span id="cnt-ausente">0</span> Ausentes</span>
                <span class="cnt-pill cnt-t"><i class="bi bi-clock-fill"></i><span id="cnt-tarde">0</span> Tarde</span>
                <span class="cnt-pill cnt-e"><i class="bi bi-shield-check"></i><span id="cnt-excusa">0</span> Excusas</span>
                <span class="cnt-pill cnt-r"><i class="bi bi-door-open"></i><span id="cnt-retiro">0</span> Retiros</span>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-success quick-btn" onclick="marcarTodos('presente')">
                    <i class="bi bi-check2-all me-1"></i>Todos Presentes
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger quick-btn" onclick="marcarTodos('ausente')">
                    <i class="bi bi-x-circle me-1"></i>Todos Ausentes
                </button>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2" style="font-size:.85rem;">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ── Student list ─────────────────────────────────────────────────── --}}
@php $num = 1; @endphp
@foreach($matriculas as $m)
@php
    $estadoActual = $existentes[$m->id] ?? 'presente';
@endphp
<div class="estudiante-row" id="row-{{ $m->id }}">
    <div class="est-num">{{ $num++ }}</div>
    <div class="est-nombre">
        {{ $m->estudiante?->nombre_completo ?? '—' }}
        <small>
            #{{ $m->numero_orden ?? $m->id }}
            @php $stats = $totalesPorMatricula[$m->id] ?? null; @endphp
            @if($stats && $stats['total'] > 0)
                @php
                    $pct     = $stats['pct'];
                    $ausentes = $stats['ausentes'];
                    $badgeClass = $pct >= 80 ? 'asist-ok' : ($pct >= 70 ? 'asist-warn' : 'asist-risk');
                    $badgeLabel = $pct . '% asist.' . ($ausentes > 0 ? ' (' . $ausentes . ' falta' . ($ausentes > 1 ? 's' : '') . ')' : '');
                @endphp
                &nbsp;<span class="asist-badge {{ $badgeClass }}"
                    title="{{ $ausentes }} ausencia(s) de {{ $stats['total'] }} clases">
                    <i class="bi bi-calendar-check"></i> {{ $badgeLabel }}
                </span>
            @endif
        </small>
    </div>
    <div class="tiles-group">
        @php
            $estadoIconos = [
                'presente' => 'check-circle',
                'ausente'  => 'x-circle',
                'tarde'    => 'clock',
                'excusa'   => 'shield-check',
                'retiro'   => 'door-open',
            ];
            $estadoLabels = [
                'presente' => 'Presente',
                'ausente'  => 'Ausente',
                'tarde'    => 'Tarde',
                'excusa'   => 'Excusa',
                'retiro'   => 'Retiro',
            ];
        @endphp
        @foreach(['presente','ausente','tarde','excusa','retiro'] as $est)
        <label class="estado-tile {{ $estadoActual === $est ? 'activo-'.$est : '' }}"
               id="tile-{{ $m->id }}-{{ $est }}"
               onclick="seleccionarEstado({{ $m->id }}, '{{ $est }}')">
            <i class="bi bi-{{ $estadoIconos[$est] }}"></i>
            {{ $estadoLabels[$est] }}
            <input type="radio"
                   name="asistencia[{{ $m->id }}]"
                   value="{{ $est }}"
                   class="d-none estado-radio"
                   data-matricula="{{ $m->id }}"
                   {{ $estadoActual === $est ? 'checked' : '' }}>
        </label>
        @endforeach
    </div>
</div>
@endforeach

{{-- ── Bottom save button ──────────────────────────────────────────── --}}
<div class="d-flex justify-content-between align-items-center mt-3">
    <a href="{{ route('admin.asistencia.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Volver
    </a>
    <button type="submit" class="btn btn-primary px-5 fw-bold">
        <i class="bi bi-floppy me-2"></i>Guardar Asistencia
    </button>
</div>

</form>
@endsection

@push('scripts')
<script>
function seleccionarEstado(matriculaId, estado) {
    const estados = ['presente', 'ausente', 'tarde', 'excusa', 'retiro'];

    // Remove active class from all tiles for this student
    estados.forEach(e => {
        const tile = document.getElementById(`tile-${matriculaId}-${e}`);
        if (tile) {
            tile.className = tile.className.replace(/activo-\w+/g, '').trim();
        }
    });

    // Set active class on selected tile
    const activeTile = document.getElementById(`tile-${matriculaId}-${estado}`);
    if (activeTile) {
        activeTile.classList.add(`activo-${estado}`);
        // Check the hidden radio
        const radio = activeTile.querySelector('.estado-radio');
        if (radio) radio.checked = true;
    }

    actualizarContadores();
}

function marcarTodos(estado) {
    document.querySelectorAll('.estado-radio').forEach(radio => {
        if (radio.value === estado) {
            radio.checked = true;
            const matriculaId = radio.dataset.matricula;
            seleccionarEstado(matriculaId, estado);
        }
    });
}

function actualizarContadores() {
    const contadores = { presente: 0, ausente: 0, tarde: 0, excusa: 0, retiro: 0 };

    document.querySelectorAll('.estado-radio:checked').forEach(radio => {
        const estado = radio.value;
        if (contadores[estado] !== undefined) contadores[estado]++;
    });

    document.getElementById('cnt-presente').textContent = contadores.presente;
    document.getElementById('cnt-ausente').textContent  = contadores.ausente;
    document.getElementById('cnt-tarde').textContent    = contadores.tarde;
    document.getElementById('cnt-excusa').textContent   = contadores.excusa;
    document.getElementById('cnt-retiro').textContent   = contadores.retiro;
}

// Initialize counters
document.addEventListener('DOMContentLoaded', actualizarContadores);
</script>
@endpush
