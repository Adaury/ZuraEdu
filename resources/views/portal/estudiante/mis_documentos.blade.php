@extends('layouts.portal')

@section('title', 'Mis Documentos')

@section('sidebar')
    @include('portal.estudiante._sidebar', ['activeKey' => 'mis-documentos'])
@endsection

@section('content')
<div class="prt-page-header">
    <div>
        <h4 class="prt-page-title"><i class="bi bi-folder2-open me-2"></i>Mis Documentos</h4>
        @if($matricula)
        <p class="prt-page-subtitle">{{ $matricula->grupo?->nombre_completo }} — {{ $schoolYear?->nombre }}</p>
        @else
        <p class="prt-page-subtitle">Centro de descargas académicas</p>
        @endif
    </div>
    <a href="{{ route('portal.estudiante.dashboard') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Inicio
    </a>
</div>

@if(! $matricula)
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-folder-x" style="font-size:2.5rem;opacity:.4;"></i>
        <p class="mt-3 mb-0">No tienes una matrícula activa. Los documentos estarán disponibles una vez matriculado.</p>
    </div>
</div>
@else

{{-- ── BOLETINES ──────────────────────────────────────────────────────────── --}}
<div class="mb-4">
    <h6 class="fw-bold mb-3" style="color:#1e3a6e;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;">
        <i class="bi bi-file-earmark-text-fill me-2" style="color:#3b82f6;"></i>Boletines
    </h6>

    <div class="row g-3">
        {{-- Boletín completo del año --}}
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #3b82f6!important;">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="width:48px;height:48px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-file-earmark-text-fill" style="font-size:1.3rem;color:#fff;"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="color:#1e3a6e;font-size:.95rem;">Boletín Completo</div>
                            <div class="text-muted" style="font-size:.8rem;">Todos los períodos · {{ $schoolYear?->nombre }}</div>
                        </div>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.8rem;flex:1;">
                        Reporte oficial con calificaciones de todas las asignaturas y períodos del año escolar vigente.
                    </p>
                    <a href="{{ route('portal.estudiante.boletin.pdf') }}"
                       target="_blank"
                       class="btn btn-sm btn-danger w-100 mt-auto">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF
                    </a>
                </div>
            </div>
        </div>

        {{-- También ver el boletín en línea --}}
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #6366f1!important;">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="width:48px;height:48px;background:linear-gradient(135deg,#6366f1,#4338ca);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-eye-fill" style="font-size:1.3rem;color:#fff;"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="color:#1e3a6e;font-size:.95rem;">Ver Boletín en línea</div>
                            <div class="text-muted" style="font-size:.8rem;">Vista interactiva</div>
                        </div>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.8rem;flex:1;">
                        Consulta tus calificaciones directamente en el portal sin necesidad de descargar.
                    </p>
                    <a href="{{ route('portal.estudiante.boletin') }}"
                       class="btn btn-sm btn-outline-primary w-100 mt-auto">
                        <i class="bi bi-bar-chart-line me-1"></i>Ver boletín
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── CERTIFICADOS Y CONSTANCIAS ─────────────────────────────────────────── --}}
<div class="mb-4">
    <h6 class="fw-bold mb-3" style="color:#1e3a6e;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;">
        <i class="bi bi-award-fill me-2" style="color:#f59e0b;"></i>Certificados y Constancias
    </h6>

    <div class="row g-3">
        {{-- Constancia de matrícula --}}
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #f59e0b!important;">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="width:48px;height:48px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-patch-check-fill" style="font-size:1.3rem;color:#fff;"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="color:#1e3a6e;font-size:.95rem;">Constancia de Matrícula</div>
                            <div class="text-muted" style="font-size:.8rem;">Documento oficial</div>
                        </div>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.8rem;flex:1;">
                        Certifica que eres estudiante activo del centro educativo en el año escolar en curso.
                    </p>
                    <a href="{{ route('portal.estudiante.constancia') }}"
                       target="_blank"
                       class="btn btn-sm btn-warning w-100 mt-auto text-dark">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── MIS NOTAS ──────────────────────────────────────────────────────────── --}}
