@extends('layouts.admin')
@section('page-title', 'Centro de Importaciones')

@push('styles')
<style>
.imp-hub-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    padding: 2rem 1.75rem;
    transition: box-shadow .2s, border-color .2s, transform .15s;
    cursor: default;
    height: 100%;
}
.imp-hub-card:hover {
    box-shadow: 0 8px 32px rgba(30,58,110,.1);
    border-color: var(--primary);
    transform: translateY(-2px);
}
.imp-hub-card .hub-icon {
    width: 56px; height: 56px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem;
    margin-bottom: 1rem;
}
.imp-hub-card .hub-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--primary);
    margin-bottom: .4rem;
}
.imp-hub-card .hub-desc {
    font-size: .83rem;
    color: #6b7280;
    line-height: 1.5;
    margin-bottom: 1.25rem;
}
.imp-hub-card .hub-cols {
    display: flex; flex-wrap: wrap; gap: .35rem;
    margin-bottom: 1.25rem;
}
.imp-hub-card .hub-col-badge {
    font-size: .7rem; font-weight: 600;
    padding: .2rem .5rem; border-radius: 20px;
    background: #f0f4fb; color: var(--primary);
    border: 1px solid #c7d7f7;
    font-family: monospace;
}
.tip-card {
    background: #f0fdf4; border: 1px solid #bbf7d0;
    border-radius: 10px; padding: .9rem 1rem;
    font-size: .82rem; color: #374151;
    margin-bottom: .6rem;
}
.tip-card strong { color: #166534; }
[data-theme="dark"] .imp-hub-card { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .imp-hub-card:hover { border-color: var(--primary); }
[data-theme="dark"] .imp-hub-card .hub-desc { color: #94a3b8; }
[data-theme="dark"] .hub-col-badge { background: #1e3a6e; color: #93c5fd; border-color: #2563eb; }
[data-theme="dark"] .tip-card { background: #052e16; border-color: #166534; color: #94a3b8; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Dashboard
    </a>
    <div>
        <h1 class="mb-0" style="font-size:1.4rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-cloud-arrow-up-fill me-2" style="color:var(--secondary);"></i>Centro de Importaciones Masivas
        </h1>
        <div class="text-muted" style="font-size:.8rem;">
            Carga datos desde archivos CSV o Excel (xlsx)
            @if($schoolYear)
                &nbsp;·&nbsp;<span class="fw-semibold">{{ $schoolYear->nombre }}</span>
            @endif
        </div>
    </div>
</div>

{{-- Módulos --}}
<div class="row g-4 mb-5">

    {{-- MÓDULO 1 — CALIFICACIONES --}}
    <div class="col-md-6">
        <div class="imp-hub-card d-flex flex-column">
            <div class="hub-icon" style="background:#eff6ff;">
                <i class="bi bi-journal-check" style="color:#2563eb;"></i>
            </div>
            <div class="hub-title">Importar Calificaciones Académicas</div>
            <div class="hub-desc">
                Carga masivamente las notas de las 4 competencias MINERD para los 4 períodos.
                Ideal para ingresar resultados desde registros externos o al cierre del período.
            </div>
            <div class="hub-cols">
                @foreach(['comp1_p1','comp2_p1','comp3_p1','comp4_p1','…','comp4_p4'] as $col)
                    <span class="hub-col-badge">{{ $col }}</span>
                @endforeach
            </div>
            <div class="mt-auto d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.importaciones.calificaciones') }}"
                   class="btn fw-semibold flex-fill"
                   style="background:var(--primary);color:#fff;border-radius:8px;">
                    <i class="bi bi-upload me-1"></i>Ir al módulo
                </a>
                <a href="{{ route('admin.importaciones.calificaciones.plantilla', ['format'=>'xlsx']) }}"
                   class="btn btn-outline-success fw-semibold"
                   style="border-radius:8px;" title="Descargar plantilla Excel">
                    <i class="bi bi-filetype-xlsx"></i>
                </a>
                <a href="{{ route('admin.importaciones.calificaciones.plantilla', ['format'=>'csv']) }}"
                   class="btn btn-outline-secondary fw-semibold"
                   style="border-radius:8px;" title="Descargar plantilla CSV">
                    <i class="bi bi-filetype-csv"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- MÓDULO 2 — ESTUDIANTES --}}
    <div class="col-md-6">
        <div class="imp-hub-card d-flex flex-column">
            <div class="hub-icon" style="background:#f0fdf4;">
                <i class="bi bi-people-fill" style="color:#16a34a;"></i>
            </div>
            <div class="hub-title">Importar Lista de Estudiantes</div>
            <div class="hub-desc">
                Registra nuevos estudiantes desde un archivo CSV o Excel. Crea automáticamente
                la matrícula en el grupo seleccionado y opcionalmente la cuenta del portal del representante.
            </div>
            <div class="hub-cols">
                @foreach(['nombres','apellidos','cedula','fecha_nacimiento','sexo','direccion','nombre_representante','telefono_representante','email_representante'] as $col)
                    <span class="hub-col-badge">{{ $col }}</span>
                @endforeach
            </div>
            <div class="mt-auto d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.importaciones.estudiantes') }}"
                   class="btn fw-semibold flex-fill"
                   style="background:#16a34a;color:#fff;border-radius:8px;">
                    <i class="bi bi-upload me-1"></i>Ir al módulo
                </a>
                <a href="{{ route('admin.importaciones.estudiantes.plantilla', ['format'=>'xlsx']) }}"
                   class="btn btn-outline-success fw-semibold"
                   style="border-radius:8px;" title="Descargar plantilla Excel">
                    <i class="bi bi-filetype-xlsx"></i>
                </a>
                <a href="{{ route('admin.importaciones.estudiantes.plantilla', ['format'=>'csv']) }}"
                   class="btn btn-outline-secondary fw-semibold"
                   style="border-radius:8px;" title="Descargar plantilla CSV">
                    <i class="bi bi-filetype-csv"></i>
                </a>
            </div>
        </div>
    </div>

</div>

{{-- Consejos generales --}}
<div class="row g-4">
    <div class="col-md-6">
        <h6 class="fw-bold mb-3" style="color:var(--primary);font-size:.85rem;letter-spacing:.06em;text-transform:uppercase;">
            <i class="bi bi-lightbulb-fill me-1"></i>Buenas prácticas
        </h6>
        <div class="tip-card">
            <strong>Descarga siempre la plantilla primero.</strong>
            Garantiza el orden exacto de columnas y evita errores de formato.
        </div>
        <div class="tip-card" style="background:#eff6ff;border-color:#bfdbfe;">
            <strong style="color:#1e40af;">Los valores existentes se actualizan.</strong>
            Si ya hay notas para ese estudiante y asignación, se sobrescriben con los nuevos valores.
        </div>
        <div class="tip-card" style="background:#fffbeb;border-color:#fde68a;">
            <strong style="color:#92400e;">Filas con errores se omiten.</strong>
            El sistema procesa las filas válidas y reporta exactamente cuáles fallaron.
        </div>
    </div>
    <div class="col-md-6">
        <h6 class="fw-bold mb-3" style="color:var(--primary);font-size:.85rem;letter-spacing:.06em;text-transform:uppercase;">
            <i class="bi bi-file-earmark-check-fill me-1"></i>Formatos aceptados
        </h6>
        <div class="tip-card">
            <div class="d-flex flex-wrap gap-2">
                @foreach(['CSV (.csv)','TXT (.txt)','Excel 2007+ (.xlsx)','Excel 97-2003 (.xls)'] as $fmt)
                    <span class="badge"
                          style="background:#e0f2fe;color:#0369a1;font-size:.78rem;font-weight:600;border-radius:8px;padding:.35rem .7rem;">
                        <i class="bi bi-file-earmark-check me-1"></i>{{ $fmt }}
                    </span>
                @endforeach
            </div>
            <div class="mt-2" style="font-size:.78rem;color:#6b7280;">
                Delimitador CSV auto-detectado: coma, punto y coma, tabulador o pipe.
                Codificación: UTF-8 (con o sin BOM) o Windows-1252.
            </div>
        </div>
        <div class="tip-card" style="background:#fdf4ff;border-color:#e9d5ff;">
            <strong style="color:#6b21a8;">Tamaño máximo:</strong> 10 MB por archivo.
            Para archivos grandes, divide en lotes de 500 filas.
        </div>
    </div>
</div>

@endsection
