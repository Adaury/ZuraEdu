@extends('layouts.admin')

@section('page-title', 'Detalle Pre-matrícula')

@push('styles')
<style>
    .detail-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; margin-bottom: 1.25rem; }
    .detail-header { padding: 1.1rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: .6rem; }
    .detail-header i { font-size: 1rem; }
    .detail-header span { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #2563eb; }
    .detail-body { padding: 1.25rem 1.5rem; }
    .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .75rem 1.5rem; }
    @media(max-width:600px){ .info-grid { grid-template-columns: 1fr; } }
    .info-item label { font-size: .73rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; display: block; margin-bottom: .2rem; }
    .info-item span  { font-size: .9rem; color: #0f172a; font-weight: 500; }

    .badge-pendiente  { background: #fef9c3; color: #854d0e; font-size: .8rem; font-weight: 700; padding: .3rem .85rem; border-radius: 20px; }
    .badge-aprobada   { background: #d1fae5; color: #065f46; font-size: .8rem; font-weight: 700; padding: .3rem .85rem; border-radius: 20px; }
    .badge-rechazada  { background: #fee2e2; color: #991b1b; font-size: .8rem; font-weight: 700; padding: .3rem .85rem; border-radius: 20px; }

    .action-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; }
    .action-header { padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9; }
    .action-header h6 { margin: 0; font-weight: 700; font-size: .88rem; }
    .action-body { padding: 1.25rem 1.5rem; }

    [data-theme="dark"] .detail-card,
    [data-theme="dark"] .action-card { background: #1e293b !important; border-color: #334155 !important; }
    [data-theme="dark"] .detail-header,
    [data-theme="dark"] .action-header { border-color: #334155 !important; }
    [data-theme="dark"] .info-item span { color: #e2e8f0 !important; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <a href="{{ route('admin.pre-matriculas.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
    <h5 class="fw-bold mb-0">
        <i class="bi bi-person-lines-fill text-primary me-1"></i>
        Pre-matrícula #{{ $preMatricula->id }}
    </h5>
    @if($preMatricula->estado === 'pendiente')
        <span class="badge-pendiente ms-1"><i class="bi bi-clock-fill me-1"></i>Pendiente</span>
    @elseif($preMatricula->estado === 'aprobada')
        <span class="badge-aprobada ms-1"><i class="bi bi-check-circle-fill me-1"></i>Aprobada</span>
    @else
        <span class="badge-rechazada ms-1"><i class="bi bi-x-circle-fill me-1"></i>Rechazada</span>
    @endif
</div>

{{-- Flash --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <span>{{ session('success') }}</span>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-3">
    {{-- Columna izquierda: datos --}}
    <div class="col-lg-7">

        {{-- Datos del Estudiante --}}
        <div class="detail-card">
            <div class="detail-header">
                <i class="bi bi-person-fill text-primary"></i>
                <span>Datos del Estudiante</span>
            </div>
            <div class="detail-body">
                {{-- Código de seguimiento --}}
                @if($preMatricula->codigo)
                <div style="background:#eff6ff;border:1.5px solid #93c5fd;border-radius:10px;padding:.75rem 1rem;margin-bottom:1rem;display:flex;align-items:center;gap:.6rem;">
                    <i class="bi bi-key-fill" style="color:#1d4ed8;"></i>
                    <div>
                        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#1e40af;">Código de seguimiento</div>
                        <div style="font-size:1.05rem;font-weight:900;letter-spacing:.1em;color:#1e3a8a;font-family:monospace;">{{ $preMatricula->codigo }}</div>
                    </div>
                </div>
                @endif
                <div class="info-grid">
                    <div class="info-item">
                        <label>Nombres</label>
                        <span>{{ $preMatricula->nombres }}</span>
                    </div>
                    <div class="info-item">
                        <label>Apellidos</label>
                        <span>{{ $preMatricula->apellidos }}</span>
                    </div>
                    <div class="info-item">
                        <label>Fecha de Nacimiento</label>
                        <span>{{ $preMatricula->fecha_nacimiento->format('d/m/Y') }}</span>
                    </div>
                    <div class="info-item">
                        <label>Género</label>
                        <span>{{ $preMatricula->genero ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <label>Lugar de Nacimiento</label>
                        <span>{{ $preMatricula->lugar_nacimiento ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <label>Cédula del Estudiante</label>
                        <span>{{ $preMatricula->cedula_estudiante ?? '—' }}</span>
                    </div>
                    <div class="info-item" style="grid-column:1/-1;">
                        <label>Grado Solicitado</label>
                        <span style="background:#eef2ff;color:#4f46e5;border-radius:6px;padding:.2rem .6rem;font-weight:700;">
                            {{ $preMatricula->grado_solicitado }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Datos del Representante --}}
        <div class="detail-card">
            <div class="detail-header">
                <i class="bi bi-people-fill text-primary"></i>
                <span>Datos del Representante</span>
            </div>
            <div class="detail-body">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Nombre Completo</label>
                        <span>{{ $preMatricula->nombre_representante }}</span>
                    </div>
                    <div class="info-item">
                        <label>Relación</label>
                        <span>{{ $preMatricula->relacion_representante ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <label>Cédula</label>
                        <span>{{ $preMatricula->cedula_representante }}</span>
                    </div>
                    <div class="info-item">
                        <label>Teléfono</label>
                        <span>{{ $preMatricula->telefono }}</span>
                    </div>
                    <div class="info-item" style="grid-column:1/-1;">
                        <label>Correo Electrónico</label>
                        <span>{{ $preMatricula->email }}</span>
                    </div>
                </div>
                <div class="info-grid mt-3">
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <label>Dirección</label>
                        <span>{{ $preMatricula->direccion }}</span>
                    </div>
                </div>
                {{-- Documentos adjuntos --}}
                @if($preMatricula->documentos)
                <div class="mt-3">
                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.5rem;">Documentos Adjuntos</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($preMatricula->documentos as $key => $path)
                        <a href="{{ Storage::url($path) }}" target="_blank"
                           style="display:inline-flex;align-items:center;gap:.35rem;background:#d1fae5;color:#065f46;border-radius:8px;padding:.35rem .75rem;font-size:.78rem;font-weight:700;text-decoration:none;"
                           title="{{ $path }}">
                            <i class="bi bi-file-earmark-check-fill"></i>
                            {{ match($key) {
                                'cedula_representante' => 'Cédula Rep.',
                                'acta_nacimiento'      => 'Acta Nacimiento',
                                'foto_estudiante'      => 'Foto Estudiante',
                                default => $key
                            } }}
                            <i class="bi bi-box-arrow-up-right" style="font-size:.65rem;"></i>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Notas de admin (si existen) --}}
        @if($preMatricula->notas_admin)
        <div class="detail-card">
            <div class="detail-header">
                <i class="bi bi-chat-left-text-fill text-secondary"></i>
                <span style="color:#64748b;">Observaciones Administrativas</span>
            </div>
            <div class="detail-body">
                <p class="mb-0" style="font-size:.9rem;color:#374151;line-height:1.7;">{{ $preMatricula->notas_admin }}</p>
            </div>
        </div>
        @endif

    </div>

    {{-- Columna derecha: acciones --}}
    <div class="col-lg-5">

        {{-- Info solicitud --}}
        <div class="detail-card">
            <div class="detail-header">
                <i class="bi bi-info-circle-fill text-primary"></i>
                <span>Información de la Solicitud</span>
            </div>
            <div class="detail-body">
                <div class="info-grid" style="grid-template-columns:1fr;">
                    <div class="info-item">
                        <label>ID Solicitud</label>
                        <span>#{{ $preMatricula->id }}</span>
                    </div>
                    <div class="info-item">
                        <label>Fecha de Envío</label>
                        <span>{{ $preMatricula->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="info-item">
                        <label>Última Actualización</label>
                        <span>{{ $preMatricula->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="info-item">
                        <label>Estado Actual</label>
                        @if($preMatricula->estado === 'pendiente')
                            <span class="badge-pendiente"><i class="bi bi-clock-fill me-1"></i>Pendiente</span>
                        @elseif($preMatricula->estado === 'aprobada')
                            <span class="badge-aprobada"><i class="bi bi-check-circle-fill me-1"></i>Aprobada</span>
                        @else
                            <span class="badge-rechazada"><i class="bi bi-x-circle-fill me-1"></i>Rechazada</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Sección resolución (solo pendiente) --}}
        @if($preMatricula->estado === 'pendiente')
        <div class="action-card" id="resolver">
            <div class="action-header" style="background:#eff6ff;">
                <h6 class="text-primary"><i class="bi bi-check2-square me-1"></i> Resolver Solicitud</h6>
            </div>
            <div class="action-body">

                {{-- Aprobar --}}
                <form action="{{ route('admin.pre-matriculas.aprobar', $preMatricula) }}" method="POST" class="mb-3">
                    @csrf
                    <label class="form-label small fw-semibold mb-1">Notas (opcional para aprobación)</label>
                    <textarea name="notas_admin" rows="2" class="form-control form-control-sm mb-2"
                              placeholder="Indicaciones adicionales para la familia..."></textarea>
                    <button type="submit" class="btn btn-success w-100"
                            onclick="return confirm('¿Aprobar esta solicitud? Se enviará notificación al representante.')">
                        <i class="bi bi-check-circle-fill me-1"></i> Aprobar Solicitud
                    </button>
                </form>

                <hr class="my-3">

                {{-- Rechazar --}}
                <form action="{{ route('admin.pre-matriculas.rechazar', $preMatricula) }}" method="POST">
                    @csrf
                    <label class="form-label small fw-semibold mb-1">
                        Motivo del rechazo <span class="text-danger">*</span>
                    </label>
                    <textarea name="notas_admin" rows="2" class="form-control form-control-sm mb-2 @error('notas_admin') is-invalid @enderror"
                              placeholder="Indique el motivo del rechazo..." required></textarea>
                    @error('notas_admin')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <button type="submit" class="btn btn-outline-danger w-100"
                            onclick="return confirm('¿Rechazar esta solicitud? Se notificará al representante.')">
                        <i class="bi bi-x-circle-fill me-1"></i> Rechazar Solicitud
                    </button>
                </form>

            </div>
        </div>
        @endif

        {{-- Eliminar --}}
        <div class="mt-2 text-end">
            <form action="{{ route('admin.pre-matriculas.destroy', $preMatricula) }}" method="POST"
                  onsubmit="return confirm('¿Eliminar permanentemente esta solicitud? Esta acción no se puede deshacer.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash-fill me-1"></i> Eliminar solicitud
                </button>
            </form>
        </div>

    </div>
</div>

@endsection
