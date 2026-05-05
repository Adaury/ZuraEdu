@extends('layouts.admin')
@section('page-title', 'Reportes Institucionales')

@section('content')

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.82rem;">
        <li class="breadcrumb-item active">Reportes Institucionales</li>
    </ol>
</nav>

{{-- Header --}}
<div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(135deg,#0f1f3d,#1e3a6e);">
    <div class="card-body py-3 px-4 text-white">
        <div class="d-flex align-items-center gap-3">
            <div style="width:48px;height:48px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-clipboard2-data" style="font-size:1.4rem;"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-0">Reportes Institucionales</h5>
                <p class="mb-0" style="font-size:.83rem;opacity:.85;">
                    Supervisión y verificación de registros académicos — {{ $schoolYear?->nombre ?? 'Año actual' }}
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div style="width:44px;height:44px;background:#dbeafe;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-people-fill" style="color:#1d4ed8;font-size:1.2rem;"></i>
                </div>
                <div>
                    <div style="font-size:1.5rem;font-weight:800;color:#1e293b;">{{ $totalMatriculas }}</div>
                    <div style="font-size:.77rem;color:#6b7280;">Estudiantes Matriculados</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div style="width:44px;height:44px;background:#dcfce7;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-check-circle-fill" style="color:#15803d;font-size:1.2rem;"></i>
                </div>
                <div>
                    <div style="font-size:1.5rem;font-weight:800;color:#15803d;">{{ $aprobados }}</div>
                    <div style="font-size:.77rem;color:#6b7280;">Registros Aprobados (A)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div style="width:44px;height:44px;background:#fee2e2;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-x-circle-fill" style="color:#991b1b;font-size:1.2rem;"></i>
                </div>
                <div>
                    <div style="font-size:1.5rem;font-weight:800;color:#991b1b;">{{ $reprobados }}</div>
                    <div style="font-size:.77rem;color:#6b7280;">Registros Reprobados (R)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div style="width:44px;height:44px;background:#fef3c7;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-diagram-3-fill" style="color:#92400e;font-size:1.2rem;"></i>
                </div>
                <div>
                    <div style="font-size:1.5rem;font-weight:800;color:#92400e;">{{ $totalAsignaciones }}</div>
                    <div style="font-size:.77rem;color:#6b7280;">Asignaciones Activas</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Report cards --}}
