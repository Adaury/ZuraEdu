@extends('layouts.portal')
@section('page-title', 'Redactar Mensaje')
@section('portal-name', 'Portal Representante')

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'mensajes'])
@endsection

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('portal.padre.mensajes.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <h6 class="fw-bold mb-0 ms-1">Redactar mensaje</h6>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body px-4 py-4">
                <p class="text-muted mb-3" style="font-size:.82rem;">
                    <i class="bi bi-info-circle me-1"></i>
                    Puedes contactar al personal administrativo y docentes de la institución.
                </p>

                @if($errors->any())
                <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.85rem;">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('portal.padre.mensajes.store') }}">
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
                        <a href="{{ route('portal.padre.mensajes.index') }}" class="btn btn-outline-secondary btn-sm">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
