@extends('admin.onboarding._layout')
@php $pasoActual = 4; @endphp

@section('wizard-content')

<div class="wizard-card-body" style="padding:2rem;">

    {{-- Hero --}}
    <div class="completion-hero">
        <div class="completion-icon">🎉</div>
        <div class="completion-title">¡Configuración inicial lista!</div>
        <div class="completion-sub">
            <strong>{{ $tenant->nombre_institucion }}</strong> está configurada.<br>
            Ahora sigue estos 5 pasos para dejarla operativa.
        </div>
    </div>

    {{-- Summary --}}
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
            <div class="sc-value" style="color:#10b981;font-size:1rem;">{{ ucfirst($tenant->plan ?? 'Free') }}</div>
            <div class="sc-label">Plan actual</div>
        </div>
    </div>

    {{-- Próximos pasos — numerados y con desc --}}
    <div style="font-size:.83rem;font-weight:700;color:#374151;margin-bottom:.75rem;margin-top:1.5rem;">
        ¿Qué sigue? — el checklist aparece en tu dashboard
    </div>

    <div style="display:flex;flex-direction:column;gap:.55rem;">
        @php
        $pasos = [
            ['n'=>1,'icon'=>'bi-book-fill',        'color'=>'#f59e0b','label'=>'Crear asignaturas',           'desc'=>'Define las materias de cada grado.',              'route'=>route('admin.asignaturas.index')],
            ['n'=>2,'icon'=>'bi-person-video3',     'color'=>'#3b82f6','label'=>'Agregar docentes',            'desc'=>'Registra al personal docente del centro.',         'route'=>route('admin.docentes.create')],
            ['n'=>3,'icon'=>'bi-grid-3x3-gap-fill', 'color'=>'#8b5cf6','label'=>'Asignar docentes a grupos',   'desc'=>'Vincula docente, grupo y asignatura.',             'route'=>route('admin.asignaciones.create')],
            ['n'=>4,'icon'=>'bi-mortarboard-fill',  'color'=>'#10b981','label'=>'Matricular estudiantes',      'desc'=>'Inscribe a los alumnos en sus grupos.',            'route'=>route('admin.estudiantes.index')],
            ['n'=>5,'icon'=>'bi-calendar-week-fill','color'=>'#ec4899','label'=>'Publicar horario',            'desc'=>'Genera y publica el horario de clases.',           'route'=>route('admin.horarios.index')],
        ];
        @endphp
        @foreach($pasos as $p)
        <a href="{{ $p['route'] }}"
           style="display:flex;align-items:center;gap:.85rem;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;padding:.7rem 1rem;text-decoration:none;transition:border-color .15s;"
           onmouseover="this.style.borderColor='{{ $p['color'] }}'" onmouseout="this.style.borderColor='#e2e8f0'">
            <div style="width:32px;height:32px;border-radius:50%;background:{{ $p['color'] }}1a;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <span style="font-size:.7rem;font-weight:900;color:{{ $p['color'] }};">{{ $p['n'] }}</span>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:.82rem;font-weight:700;color:#1e293b;">{{ $p['label'] }}</div>
                <div style="font-size:.71rem;color:#64748b;">{{ $p['desc'] }}</div>
            </div>
            <i class="bi bi-arrow-right" style="color:#94a3b8;font-size:.85rem;flex-shrink:0;"></i>
        </a>
        @endforeach
    </div>

    {{-- Botón completar --}}
    <form method="POST" action="{{ route('admin.onboarding.store', 4) }}" style="margin-top:1.75rem;">
        @csrf
        <button type="submit" class="btn btn-success" style="width:100%;justify-content:center;padding:.85rem;">
            <i class="bi bi-rocket-takeoff-fill"></i> Ir al Dashboard con el checklist
        </button>
    </form>

    <div style="text-align:center;margin-top:.75rem;">
        <span style="font-size:.78rem;color:#94a3b8;">El checklist aparecerá en tu dashboard hasta completar los 5 pasos.</span>
    </div>

</div>

@endsection
