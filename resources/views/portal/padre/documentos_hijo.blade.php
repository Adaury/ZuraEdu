@extends('layouts.portal')

@section('title', 'Documentos de ' . $estudiante->nombres)

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'documentos', 'estudiante' => $estudiante])
@endsection

@section('content')
<div class="prt-page-header">
    <div>
        <h4 class="prt-page-title">
            <i class="bi bi-folder2-open me-2"></i>Documentos — {{ $estudiante->nombre_completo }}
        </h4>
        @if($matricula)
        <p class="prt-page-subtitle">
            {{ $matricula->grupo?->nombre_completo }} — {{ $schoolYear?->nombre }}
        </p>
        @endif
    </div>
    <div>
        <a href="{{ route('portal.padre.hijo', $estudiante) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver al perfil
        </a>
    </div>
</div>

@if(! $matricula)
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-folder-x" style="font-size:2.5rem;opacity:.4;"></i>
        <p class="mt-3 mb-0">El estudiante no tiene una matrícula activa. Los documentos no están disponibles.</p>
    </div>
</div>
@else

<div class="row g-4">

    {{-- Boletín --}}
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column align-items-start p-4">
                <div class="mb-3" style="width:48px;height:48px;background:#eff6ff;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-file-earmark-text-fill" style="font-size:1.5rem;color:#2563eb;"></i>
                </div>
                <h6 class="fw-bold mb-1">Boletín de Notas</h6>
                <p class="text-muted mb-3" style="font-size:.82rem;">Calificaciones por período del año escolar en curso.</p>
                <div class="mt-auto d-flex gap-2 flex-wrap">
                    <a href="{{ route('portal.padre.hijo.boletin', $estudiante) }}"
                       class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye me-1"></i>Ver
                    </a>
                    <a href="{{ route('portal.padre.hijo.boletin.pdf', $estudiante) }}" target="_blank"
                       class="btn btn-sm btn-danger">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Constancia de matrícula --}}
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column align-items-start p-4">
                <div class="mb-3" style="width:48px;height:48px;background:#f0fdf4;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-patch-check-fill" style="font-size:1.5rem;color:#16a34a;"></i>
                </div>
                <h6 class="fw-bold mb-1">Constancia de Matrícula</h6>
                <p class="text-muted mb-3" style="font-size:.82rem;">Documento oficial que certifica la inscripción del estudiante.</p>
                <div class="mt-auto">
                    <a href="{{ route('portal.padre.hijo.constancia', $estudiante) }}" target="_blank"
                       class="btn btn-sm btn-success">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Notas PDF --}}
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column align-items-start p-4">
                <div class="mb-3" style="width:48px;height:48px;background:#faf5ff;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-bar-chart-fill" style="font-size:1.5rem;color:#7c3aed;"></i>
                </div>
                <h6 class="fw-bold mb-1">Reporte de Notas</h6>
                <p class="text-muted mb-3" style="font-size:.82rem;">Resumen completo de calificaciones en formato descargable.</p>
                <div class="mt-auto d-flex gap-2 flex-wrap">
                    <a href="{{ route('portal.padre.hijo.notas-pdf', $estudiante) }}" target="_blank"
                       class="btn btn-sm btn-danger">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </a>
                    <a href="{{ route('portal.padre.hijo.notas.excel', $estudiante) }}"
                       class="btn btn-sm btn-success">
                        <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Asistencia PDF --}}
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column align-items-start p-4">
                <div class="mb-3" style="width:48px;height:48px;background:#fff7ed;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-clipboard-check-fill" style="font-size:1.5rem;color:#ea580c;"></i>
                </div>
                <h6 class="fw-bold mb-1">Reporte de Asistencia</h6>
                <p class="text-muted mb-3" style="font-size:.82rem;">Registro detallado de presencias, ausencias y tardanzas.</p>
                <div class="mt-auto d-flex gap-2 flex-wrap">
                    <a href="{{ route('portal.padre.hijo.asistencia.pdf', $estudiante) }}" target="_blank"
                       class="btn btn-sm btn-danger">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </a>
                    <a href="{{ route('portal.padre.hijo.asistencia.excel', $estudiante) }}"
                       class="btn btn-sm btn-success">
                        <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Horario PDF --}}
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column align-items-start p-4">
                <div class="mb-3" style="width:48px;height:48px;background:#f0f9ff;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-calendar3" style="font-size:1.5rem;color:#0284c7;"></i>
                </div>
                <h6 class="fw-bold mb-1">Horario de Clases</h6>
                <p class="text-muted mb-3" style="font-size:.82rem;">Distribución semanal de materias y docentes del grupo.</p>
                <div class="mt-auto d-flex gap-2 flex-wrap">
                    <a href="{{ route('portal.padre.hijo.horario.pdf', $estudiante) }}" target="_blank"
                       class="btn btn-sm btn-danger">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </a>
                    <a href="{{ route('portal.padre.hijo.horario.excel', $estudiante) }}"
                       class="btn btn-sm btn-success">
                        <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Estado de cuenta (solo si módulo pagos activo) --}}
    @if($moduloPagos)
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column align-items-start p-4">
                <div class="mb-3" style="width:48px;height:48px;background:#fdf2f8;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-credit-card-fill" style="font-size:1.5rem;color:#db2777;"></i>
                </div>
                <h6 class="fw-bold mb-1">Estado de Cuenta</h6>
                <p class="text-muted mb-3" style="font-size:.82rem;">Detalle de pagos realizados y saldos pendientes del año escolar.</p>
                <div class="mt-auto d-flex gap-2 flex-wrap">
                    <a href="{{ route('portal.padre.hijo.estado-cuenta', $estudiante) }}"
                       class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye me-1"></i>Ver
                    </a>
                    <a href="{{ route('portal.padre.hijo.estado-cuenta.pdf', $estudiante) }}" target="_blank"
                       class="btn btn-sm btn-danger">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endif
@endsection
