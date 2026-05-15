@extends('admin.onboarding._layout')
@php $pasoActual = 3; @endphp

@section('wizard-content')

<div class="wizard-card-header">
    <h2>📚 Grados académicos</h2>
    <p>Selecciona los grados que ofrece tu institución. Puedes activar o desactivar grados en cualquier momento desde la configuración.</p>
</div>

<form method="POST" action="{{ route('admin.onboarding.store', 3) }}">
@csrf

<div class="wizard-card-body">

    @if($errors->any())
    <div class="alert-error">
        <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Seleccionar todos --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
        <span style="font-size:.83rem;font-weight:600;color:#374151;">Grados disponibles</span>
        <button type="button" onclick="toggleTodos()" class="btn-skip" id="toggleBtn">
            Deseleccionar todos
        </button>
    </div>

    <div class="grados-grid" id="gradosGrid">
        @forelse($grados as $grado)
        <label class="grado-toggle">
            <input type="checkbox" name="grados_activos[]" value="{{ $grado->id }}"
                   {{ $grado->activo !== false ? 'checked' : '' }}>
            <div class="grado-card">
                <div>
                    <div class="grado-name">{{ $grado->nombre }}</div>
                    <div style="font-size:.72rem;color:#94a3b8;margin-top:.15rem;">Nivel {{ $grado->nivel }}</div>
                </div>
                <span class="grado-badge">
                    {{ $grado->ciclo === 'primer_ciclo' ? '1er Ciclo' : '2do Ciclo' }}
                </span>
            </div>
        </label>
        @empty
        <div style="grid-column:span 2;text-align:center;color:#94a3b8;padding:2rem;">
            <i class="bi bi-exclamation-circle" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>
            No hay grados configurados
        </div>
        @endforelse
    </div>

    {{-- Agregar grado personalizado --}}
    <div style="margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid #e2e8f0;">
        <button type="button" onclick="toggleNuevoGrado()" class="btn-skip" style="color:#3b82f6;">
            <i class="bi bi-plus-circle me-1"></i>Agregar grado personalizado
        </button>

        <div id="nuevoGradoPanel" style="display:none;margin-top:1rem;">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nombre del grado</label>
                    <input type="text" name="nuevo_grado" class="form-control"
                           placeholder="Ej: 1ro de Bachillerato" maxlength="80">
                </div>
                <div class="form-group">
                    <label class="form-label">Ciclo</label>
                    <select name="nuevo_ciclo" class="form-control">
                        <option value="primer_ciclo">Primer Ciclo</option>
                        <option value="segundo_ciclo">Segundo Ciclo</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="wizard-card-footer">
    <a href="{{ route('admin.onboarding.show', 2) }}" class="btn btn-outline">
        <i class="bi bi-arrow-left"></i> Anterior
    </a>
    <button type="submit" class="btn btn-primary">
        Continuar <i class="bi bi-arrow-right"></i>
    </button>
</div>

</form>

@endsection

@push('scripts')
<script>
let todosActivos = true;

function toggleTodos() {
    const checks = document.querySelectorAll('#gradosGrid input[type=checkbox]');
    todosActivos = !todosActivos;
    checks.forEach(c => c.checked = todosActivos);
    document.getElementById('toggleBtn').textContent = todosActivos ? 'Deseleccionar todos' : 'Seleccionar todos';
}

function toggleNuevoGrado() {
    const panel = document.getElementById('nuevoGradoPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}
</script>
@endpush
