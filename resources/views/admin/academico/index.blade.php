@extends('layouts.admin')

@section('page-title', 'Año Escolar — Cursos')

@push('styles')
<style>
.ac-toolbar { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:1.5rem; }
.ac-toolbar .ac-title { font-size:1.55rem; font-weight:900; color:#1e3a6e; flex:1; min-width:160px; }
.ac-year-sel { padding:.38rem .9rem; border:1.5px solid #c7d4e8; border-radius:8px; font-size:.875rem; font-weight:700; color:#1e3a6e; background:#fff; cursor:pointer; }
.ac-grade-section { margin-bottom:2rem; }
.ac-grade-title { font-size:.75rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:#6b7280; margin-bottom:.9rem; padding-left:4px; border-left:3px solid #3b82f6; padding-left:8px; }
.curso-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:14px; }
.curso-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; overflow:hidden; transition:box-shadow .2s,transform .15s; text-decoration:none; color:inherit; display:block; }
.curso-card:hover { box-shadow:0 6px 24px rgba(30,58,110,.13); transform:translateY(-2px); }
.curso-card-hdr { background:linear-gradient(135deg,#1e3a6e 0%,#2f5eb3 100%); color:#fff; padding:1rem 1rem .75rem; position:relative; }
.curso-card-hdr.inactive { background:linear-gradient(135deg,#6b7280,#9ca3af); }
.curso-nombre { font-size:1.6rem; font-weight:900; line-height:1; }
.curso-year-lbl { font-size:.65rem; opacity:.7; text-transform:uppercase; letter-spacing:.07em; margin-top:.25rem; }
.curso-badge-activo { position:absolute; top:.6rem; right:.6rem; font-size:.6rem; font-weight:800; padding:.15rem .45rem; border-radius:20px; background:rgba(255,255,255,.22); color:#fff; text-transform:uppercase; letter-spacing:.05em; }
.curso-card-body { padding:.85rem 1rem; }
.curso-stat { display:flex; align-items:center; gap:6px; font-size:.8rem; color:#4b5563; margin-bottom:.35rem; }
.curso-stat i { width:16px; text-align:center; color:#6b7280; }
.curso-stat strong { color:#1e3a6e; font-weight:800; }
.stat-summary { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:1.5rem; }
.stat-box { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:.85rem 1.2rem; min-width:130px; }
.stat-box .val { font-size:1.7rem; font-weight:900; color:#1e3a6e; line-height:1; }
.stat-box .lbl { font-size:.7rem; color:#6b7280; font-weight:600; text-transform:uppercase; letter-spacing:.06em; margin-top:.2rem; }
.btn-nuevo { background:#1e3a6e; color:#fff; border:none; border-radius:8px; padding:.45rem 1rem; font-size:.85rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
.btn-nuevo:hover { background:#2f5eb3; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-3">

    {{-- Toolbar --}}
    <div class="ac-toolbar">
        <div class="ac-title"><i class="bi bi-calendar3 me-2" style="color:#3b82f6"></i>Año Escolar — Cursos</div>

        {{-- Selector año escolar --}}
        <form method="GET" action="{{ route('admin.academico.index') }}" id="formSy">
            <select name="sy" class="ac-year-sel" onchange="document.getElementById('formSy').submit()">
                @foreach($schoolYears as $sy)
                    <option value="{{ $sy->id }}" @selected($schoolYear?->id === $sy->id)>{{ $sy->nombre }}</option>
                @endforeach
            </select>
        </form>

        <button class="btn-nuevo" data-bs-toggle="modal" data-bs-target="#modalNuevoCurso">
            <i class="bi bi-plus-lg"></i> Nuevo Curso
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 mb-3" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2 mb-3" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Stats globales --}}
    @php
        $totalCursos = $cursos->flatten()->count();
        $totalEstudiantes = $cursos->flatten()->sum('matriculas_count');
        $totalMaterias = $asigCount->sum();
    @endphp
    <div class="stat-summary">
        <div class="stat-box">
            <div class="val">{{ $totalCursos }}</div>
            <div class="lbl">Cursos</div>
        </div>
        <div class="stat-box">
            <div class="val">{{ $totalEstudiantes }}</div>
            <div class="lbl">Estudiantes</div>
        </div>
        <div class="stat-box">
            <div class="val">{{ $totalMaterias }}</div>
            <div class="lbl">Asignaciones</div>
        </div>
    </div>

    {{-- Grid por grado --}}
    @forelse($cursos as $gradoNombre => $grupos)
        <div class="ac-grade-section">
            <div class="ac-grade-title">{{ $gradoNombre }}</div>
            <div class="curso-grid">
                @foreach($grupos as $grupo)
                    <a href="{{ route('admin.academico.show', $grupo) }}" class="curso-card">
                        <div class="curso-card-hdr {{ $grupo->activo ? '' : 'inactive' }}">
                            <span class="curso-badge-activo">{{ $grupo->activo ? 'Activo' : 'Inactivo' }}</span>
                            <div class="curso-nombre">{{ $grupo->nombre_corto ?? $grupo->nombre_completo }}</div>
                            <div class="curso-year-lbl">{{ $schoolYear?->nombre }}</div>
                        </div>
                        <div class="curso-card-body">
                            <div class="curso-stat">
                                <i class="bi bi-people-fill"></i>
                                <strong>{{ $grupo->matriculas_count }}</strong> estudiantes
                            </div>
                            <div class="curso-stat">
                                <i class="bi bi-book-fill"></i>
                                <strong>{{ $asigCount[$grupo->id] ?? 0 }}</strong> materias
                            </div>
                            <div class="curso-stat">
                                <i class="bi bi-person-badge-fill"></i>
                                <strong>{{ $docenteCount[$grupo->id] ?? 0 }}</strong> docentes
                            </div>
                            @if($grupo->tutor)
                            <div class="curso-stat mt-1" style="color:#6b7280;font-size:.75rem;">
                                <i class="bi bi-person-heart"></i>
                                Guía: {{ $grupo->tutor->apellidos ?? $grupo->tutor->name ?? '—' }}
                            </div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @empty
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox" style="font-size:3rem;opacity:.3"></i>
            <p class="mt-2">No hay cursos para este año escolar.</p>
        </div>
    @endforelse

</div>

{{-- Modal Nuevo Curso --}}
<div class="modal fade" id="modalNuevoCurso" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.academico.cursos.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Nuevo Curso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="school_year_id" value="{{ $schoolYear?->id }}">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Grado <span class="text-danger">*</span></label>
                        <select name="grado_id" class="form-select" required>
                            <option value="">Seleccionar grado…</option>
                            @foreach($grados as $grado)
                                <option value="{{ $grado->id }}">{{ $grado->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sección <span class="text-danger">*</span></label>
                        <select name="seccion_id" class="form-select" required>
                            <option value="">Seleccionar sección…</option>
                            @foreach($secciones as $sec)
                                <option value="{{ $sec->id }}">{{ $sec->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2">
                        <div class="col-7">
                            <label class="form-label fw-semibold">Aula</label>
                            <input type="text" name="aula" class="form-control" placeholder="Ej: Aula 3B">
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold">Capacidad</label>
                            <input type="number" name="capacidad" class="form-control" min="1" max="60" placeholder="35">
                        </div>
                    </div>

                    <p class="text-muted mt-3 mb-0" style="font-size:.8rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        Las materias básicas se asignarán automáticamente al crear el curso.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">
                        <i class="bi bi-check-lg me-1"></i>Crear Curso
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
