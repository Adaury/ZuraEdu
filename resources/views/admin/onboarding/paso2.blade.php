@extends('admin.onboarding._layout')
@php $pasoActual = 2; @endphp

@section('wizard-content')

<div class="wizard-card-header">
    <h2>📅 Año escolar</h2>
    <p>Configura el período académico activo. Esto define el marco temporal para asistencia, calificaciones y boletines.</p>
</div>

<form method="POST" action="{{ route('admin.onboarding.store', 2) }}">
@csrf

<div class="wizard-card-body">

    @if($errors->any())
    <div class="alert-error">
        <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="form-group">
        <label class="form-label">Nombre del año escolar</label>
        <input type="text" name="nombre" class="form-control"
               value="{{ old('nombre', $schoolYear?->nombre ?? (date('Y').'-'.(date('Y')+1))) }}"
               placeholder="Ej: 2025-2026" required maxlength="30">
        <div style="font-size:.75rem;color:#94a3b8;margin-top:.3rem;">Ejemplo: 2025-2026, Año Académico 2025</div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Fecha de inicio</label>
            <input type="date" name="fecha_inicio" class="form-control"
                   value="{{ old('fecha_inicio', $schoolYear?->fecha_inicio?->format('Y-m-d') ?? date('Y').'-08-01') }}"
                   required>
        </div>
        <div class="form-group">
            <label class="form-label">Fecha de fin</label>
            <input type="date" name="fecha_fin" class="form-control"
                   value="{{ old('fecha_fin', $schoolYear?->fecha_fin?->format('Y-m-d') ?? (date('Y')+1).'-06-30') }}"
                   required>
        </div>
    </div>

    {{-- Períodos info --}}
    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:1rem 1.25rem;margin-top:.5rem;">
        <div style="font-size:.83rem;font-weight:700;color:#1e40af;margin-bottom:.4rem;">
            <i class="bi bi-info-circle me-1"></i>Sistema de evaluación MINERD
        </div>
        <div style="font-size:.82rem;color:#1e3a8a;line-height:1.5;">
            El sistema usa <strong>4 períodos</strong> con competencias e indicadores de logro.
            Puedes ajustar los períodos exactos después en <strong>Configuración → Períodos</strong>.
        </div>
    </div>

</div>

<div class="wizard-card-footer">
    <a href="{{ route('admin.onboarding.show', 1) }}" class="btn btn-outline">
        <i class="bi bi-arrow-left"></i> Anterior
    </a>
    <button type="submit" class="btn btn-primary">
        Continuar <i class="bi bi-arrow-right"></i>
    </button>
</div>

</form>

@endsection
