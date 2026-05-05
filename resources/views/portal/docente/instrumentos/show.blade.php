@extends('layouts.portal')
@section('page-title', $instrumento->titulo)
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'instrumentos'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.planes-clase.index', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-text"></i>Planes
    </a>
    <a href="{{ route('portal.docente.instrumentos.index', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-clipboard-check-fill"></i>Instrum.
    </a>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="h4 mb-1">{{ $instrumento->titulo }}</h2>
            <p class="text-muted small mb-0">
                {{ $asignacion->asignatura->nombre }} — {{ $asignacion->grupo->nombre_completo ?? '' }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('portal.docente.instrumentos.pdf', [$asignacion, $instrumento]) }}"
               target="_blank" class="btn btn-sm btn-danger">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
            </a>
            <a href="{{ route('portal.docente.instrumentos.index', $asignacion) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold small"><i class="bi bi-info-circle me-1"></i>Detalles</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Tipo</dt>
                        <dd class="col-7"><span class="badge bg-info text-dark">{{ $instrumento->tipo_label }}</span></dd>
                        @if($instrumento->competencia)
                        <dt class="col-5">Competencia</dt>
                        <dd class="col-7">{{ $instrumento->competencia }}</dd>
                        @endif
                        @if($instrumento->indicadores_logro)
                        <dt class="col-5">Indicadores</dt>
                        <dd class="col-7">{{ $instrumento->indicadores_logro }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold small"><i class="bi bi-list-check me-1"></i>Criterios</div>
                <div class="card-body p-0">
                    <ol class="list-group list-group-flush list-group-numbered">
                        @foreach($instrumento->criterios->sortBy('orden') as $crit)
                        <li class="list-group-item d-flex justify-content-between align-items-start py-2">
                            <div class="ms-2 me-auto small">
                                <div class="fw-semibold">{{ $crit->nombre }}</div>
                                @if($crit->descripcion)<div class="text-muted">{{ $crit->descripcion }}</div>@endif
                            </div>
                            <span class="badge bg-primary rounded-pill">{{ $crit->peso_max }}</span>
                        </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            @if($instrumento->tipo === 'rubrica' && $instrumento->niveles_desempeno)
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold small"><i class="bi bi-bar-chart me-1"></i>Niveles de Desempeño</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($instrumento->niveles_desempeno as $n)
                        <li class="list-group-item d-flex justify-content-between small py-2">
                            <span>{{ $n['label'] }}</span>
                            <span class="badge bg-secondary">{{ $n['valor'] }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-8">
            <form method="POST" action="{{ route('portal.docente.instrumentos.guardar', [$asignacion, $instrumento]) }}">
                @csrf
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold small"><i class="bi bi-people me-1"></i>Registro de Evaluaciones</span>
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="bi bi-save me-1"></i> Guardar
                        </button>
                    </div>
                    <div class="card-body p-0">
                        @if($matriculas->count())
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="small" style="min-width:150px">Estudiante</th>
                                        @foreach($instrumento->criterios->sortBy('orden') as $crit)
                                            <th class="text-center small" style="min-width:75px">
                                                {{ Str::limit($crit->nombre, 18) }}<br>
                                                <span class="text-muted fw-normal">({{ $crit->peso_max }})</span>
                                            </th>
                                        @endforeach
                                        @if($instrumento->tipo === 'rubrica')
                                            <th class="text-center small" style="min-width:110px">Nivel</th>
                                        @endif
                                        <th class="text-center small" style="min-width:70px">Total</th>
                                        <th class="small" style="min-width:110px">Observación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($matriculas as $mat)
                                    @php $ev = $evaluaciones[$mat->id] ?? null; @endphp
                                    <tr data-mat="{{ $mat->id }}">
                                        <td class="small">
                                            {{ $mat->estudiante->apellidos }}, {{ $mat->estudiante->nombres }}
                                        </td>
                                        @foreach($instrumento->criterios->sortBy('orden') as $crit)
                                        <td class="text-center p-1">
                                            <input type="number"
                                                name="evaluaciones[{{ $mat->id }}][puntajes][{{ $crit->id }}]"
                                                class="form-control form-control-sm text-center puntaje-inp"
                                                data-criterio="{{ $crit->id }}"
                                                data-mat="{{ $mat->id }}"
                                                data-max="{{ $crit->peso_max }}"
                                                min="0" max="{{ $crit->peso_max }}" step="0.5"
                                                value="{{ $ev?->getPuntajeCriterio($crit->id) ?? '' }}"
                                                oninput="calcPond({{ $mat->id }})">
                                        </td>
                                        @endforeach
                                        @if($instrumento->tipo === 'rubrica')
                                        <td class="p-1">
                                            <select name="evaluaciones[{{ $mat->id }}][nivel_desempeno]"
                                                class="form-select form-select-sm">
                                                <option value="">—</option>
                                                @foreach($instrumento->niveles_desempeno ?? [] as $n)
                                                    <option value="{{ $n['label'] }}"
                                                        @selected($ev?->nivel_desempeno === $n['label'])>
                                                        {{ $n['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        @endif
                                        <td class="text-center p-1">
                                            <input type="number"
                                                name="evaluaciones[{{ $mat->id }}][ponderacion]"
                                                id="pond-{{ $mat->id }}"
                                                class="form-control form-control-sm text-center"
                                                min="0" max="100" step="0.01"
                                                value="{{ $ev?->ponderacion ?? '' }}" readonly>
                                        </td>
                                        <td class="p-1">
                                            <input type="text"
                                                name="evaluaciones[{{ $mat->id }}][observacion]"
                                                class="form-control form-control-sm"
                                                value="{{ $ev?->observacion ?? '' }}"
                                                placeholder="Obs...">
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                            <div class="p-4 text-center text-muted small">No hay estudiantes en esta asignación.</div>
                        @endif
                    </div>
                    @if($matriculas->count())
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-save me-1"></i> Guardar Evaluaciones
                        </button>
                    </div>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const criteriosInfo = @json($instrumento->criterios->sortBy('orden')->values()->map(fn($c) => ['id'=>$c->id,'peso_max'=>$c->peso_max]));

function calcPond(matId) {
    let sum = 0, total = 0;
    criteriosInfo.forEach(c => {
        const inp = document.querySelector(`.puntaje-inp[data-criterio="${c.id}"][data-mat="${matId}"]`);
        sum   += parseFloat(inp?.value) || 0;
        total += c.peso_max;
    });
    const pond = document.getElementById('pond-' + matId);
    if (pond && total > 0) pond.value = ((sum / total) * 100).toFixed(2);
}

// Init
document.querySelectorAll('tbody tr[data-mat]').forEach(tr => calcPond(tr.dataset.mat));
</script>
@endpush
@endsection
