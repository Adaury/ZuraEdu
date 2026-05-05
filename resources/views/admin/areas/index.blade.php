@extends('layouts.admin')

@section('page-title', 'Áreas')

@section('content')
<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <div>
        <h4 class="fw-bold mb-1" style="color:#1e3a6e;"><i class="bi bi-diagram-2 me-2"></i>Áreas del Politécnico</h4>
        <p class="text-muted mb-0" style="font-size:.875rem;">Selecciona el área para visualizar sus docentes y asignaciones.</p>
    </div>
    <a href="{{ route('admin.asignaturas.create') }}" class="btn btn-primary btn-sm ms-auto" style="border-radius:8px;">
        <i class="bi bi-plus-lg me-1"></i>Nueva Materia
    </a>
</div>

{{-- Resumen de materias por área --}}
@if($areas->isNotEmpty())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header border-bottom" style="background:#f8faff;font-size:.82rem;font-weight:700;color:#1e3a6e;">
        <i class="bi bi-book me-2"></i>Materias por Área Curricular
        <a href="{{ route('admin.asignaturas.index') }}" class="btn btn-sm btn-outline-secondary ms-2" style="font-size:.72rem;border-radius:6px;">Ver todas</a>
    </div>
    <div class="card-body p-0">
        <div style="overflow-x:auto;">
        <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
            <thead style="background:#f8faff;font-size:.75rem;color:#6b7280;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">
                <tr>
                    <th class="ps-3">Área</th>
                    <th>Tipo</th>
                    <th class="text-center">Materias</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($areas as $ar)
            <tr>
                <td class="ps-3">
                    <span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:{{ $ar->color ?? '#6b7280' }};margin-right:.4rem;vertical-align:middle;"></span>
                    <strong>{{ $ar->nombre }}</strong>
                </td>
                <td>
                    <span class="badge {{ $ar->tipo === 'academica' ? 'bg-primary' : ($ar->tipo === 'tecnica' ? 'bg-danger' : 'bg-secondary') }}" style="font-size:.68rem;">
                        {{ ucfirst($ar->tipo) }}
                    </span>
                </td>
                <td class="text-center">
                    @if($ar->asignaturas_count > 0)
                    <span class="badge bg-success" style="font-size:.72rem;">{{ $ar->asignaturas_count }}</span>
                    @else
                    <span class="text-muted" style="font-size:.72rem;">0</span>
                    @endif
                </td>
                <td class="text-end pe-3">
                    @if($ar->asignaturas_count === 0)
                    <a href="{{ route('admin.asignaturas.create') }}?area_id={{ $ar->id }}"
                       class="btn btn-sm btn-outline-primary" style="font-size:.7rem;padding:.15rem .5rem;border-radius:5px;">
                        <i class="bi bi-plus"></i> Agregar materia
                    </a>
                    @else
                    <a href="{{ route('admin.asignaturas.index') }}?area_id={{ $ar->id }}"
                       class="btn btn-sm btn-outline-secondary" style="font-size:.7rem;padding:.15rem .5rem;border-radius:5px;">
                        Ver materias
                    </a>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
</div>
@endif

<div class="row g-4">
    <div class="col-md-6">
        <a href="{{ route('admin.areas.academica') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 p-4" style="border-left:5px solid #1e3a6e !important;transition:transform .2s,box-shadow .2s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 30px rgba(30,58,110,.15)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="width:56px;height:56px;border-radius:14px;background:#dbeafe;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:#1d4ed8;">
                        <i class="bi bi-book-half"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color:#1e293b;">Área Académica</h5>
                        <div class="text-muted" style="font-size:.82rem;">Docentes de materias generales</div>
                    </div>
                </div>
                <ul class="list-unstyled mb-0" style="font-size:.84rem;color:#4b5563;">
                    <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>Primer Ciclo: 1ro – 3ro</li>
                    <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>Segundo Ciclo: 4to – 6to</li>
                    <li><i class="bi bi-check2 text-success me-2"></i>Lengua, Matemática, CCNN, CCSS y más</li>
                </ul>
                <div class="mt-3 text-primary fw-semibold" style="font-size:.82rem;">
                    Ver Área Académica <i class="bi bi-arrow-right ms-1"></i>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-6">
        <a href="{{ route('admin.areas.tecnica') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 p-4" style="border-left:5px solid #c0392b !important;transition:transform .2s,box-shadow .2s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 30px rgba(192,57,43,.15)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="width:56px;height:56px;border-radius:14px;background:#fee2e2;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:#dc2626;">
                        <i class="bi bi-tools"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color:#1e293b;">Área Técnica</h5>
                        <div class="text-muted" style="font-size:.82rem;">Docentes de especialidades técnicas</div>
                    </div>
                </div>
                <ul class="list-unstyled mb-0" style="font-size:.84rem;color:#4b5563;">
                    <li class="mb-1"><i class="bi bi-airplane me-2" style="color:#e67e22;"></i>Turismo y Hotelería</li>
                    <li class="mb-1"><i class="bi bi-laptop me-2" style="color:#2980b9;"></i>Informática</li>
                    <li class="mb-1"><i class="bi bi-graph-up me-2" style="color:#27ae60;"></i>Mercadeo</li>
                    <li class="mb-1"><i class="bi bi-heart-pulse me-2" style="color:#8e44ad;"></i>Acondicionamiento Físico</li>
                    <li><i class="bi bi-truck me-2" style="color:#c0392b;"></i>Logística y Transporte</li>
                </ul>
                <div class="mt-3 fw-semibold" style="font-size:.82rem;color:#c0392b;">
                    Ver Área Técnica <i class="bi bi-arrow-right ms-1"></i>
                </div>
            </div>
        </a>
    </div>
</div>

@if(Auth::user()->hasAnyRole(['Administrador','Director','Coordinador Académico']))
<div class="row g-4 mt-1">
    <div class="col-md-4">
        <a href="{{ route('admin.malla.index') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm p-3 d-flex flex-row align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:10px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#16a34a;flex-shrink:0;">
                    <i class="bi bi-grid-3x3"></i>
                </div>
                <div>
                    <div class="fw-bold" style="font-size:.88rem;color:#1e293b;">Malla Curricular</div>
                    <div class="text-muted" style="font-size:.76rem;">Asignaturas por grado (MINERD)</div>
                </div>
                <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('admin.rendimiento.dashboard') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm p-3 d-flex flex-row align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:10px;background:#eff6ff;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#1d4ed8;flex-shrink:0;">
                    <i class="bi bi-bar-chart-line"></i>
                </div>
                <div>
                    <div class="fw-bold" style="font-size:.88rem;color:#1e293b;">Dashboard Rendimiento</div>
                    <div class="text-muted" style="font-size:.76rem;">Métricas y semáforo</div>
                </div>
                <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('admin.calendario.index') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm p-3 d-flex flex-row align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:10px;background:#fffbeb;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#d97706;flex-shrink:0;">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div>
                    <div class="fw-bold" style="font-size:.88rem;color:#1e293b;">Calendario Académico</div>
                    <div class="text-muted" style="font-size:.76rem;">Eventos y entregas de notas</div>
                </div>
                <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </div>
        </a>
    </div>
</div>
@endif
@endsection
