@extends('layouts.admin')
@section('page-title', 'Solicitud — ' . $solicitud->asunto)

@section('content')
<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <a href="{{ route('admin.solicitudes.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <h4 class="fw-bold mb-0" style="color:#1e3a6e;">Solicitud #{{ $solicitud->id }}</h4>
    @php $ec = $solicitud->estado_config; @endphp
    <span style="background:{{ $ec['bg'] }};color:{{ $ec['color'] }};border:1px solid {{ $ec['color'] }}44;border-radius:99px;font-size:.72rem;font-weight:700;padding:.3rem .9rem;">
        {{ $ec['label'] }}
    </span>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:.83rem;border-radius:10px;">
    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
</div>
@endif

<div class="row g-3">

{{-- Detalles --}}
<div class="col-lg-7">
    <div class="card border-0 shadow-sm mb-3" style="border-radius:14px;">
        <div class="card-body p-4">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:.75rem;">
                <i class="bi bi-info-circle me-1"></i>Datos de la Solicitud
            </div>
            <table class="table table-sm table-borderless mb-0" style="font-size:.85rem;">
                <tr>
                    <td class="text-muted fw-600" style="width:35%;padding:.4rem 0;">Tipo</td>
                    <td style="padding:.4rem 0;"><span class="badge bg-light text-dark">{{ $solicitud->tipo_label }}</span></td>
                </tr>
                <tr>
                    <td class="text-muted fw-600" style="padding:.4rem 0;">Asunto</td>
                    <td style="padding:.4rem 0;font-weight:700;">{{ $solicitud->asunto }}</td>
                </tr>
                @if($solicitud->fecha_evento)
                <tr>
                    <td class="text-muted fw-600" style="padding:.4rem 0;">Fecha evento</td>
                    <td style="padding:.4rem 0;">{{ $solicitud->fecha_evento->format('d/m/Y') }}</td>
                </tr>
                @endif
                <tr>
                    <td class="text-muted fw-600" style="padding:.4rem 0;">Enviada</td>
                    <td style="padding:.4rem 0;">{{ $solicitud->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @if($solicitud->estudiante)
                <tr>
                    <td class="text-muted fw-600" style="padding:.4rem 0;">Estudiante</td>
                    <td style="padding:.4rem 0;">{{ $solicitud->estudiante->nombre_completo }}</td>
                </tr>
                @endif
            </table>

            <hr style="margin:1rem 0;">
            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:.65rem;">Descripción</div>
            <p style="font-size:.88rem;color:#374151;white-space:pre-wrap;margin:0;">{{ $solicitud->descripcion }}</p>

            @if($solicitud->adjunto)
            <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid #f1f5f9;">
                <a href="{{ Storage::url($solicitud->adjunto) }}" target="_blank"
                   class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-paperclip me-1"></i>Ver documento adjunto
                </a>
            </div>
            @endif
        </div>
    </div>

    {{-- Respuesta existente --}}
    @if($solicitud->respuesta)
    <div class="card border-0 shadow-sm mb-3" style="border-radius:14px;border-left:4px solid {{ $ec['color'] }} !important;">
        <div class="card-body p-4">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:.65rem;">
                <i class="bi bi-reply-fill me-1"></i>Respuesta enviada
            </div>
            <p style="font-size:.88rem;color:#374151;white-space:pre-wrap;margin-bottom:.75rem;">{{ $solicitud->respuesta }}</p>
            <div style="font-size:.76rem;color:#9ca3af;">
                Por {{ $solicitud->respondidoPor?->name ?? '—' }} · {{ $solicitud->respondido_en?->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>
    @endif

    {{-- Formulario de respuesta --}}
    <div class="card border-0 shadow-sm" style="border-radius:14px;">
        <div class="card-body p-4">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:.9rem;">
                <i class="bi bi-pencil-fill me-1"></i>{{ $solicitud->respuesta ? 'Actualizar respuesta' : 'Responder solicitud' }}
            </div>
            <form method="POST" action="{{ route('admin.solicitudes.responder', $solicitud) }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" style="font-size:.82rem;font-weight:600;">Estado</label>
                    <div class="d-flex gap-2 flex-wrap">
                        @foreach(['en_proceso' => 'En proceso', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada'] as $key => $lbl)
                        <label class="d-flex align-items-center gap-1" style="cursor:pointer;">
                            <input type="radio" name="estado" value="{{ $key }}"
                                   {{ (old('estado', $solicitud->estado) === $key) ? 'checked' : '' }}>
                            <span style="font-size:.82rem;font-weight:600;color:{{ $estados[$key]['color'] ?? '#374151' }};">{{ $lbl }}</span>
                        </label>
                        @endforeach
                    </div>
                    @error('estado')<div class="text-danger" style="font-size:.76rem;">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:.82rem;font-weight:600;">Respuesta al representante</label>
                    <textarea name="respuesta" rows="4" class="form-control form-control-sm"
                              placeholder="Escribe aquí la respuesta que recibirá el representante…">{{ old('respuesta', $solicitud->respuesta) }}</textarea>
                    @error('respuesta')<div class="text-danger" style="font-size:.76rem;">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="bi bi-send me-1"></i>Enviar respuesta y notificar
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Panel del representante --}}
<div class="col-lg-5">
    <div class="card border-0 shadow-sm mb-3" style="border-radius:14px;">
        <div class="card-body p-4">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:.75rem;">
                <i class="bi bi-person-fill me-1"></i>Representante
            </div>
            @php $rep = $solicitud->representante; @endphp
            <div class="fw-bold" style="font-size:.95rem;color:#1e293b;">{{ $rep?->nombre_completo ?? '—' }}</div>
            <div style="font-size:.8rem;color:#64748b;margin-top:.35rem;">
                @if($rep?->email)<div><i class="bi bi-envelope me-1"></i>{{ $rep->email }}</div>@endif
                @if($rep?->telefono)<div><i class="bi bi-telephone me-1"></i>{{ $rep->telefono }}</div>@endif
                @if($rep?->cedula)<div><i class="bi bi-person-badge me-1"></i>{{ $rep->cedula }}</div>@endif
            </div>
        </div>
    </div>

    @if($solicitud->estudiante)
    <div class="card border-0 shadow-sm" style="border-radius:14px;">
        <div class="card-body p-4">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:.75rem;">
                <i class="bi bi-mortarboard-fill me-1"></i>Estudiante
            </div>
            <div class="fw-bold" style="font-size:.9rem;color:#1e293b;">{{ $solicitud->estudiante->nombre_completo }}</div>
            <a href="{{ route('admin.estudiantes.show', $solicitud->estudiante) }}"
               class="btn btn-sm btn-outline-primary mt-2" style="font-size:.76rem;">
                Ver expediente
            </a>
        </div>
    </div>
    @endif
</div>

</div>
@endsection
