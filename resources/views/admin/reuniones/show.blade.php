@extends('layouts.admin')
@section('page-title', 'Detalle de Reunión')

@section('content')

{{-- Encabezado --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-journal-text me-2"></i>{{ $reunion->titulo }}
        </h4>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">
            {{ $reunion->tipoLabel() }} &bull;
            <span class="badge {{ $reunion->estadoBadgeClass() }}">{{ $reunion->estadoLabel() }}</span>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.reuniones.acta_pdf', $reunion) }}" target="_blank"
           class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>Descargar Acta PDF
        </a>
        <a href="{{ route('admin.reuniones.edit', $reunion) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil-fill me-1"></i>Editar
        </a>
        <a href="{{ route('admin.reuniones.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" style="border-radius:10px;" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-3">
    {{-- Panel izquierdo: datos de la reunión --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header py-2 px-3 border-bottom" style="background:var(--primary);color:#fff;">
                <i class="bi bi-info-circle me-2"></i><strong>Datos de la Reunión</strong>
            </div>
            <div class="card-body p-3" style="font-size:.85rem;">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-semibold">Tipo</dt>
                    <dd class="col-7">{{ $reunion->tipoLabel() }}</dd>

                    <dt class="col-5 text-muted fw-semibold">Estado</dt>
                    <dd class="col-7">
                        <span class="badge {{ $reunion->estadoBadgeClass() }}">{{ $reunion->estadoLabel() }}</span>
                    </dd>

                    <dt class="col-5 text-muted fw-semibold">Fecha</dt>
                    <dd class="col-7">{{ $reunion->fecha->format('d/m/Y') }} a las {{ $reunion->fecha->format('H:i') }}</dd>

                    <dt class="col-5 text-muted fw-semibold">Lugar</dt>
                    <dd class="col-7">{{ $reunion->lugar ?: '—' }}</dd>

                    <dt class="col-5 text-muted fw-semibold">Convocante</dt>
                    <dd class="col-7">{{ $reunion->convocante?->name ?? '—' }}</dd>
                </dl>

                @if($reunion->agenda)
                <hr class="my-2">
                <p class="fw-semibold text-muted mb-1" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">
                    Agenda
                </p>
                <div style="white-space:pre-line;font-size:.84rem;">{{ $reunion->agenda }}</div>
                @endif

                @if($reunion->participantes)
                <hr class="my-2">
                <p class="fw-semibold text-muted mb-1" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">
                    Participantes
                </p>
                <div style="white-space:pre-line;font-size:.84rem;">{{ $reunion->participantes }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Panel derecho: acuerdos --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 px-3 border-bottom d-flex align-items-center justify-content-between"
                 style="background:var(--primary);color:#fff;">
                <span><i class="bi bi-check2-square me-2"></i><strong>Acuerdos</strong></span>
                <span class="badge bg-light text-dark" style="font-size:.75rem;">
                    {{ $reunion->acuerdos->count() }} registrados
                </span>
            </div>
            <div class="card-body p-0">
                {{-- Lista de acuerdos --}}
                @forelse($reunion->acuerdos as $acuerdo)
                <div class="px-3 py-2 border-bottom d-flex align-items-start gap-2"
                     style="font-size:.84rem;{{ $acuerdo->cumplido ? 'background:#f0fdf4;' : '' }}">

                    {{-- Toggle cumplido --}}
                    <form method="POST"
                          action="{{ route('admin.reuniones.acuerdos.toggle', $acuerdo) }}"
                          class="mt-1">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent"
                                title="{{ $acuerdo->cumplido ? 'Marcar pendiente' : 'Marcar cumplido' }}">
                            <i class="bi {{ $acuerdo->cumplido ? 'bi-check-circle-fill text-success' : 'bi-circle text-secondary' }} fs-5"></i>
                        </button>
                    </form>

                    {{-- Contenido --}}
                    <div class="flex-grow-1">
                        <p class="mb-0 {{ $acuerdo->cumplido ? 'text-decoration-line-through text-muted' : '' }}">
                            {{ $acuerdo->descripcion }}
                        </p>
                        <div class="d-flex gap-3 mt-1" style="font-size:.77rem;color:#64748b;">
                            @if($acuerdo->responsable)
                            <span><i class="bi bi-person me-1"></i>{{ $acuerdo->responsable }}</span>
                            @endif
                            @if($acuerdo->fecha_limite)
                            <span class="{{ now()->gt($acuerdo->fecha_limite) && !$acuerdo->cumplido ? 'text-danger fw-semibold' : '' }}">
                                <i class="bi bi-calendar-event me-1"></i>
                                Límite: {{ $acuerdo->fecha_limite->format('d/m/Y') }}
                            </span>
                            @endif
                            @if($acuerdo->cumplido)
                            <span class="text-success fw-semibold"><i class="bi bi-check-all me-1"></i>Cumplido</span>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4" style="font-size:.85rem;">
                    <i class="bi bi-clipboard2-x fs-3 d-block mb-2"></i>
                    No hay acuerdos registrados aún.
                </div>
                @endforelse

                {{-- Formulario para agregar acuerdo --}}
                <div class="p-3 border-top bg-light">
                    <p class="fw-semibold mb-2" style="font-size:.82rem;text-transform:uppercase;
                       letter-spacing:.04em;color:#475569;">
                        <i class="bi bi-plus-circle me-1"></i>Agregar Acuerdo
                    </p>
                    <form method="POST" action="{{ route('admin.reuniones.acuerdos.store', $reunion) }}">
                        @csrf
                        <div class="row g-2">
                            <div class="col-12">
                                <textarea name="descripcion" rows="2"
                                          class="form-control form-control-sm @error('descripcion') is-invalid @enderror"
                                          placeholder="Descripción del acuerdo…" required>{{ old('descripcion') }}</textarea>
                                @error('descripcion')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-sm-6">
                                <input type="text" name="responsable"
                                       class="form-control form-control-sm"
                                       value="{{ old('responsable') }}"
                                       placeholder="Responsable (opcional)">
                            </div>
                            <div class="col-sm-4">
                                <input type="date" name="fecha_limite"
                                       class="form-control form-control-sm"
                                       value="{{ old('fecha_limite') }}"
                                       title="Fecha límite (opcional)">
                            </div>
                            <div class="col-sm-2 d-flex">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>{{-- /card-body --}}
        </div>{{-- /card acuerdos --}}
    </div>
</div>
@endsection
