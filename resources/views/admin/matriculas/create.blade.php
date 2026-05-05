@extends('layouts.admin')

@section('page-title', 'Nueva Matrícula')

@push('styles')
<style>
    .form-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(30,58,110,.06);
    }
    .form-section-title {
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--primary);
        margin-bottom: 1rem;
        padding-bottom: .4rem;
        border-bottom: 1px solid #e5e7eb;
    }
    .form-label {
        font-size: .8rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: .3rem;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #d1d5db;
        font-size: .875rem;
        padding: .5rem .75rem;
        transition: border-color .15s, box-shadow .15s;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30,58,110,.1);
    }
    .form-control.is-invalid, .form-select.is-invalid {
        border-color: var(--secondary);
    }
    .invalid-feedback { font-size: .75rem; }
    .year-readonly-pill {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        background: #f0f4f8;
        border: 1px solid #dde3ef;
        border-radius: 8px;
        padding: .5rem .85rem;
        font-size: .85rem;
        font-weight: 600;
        color: var(--primary);
    }
    /* Student search wrapper */
    .student-search-wrap {
        position: relative;
    }
    .student-search-wrap input[type="text"] {
        padding-right: 2.2rem;
    }
    .student-search-icon {
        position: absolute;
        right: .75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        pointer-events: none;
        font-size: .9rem;
    }
    .student-picker-list {
        position: absolute;
        z-index: 200;
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,.12);
        width: 100%;
        max-height: 240px;
        overflow-y: auto;
        top: calc(100% + 4px);
        left: 0;
        display: none;
    }
    .student-picker-list.show { display: block; }
    .student-picker-item {
        padding: .55rem .85rem;
        cursor: pointer;
        border-bottom: 1px solid #f3f4f6;
        transition: background .12s;
    }
    .student-picker-item:last-child { border-bottom: none; }
    .student-picker-item:hover { background: #f0f4f8; }
    .student-picker-item .s-name {
        font-size: .83rem;
        font-weight: 600;
        color: #1e293b;
    }
    .student-picker-item .s-id {
        font-size: .72rem;
        color: #9ca3af;
    }
    .student-picker-empty {
        padding: .75rem .85rem;
        font-size: .82rem;
        color: #9ca3af;
        text-align: center;
    }
    .selected-student-card {
        display: none;
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 8px;
        padding: .6rem .85rem;
        margin-top: .4rem;
        font-size: .82rem;
        color: #0c4a6e;
    }
    .selected-student-card.show { display: flex; }

    [data-theme="dark"] .form-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .selected-student-card { background: #0c1f3f; border-color: #1d4ed8; color: #93c5fd; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:.8rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.matriculas.index') }}" class="text-decoration-none">Matrículas</a></li>
        <li class="breadcrumb-item active">Nueva Matrícula</li>
    </ol>
</nav>

<div class="d-flex align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-0" style="color:var(--primary);">
            <i class="bi bi-person-plus me-2"></i>Nueva Matrícula
        </h1>
        <p class="text-muted mb-0 mt-1" style="font-size:.82rem;">Inscribir un estudiante en el año escolar actual</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="form-card p-4">

            @if($errors->any())
                <div class="alert alert-danger border-0 mb-4" style="border-radius:10px;font-size:.85rem;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Por favor corrige los siguientes errores:</strong>
                    <ul class="mb-0 mt-1 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.matriculas.store') }}" method="POST">
                @csrf

                {{-- Section: Año Escolar --}}
                <div class="form-section-title">
                    <i class="bi bi-calendar2-check me-1"></i>Año Escolar
                </div>

                <div class="mb-4">
                    @if($schoolYear)
                        <div class="year-readonly-pill">
                            <i class="bi bi-calendar-check"></i>
                            {{ $schoolYear->nombre }}
                            <span style="font-size:.7rem;opacity:.7;font-weight:400;">(Año actual)</span>
                        </div>
                        <input type="hidden" name="school_year_id" value="{{ $schoolYear->id }}">
                    @else
                        <div class="alert alert-warning border-0" style="border-radius:8px;font-size:.83rem;">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            No hay un año escolar activo. Por favor activa uno antes de registrar matrículas.
                        </div>
                    @endif
                </div>

                {{-- Section: Estudiante --}}
                <div class="form-section-title">
                    <i class="bi bi-person me-1"></i>Estudiante
                </div>

                <div class="mb-4">
                    <label class="form-label" for="student_search">
                        Seleccionar Estudiante <span class="text-danger">*</span>
                    </label>

                    {{-- Hidden real input --}}
                    <input type="hidden" name="estudiante_id" id="estudiante_id" value="{{ old('estudiante_id') }}">

                    <div class="student-search-wrap">
                        <input type="text"
                               id="student_search"
                               class="form-control @error('estudiante_id') is-invalid @enderror"
                               placeholder="Escriba el nombre o número de matrícula del estudiante..."
                               autocomplete="off">
                        <i class="bi bi-search student-search-icon"></i>

                        <div class="student-picker-list" id="studentPickerList">
                            <div class="student-picker-empty" id="studentPickerEmpty">Escriba al menos 2 caracteres</div>
                        </div>
                    </div>

                    {{-- Selected student indicator --}}
                    <div class="selected-student-card align-items-center gap-2" id="selectedStudentCard">
                        <i class="bi bi-check-circle-fill" style="color:#0ea5e9;"></i>
                        <span id="selectedStudentName" style="font-weight:600;"></span>
                        <button type="button" class="btn btn-sm ms-auto" id="clearStudentBtn"
                                style="background:transparent;border:none;color:#0369a1;font-size:.75rem;padding:0;">
                            <i class="bi bi-x-lg"></i> Cambiar
                        </button>
                    </div>

                    @error('estudiante_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror

                    <p class="text-muted mt-1" style="font-size:.73rem;">
                        Solo se muestran estudiantes activos que aún no están matriculados en el año actual.
                    </p>
                </div>

                {{-- Section: Grupo y Fecha --}}
                <div class="form-section-title">
                    <i class="bi bi-grid me-1"></i>Grupo y Fecha
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-7">
                        <label class="form-label" for="grupo_id">
                            Grupo / Sección <span class="text-danger">*</span>
                        </label>
                        <select name="grupo_id" id="grupo_id"
                                class="form-select @error('grupo_id') is-invalid @enderror" required>
                            <option value="">— Seleccionar grupo —</option>
                            @php
                                $niveles = [1=>'1ro',2=>'2do',3=>'3ro',4=>'4to',5=>'5to',6=>'6to'];
                                $gruposPorCiclo = $grupos->groupBy(fn($g) => $g->grado->ciclo ?? 'primer_ciclo');
                            @endphp
                            @foreach(['primer_ciclo' => '— Primer Ciclo (1ro – 3ro) —', 'segundo_ciclo' => '— Segundo Ciclo (4to – 6to) —'] as $cKey => $cLabel)
                                @if($gruposPorCiclo->has($cKey))
                                <optgroup label="{{ $cLabel }}">
                                    @foreach($gruposPorCiclo[$cKey] as $g)
                                        @php
                                            $pref   = $niveles[$g->grado->nivel ?? 0] ?? ($g->grado->nivel.'mo');
                                            $gLabel = $pref . ' ' . ($g->seccion->nombre ?? '');
                                            $cap    = $g->matriculas()->count();
                                            $max    = $g->capacidad;
                                            $info   = $max ? " ({$cap}/{$max})" : " ({$cap} alumnos)";
                                        @endphp
                                        <option value="{{ $g->id }}" {{ old('grupo_id') == $g->id ? 'selected' : '' }}>
                                            {{ $gLabel }}{{ $info }}
                                        </option>
                                    @endforeach
                                </optgroup>
                                @endif
                            @endforeach
                        </select>
                        @error('grupo_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-5">
                        <label class="form-label" for="fecha_matricula">
                            Fecha de Matrícula <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="fecha_matricula" id="fecha_matricula"
                               class="form-control @error('fecha_matricula') is-invalid @enderror"
                               value="{{ old('fecha_matricula', now()->format('Y-m-d')) }}" required>
                        @error('fecha_matricula')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Section: Observaciones --}}
                <div class="form-section-title">
                    <i class="bi bi-chat-text me-1"></i>Observaciones
                </div>

                <div class="mb-4">
                    <textarea name="observaciones" id="observaciones"
                              class="form-control @error('observaciones') is-invalid @enderror"
                              rows="3"
                              placeholder="Observaciones adicionales sobre la matrícula (opcional)...">{{ old('observaciones') }}</textarea>
                    @error('observaciones')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Actions --}}
                <div class="d-flex gap-2 pt-2 border-top">
                    <button type="submit" class="btn fw-semibold"
                            style="background:var(--primary);color:#fff;border-radius:8px;padding:.5rem 1.4rem;">
                        <i class="bi bi-check-lg me-1"></i>Registrar Matrícula
                    </button>
                    <a href="{{ route('admin.matriculas.index') }}"
                       class="btn fw-semibold"
                       style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;padding:.5rem 1.2rem;">
                        Cancelar
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // ── Student search / picker ──────────────────────────────────────────────
    @php
        $studentsJs = $estudiantes->map(function($e) {
            return ['id' => $e->id, 'name' => $e->nombre_completo, 'numero' => $e->numero_matricula ?? ''];
        })->values();
    @endphp
    const students = @json($studentsJs);

    const searchInput       = document.getElementById('student_search');
    const hiddenInput       = document.getElementById('estudiante_id');
    const pickerList        = document.getElementById('studentPickerList');
    const pickerEmpty       = document.getElementById('studentPickerEmpty');
    const selectedCard      = document.getElementById('selectedStudentCard');
    const selectedName      = document.getElementById('selectedStudentName');
    const clearBtn          = document.getElementById('clearStudentBtn');

    // Restore selection if old value present
    @if(old('estudiante_id'))
        const preSelected = students.find(s => s.id == {{ old('estudiante_id') }});
        if (preSelected) setStudent(preSelected);
    @endif

    searchInput.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        if (q.length < 2) {
            pickerEmpty.textContent = 'Escriba al menos 2 caracteres';
            pickerList.innerHTML = '';
            pickerList.appendChild(pickerEmpty);
            pickerList.classList.remove('show');
            return;
        }

        const matches = students.filter(s =>
            s.name.toLowerCase().includes(q) ||
            s.numero.toLowerCase().includes(q)
        ).slice(0, 12);

        pickerList.innerHTML = '';
        if (matches.length === 0) {
            const emp = document.createElement('div');
            emp.className = 'student-picker-empty';
            emp.textContent = 'No se encontraron estudiantes';
            pickerList.appendChild(emp);
        } else {
            matches.forEach(s => {
                const item = document.createElement('div');
                item.className = 'student-picker-item';
                item.innerHTML = `<div class="s-name">${s.name}</div>
                                  <div class="s-id">Nº matrícula: ${s.numero || '—'}</div>`;
                item.addEventListener('click', () => {
                    setStudent(s);
                    pickerList.classList.remove('show');
                });
                pickerList.appendChild(item);
            });
        }
        pickerList.classList.add('show');
    });

    function setStudent(s) {
        hiddenInput.value        = s.id;
        searchInput.value        = s.name;
        selectedName.textContent = s.name + (s.numero ? ' — Nº ' + s.numero : '');
        selectedCard.classList.add('show');
        searchInput.style.display = 'none';
        pickerList.classList.remove('show');
    }

    clearBtn.addEventListener('click', () => {
        hiddenInput.value = '';
        searchInput.value = '';
        selectedCard.classList.remove('show');
        searchInput.style.display = '';
        searchInput.focus();
    });

    // Close picker when clicking outside
    document.addEventListener('click', e => {
        if (!searchInput.contains(e.target) && !pickerList.contains(e.target)) {
            pickerList.classList.remove('show');
        }
    });
</script>
@endpush