<div class="mb-4">
    <h6 class="fw-bold mb-3" style="color:#1e3a6e;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;">
        <i class="bi bi-pencil-square me-2" style="color:#10b981;"></i>Mis Notas
    </h6>

    <div class="row g-3">
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #10b981!important;">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="width:48px;height:48px;background:linear-gradient(135deg,#10b981,#059669);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-file-earmark-bar-graph-fill" style="font-size:1.3rem;color:#fff;"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="color:#1e3a6e;font-size:.95rem;">Calificaciones PDF</div>
                            <div class="text-muted" style="font-size:.8rem;">Resumen de notas</div>
                        </div>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.8rem;flex:1;">
                        Descarga un PDF con el resumen de tus calificaciones por asignatura y período publicados.
                    </p>
                    <a href="{{ route('portal.estudiante.notas.pdf') }}"
                       target="_blank"
                       class="btn btn-sm btn-success w-100 mt-auto">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #34d399!important;">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="width:48px;height:48px;background:linear-gradient(135deg,#34d399,#10b981);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-file-earmark-excel-fill" style="font-size:1.3rem;color:#fff;"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="color:#1e3a6e;font-size:.95rem;">Calificaciones Excel</div>
                            <div class="text-muted" style="font-size:.8rem;">Hoja de cálculo</div>
                        </div>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.8rem;flex:1;">
                        Exporta tus notas en formato Excel para análisis personal o archivo.
                    </p>
                    <a href="{{ route('portal.estudiante.notas.excel') }}"
                       class="btn btn-sm btn-outline-success w-100 mt-auto">
                        <i class="bi bi-file-earmark-excel me-1"></i>Descargar Excel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── MI ASISTENCIA ──────────────────────────────────────────────────────── --}}
<div class="mb-4">
    <h6 class="fw-bold mb-3" style="color:#1e3a6e;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;">
        <i class="bi bi-clipboard-check-fill me-2" style="color:#0ea5e9;"></i>Mi Asistencia
    </h6>

    <div class="row g-3">
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #0ea5e9!important;">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="width:48px;height:48px;background:linear-gradient(135deg,#0ea5e9,#0284c7);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-calendar-check-fill" style="font-size:1.3rem;color:#fff;"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="color:#1e3a6e;font-size:.95rem;">Asistencia PDF</div>
                            <div class="text-muted" style="font-size:.8rem;">Reporte de asistencia</div>
                        </div>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.8rem;flex:1;">
                        Descarga el reporte de tu asistencia por materia, incluyendo presencias, ausencias y tardanzas.
                    </p>
                    <a href="{{ route('portal.estudiante.asistencia.pdf') }}"
                       target="_blank"
                       class="btn btn-sm btn-danger w-100 mt-auto">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #38bdf8!important;">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="width:48px;height:48px;background:linear-gradient(135deg,#38bdf8,#0ea5e9);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-table" style="font-size:1.3rem;color:#fff;"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="color:#1e3a6e;font-size:.95rem;">Asistencia Excel</div>
                            <div class="text-muted" style="font-size:.8rem;">Hoja de cálculo</div>
                        </div>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.8rem;flex:1;">
                        Exporta el registro de asistencia en formato Excel desglosado por materia.
                    </p>
                    <a href="{{ route('portal.estudiante.asistencia.excel') }}"
                       class="btn btn-sm btn-outline-info w-100 mt-auto">
                        <i class="bi bi-file-earmark-excel me-1"></i>Descargar Excel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── MI HORARIO ─────────────────────────────────────────────────────────── --}}
<div class="mb-4">
    <h6 class="fw-bold mb-3" style="color:#1e3a6e;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;">
        <i class="bi bi-calendar3 me-2" style="color:#8b5cf6;"></i>Mi Horario
    </h6>

    <div class="row g-3">
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #8b5cf6!important;">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="width:48px;height:48px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-file-earmark-pdf-fill" style="font-size:1.3rem;color:#fff;"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="color:#1e3a6e;font-size:.95rem;">Horario PDF</div>
                            <div class="text-muted" style="font-size:.8rem;">Distribución semanal</div>
                        </div>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.8rem;flex:1;">
                        Descarga tu horario semanal de clases en PDF (orientación horizontal).
                    </p>
                    <a href="{{ route('portal.estudiante.horario.pdf') }}"
                       target="_blank"
                       class="btn btn-sm btn-danger w-100 mt-auto">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #a78bfa!important;">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="width:48px;height:48px;background:linear-gradient(135deg,#a78bfa,#8b5cf6);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-file-earmark-spreadsheet-fill" style="font-size:1.3rem;color:#fff;"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="color:#1e3a6e;font-size:.95rem;">Horario Excel</div>
                            <div class="text-muted" style="font-size:.8rem;">Hoja de cálculo</div>
                        </div>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.8rem;flex:1;">
                        Exporta tu horario en Excel con todos los detalles de asignaturas y franjas horarias.
                    </p>
                    <a href="{{ route('portal.estudiante.horario.excel') }}"
                       class="btn btn-sm btn-outline-secondary w-100 mt-auto">
                        <i class="bi bi-file-earmark-excel me-1"></i>Descargar Excel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── PLANIFICACIONES ─────────────────────────────────────────────────────── --}}
