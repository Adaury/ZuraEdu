@extends('layouts.portal')
@section('page-title', 'Nueva Solicitud')
@section('portal-name', 'Portal del Representante')

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'solicitudes'])
@endsection

@section('content')

<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;">
    <a href="{{ route('portal.padre.solicitudes.index') }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <h1 style="font-size:1rem;font-weight:800;margin:0;color:#1e3a6e;">Nueva Solicitud</h1>
</div>

@if($errors->any())
<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.82rem;color:#dc2626;">
    <i class="bi bi-x-circle-fill me-1"></i>{{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('portal.padre.solicitudes.store') }}" enctype="multipart/form-data">
@csrf

<div class="prt-card" style="padding:1.4rem;">

    {{-- Tipo --}}
    <div style="margin-bottom:1.1rem;">
        <label style="font-size:.82rem;font-weight:700;color:#374151;display:block;margin-bottom:.5rem;">
            Tipo de solicitud <span style="color:#dc2626;">*</span>
        </label>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:.6rem;">
            @foreach($tipos as $key => $label)
            <label style="cursor:pointer;">
                <input type="radio" name="tipo" value="{{ $key }}" class="tipo-radio visually-hidden"
                       {{ old('tipo') === $key ? 'checked' : '' }}>
                <div class="tipo-card" style="border:2px solid #e5e7eb;border-radius:10px;padding:.75rem .6rem;text-align:center;transition:all .15s;font-size:.78rem;font-weight:600;color:#374151;">
                    <i class="bi bi-{{ $key === 'justificacion_ausencia' ? 'calendar-x-fill' : ($key === 'cita_docente' ? 'person-lines-fill' : ($key === 'cita_direccion' ? 'building-fill' : ($key === 'solicitar_documento' ? 'file-earmark-text-fill' : ($key === 'actualizar_datos' ? 'pencil-square' : 'three-dots')))) }}" style="font-size:1.3rem;display:block;margin-bottom:.35rem;"></i>
                    {{ $label }}
                </div>
            </label>
            @endforeach
        </div>
        @error('tipo')<div style="color:#dc2626;font-size:.75rem;margin-top:.3rem;">{{ $message }}</div>@enderror
    </div>

    {{-- Hijo --}}
    @if($hijos->count() > 0)
    <div style="margin-bottom:1rem;">
        <label for="estudiante_id" style="font-size:.82rem;font-weight:700;color:#374151;display:block;margin-bottom:.4rem;">
            Relacionado con (opcional)
        </label>
        <select name="estudiante_id" id="estudiante_id" class="form-select form-select-sm">
            <option value="">— General (no relacionado con un hijo específico) —</option>
            @foreach($hijos as $hijo)
            <option value="{{ $hijo->id }}" {{ old('estudiante_id') == $hijo->id ? 'selected' : '' }}>
                {{ $hijo->nombre_completo }}
            </option>
            @endforeach
        </select>
    </div>
    @endif

    {{-- Fecha evento (solo para justificacion_ausencia) --}}
    <div id="campo-fecha" style="margin-bottom:1rem;display:none;">
        <label for="fecha_evento" style="font-size:.82rem;font-weight:700;color:#374151;display:block;margin-bottom:.4rem;">
            Fecha de la ausencia
        </label>
        <input type="date" name="fecha_evento" id="fecha_evento" class="form-control form-control-sm"
               value="{{ old('fecha_evento') }}" max="{{ date('Y-m-d') }}">
        @error('fecha_evento')<div style="color:#dc2626;font-size:.75rem;margin-top:.3rem;">{{ $message }}</div>@enderror
    </div>

    {{-- Asunto --}}
    <div style="margin-bottom:1rem;">
        <label for="asunto" style="font-size:.82rem;font-weight:700;color:#374151;display:block;margin-bottom:.4rem;">
            Asunto <span style="color:#dc2626;">*</span>
        </label>
        <input type="text" name="asunto" id="asunto" class="form-control form-control-sm"
               placeholder="Ej: Justificación de ausencia del 5 de mayo"
               value="{{ old('asunto') }}" maxlength="200">
        @error('asunto')<div style="color:#dc2626;font-size:.75rem;margin-top:.3rem;">{{ $message }}</div>@enderror
    </div>

    {{-- Descripción --}}
    <div style="margin-bottom:1rem;">
        <label for="descripcion" style="font-size:.82rem;font-weight:700;color:#374151;display:block;margin-bottom:.4rem;">
            Descripción detallada <span style="color:#dc2626;">*</span>
        </label>
        <textarea name="descripcion" id="descripcion" rows="5" class="form-control form-control-sm"
                  placeholder="Explica el motivo de tu solicitud con el mayor detalle posible..."
                  maxlength="3000">{{ old('descripcion') }}</textarea>
        <div style="font-size:.72rem;color:#9ca3af;text-align:right;margin-top:.2rem;">
            <span id="charCount">0</span>/3000
        </div>
        @error('descripcion')<div style="color:#dc2626;font-size:.75rem;margin-top:.3rem;">{{ $message }}</div>@enderror
    </div>

    {{-- Adjunto --}}
    <div style="margin-bottom:1.4rem;">
        <label for="adjunto" style="font-size:.82rem;font-weight:700;color:#374151;display:block;margin-bottom:.4rem;">
            Documento adjunto (opcional)
        </label>
        <input type="file" name="adjunto" id="adjunto" class="form-control form-control-sm"
               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
        <div style="font-size:.72rem;color:#9ca3af;margin-top:.25rem;">
            Formatos: PDF, JPG, PNG, DOC. Máximo 5 MB.
        </div>
    </div>

    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
        <button type="submit"
                style="background:#1e3a6e;color:#fff;border:none;border-radius:10px;padding:.6rem 1.4rem;font-size:.85rem;font-weight:700;cursor:pointer;">
            <i class="bi bi-send-fill me-1"></i>Enviar Solicitud
        </button>
        <a href="{{ route('portal.padre.solicitudes.index') }}"
           style="background:#f1f5f9;color:#374151;border-radius:10px;padding:.6rem 1.2rem;font-size:.85rem;font-weight:600;text-decoration:none;">
            Cancelar
        </a>
    </div>
