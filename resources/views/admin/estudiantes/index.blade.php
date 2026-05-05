@extends('layouts.admin')
@section('page-title', 'Estudiantes')

@push('styles')
<style>
    .avatar-initials {
        width: 40px; height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2a4f96, var(--primary));
        color: #fff;
        font-size: .75rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .avatar-img {
        width: 40px; height: 40px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
    }
    .table-hover tbody tr:hover { background: #f8faff; }
    .status-badge {
        font-size: .72rem;
        font-weight: 600;
        padding: .28rem .65rem;
        border-radius: 20px;
        letter-spacing: .03em;
    }
    .badge-activo      { background: #d1fae5; color: #065f46; }
    .badge-inactivo    { background: #fee2e2; color: #991b1b; }
    .badge-egresado    { background: #dbeafe; color: #1e40af; }
    .badge-transferido { background: #fef3c7; color: #92400e; }
    .btn-action {
        padding: .25rem .55rem;
        font-size: .78rem;
        border-radius: 6px;
        line-height: 1.4;
    }
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #9ca3af;
    }
    .empty-state i { font-size: 3.5rem; display: block; margin-bottom: 1rem; opacity: .4; }
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: .75rem;
    }
    .page-header h1 {
        font-size: 1.45rem;
        font-weight: 800;
        color: var(--primary);
        margin: 0;
    }
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        align-items: center;
    }
    .filter-bar .form-control,
    .filter-bar .form-select { font-size: .84rem; border-radius: 8px; padding: .45rem .8rem; }

    /* Botones A-Z */
    .btn-letra {
        padding: .18rem .38rem;
        font-size: .7rem;
        font-weight: 700;
        line-height: 1.4;
        border-radius: 5px;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        color: #374151;
        min-width: 24px;
        transition: background .12s, border-color .12s, color .12s;
    }
    .btn-letra:hover { background: #eff6ff; border-color: var(--primary); color: var(--primary); }
    .btn-letra.activa {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }
    .btn-letra-reset {
        padding: .18rem .42rem;
        font-size: .7rem;
        font-weight: 700;
        border-radius: 5px;
        border: 1px solid #fca5a5;
        background: #fee2e2;
        color: #991b1b;
        transition: background .12s;
    }
    .btn-letra-reset:hover { background: #fecaca; }

    /* Badges de filtros activos */
    .badge-filtro {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        font-size: .75rem;
        background: #eff6ff;
        color: #1e40af;
        border: 1px solid #bfdbfe;
        border-radius: 20px;
        padding: .2rem .65rem;
        font-weight: 500;
    }

    [data-theme="dark"] .badge-activo { background: #052e16; color: #4ade80; }
    [data-theme="dark"] .badge-inactivo { background: #1c0000; color: #f87171; }
    [data-theme="dark"] .grupo-badge { background: #0c1f3f; color: #93c5fd; border-color: #1d4ed8; }
</style>
@endpush

@section('content')

<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Estudiantes'],
]" />

{{-- Flash messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        {{ session('success') }}
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Banner: students without enrollment --}}
@if(!$ciclo)
@php
    $sinMatricula = \App\Models\Estudiante::whereDoesntHave('matriculas', fn($q) =>
        $q->where('school_year_id', \App\Models\SchoolYear::actual()?->id)
    )->count();
@endphp
@if($sinMatricula > 0)
<div class="alert alert-warning d-flex gap-2 align-items-center mb-3 py-2 px-3" style="border-radius:10px;font-size:.84rem;">
    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
    <div class="flex-grow-1">
        <strong>{{ $sinMatricula }} estudiante(s)</strong> sin matrícula en el año escolar actual.
        Para asignarles un grupo y sección, ve a
        <a href="{{ route('admin.grupos.index') }}" class="fw-semibold">Grupos/Cursos</a>
        y usa el botón <strong>"Ver"</strong> en cada grupo para matricular estudiantes.
    </div>
    <a href="{{ route('admin.grupos.index') }}" class="btn btn-sm btn-warning fw-semibold" style="border-radius:7px;white-space:nowrap;">
        <i class="bi bi-people me-1"></i>Ir a Grupos
    </a>
</div>
@endif
@endif

{{-- Page header --}}
<div class="page-header">
    <div>
        <h1><i class="bi bi-people-fill me-2" style="color:var(--secondary);"></i>Estudiantes
            @isset($contexto)
                <span class="badge ms-2 px-2 py-1" style="font-size:.7rem;background:var(--primary-light);color:var(--primary);border-radius:8px;font-weight:700;vertical-align:middle;">{{ $contexto }}</span>
            @endisset
        </h1>
        <p class="text-muted mb-0" style="font-size:.85rem;">
            Gestión del alumnado
            @if($estudiantes->total() > 0)
                &nbsp;·&nbsp; <strong>{{ $estudiantes->total() }}</strong> registros
            @endif
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap justify-content-end">
        @if($ciclo || true)
        <a href="{{ route('admin.estudiantes.import', array_filter(['ciclo'=>$ciclo,'area'=>$area])) }}"
           class="btn btn-sm px-3 py-2"
           style="background:#e5e7eb;color:#374151;border-radius:8px;font-size:.85rem;font-weight:600;">
            <i class="bi bi-upload me-1"></i>Importar
        </a>
        @endif
        <a href="{{ route('admin.estudiantes.lista-excel', request()->query()) }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('admin.estudiantes.lista-pdf', request()->query()) }}" target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.representantes.lista-pdf') }}" class="btn btn-sm"
           style="background:#dc2626;color:#fff;" title="PDF directorio de representantes">
            <i class="bi bi-people-fill me-1"></i>Rep. PDF
        </a>
        <a href="{{ route('admin.representantes.lista-excel') }}" class="btn btn-sm"
           style="background:#059669;color:#fff;" title="Exportar directorio de representantes">
            <i class="bi bi-people-fill me-1"></i>Rep. Excel
        </a>
        <a href="{{ route('admin.estudiantes.create') }}" class="btn btn-sm px-3 py-2"
           style="background:var(--primary);color:#fff;border-radius:8px;font-size:.85rem;font-weight:600;">
            <i class="bi bi-plus-lg me-1"></i>Nuevo Estudiante
        </a>
    </div>
</div>

{{-- Aviso: lista filtrada por ciclo no muestra estudiantes sin matrícula --}}
@if($ciclo && $estudiantes->total() >= 0)
<div class="alert alert-info d-flex gap-2 align-items-start mb-3 py-2 px-3" style="border-radius:10px;font-size:.82rem;">
    <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
    <div>
        Mostrando estudiantes <strong>matriculados</strong> en este ciclo.
        Los estudiantes importados sin matrícula aparecen en la
        <a href="{{ route('admin.estudiantes.index') }}" class="fw-semibold">lista general</a>.
    </div>
</div>
@endif

{{-- ══ FILTROS ══ --}}
<div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
    <div class="card-body py-3 px-4">
        <form method="GET" action="{{ route('admin.estudiantes.index') }}" id="filterForm">
            {{-- Preservar ciclo/area si están activos --}}
            @if($ciclo) <input type="hidden" name="ciclo" value="{{ $ciclo }}"> @endif
            @if($area)  <input type="hidden" name="area"  value="{{ $area }}">  @endif

            {{-- ── Fila 1: búsqueda principal ── --}}
            <div class="d-flex flex-wrap gap-2 align-items-center mb-2 filter-row">

                {{-- Campo de búsqueda --}}
                <div class="input-group" style="max-width:320px;min-width:200px;flex:1;">
                    <span class="input-group-text bg-white border-end-0" style="border-radius:8px 0 0 8px;">
                        <i class="bi bi-search text-muted" style="font-size:.83rem;"></i>
                    </span>
                    <input type="text"
                           id="buscarInput"
                           name="buscar"
                           value="{{ $buscar }}"
                           class="form-control border-start-0 border-end-0 ps-0"
                           placeholder="Buscar…"
                           autocomplete="off"
                           style="border-radius:0;">
                    @if($buscar)
                    <button type="button" class="btn btn-outline-secondary border-start-0"
                            style="border-radius:0 8px 8px 0;"
                            onclick="document.getElementById('buscarInput').value='';this.closest('form').submit();">
                        <i class="bi bi-x-lg" style="font-size:.75rem;"></i>
                    </button>
                    @else
                    <span class="input-group-text bg-white" style="border-radius:0 8px 8px 0;"></span>
                    @endif
                </div>

                {{-- Selector de campo --}}
                <select name="campo" id="campoSelect"
                        class="form-select form-select-sm"
                        style="max-width:170px;border-radius:8px;font-size:.82rem;"
                        onchange="this.closest('form').submit()">
                    <option value="todo"     {{ $campo==='todo'     ? 'selected':'' }}>Todos los campos</option>
                    <option value="nombre"   {{ $campo==='nombre'   ? 'selected':'' }}>Por nombre</option>
                    <option value="apellido" {{ $campo==='apellido' ? 'selected':'' }}>Por apellido</option>
                    <option value="cedula"   {{ $campo==='cedula'   ? 'selected':'' }}>Por cédula</option>
                    <option value="codigo"   {{ $campo==='codigo'   ? 'selected':'' }}>Por código / matrícula</option>
                </select>

                {{-- Grado --}}
                <select name="grado"
                        class="form-select form-select-sm"
                        style="max-width:185px;border-radius:8px;font-size:.82rem;"
                        onchange="this.closest('form').submit()">
                    <option value="">Todos los grados</option>
                    @foreach($grados as $g)
                        <option value="{{ $g->id }}" {{ (string)$gradoId===(string)$g->id ? 'selected':'' }}>
                            {{ $g->nombre }}
                        </option>
                    @endforeach
                </select>

                {{-- Botón buscar (Enter / submit explícito) --}}
                <button type="submit" class="btn btn-sm px-3"
                        style="background:var(--primary);color:#fff;border-radius:8px;font-size:.83rem;white-space:nowrap;">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>

                @if($hayFiltros)
                <a href="{{ route('admin.estudiantes.index', array_filter(['ciclo'=>$ciclo,'area'=>$area])) }}"
                   class="btn btn-sm btn-outline-secondary" style="border-radius:8px;font-size:.83rem;">
                    <i class="bi bi-x-circle me-1"></i>Limpiar
                </a>
                @endif
            </div>

            {{-- ── Fila 2: filtro A–Z apellido ── --}}
            <div class="d-flex align-items-center gap-1 flex-wrap">
                <span style="font-size:.7rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;">Apellido:</span>
                @foreach(range('A','Z') as $l)
                    <button type="submit" name="letra" value="{{ $l }}"
                            class="btn btn-letra {{ $letra===$l ? 'activa' : '' }}"
                            title="Apellidos que empiezan con {{ $l }}">{{ $l }}</button>
                @endforeach
                @if($letra)
                    <button type="submit" name="letra" value=""
                            class="btn btn-letra-reset" title="Quitar filtro de letra">
                        <i class="bi bi-x"></i>
                    </button>
                @endif
            </div>

        </form>
    </div>
</div>

{{-- ── Badges de filtros activos ── --}}
@if($hayFiltros)
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <span style="font-size:.75rem;font-weight:600;color:#9ca3af;">Filtrando por:</span>
    @if($buscar)
        @php $campoLabel = match($campo) {
            'nombre'   => 'Nombre',
            'apellido' => 'Apellido',
            'cedula'   => 'Cédula',
            'codigo'   => 'Código',
            default    => 'Cualquier campo',
        }; @endphp
        <span class="badge-filtro">
            <i class="bi bi-search me-1"></i>{{ $campoLabel }}: "<strong>{{ $buscar }}</strong>"
        </span>
    @endif
    @if($letra)
        <span class="badge-filtro">
            <i class="bi bi-sort-alpha-down me-1"></i>Apellido empieza con <strong>{{ $letra }}</strong>
        </span>
    @endif
    @if($gradoId && ($g = $grados->firstWhere('id', $gradoId)))
        <span class="badge-filtro">
            <i class="bi bi-mortarboard me-1"></i>Grado: <strong>{{ $g->nombre }}</strong>
        </span>
    @endif
    <span style="font-size:.75rem;color:#6b7280;">
        — {{ $estudiantes->total() }} {{ $estudiantes->total() === 1 ? 'resultado' : 'resultados' }}
    </span>
</div>
@endif

{{-- Table card --}}
<div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
    @if($estudiantes->isEmpty())
        <div class="empty-state-enhanced">
            <div class="empty-illustration"><i class="bi bi-people"></i></div>
            <div class="empty-title">
                @if($hayFiltros)
                    No se encontraron estudiantes con ese filtro
                @elseif($ciclo)
                    No hay estudiantes matriculados en este ciclo
                @else
                    No hay estudiantes registrados
                @endif
            </div>
            <div class="empty-desc">
                @if($hayFiltros)
                    Intenta con otros términos de búsqueda o limpia los filtros.
                @elseif($ciclo)
                    Los estudiantes importados sin matrícula no aparecen aquí.
                    Ve a la <strong>lista general</strong> para verlos y luego asígnalos a un grupo desde <strong>Matrículas</strong>.
                @else
                    Comienza importando estudiantes o creando uno manualmente.
                @endif
            </div>
            <div class="empty-actions">
                @if($hayFiltros)
                    <a href="{{ route('admin.estudiantes.index', array_filter(['ciclo'=>$ciclo,'area'=>$area])) }}"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle me-1"></i>Limpiar filtros
                    </a>
                @elseif($ciclo)
                    <a href="{{ route('admin.estudiantes.index') }}"
                       class="btn btn-primary btn-sm">
                        <i class="bi bi-people me-1"></i>Ver lista general
                    </a>
                    <a href="{{ route('admin.estudiantes.import', array_filter(['ciclo'=>$ciclo,'area'=>$area])) }}"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-upload me-1"></i>Importar estudiantes
                    </a>
                @else
                    <a href="{{ route('admin.estudiantes.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-person-plus me-1"></i>Nuevo Estudiante
                    </a>
                    <a href="{{ route('admin.estudiantes.import') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-upload me-1"></i>Importar
                    </a>
                @endif
            </div>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.875rem;">
                <thead style="background:#f8faff;border-bottom:2px solid #e5e7eb;">
                    <tr>
                        <th class="ps-4 py-3 text-center" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;width:60px;">ID</th>
                        <th class="py-3" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Estudiante</th>
                        <th class="py-3" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Matrícula #</th>
                        <th class="py-3" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Cédula</th>
                        <th class="py-3" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Nacimiento</th>
                        <th class="py-3" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Grupo / Sección</th>
                        <th class="py-3 text-center" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Estado</th>
                        <th class="py-3 pe-4 text-end" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($estudiantes as $estudiante)
                    <tr>
                        <td class="ps-4 py-3 text-center">
                            <span style="font-size:.78rem;font-weight:700;color:#2563eb;font-family:monospace;">
                                #{{ str_pad($estudiante->id, 4, '0', STR_PAD_LEFT) }}
                            </span>
                        </td>
                        <td class="py-3">
                            <div class="d-flex align-items-center gap-3">
                                @if($estudiante->foto)
                                    <img src="{{ asset('storage/'.$estudiante->foto) }}"
                                         alt="{{ $estudiante->nombres }}"
                                         class="avatar-img">
                                @else
                                    <div class="avatar-initials">
                                        {{ substr($estudiante->nombres,0,1) }}{{ substr($estudiante->apellidos,0,1) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-600 est-nombre-tabla">
                                        {{ $estudiante->apellidos }}, {{ $estudiante->nombres }}
                                    </div>
                                    @if($estudiante->email)
                                        <div style="font-size:.76rem;color:#9ca3af;">{{ $estudiante->email }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            <span style="font-family:monospace;color:#2563eb;font-size:.82rem;font-weight:700;">{{ $estudiante->numero_matricula }}</span>
                        </td>
                        <td class="py-3">
                            <span style="font-family:monospace;color:#374151;">{{ $estudiante->cedula ?? '—' }}</span>
                        </td>
                        <td class="py-3" style="color:#374151;">
                            {{ $estudiante->fecha_nacimiento?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="py-3">
                            @php
                                $matriculaActiva = $estudiante->matriculas->first();
                            @endphp
                            @if($matriculaActiva && $matriculaActiva->grupo)
                                <div style="font-size:.82rem;font-weight:600;color:#111827;">
                                    {{ $matriculaActiva->grupo->nombreCorto ?? $matriculaActiva->grupo->nombre_completo }}
                                </div>
                                <div style="font-size:.72rem;color:#9ca3af;">
                                    {{ $matriculaActiva->grupo->grado->nivel <= 3 ? 'Primer Ciclo' : 'Segundo Ciclo' }}
                                </div>
                            @else
                                <span style="font-size:.78rem;color:#d1d5db;">Sin matrícula</span>
                            @endif
                        </td>
                        <td class="py-3 text-center">
                            @php
                                $badgeClass = match($estudiante->estado) {
                                    'activo'      => 'badge-activo',
                                    'inactivo'    => 'badge-inactivo',
                                    'egresado'    => 'badge-egresado',
                                    'transferido' => 'badge-transferido',
                                    default       => 'badge-inactivo',
                                };
                                $label = match($estudiante->estado) {
                                    'activo'      => 'Activo',
                                    'inactivo'    => 'Inactivo',
                                    'egresado'    => 'Egresado',
                                    'transferido' => 'Transferido',
                                    default       => ucfirst($estudiante->estado),
                                };
                            @endphp
                            <span class="status-badge {{ $badgeClass }}">{{ $label }}</span>
                        </td>
                        <td class="py-3 pe-4 text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('admin.estudiantes.show', $estudiante) }}"
                                   class="btn btn-action btn-outline-primary" title="Ver perfil">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.estudiantes.edit', $estudiante) }}"
                                   class="btn btn-action btn-outline-secondary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Eliminar"
                                        onclick="confirmarEliminarEstudiante(
                                            {{ $estudiante->id }},
                                            '{{ addslashes($estudiante->apellidos . ', ' . $estudiante->nombres) }}',
                                            '{{ $estudiante->numero_matricula ?? '' }}',
                                            '{{ route('admin.estudiantes.destroy', $estudiante) }}'
                                        )">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($estudiantes->hasPages())
            <div class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <p class="text-muted mb-0" style="font-size:.82rem;">
                    Mostrando {{ $estudiantes->firstItem() }}–{{ $estudiantes->lastItem() }} de {{ $estudiantes->total() }} estudiantes
                </p>
                <div>{{ $estudiantes->links() }}</div>
            </div>
        @endif
    @endif
</div>

{{-- Modal de eliminación único y reutilizable --}}
<div class="modal fade" id="modalDeleteGlobal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content border-0 shadow" style="border-radius:16px;">
            <div class="modal-body p-4 text-center">
                <div class="mb-3" style="font-size:2.2rem;">
                    <i class="bi bi-exclamation-triangle" style="color:var(--secondary,#dc3545);"></i>
                </div>
                <h5 class="fw-bold mb-2" style="color:#111827;">¿Eliminar estudiante?</h5>
                <p class="text-muted mb-4" style="font-size:.87rem;line-height:1.5;">
                    Se eliminará permanentemente el registro de
                    <strong id="mdel-nombre">—</strong>
                    <span class="d-block mt-1" style="font-size:.8rem;">(Matr. <span id="mdel-matricula">—</span>)</span>
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                    <form id="mdel-form" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger px-4 fw-semibold">
                            <i class="bi bi-trash me-1"></i>Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmarEliminarEstudiante(id, nombre, matricula, deleteRoute) {
    document.getElementById('mdel-nombre').textContent    = nombre;
    document.getElementById('mdel-matricula').textContent = matricula;
    document.getElementById('mdel-form').action           = deleteRoute;
    new bootstrap.Modal(document.getElementById('modalDeleteGlobal')).show();
}
</script>
@endpush

@endsection
