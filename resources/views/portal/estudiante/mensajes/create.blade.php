@extends('layouts.portal-estudiante')
@section('title', 'Redactar Mensaje')
@section('activeKey', 'mensajes')

@section('content')
<div class="prt-page-header">
    <div>
        <h4 class="prt-page-title"><i class="bi bi-pencil-square me-2"></i>Redactar Mensaje</h4>
        <p class="prt-page-subtitle">Envía un mensaje a tu docente o coordinación</p>
    </div>
    <a href="{{ route('portal.estudiante.mensajes.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
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

                <form method="POST" action="{{ route('portal.estudiante.mensajes.store') }}">
                    @csrf

                    @if($replyTo)
                    <div class="alert alert-light border mb-3 py-2 px-3" style="font-size:.82rem;">
                        <i class="bi bi-reply me-1 text-muted"></i>
                        Respondiendo a: <strong>{{ $replyTo->asunto }}</strong>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">
                            Destinatario <span class="text-danger">*</span>
                        </label>
                        <select name="destinatarios[]" class="form-select form-select-sm @error('destinatarios') is-invalid @enderror">
                            <option value="">— Seleccionar —</option>
                            @foreach($destinatarios as $u)
                            <option value="{{ $u->id }}" {{ in_array($u->id, old('destinatarios', [])) ? 'selected' : '' }}>
                                {{ $u->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('destinatarios')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">
                            Asunto <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="asunto"
                               value="{{ old('asunto', $replyTo ? 'Re: ' . $replyTo->asunto : '') }}"
                               class="form-control form-control-sm @error('asunto') is-invalid @enderror"
                               maxlength="255">
                        @error('asunto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">
                            Mensaje <span class="text-danger">*</span>
                        </label>
                        <textarea name="cuerpo" rows="7"
                                  class="form-control form-control-sm @error('cuerpo') is-invalid @enderror">{{ old('cuerpo') }}</textarea>
                        @error('cuerpo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-send me-1"></i>Enviar
                        </button>
                        <a href="{{ route('portal.estudiante.mensajes.index') }}" class="btn btn-outline-secondary btn-sm">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body px-4 py-3">
                <div class="fw-semibold mb-2" style="font-size:.82rem;color:#374151;">
                    <i class="bi bi-info-circle me-1 text-primary"></i>Información
                </div>
                <p class="text-muted mb-0" style="font-size:.8rem;">
                    Puedes enviar mensajes a tus docentes, coordinadores o dirección.
                    Recibirás una respuesta en tu bandeja de entrada.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