</div>
</form>

<style>
.tipo-radio:checked + .tipo-card {
    border-color: #1e3a6e;
    background: #eff6ff;
    color: #1e3a6e;
}
.tipo-card:hover { border-color: #93c5fd; background: #f8faff; }
</style>

<script>
// Tipo radio styling
document.querySelectorAll('.tipo-radio').forEach(r => {
    r.addEventListener('change', () => {
        document.querySelectorAll('.tipo-card').forEach(c => {
            c.style.borderColor = '#e5e7eb';
            c.style.background = '';
            c.style.color = '#374151';
        });
        if (r.checked) {
            const card = r.nextElementSibling;
            card.style.borderColor = '#1e3a6e';
            card.style.background = '#eff6ff';
            card.style.color = '#1e3a6e';
        }
        // Show date field for justificacion_ausencia
        document.getElementById('campo-fecha').style.display =
            r.value === 'justificacion_ausencia' && r.checked ? 'block' : 'none';
    });
});

// Restore on page load (for old() values)
const checked = document.querySelector('.tipo-radio:checked');
if (checked) {
    checked.nextElementSibling.style.borderColor = '#1e3a6e';
    checked.nextElementSibling.style.background = '#eff6ff';
    checked.nextElementSibling.style.color = '#1e3a6e';
    if (checked.value === 'justificacion_ausencia') {
        document.getElementById('campo-fecha').style.display = 'block';
    }
}

// Char counter
const desc = document.getElementById('descripcion');
const cnt  = document.getElementById('charCount');
desc.addEventListener('input', () => cnt.textContent = desc.value.length);
cnt.textContent = desc.value.length;
</script>
@endsection