<div class="row g-3">

    {{-- Consolidado --}}
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-top:4px solid #1d4ed8!important;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-table" style="font-size:1.5rem;color:#1d4ed8;"></i>
                    <h6 class="fw-bold mb-0" style="color:#1d4ed8;">Consolidado de Calificaciones</h6>
                </div>
                <p style="font-size:.83rem;color:#6b7280;line-height:1.6;">
                    Vista completa de calificaciones por grupo. Verifica que los registros digitales coincidan con el Libro de Calificaciones físico. Incluye Competencias (C1–C4), PC, Cal. Final, Completivo y Extraordinario.
                </p>
                <div class="d-flex flex-column gap-2 mt-3">
                    <a href="{{ route('admin.reportes.consolidado', ['ciclo'=>'primer']) }}"
                       class="btn btn-sm btn-outline-primary fw-semibold">
                        <i class="bi bi-1-circle me-1"></i>Primer Ciclo (1ro – 3ro)
                    </a>
                    <a href="{{ route('admin.reportes.consolidado', ['ciclo'=>'segundo']) }}"
                       class="btn btn-sm btn-primary fw-semibold">
                        <i class="bi bi-2-circle me-1"></i>Segundo Ciclo (4to – 6to)
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Situación Final --}}
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-top:4px solid #15803d!important;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-person-check-fill" style="font-size:1.5rem;color:#15803d;"></i>
                    <h6 class="fw-bold mb-0" style="color:#15803d;">Situación Final de Estudiantes</h6>
                </div>
                <p style="font-size:.83rem;color:#6b7280;line-height:1.6;">
                    Reporte de aprobados (A) y reprobados (R) por grupo. Identifica estudiantes con materias pendientes y verifica que la situación final del registro físico coincida con el sistema.
                </p>
                <div class="mt-3">
                    <a href="{{ route('admin.reportes.situacion') }}"
                       class="btn btn-sm btn-success fw-semibold w-100">
                        <i class="bi bi-clipboard2-check me-1"></i>Ver Situación por Grupo
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Asistencia --}}
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-top:4px solid #064e3b!important;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-calendar2-check-fill" style="font-size:1.5rem;color:#064e3b;"></i>
                    <h6 class="fw-bold mb-0" style="color:#064e3b;">Reporte de Asistencia</h6>
                </div>
                <p style="font-size:.83rem;color:#6b7280;line-height:1.6;">
                    Porcentaje de asistencia por estudiante y grupo. Identifica estudiantes con asistencia crítica (&lt;75%) que podrían ser afectados según el Reglamento del MINERD.
                </p>
                <div class="mt-3">
                    <a href="{{ route('admin.reportes.asistencia') }}"
                       class="btn btn-sm fw-semibold w-100" style="background:#064e3b;color:#fff;">
                        <i class="bi bi-calendar-check me-1"></i>Ver Asistencia por Grupo
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Boletines --}}
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-top:4px solid #6d28d9!important;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-file-earmark-text-fill" style="font-size:1.5rem;color:#6d28d9;"></i>
                    <h6 class="fw-bold mb-0" style="color:#6d28d9;">Boletines de Notas</h6>
                </div>
                <p style="font-size:.83rem;color:#6b7280;line-height:1.6;">
                    Acceso a los boletines de calificaciones individuales y por grupo. Verifica que los boletines impresos coincidan con los registros del sistema antes de ser entregados.
                </p>
                <div class="mt-3">
                    <a href="{{ route('admin.boletines.grupo') }}"
                       class="btn btn-sm fw-semibold w-100" style="background:#6d28d9;color:#fff;">
                        <i class="bi bi-file-earmark-text me-1"></i>Ver Boletines por Grupo
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Ranking --}}
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-top:4px solid #92400e!important;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-trophy-fill" style="font-size:1.5rem;color:#92400e;"></i>
                    <h6 class="fw-bold mb-0" style="color:#92400e;">Ranking Académico</h6>
                </div>
                <p style="font-size:.83rem;color:#6b7280;line-height:1.6;">
                    Clasificación de estudiantes por rendimiento académico. Útil para reportes de cuadro de honor y reconocimientos institucionales al cierre del año escolar.
                </p>
                <div class="mt-3">
                    <a href="{{ route('admin.calificaciones.ranking') }}"
                       class="btn btn-sm fw-semibold w-100" style="background:#92400e;color:#fff;">
                        <i class="bi bi-trophy me-1"></i>Ver Ranking
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Resumen General --}}
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-top:4px solid #0f766e!important;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-grid-3x3-gap-fill" style="font-size:1.5rem;color:#0f766e;"></i>
                    <h6 class="fw-bold mb-0" style="color:#0f766e;">Resumen General de Notas</h6>
                </div>
                <p style="font-size:.83rem;color:#6b7280;line-height:1.6;">
                    Matriz completa de calificaciones por grupo y asignatura. Vista tipo planilla para comparar el rendimiento de todos los cursos en una sola pantalla.
                </p>
                <div class="mt-3">
                    <a href="{{ route('admin.calificaciones.resumen') }}"
                       class="btn btn-sm fw-semibold w-100" style="background:#0f766e;color:#fff;">
                        <i class="bi bi-table me-1"></i>Ver Resumen
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- MINERD Structure info card --}}
<div class="card border-0 shadow-sm mt-4" style="border-left:4px solid #1e3a6e!important;">
    <div class="card-body p-4">
        <h6 class="fw-bold mb-3" style="color:#1e3a6e;">
            <i class="bi bi-info-circle me-2"></i>Estructura del Nivel Secundario — MINERD
        </h6>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="p-3 rounded" style="background:#dbeafe;border:1px solid #bfdbfe;">
                    <div class="fw-bold mb-2" style="color:#1d4ed8;font-size:.85rem;">
                        <i class="bi bi-1-circle-fill me-1"></i>PRIMER CICLO DEL NIVEL SECUNDARIO
                    </div>
                    <div style="font-size:.82rem;color:#1e40af;">
                        1ro de Bachillerato · 2do de Bachillerato · 3ro de Bachillerato
                    </div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.4rem;">
                        Formación General · 4 Competencias · Evaluación por Trimestre
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 rounded" style="background:#ede9fe;border:1px solid #ddd6fe;">
                    <div class="fw-bold mb-2" style="color:#6d28d9;font-size:.85rem;">
                        <i class="bi bi-2-circle-fill me-1"></i>SEGUNDO CICLO DEL NIVEL SECUNDARIO
                    </div>
                    <div style="font-size:.82rem;color:#5b21b6;">
                        4to de Bachillerato · 5to de Bachillerato · 6to de Bachillerato
                    </div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.4rem;">
                        Modalidad Técnico-Profesional · Área Técnica con RA · Especialidades
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-3 p-2 rounded" style="background:#fef9c3;font-size:.77rem;color:#854d0e;">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <strong>Nota para supervisión:</strong> Verificar que los registros del sistema coincidan con:
            Libro de Calificaciones Oficial · Libro de Asistencia · Actas de Notas · Registro de Evaluación del MINERD
        </div>
    </div>
</div>

@endsection
