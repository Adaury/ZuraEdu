@extends('layouts.admin')
@section('page-title', 'Redactar Mensaje')

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.comunicaciones.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <h5 class="fw-bold mb-0 ms-1">Redactar mensaje</h5>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body px-4 py-4">
                @if($errors->any())
                <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.85rem;">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('admin.comunicaciones.store') }}"
                      enctype="multipart/form-data" id="msgForm">
                    @csrf

                    @if($replyTo)
                    <input type="hidden" name="reply_to_id" value="{{ $replyTo->id }}">
                    <div class="alert alert-light border mb-3 py-2 px-3" style="font-size:.82rem;">
                        <i class="bi bi-reply me-1 text-muted"></i>
                        Respondiendo a: <strong>{{ $replyTo->asunto }}</strong>
                        <span class="text-muted">— {{ $replyTo->remitente?->name }}</span>
                    </div>
                    @endif

                    {{-- Tipo de envío --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Tipo de envío</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_envio" id="tipoInd" value="individual" checked>
                                <label class="form-check-label" for="tipoInd" style="font-size:.85rem;">Individual / específico</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_envio" id="tipoGrp" value="circular">
                                <label class="form-check-label" for="tipoGrp" style="font-size:.85rem;">Circular (por rol)</label>
                            </div>
                        </div>
                    </div>

                    {{-- Destinatarios individuales --}}
                    <div class="mb-3" id="destIndividual">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">
                            Destinatarios <span class="text-danger">*</span>
                        </label>
                        <select name="destinatarios[]" class="form-select form-select-sm @error('destinatarios') is-invalid @enderror"
                                multiple size="6" style="font-size:.82rem;">
                            @foreach($usuarios as $rol => $grupo)
                            <optgroup label="{{ $rol }}">
                                @foreach($grupo as $u)
                                <option value="{{ $u->id }}"
                                    {{ in_array($u->id, old('destinatarios', [])) ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        <div class="form-text" style="font-size:.72rem;">Mantén Ctrl/Cmd para seleccionar múltiples.</div>
                        @error('destinatarios')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Destinatarios por grupo (circular) --}}
                    <div class="mb-3 d-none" id="destCircular">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Enviar a <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach(['todos' => 'Todos', 'todos_docentes' => 'Todos los Docentes', 'todos_padres' => 'Todos los Representantes', 'todos_estudiantes' => 'Todos los Estudiantes'] as $val => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="destinatarios_grupo[]"
                                       id="grp_{{ $val }}" value="{{ $val }}">
                                <label class="form-check-label" for="grp_{{ $val }}" style="font-size:.85rem;">{{ $label }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Asunto --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">
                            Asunto <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="asunto" value="{{ old('asunto', $replyTo ? 'Re: ' . $replyTo->asunto : '') }}"
                               class="form-control form-control-sm @error('asunto') is-invalid @enderror"
                               placeholder="Asunto del mensaje" maxlength="255">
                        @error('asunto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Cuerpo --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">
                            Mensaje <span class="text-danger">*</span>
                        </label>
                        <textarea name="cuerpo" rows="8"
                                  class="form-control form-control-sm @error('cuerpo') is-invalid @enderror"
                                  placeholder="Escribe tu mensaje aquí…">{{ old('cuerpo') }}</textarea>
                        @error('cuerpo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Adjunto --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Adjunto (opcional)</label>
                        <input type="file" name="adjunto" class="form-control form-control-sm @error('adjunto') is-invalid @enderror"
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <div class="form-text" style="font-size:.72rem;">PDF, Word o imágenes — máx. 5 MB.</div>
                        @error('adjunto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-send me-1"></i>Enviar
                        </button>
                        <a href="{{ route('admin.comunicaciones.index') }}" class="btn btn-outline-secondary btn-sm">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('input[name="tipo_envio"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        var isCircular = this.value === 'circular';
        document.getElementById('destIndividual').classList.toggle('d-none', isCircular);
        document.getElementById('destCircular').classList.toggle('d-none', !isCircular);
    });
});
</script>
@endpush
@endsection
