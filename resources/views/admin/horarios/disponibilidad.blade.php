@extends('layouts.admin')

@section('title', 'Disponibilidad Docentes')

@section('content')
<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Horarios', 'url' => route('admin.horarios.index')],
    ['label' => 'Disponibilidad Docentes'],
]" />

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 fw-bold" style="color: var(--primary);">
        <i class="bi bi-calendar2-check me-2"></i>Disponibilidad Docentes
    </h1>
    @if($schoolYear)
        <span class="badge fs-6 px-3 py-2" style="background: var(--primary);">
            <i class="bi bi-calendar3 me-1"></i>{{ $schoolYear->nombre ?? $schoolYear->anio ?? 'Año en curso' }}
        </span>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ===================== Selector de Docente ===================== --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.horarios.disponibilidad') }}" class="row g-3 align-items-end">
            <div class="col-md-6 col-lg-5">
                <label class="form-label fw-semibold">
                    <i class="bi bi-person-badge me-1"></i>Seleccionar docente
                </label>
                <select
                    name="docente_id"
                    id="docenteSelector"
                    class="form-select"
                    onchange="this.form.submit()"
                >
                    <option value="">— Seleccione un docente —</option>
                    @foreach($docentes as $docente)
                        <option
                            value="{{ $docente->id }}"
                            {{ $docenteId == $docente->id ? 'selected' : '' }}
                        >
                            {{ $docente->nombre_completo }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-right-circle me-1"></i>Cargar
                </button>
            </div>
        </form>
    </div>
</div>

@if($docenteId && $franjas->isNotEmpty())
    @php
        $dias = [
            'lunes'     => 'Lunes',
            'martes'    => 'Martes',
            'miercoles' => 'Miércoles',
            'jueves'    => 'Jueves',
            'viernes'   => 'Viernes',
        ];
    @endphp

    {{-- ===================== Grid de Disponibilidad ===================== --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
            <h6 class="mb-0 fw-semibold">
                <i class="bi bi-grid-3x3-gap me-2" style="color: var(--primary);"></i>
                Grilla de disponibilidad semanal
            </h6>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-success" onclick="toggleTodos(true)">
                    <i class="bi bi-check2-all me-1"></i>Seleccionar todo
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleTodos(false)">
                    <i class="bi bi-x-square me-1"></i>Deseleccionar todo
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <form
                action="{{ route('admin.horarios.disponibilidad.guardar') }}"
                method="POST"
                id="formDisponibilidad"
            >
                @csrf
                <input type="hidden" name="docente_id" value="{{ $docenteId }}">

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="tablaDisponibilidad">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="min-width: 130px;">Franja</th>
                                @foreach($dias as $diaKey => $diaNombre)
                                    <th class="text-center" style="min-width: 100px;">
                                        <div>{{ $diaNombre }}</div>
                                        <div class="d-flex justify-content-center gap-1 mt-1">
                                            <button
                                                type="button"
                                                class="btn btn-xs btn-outline-success py-0 px-1"
                                                style="font-size: 0.7rem;"
                                                onclick="toggleColumna('{{ $diaKey }}', true)"
                                                title="Seleccionar todo {{ $diaNombre }}"
                                            >
                                                <i class="bi bi-check2-all"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-xs btn-outline-secondary py-0 px-1"
                                                style="font-size: 0.7rem;"
                                                onclick="toggleColumna('{{ $diaKey }}', false)"
                                                title="Deseleccionar todo {{ $diaNombre }}"
                                            >
                                                <i class="bi bi-dash-square"></i>
                                            </button>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($franjas as $franja)
                                @if($franja->es_recreo)
                                    {{-- Fila de recreo: mostrar pero sin checkboxes --}}
                                    <tr class="table-warning">
                                        <td class="ps-3">
                                            <div class="fw-semibold text-warning-emphasis">
                                                <i class="bi bi-cup-hot me-1"></i>
                                                {{ $franja->nombre ?? 'Recreo' }}
                                            </div>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }}
                                                – {{ \Carbon\Carbon::parse($franja->hora_fin)->format('H:i') }}
                                            </small>
                                        </td>
                                        @foreach($dias as $diaKey => $diaNombre)
                                            <td class="text-center bg-warning bg-opacity-10">
                                                <span class="text-muted small fst-italic">Recreo</span>
                                            </td>
                                        @endforeach
                                    </tr>
                                @else
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-semibold">
                                                Franja {{ $franja->numero ?? '' }}
                                                @if($franja->nombre)
                                                    <span class="text-muted fw-normal">— {{ $franja->nombre }}</span>
                                                @endif
                                            </div>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }}
                                                – {{ \Carbon\Carbon::parse($franja->hora_fin)->format('H:i') }}
                                            </small>
                                        </td>
                                        @foreach($dias as $diaKey => $diaNombre)
                                            @php
                                                $key       = "{$diaKey}_{$franja->id}";
                                                $isChecked = $disponibilidad[$key]->disponible ?? true;
                                            @endphp
                                            <td class="text-center">
                                                <div class="form-check d-flex justify-content-center mb-0">
                                                    <input
                                                        class="form-check-input disponibilidad-check"
                                                        type="checkbox"
                                                        name="disponible[{{ $key }}]"
                                                        value="1"
                                                        data-dia="{{ $diaKey }}"
                                                        data-franja="{{ $franja->id }}"
                                                        {{ $isChecked ? 'checked' : '' }}
                                                        title="{{ $diaNombre }} – {{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }}"
                                                    >
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-light d-flex align-items-center justify-content-between">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Casilla marcada = el docente está disponible en ese horario.
                    </small>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-floppy me-1"></i>Guardar Disponibilidad
                    </button>
                </div>
            </form>
        </div>
    </div>

@elseif($docenteId && $franjas->isEmpty())
    <div class="alert alert-warning d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill"></i>
        No hay franjas horarias activas configuradas. Ve a
        <a href="{{ route('admin.horarios.franjas') }}" class="alert-link ms-1">Franjas Horarias</a>
        para crearlas.
    </div>

@else
    <div class="card shadow-sm border-0">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-person-check display-4 d-block mb-3" style="color: var(--primary); opacity: .4;"></i>
            <p class="mb-0 fs-5">Selecciona un docente para ver y editar su disponibilidad semanal.</p>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
    /**
     * Marca o desmarca todos los checkboxes de disponibilidad.
     * @param {boolean} estado - true = marcar, false = desmarcar
     */
    function toggleTodos(estado) {
        document.querySelectorAll('.disponibilidad-check').forEach(function(cb) {
            cb.checked = estado;
        });
    }

    /**
     * Marca o desmarca todos los checkboxes de una columna (día).
     * @param {string} dia   - Clave del día (lunes, martes, ...)
     * @param {boolean} estado
     */
    function toggleColumna(dia, estado) {
        document.querySelectorAll('.disponibilidad-check[data-dia="' + dia + '"]').forEach(function(cb) {
            cb.checked = estado;
        });
    }

    // Feedback visual al cambiar checkbox
    document.querySelectorAll('.disponibilidad-check').forEach(function(cb) {
        cb.addEventListener('change', function() {
            const cell = this.closest('td');
            if (this.checked) {
                cell.classList.remove('table-danger');
            } else {
                cell.classList.add('table-danger');
            }
        });

        // Estado inicial
        if (!cb.checked) {
            cb.closest('td').classList.add('table-danger');
        }
    });
</script>
@endpush
