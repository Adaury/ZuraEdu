@extends('layouts.admin')
@section('page-title', 'Nuevo Mensaje')

@section('content')

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.mensajes.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Bandeja
    </a>
    <h4 class="fw-bold mb-0" style="color:var(--primary);">
        <i class="bi bi-pencil-square me-2"></i>Nuevo Mensaje
    </h4>
</div>

<div class="card border-0 shadow-sm" style="max-width:700px;">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.mensajes.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:.85rem;">Destinatario</label>
                <select name="destinatario_id" class="form-select" required>
                    <option value="">— Selecciona un usuario —</option>
                    @foreach($usuarios as $u)
                    <option value="{{ $u->id }}" {{ (old('destinatario_id', $destinatario?->id) == $u->id) ? 'selected' : '' }}>
                        {{ $u->name }}
                    </option>
                    @endforeach
                </select>
                @error('destinatario_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:.85rem;">Asunto</label>
                <input type="text" name="asunto" class="form-control" value="{{ old('asunto') }}"
                       placeholder="Asunto del mensaje" required maxlength="200">
                @error('asunto')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold" style="font-size:.85rem;">Mensaje</label>
                <textarea name="cuerpo" class="form-control" rows="8" placeholder="Escribe tu mensaje..."
                          required maxlength="5000" style="resize:vertical;">{{ old('cuerpo') }}</textarea>
                @error('cuerpo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send-fill me-1"></i>Enviar Mensaje
                </button>
                <a href="{{ route('admin.mensajes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
