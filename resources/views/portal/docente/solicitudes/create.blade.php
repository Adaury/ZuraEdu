@extends('layouts.portal')
@section('page-title', 'Nueva Solicitud')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'solicitudes'])
@endsection

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('portal.docente.solicitudes.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <h6 class="fw-bold mb-0 ms-1">Nueva Solicitud</h6>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body px-4 py-4">
                @if($errors->any())
                <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.85rem;">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('portal.docente.solicitudes.store') }}"
                      enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">
                            Tipo de solicitud <span class="text-danger">*</span>
                        </label>
                        <select name="tipo" class="form-select form-select-sm @error('tipo') is-invalid @enderror">
                            <option value="">— Seleccionar tipo —</option>
                            @foreach($tipos as $val => $label)
                            <option value="{{ $val }}" {{ old('tipo') === $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                        @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">
                            Asunto <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="asunto" value="{{ old('asunto') }}"
                               class="form-control form-control-sm @error('asunto') is-invalid @enderror"
                               placeholder="Breve descripción de tu solicitud" maxlength="200">
                        @error('asunto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3 mb-3" id="fechas">
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Fecha inicio</label>
                            <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio') }}"
                                   class="form-control form-control-sm @error('fecha_inicio') is-invalid @enderror">
                            @error('fecha_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Fecha fin</label>
                            <input type="date" name="fecha_fin" value="{{ old('fecha_fin') }}"
                                   class="form-control form-control-sm @error('fecha_fin') is-invalid @enderror">
                            @error('fecha_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">
                            Descripción / Justificación <span class="text-danger">*</span>
                        </label>
                        <textarea name="descripcion" rows="5"
                                  class="form-control form-control-sm @error('descripcion') is-invalid @enderror"
                                  placeholder="Explica los detalles de tu solicitud...">{{ old('descripcion') }}</textarea>
                        @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">
                            Documento de apoyo (opcional)
                        </label>
                        <input type="file" name="adjunto"
                               class="form-control form-control-sm @error('adjunto') is-invalid @enderror"
                               accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        <div class="form-text" style="font-size:.72rem;">PDF, Word o imagen — máx. 4 MB.</div>
                        @error('adjunto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-send me-1"></i>Enviar Solicitud
                        </button>
                        <a href="{{ route('portal.docente.solicitudes.index') }}" class="btn btn-outline-secondary btn-sm">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm bg-light">
            <div class="card-body px-4 py-3">
                <p class="fw-semibold mb-2" style="font-size:.82rem;"><i class="bi bi-info-circle me-1 text-primary"></i>Información</p>
                <ul class="mb-0 ps-3" style="font-size:.8rem;color:#475569;line-height:1.8;">
                    <li>Las solicitudes son revisadas por la dirección.</li>
                    <li>Recibirás una notificación cuando haya respuesta.</li>
                    <li>Para permisos y licencias, indica las fechas exactas.</li>
                    <li>Adjunta el documento médico si es licencia por enfermedad.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
