@extends('layouts.admin')

@section('page-title', 'Inscripciones')

@push('styles')
<style>
.ins-header { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:1.25rem; }
.ins-title { font-size:1.5rem; font-weight:900; color:#1e3a6e; flex:1; }
.ins-flow { display:flex; align-items:center; gap:6px; font-size:.8rem; font-weight:700; color:#6b7280; flex-wrap:wrap; margin-bottom:1.5rem; }
.ins-flow-step { display:flex; align-items:center; gap:6px; background:#fff; border:1.5px solid #e5e7eb; border-radius:10px; padding:.4rem .9rem; }
.ins-flow-step.active { border-color:#3b82f6; background:#eff6ff; color:#1d4ed8; }
.ins-flow-step.done { border-color:#16a34a; background:#f0fdf4; color:#15803d; }
.ins-flow-arrow { color:#d1d5db; font-size:.9rem; }
.stat-pills { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:1.25rem; }
.stat-pill { display:flex; flex-direction:column; align-items:center; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:.7rem 1.1rem; min-width:100px; cursor:pointer; text-decoration:none; transition:box-shadow .15s; }
.stat-pill:hover { box-shadow:0 3px 12px rgba(30,58,110,.1); }
.stat-pill.active-tab { border-color:#3b82f6; background:#eff6ff; }
.stat-pill .num { font-size:1.6rem; font-weight:900; line-height:1; }
.stat-pill .lbl { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#6b7280; margin-top:.2rem; }
.stat-pill.p-pendiente .num { color:#d97706; }
.stat-pill.p-asignada  .num { color:#16a34a; }
.stat-pill.p-cancelada .num { color:#dc2626; }
.stat-pill.p-total     .num { color:#1e3a6e; }
.filter-row { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:.75rem 1rem; margin-bottom:1rem; display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
.ins-table { width:100%; border-collapse:collapse; }
.ins-table thead th { font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.07em; color:#6b7280; padding:.6rem .9rem; border-bottom:2px solid #e5e7eb; white-space:nowrap; background:#f8fafc; }
.ins-table tbody tr { border-bottom:1px solid #f3f4f6; transition:background .12s; }
.ins-table tbody tr:hover { background:#fafbff; }
.ins-table td { padding:.65rem .9rem; vertical-align:middle; font-size:.855rem; }
.badge-origen { font-size:.62rem; font-weight:800; padding:.18rem .5rem; border-radius:6px; text-transform:uppercase; letter-spacing:.05em; }
.badge-continuidad { background:#dbeafe; color:#1e40af; }
.badge-nueva       { background:#dcfce7; color:#166534; }
.badge-traslado    { background:#fef9c3; color:#78350f; }
.btn-asignar { background:#1e3a6e; color:#fff; border:none; border-radius:7px; padding:.3rem .75rem; font-size:.78rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px; }
.btn-asignar:hover { background:#2f5eb3; }
.btn-cancelar { background:#fee2e2; color:#991b1b; border:none; border-radius:7px; padding:.3rem .6rem; font-size:.75rem; font-weight:700; cursor:pointer; }
.btn-cancelar:hover { background:#fecaca; }
.bulk-bar { background:#1e3a6e; color:#fff; border-radius:10px; padding:.65rem 1.1rem; margin-bottom:.9rem; display:none; align-items:center; gap:12px; flex-wrap:wrap; }
.bulk-bar.visible { display:flex; }
.aviso-continuidad { background:linear-gradient(135deg,#eff6ff,#dbeafe); border:1.5px solid #93c5fd; border-radius:12px; padding:1rem 1.25rem; margin-bottom:1.25rem; display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-3">

    {{-- Header --}}
    <div class="ins-header">
        <div class="ins-title"><i class="bi bi-clipboard-check me-2" style="color:#3b82f6"></i>Inscripciones</div>
        <button class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalInscribir">
            <i class="bi bi-plus-lg me-1"></i>Inscribir Estudiante
        </button>
    </div>

    {{-- Flujo visual --}}
    <div class="ins-flow">
        <div class="ins-flow-step done">
            <i class="bi bi-person-plus"></i> Pre-matrícula
        </div>
        <span class="ins-flow-arrow">›</span>
        <div class="ins-flow-step active">
            <i class="bi bi-clipboard-check"></i> Inscripción
        </div>
        <span class="ins-flow-arrow">›</span>
        <div class="ins-flow-step">
            <i class="bi bi-grid-3x3-gap"></i> Asignación al curso
        </div>
        <span class="ins-flow-arrow">›</span>
        <div class="ins-flow-step">
            <i class="bi bi-check2-circle"></i> Matrícula activa
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 mb-3">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2 mb-3">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Aviso continuidad --}}
    @if($continuarCount > 0 && $estado === 'pendiente')
    <div class="aviso-continuidad">
        <i class="bi bi-info-circle-fill" style="font-size:1.4rem;color:#3b82f6;flex-shrink:0"></i>
        <div style="flex:1">
            <strong>{{ $continuarCount }} estudiante(s)</strong> del año anterior
            <strong>{{ $syAnterior?->nombre }}</strong> aún no han sido inscritos para este año.
        </div>
        <button class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalMasivo">
            <i class="bi bi-lightning-charge me-1"></i>Inscripción Masiva
        </button>
    </div>
    @endif

    {{-- Stats / tabs --}}
    <div class="stat-pills">
        @foreach([
            ['estado' => 'pendiente', 'label' => 'Pendientes',  'class' => 'p-pendiente', 'icon' => 'bi-clock'],
            ['estado' => 'asignada',  'label' => 'Asignadas',   'class' => 'p-asignada',  'icon' => 'bi-check2-circle'],
            ['estado' => 'cancelada', 'label' => 'Canceladas',  'class' => 'p-cancelada', 'icon' => 'bi-x-circle'],
            ['estado' => 'todos',     'label' => 'Total',       'class' => 'p-total',     'icon' => 'bi-list'],
        ] as $tab)
        <a href="{{ request()->fullUrlWithQuery(['estado' => $tab['estado']]) }}"
           class="stat-pill {{ $tab['class'] }} {{ $estado === $tab['estado'] ? 'active-tab' : '' }}">
            <span class="num">
                {{ $tab['estado'] === 'todos' ? $conteos['total'] : ($conteos[$tab['estado']] ?? 0) }}
            </span>
            <span class="lbl">{{ $tab['label'] }}</span>
        </a>
        @endforeach
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('admin.inscripciones.index') }}" class="filter-row">
        <input type="hidden" name="estado" value="{{ $estado }}">
        <div style="flex:1;min-width:200px">
            <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Buscar estudiante…" value="{{ request('buscar') }}">
        </div>
        <button type="submit" class="btn btn-primary btn-sm fw-bold">
            <i class="bi bi-search"></i>
        </button>
        @if(request()->hasAny(['buscar']))
        <a href="{{ route('admin.inscripciones.index', ['estado' => $estado]) }}" class="btn btn-secondary btn-sm">Limpiar</a>
        @endif
    </form>

    {{-- Barra de selección masiva --}}
    <div class="bulk-bar" id="bulkBar">
        <i class="bi bi-check2-square"></i>
        <span id="bulkCount">0 seleccionados</span>
        <button class="btn btn-sm btn-warning fw-bold" data-bs-toggle="modal" data-bs-target="#modalAsignarMasivo">
            <i class="bi bi-grid-3x3-gap me-1"></i>Asignar a Curso
        </button>
        <button class="btn btn-sm btn-outline-light" id="btnDeselAll">Deseleccionar todo</button>
    </div>

    {{-- Tabla de inscripciones --}}
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;">
        @if($inscripciones->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-clipboard-x" style="font-size:2.5rem;opacity:.3"></i>
                <p class="mt-2 mb-0">No hay inscripciones {{ $estado !== 'todos' ? $estado . 's' : '' }} para este año escolar.</p>
            </div>
        @else
        <div class="table-responsive">
            <table class="ins-table">
                <thead>
                    <tr>
                        @if($estado === 'pendiente')
                        <th style="width:36px">
                            <input type="checkbox" id="chkAll" class="form-check-input" style="cursor:pointer">
                        </th>
                        @endif
                        <th>#</th>
                        <th>Estudiante</th>
                        <th>Origen</th>
                        <th>Grado Solicitado</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        @if($estado !== 'pendiente')
                        <th>Curso Asignado</th>
                        @endif
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inscripciones as $i => $ins)
                    <tr>
                        @if($estado === 'pendiente')
                        <td>
                            <input type="checkbox" class="form-check-input chk-row" value="{{ $ins->id }}" style="cursor:pointer">
                        </td>
                        @endif
                        <td class="text-muted" style="font-size:.75rem">{{ $inscripciones->firstItem() + $i }}</td>
                        <td>
                            <div class="fw-bold" style="color:#1e3a6e">
                                {{ $ins->estudiante?->apellidos }}, {{ $ins->estudiante?->nombres }}
                            </div>
                            <div style="font-size:.72rem;color:#9ca3af">
                                {{ $ins->estudiante?->numero_matricula }}
                            </div>
                        </td>
                        <td>
                            <span class="badge-origen badge-{{ $ins->origen }}">{{ $ins->origen_label }}</span>
                        </td>
                        <td style="color:#4b5563">{{ $ins->grado?->nombre ?? '—' }}</td>
                        <td style="color:#6b7280;font-size:.8rem">{{ $ins->fecha_inscripcion?->format('d/m/Y') }}</td>
                        <td>
                            @php
                                $colors = ['pendiente'=>'warning','asignada'=>'success','cancelada'=>'danger'];
                                $labels = ['pendiente'=>'Pendiente','asignada'=>'Asignada','cancelada'=>'Cancelada'];
                            @endphp
                            <span class="badge bg-{{ $colors[$ins->estado] ?? 'secondary' }}-subtle text-{{ $colors[$ins->estado] ?? 'secondary' }} fw-bold" style="font-size:.65rem">
                                {{ $labels[$ins->estado] ?? $ins->estado }}
                            </span>
                        </td>
                        @if($estado !== 'pendiente')
                        <td style="font-size:.8rem">
                            @if($ins->grupo)
                                <span class="fw-bold" style="color:#1e3a6e">{{ $ins->grupo->nombre_completo }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        @endif
                        <td>
                            <div class="d-flex gap-1">
                                @if($ins->estado === 'pendiente')
                                <button class="btn-asignar btn-asignar-modal"
                                    data-id="{{ $ins->id }}"
                                    data-nombre="{{ $ins->estudiante?->apellidos }}, {{ $ins->estudiante?->nombres }}"
                                    data-grado="{{ $ins->grado?->id }}">
                                    <i class="bi bi-grid-3x3-gap" style="font-size:.75rem"></i>Asignar
                                </button>
                                <form method="POST" action="{{ route('admin.inscripciones.destroy', $ins) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-cancelar" title="Cancelar inscripción"
                                        onclick="return confirm('¿Cancelar la inscripción de {{ $ins->estudiante?->apellidos }}?')">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                                @elseif($ins->estado === 'asignada' && $ins->matricula_id)
                                <a href="{{ route('admin.matriculas.show', $ins->matricula_id) }}"
                                   class="btn btn-sm btn-outline-success fw-bold" style="font-size:.75rem">
                                    <i class="bi bi-eye me-1"></i>Ver matrícula
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $inscripciones->links() }}</div>
        @endif
    </div>

</div>

{{-- Modal: Inscribir estudiante --}}
<div class="modal fade" id="modalInscribir" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.inscripciones.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-clipboard-check me-2"></i>Inscribir Estudiante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estudiante <span class="text-danger">*</span></label>
                        <select name="estudiante_id" class="form-select" required>
                            <option value="">Seleccionar…</option>
                            @foreach($estudiantesDisponibles as $est)
                                <option value="{{ $est->id }}">
                                    {{ $est->apellidos }}, {{ $est->nombres }}
                                    @if($est->numero_matricula) · {{ $est->numero_matricula }} @endif
                                </option>
                            @endforeach
                        </select>
                        @if($estudiantesDisponibles->isEmpty())
                        <p class="text-muted small mt-1 mb-0">Todos los estudiantes activos ya están inscritos para este año.</p>
                        @endif
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Origen <span class="text-danger">*</span></label>
                            <select name="origen" class="form-select" required>
                                <option value="continuidad">Continuidad</option>
                                <option value="nueva">Nuevo Ingreso</option>
                                <option value="traslado">Traslado</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Grado Solicitado</label>
                            <select name="grado_id" class="form-select">
                                <option value="">Sin especificar</option>
                                @foreach($grados as $grado)
                                    <option value="{{ $grado->id }}">{{ $grado->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label fw-semibold">Observaciones</label>
                        <textarea name="observaciones" class="form-control form-control-sm" rows="2" placeholder="Notas adicionales…"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">
                        <i class="bi bi-check-lg me-1"></i>Inscribir
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Inscripción masiva (continuidad) --}}
<div class="modal fade" id="modalMasivo" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.inscripciones.masivo') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-lightning-charge me-2" style="color:#f59e0b"></i>Inscripción Masiva por Continuidad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3" style="font-size:.875rem">
                        Inscribe automáticamente a todos los estudiantes activos del año anterior que aún no han sido inscritos para <strong>{{ $schoolYear?->nombre }}</strong>.
                    </p>
                    @if($syAnterior)
                    <div class="alert alert-info py-2 mb-0">
                        <strong>Año origen:</strong> {{ $syAnterior->nombre }}
                        &nbsp;·&nbsp;
                        <strong>{{ $continuarCount }}</strong> estudiante(s) disponibles para inscripción masiva.
                    </div>
                    <input type="hidden" name="school_year_anterior_id" value="{{ $syAnterior->id }}">
                    @else
                    <div class="alert alert-warning py-2 mb-0">No se encontró un año escolar anterior.</div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold" @if(!$syAnterior) disabled @endif>
                        <i class="bi bi-lightning-charge me-1"></i>Inscribir {{ $continuarCount }} Estudiantes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Asignar a curso (individual) --}}
<div class="modal fade" id="modalAsignarInd" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="formAsignarInd" action="">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-grid-3x3-gap me-2"></i>Asignar a Curso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Asignando a: <strong id="nombreAsignarInd"></strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Curso <span class="text-danger">*</span></label>
                        <select name="grupo_id" class="form-select" required id="selectGrupoInd">
                            <option value="">Seleccionar curso…</option>
                            @foreach($grupos as $g)
                                <option value="{{ $g->id }}" data-grado="{{ $g->grado_id }}">
                                    {{ $g->nombre_completo }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Observaciones</label>
                        <textarea name="observaciones" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">
                        <i class="bi bi-check-lg me-1"></i>Asignar y Matricular
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Asignar masivo --}}
<div class="modal fade" id="modalAsignarMasivo" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.inscripciones.asignar-masivo') }}" id="formAsignarMasivo">
            @csrf
            <div id="hiddenIds"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-grid-3x3-gap me-2"></i>Asignar Seleccionados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Selecciona el curso para <strong id="masivoCantidad">0</strong> estudiante(s):</p>
                    <select name="grupo_id" class="form-select" required>
                        <option value="">Seleccionar curso…</option>
                        @foreach($grupos as $g)
                            <option value="{{ $g->id }}">{{ $g->nombre_completo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">
                        <i class="bi bi-check-lg me-1"></i>Asignar y Matricular
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Checkbox masivo
const chkAll   = document.getElementById('chkAll');
const bulkBar  = document.getElementById('bulkBar');
const bulkCount= document.getElementById('bulkCount');
const masivoCant = document.getElementById('masivoCantidad');

function updateBulk() {
    const checked = document.querySelectorAll('.chk-row:checked');
    const n = checked.length;
    bulkBar.classList.toggle('visible', n > 0);
    if (bulkCount) bulkCount.textContent = n + ' seleccionado' + (n !== 1 ? 's' : '');
    if (masivoCant) masivoCant.textContent = n;
    // hidden inputs
    const container = document.getElementById('hiddenIds');
    if (container) {
        container.innerHTML = '';
        checked.forEach(c => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = c.value;
            container.appendChild(inp);
        });
    }
}

if (chkAll) {
    chkAll.addEventListener('change', () => {
        document.querySelectorAll('.chk-row').forEach(c => c.checked = chkAll.checked);
        updateBulk();
    });
}
document.querySelectorAll('.chk-row').forEach(c => c.addEventListener('change', updateBulk));

const btnDesel = document.getElementById('btnDeselAll');
if (btnDesel) {
    btnDesel.addEventListener('click', () => {
        document.querySelectorAll('.chk-row').forEach(c => c.checked = false);
        if (chkAll) chkAll.checked = false;
        updateBulk();
    });
}

// Modal asignar individual
document.querySelectorAll('.btn-asignar-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        const id     = btn.dataset.id;
        const nombre = btn.dataset.nombre;
        const gradoId= btn.dataset.grado;

        document.getElementById('formAsignarInd').action =
            '{{ route("admin.inscripciones.asignar", ":id") }}'.replace(':id', id);
        document.getElementById('nombreAsignarInd').textContent = nombre;

        // Pre-filtrar grupos por grado si hay grado_id
        if (gradoId) {
            document.querySelectorAll('#selectGrupoInd option[data-grado]').forEach(o => {
                o.style.display = (o.dataset.grado === gradoId || o.value === '') ? '' : 'none';
            });
        } else {
            document.querySelectorAll('#selectGrupoInd option').forEach(o => o.style.display = '');
        }

        new bootstrap.Modal(document.getElementById('modalAsignarInd')).show();
    });
});
</script>
@endpush
@endsection