<div class="mb-4">
    <h6 class="fw-bold mb-3" style="color:#1e3a6e;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;">
        <i class="bi bi-journal-text me-2" style="color:#f97316;"></i>Planificaciones
    </h6>

    <div class="row g-3">
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #f97316!important;">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="width:48px;height:48px;background:linear-gradient(135deg,#f97316,#ea580c);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-journal-richtext" style="font-size:1.3rem;color:#fff;"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="color:#1e3a6e;font-size:.95rem;">Planificaciones PDF</div>
                            <div class="text-muted" style="font-size:.8rem;">Módulos publicados</div>
                        </div>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.8rem;flex:1;">
                        Descarga en PDF las planificaciones de módulos publicadas por tus docentes.
                    </p>
                    <a href="{{ route('portal.estudiante.planificaciones.pdf') }}"
                       target="_blank"
                       class="btn btn-sm btn-danger w-100 mt-auto">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #fb923c!important;">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="width:48px;height:48px;background:linear-gradient(135deg,#fb923c,#f97316);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-grid-3x3-gap-fill" style="font-size:1.3rem;color:#fff;"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="color:#1e3a6e;font-size:.95rem;">Planificaciones Excel</div>
                            <div class="text-muted" style="font-size:.8rem;">Hoja de cálculo</div>
                        </div>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.8rem;flex:1;">
                        Exporta el listado de planificaciones en formato Excel con todos los módulos y actividades.
                    </p>
                    <a href="{{ route('portal.estudiante.planificaciones.excel') }}"
                       class="btn btn-sm btn-outline-warning w-100 mt-auto">
                        <i class="bi bi-file-earmark-excel me-1"></i>Descargar Excel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── OBSERVACIONES ───────────────────────────────────────────────────────── --}}
<div class="mb-2">
    <h6 class="fw-bold mb-3" style="color:#1e3a6e;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;">
        <i class="bi bi-chat-square-text-fill me-2" style="color:#ec4899;"></i>Observaciones Docentes
    </h6>

    <div class="row g-3">
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #ec4899!important;">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="width:48px;height:48px;background:linear-gradient(135deg,#ec4899,#db2777);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-chat-square-text-fill" style="font-size:1.3rem;color:#fff;"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="color:#1e3a6e;font-size:.95rem;">Observaciones PDF</div>
                            <div class="text-muted" style="font-size:.8rem;">Comentarios de docentes</div>
                        </div>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.8rem;flex:1;">
                        Descarga en PDF las observaciones y comentarios que tus docentes han registrado sobre ti.
                    </p>
                    <a href="{{ route('portal.estudiante.observaciones.pdf') }}"
                       target="_blank"
                       class="btn btn-sm btn-danger w-100 mt-auto">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #f472b6!important;">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div style="width:48px;height:48px;background:linear-gradient(135deg,#f472b6,#ec4899);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-file-earmark-spreadsheet" style="font-size:1.3rem;color:#fff;"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="color:#1e3a6e;font-size:.95rem;">Observaciones Excel</div>
                            <div class="text-muted" style="font-size:.8rem;">Hoja de cálculo</div>
                        </div>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.8rem;flex:1;">
                        Exporta el historial de observaciones docentes en formato Excel.
                    </p>
                    <a href="{{ route('portal.estudiante.observaciones.excel') }}"
                       class="btn btn-sm btn-outline-danger w-100 mt-auto">
                        <i class="bi bi-file-earmark-excel me-1"></i>Descargar Excel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endif
@endsection
