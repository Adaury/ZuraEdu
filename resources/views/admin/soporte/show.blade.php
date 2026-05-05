@extends('layouts.admin')
@section('page-title', 'Ticket #' . $soporte->id)

@push('styles')
<style>
/* Hilo de conversación */
.chat-bubble {
    max-width: 78%;
    padding: .65rem 1rem;
    border-radius: 1rem;
    font-size: .875rem;
    line-height: 1.5;
    word-break: break-word;
    position: relative;
}
.bubble-left {
    background: #f1f5f9;
    color: #1e293b;
    border-bottom-left-radius: .25rem;
}
.bubble-right {
    background: #1e3a6e;
    color: #fff;
    border-bottom-right-radius: .25rem;
}
.bubble-admin {
    background: #eff6ff;
    color: #1d4ed8;
    border-bottom-left-radius: .25rem;
}
.chat-meta { font-size: .7rem; margin-top: .25rem; }
.avatar-sm {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .78rem; font-weight: 800; flex-shrink: 0;
}
.ticket-info-row { font-size: .82rem; }
.badge-prioridad, .badge-estado {
    display: inline-flex; align-items: center;
    padding: .22rem .65rem; border-radius: 9999px;
    font-size: .72rem; font-weight: 700;
    letter-spacing: .02em; text-transform: uppercase;
}
.section-divider { border-color: #e2e8f0; }
</style>
@endpush

@section('content')

{{-- Cabecera --}}
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="{{ route('admin.soporte.index') }}" class="text-muted text-decoration-none small">
                <i class="bi bi-arrow-left"></i> Soporte
            </a>
            <span class="text-muted small">/</span>
            <span class="text-muted small">Ticket #{{ $soporte->id }}</span>
        </div>
        <h4 class="fw-bold mb-0" style="color:var(--primary,#1e3a6e);">
            {{ $soporte->titulo }}
        </h4>
    </div>
    <a href="{{ route('admin.soporte.create') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Ticket
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show py-2">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-4">

    {{-- Panel izquierdo: hilo + responder --}}
    <div class="col-12 col-lg-8">

        {{-- Descripción original --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-2">
                <div class="avatar-sm" style="background:#1e3a6e;color:#fff;">
                    {{ strtoupper(substr($soporte->solicitante?->name ?? 'U', 0, 1)) }}
                </div>
                <div>
                    <div class="fw-semibold small">{{ $soporte->solicitante?->nombre_completo ?? 'Usuario' }}</div>
                    <div class="text-muted" style="font-size:.68rem;">Abrió el ticket — {{ $soporte->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <div class="ms-auto d-flex gap-2">
                    <span class="badge-prioridad {{ $soporte->color_prioridad }}">{{ $soporte->prioridad_nombre }}</span>
                    <span class="badge-estado {{ $soporte->color_estado }}">{{ $soporte->estado_nombre }}</span>
                </div>
            </div>
            <div class="card-body">
                <p class="mb-0" style="white-space:pre-wrap;font-size:.875rem;">{{ $soporte->descripcion }}</p>
            </div>
        </div>

        {{-- Hilo de respuestas --}}
        @if($soporte->respuestas->isNotEmpty())
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2">
                <span class="fw-semibold small"><i class="bi bi-chat-dots me-1"></i>Respuestas ({{ $soporte->respuestas->count() }})</span>
            </div>
            <div class="card-body p-3 d-flex flex-column gap-3">
                @foreach($soporte->respuestas as $resp)
                @php
                    $esMio = $resp->user_id === auth()->id();
                    $esAdminResp = $resp->user?->hasAnyRole(['Administrador','Director','Super Admin']);
                @endphp
                <div class="d-flex gap-2 {{ $esMio ? 'flex-row-reverse' : '' }}">
                    {{-- Avatar --}}
                    <div class="avatar-sm flex-shrink-0"
                         style="background:{{ $esMio ? '#1e3a6e' : ($esAdminResp ? '#3b82f6' : '#64748b') }};color:#fff;">
                        {{ strtoupper(substr($resp->user?->name ?? 'U', 0, 1)) }}
                    </div>
                    {{-- Burbuja --}}
                    <div class="{{ $esMio ? 'align-items-end' : 'align-items-start' }} d-flex flex-column">
                        <div class="chat-bubble {{ $esMio ? 'bubble-right' : ($esAdminResp ? 'bubble-admin' : 'bubble-left') }}">
                            {{ $resp->mensaje }}
                        </div>
                        <div class="chat-meta text-muted {{ $esMio ? 'text-end' : '' }}">
                            <span class="fw-semibold">{{ $resp->user?->nombre_completo ?? 'Usuario' }}</span>
                            @if($esAdminResp && !$esMio)
                            <span class="badge bg-primary ms-1" style="font-size:.6rem;">Soporte</span>
                            @endif
                            &middot; {{ $resp->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Formulario responder --}}
        @if(!in_array($soporte->estado, ['cerrado']) || $esAdmin)
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2">
                <span class="fw-semibold small"><i class="bi bi-reply me-1"></i>Agregar respuesta</span>
            </div>
            <div class="card-body p-3">
                @if(in_array($soporte->estado, ['resuelto', 'cerrado']) && !$esAdmin)
                <div class="alert alert-warning py-2 small mb-0">
                    <i class="bi bi-lock me-1"></i>Este ticket está {{ $soporte->estado_nombre }}. No puedes agregar respuestas.
                </div>
                @else
                <form action="{{ route('admin.soporte.responder', $soporte) }}" method="POST">
                    @csrf
                    <textarea name="mensaje"
                              rows="4"
                              placeholder="Escribe tu respuesta..."
                              class="form-control @error('mensaje') is-invalid @enderror mb-3"
                              maxlength="5000">{{ old('mensaje') }}</textarea>
                    @error('mensaje')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-send me-1"></i>Enviar respuesta
                        </button>
                    </div>
                </form>
                @endif
            </div>
        </div>
        @endif

    </div>

    {{-- Panel derecho: info + acciones --}}
    <div class="col-12 col-lg-4">

        {{-- Información del ticket --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2">
                <span class="fw-semibold small"><i class="bi bi-info-circle me-1"></i>Información</span>
            </div>
            <div class="card-body p-3">
                <dl class="ticket-info-row row g-2 mb-0">
                    <dt class="col-5 text-muted">ID</dt>
                    <dd class="col-7 mb-0 fw-semibold">#{{ $soporte->id }}</dd>

                    <dt class="col-5 text-muted">Estado</dt>
                    <dd class="col-7 mb-0">
                        <span class="badge-estado {{ $soporte->color_estado }}">{{ $soporte->estado_nombre }}</span>
                    </dd>

                    <dt class="col-5 text-muted">Prioridad</dt>
                    <dd class="col-7 mb-0">
                        <span class="badge-prioridad {{ $soporte->color_prioridad }}">{{ $soporte->prioridad_nombre }}</span>
                    </dd>

                    <dt class="col-5 text-muted">Categoría</dt>
                    <dd class="col-7 mb-0">{{ $soporte->categoria_nombre }}</dd>

                    <dt class="col-5 text-muted">Solicitante</dt>
                    <dd class="col-7 mb-0">{{ $soporte->solicitante?->nombre_completo ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Asignado</dt>
                    <dd class="col-7 mb-0">{{ $soporte->asignadoA?->nombre_completo ?? 'Sin asignar' }}</dd>

                    <dt class="col-5 text-muted">Apertura</dt>
                    <dd class="col-7 mb-0">{{ $soporte->created_at->format('d/m/Y H:i') }}</dd>

                    <dt class="col-5 text-muted">Última act.</dt>
                    <dd class="col-7 mb-0">{{ $soporte->updated_at->diffForHumans() }}</dd>
                </dl>
            </div>
        </div>

        {{-- Cambiar estado (admin o solicitante cerrando resuelto) --}}
        @php
            $puedeAdmin  = $esAdmin;
            $puedeCerrar = !$esAdmin && $soporte->solicitante_id === auth()->id() && $soporte->estado === 'resuelto';
        @endphp
        @if($puedeAdmin || $puedeCerrar)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2">
                <span class="fw-semibold small"><i class="bi bi-arrow-repeat me-1"></i>Cambiar estado</span>
            </div>
            <div class="card-body p-3">
                <form action="{{ route('admin.soporte.estado', $soporte) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <select name="estado" class="form-select form-select-sm mb-2">
                        @foreach($estados as $val => $label)
                        @if($puedeAdmin || $val === 'cerrado')
                        <option value="{{ $val }}" {{ $soporte->estado === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endif
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-check2 me-1"></i>Actualizar estado
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Asignar (solo admin) --}}
        @if($esAdmin)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2">
                <span class="fw-semibold small"><i class="bi bi-person-check me-1"></i>Asignar a</span>
            </div>
            <div class="card-body p-3">
                <form action="{{ route('admin.soporte.asignar', $soporte) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <select name="asignado_a_id" class="form-select form-select-sm mb-2">
                        <option value="">Sin asignar</option>
                        @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" {{ $soporte->asignado_a_id == $admin->id ? 'selected' : '' }}>
                            {{ $admin->nombre_completo }}
                        </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="bi bi-person-plus me-1"></i>Guardar asignación
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Acción: confirmar resolución (solicitante) --}}
        @if(!$esAdmin && $soporte->solicitante_id === auth()->id() && $soporte->estado === 'resuelto')
        <div class="alert alert-success py-2 small">
            <i class="bi bi-check2-circle me-1"></i>
            Este ticket fue marcado como <strong>resuelto</strong>. ¿El problema fue solucionado?
            Puedes cerrarlo con el panel de arriba o agregar una respuesta si aún persiste.
        </div>
        @endif

    </div>

</div>

@endsection
