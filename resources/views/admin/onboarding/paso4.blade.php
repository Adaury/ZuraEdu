@extends('admin.onboarding._layout')
@php $pasoActual = 4; @endphp

@section('wizard-content')

<div class="wizard-card-body" style="padding:2rem;">

    {{-- Hero --}}
    <div class="completion-hero">
        <div class="completion-icon">🎉</div>
        <div class="completion-title">¡Todo listo!</div>
        <div class="completion-sub">
            {{ $tenant->nombre_institucion }} está configurada y lista para usar.<br>
            Aquí tienes un resumen de tu configuración inicial.
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="summary-cards">
        <div class="summary-card">
            <div class="sc-value">{{ $gradosActivos }}</div>
            <div class="sc-label">Grados activos</div>
        </div>
        <div class="summary-card">
            <div class="sc-value" style="font-size:1rem;">{{ $schoolYear?->nombre ?? '—' }}</div>
            <div class="sc-label">Año escolar</div>
        </div>
        <div class="summary-card">
            <div class="sc-value" style="color:#10b981;font-size:1rem;">
                {{ ucfirst($tenant->plan ?? 'Free') }}
            </div>
            <div class="sc-label">Plan actual</div>
        </div>
    </div>

    {{-- Próximos pasos --}}
    <div style="font-size:.83rem;font-weight:700;color:#374151;margin-bottom:.75rem;">¿Qué hacer ahora?</div>
    <div class="quick-actions">
        <a href="{{ route('admin.docentes.create') }}" class="qa-link">
            <i class="bi bi-person-plus-fill" style="color:#3b82f6;"></i>
            Agregar docentes
        </a>
        <a href="{{ route('admin.estudiantes.index') }}" class="qa-link">
            <i class="bi bi-mortarboard-fill" style="color:#10b981;"></i>
            Matricular estudiantes
        </a>
        <a href="{{ route('admin.asignaturas.index') }}" class="qa-link">
            <i class="bi bi-book-fill" style="color:#f59e0b;"></i>
            Ver asignaturas
        </a>
        <a href="{{ route('admin.asignaciones.create') }}" class="qa-link">
            <i class="bi bi-grid-fill" style="color:#6366f1;"></i>
            Crear asignaciones
        </a>
    </div>

    {{-- Botón completar --}}
    <form method="POST" action="{{ route('admin.onboarding.store', 4) }}" style="margin-top:1.75rem;">
        @csrf
        <button type="submit" class="btn btn-success" style="width:100%;justify-content:center;padding:.85rem;">
            <i class="bi bi-check2-circle"></i> Ir al Dashboard
        </button>
    </form>

    <div style="text-align:center;margin-top:.75rem;">
        <span style="font-size:.78rem;color:#94a3b8;">Puedes modificar toda esta configuración en <strong>Configuración → Sistema</strong></span>
    </div>

</div>

@endsection
