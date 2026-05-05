@extends('layouts.admin')

@section('title', $instrumento->titulo)

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h3 mb-0">{{ $instrumento->titulo }}</h1>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.instrumentos.index') }}">Instrumentos</a></li>
                <li class="breadcrumb-item active">Ver / Evaluar</li>
            </ol></nav>
        </div>
        <form method="POST" action="{{ route('admin.instrumentos.destroy', $instrumento) }}"
              onsubmit="return confirm('¿Eliminar este instrumento?')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i> Eliminar</button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="bi bi-info-circle me-1"></i>Detalles</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Tipo</dt>
                        <dd class="col-7"><span class="badge bg-info text-dark">{{ $instrumento->tipo_label }}</span></dd>
                        @if($instrumento->asignacion)
                        <dt class="col-5">Asignación</dt>
                        <dd class="col-7">{{ $instrumento->asignacion->asignatura->nombre ?? '—' }}<br>
                            <span class="text-muted">{{ $instrumento->asignacion->grupo->nombre_completo ?? '' }}</span></dd>
                        @endif
                        @if($instrumento->docente)
                        <dt class="col-5">Docente</dt>
                        <dd class="col-7">{{ $instrumento->docente->nombre_completo }}</dd>
                        @endif
                        @if($instrumento->competencia)
                        <dt class="col-5">Competencia</dt>
                        <dd class="col-7">{{ $instrumento->competencia }}</dd>
                        @endif
                        @if($instrumento->indicadores_logro)
                        <dt class="col-5">Indicadores</dt>
                        <dd class="col-7">{{ $instrumento->indicadores_logro }}</dd>
                        @endif
                        <dt class="col-5">Estado</dt>
                        <dd class="col-7">
                            @if($instrumento->publicado)<span class="badge bg-success">Publicado</span>
                            @else<span class="badge bg-secondary">Borrador</span>@endif
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="bi bi-list-check me-1"></i>Criterios ({{ $instrumento->criterios->count() }})</div>
                <div class="card-body p-0">
                    <ol class="list-group list-group-flush list-group-numbered">
                        @foreach($instrumento->criterios->sortBy('orden') as $crit)
                        <li class="list-group-item d-flex justify-content-between align-items-start py-2">
                            <div class="ms-2 me-auto small">
                                <div class="fw-semibold">{{ $crit->nombre }}</div>
                                @if($crit->descripcion)<div class="text-muted">{{ $crit->descripcion }}</div>@endif
                            </div>
                            <span class="badge bg-primary rounded-pill">{{ $crit->peso_max }} pts</span>
                        </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            @if($instrumento->tipo === 'rubrica' && $instrumento->niveles_desempeno)
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="bi bi-bar-chart me-1"></i>Niveles de Desempeño</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($instrumento->niveles_desempeno as $n)
                        <li class="list-group-item d-flex justify-content-between small py-2">
                            <span>{{ $n['label'] }}</span>
                            <span class="badge bg-secondary">{{ $n['valor'] }} pts</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-8">
            @if($matriculas->count())
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-people me-1"></i>Registro de Evaluaciones</span>
                    <button type="button" class="btn btn-sm btn-success" onclick="guardarEvaluaciones()">
                        <i class="bi bi-save me-1"></i> Guardar Todo
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0 align-middle" id="tabla-eval">
                            <thead class="table-light">
                                <tr>
                                    <th class="small" style="min-width:160px">Estudiante</th>
                                    @foreach($instrumento->criterios->sortBy('orden') as $crit)
                                        <th class="text-center small" style="min-width:80px">
                                            {{ Str::limit($crit->nombre, 20) }}<br>
                                            <span class="text-muted fw-normal">({{ $crit->peso_max }})</span>
                                        </th>
                                    @endforeach
                                    @if($instrumento->tipo === 'rubrica')
                                        <th class="text-center small" style="min-width:120px">Nivel</th>
                                    @endif
                                    <th class="text-center small" style="min-width:80px">Ponderación</th>
                                    <th class="small" style="min-width:120px">Observación</th>
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
                                    <td class="text-center">
                                        <input type="number" class="form-control form-control-sm text-center puntaje-input"
                                            data-criterio="{{ $crit->id }}" data-mat="{{ $mat->id }}"
                                            min="0" max="{{ $crit->peso_max }}" step="0.5"
                                            value="{{ $ev?->getPuntajeCriterio($crit->id) ?? '' }}"
                                            oninput="calcTotal({{ $mat->id }})">
                                    </td>
                                    @endforeach
                                    @if($instrumento->tipo === 'rubrica')
                                    <td>
                                        <select class="form-select form-select-sm nivel-input" data-mat="{{ $mat->id }}">
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
                                    <td class="text-center">
                                        <input type="number" class="form-control form-control-sm text-center pond-input"
                                            data-mat="{{ $mat->id }}" id="pond-{{ $mat->id }}"
                                            min="0" max="100" step="0.01"
                                            value="{{ $ev?->ponderacion ?? '' }}">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm obs-input"
                                            data-mat="{{ $mat->id }}"
                                            value="{{ $ev?->observacion ?? '' }}">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @else
                <div class="alert alert-info">Este instrumento no tiene una asignación con estudiantes.</div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
const criterios = @json($instrumento->criterios->sortBy('orden')->values()->map(fn($c) => ['id'=>$c->id,'peso_max'=>$c->peso_max]));

function calcTotal(matId) {
    let sum = 0, total = 0;
    criterios.forEach(c => {
        const inp = document.querySelector(`.puntaje-input[data-criterio="${c.id}"][data-mat="${matId}"]`);
        const v = parseFloat(inp?.value) || 0;
        sum += v;
        total += c.peso_max;
    });
    const pond = document.getElementById('pond-' + matId);
    if (pond && total > 0) {
        pond.value = ((sum / total) * 100).toFixed(2);
    }
}

async function guardarEvaluaciones() {
    const btn = document.querySelector('[onclick="guardarEvaluaciones()"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

    const body = new FormData();
    body.append('_token', '{{ csrf_token() }}');

    document.querySelectorAll('#tabla-eval tbody tr').forEach(tr => {
        const matId = tr.dataset.mat;
        tr.querySelectorAll('.puntaje-input').forEach(inp => {
            body.append(`evaluaciones[${matId}][puntajes][${inp.dataset.criterio}]`, inp.value);
        });
        const pond = tr.querySelector('.pond-input');
        if (pond) body.append(`evaluaciones[${matId}][ponderacion]`, pond.value);
        const nivel = tr.querySelector('.nivel-input');
        if (nivel) body.append(`evaluaciones[${matId}][nivel_desempeno]`, nivel.value);
        const obs = tr.querySelector('.obs-input');
        if (obs) body.append(`evaluaciones[${matId}][observacion]`, obs.value);
    });

    try {
        const resp = await fetch('{{ route("admin.instrumentos.registrar", $instrumento) }}', {method:'POST', body});
        const data = await resp.json();
        btn.innerHTML = data.success ? '<i class="bi bi-check-circle me-1"></i> Guardado' : '<i class="bi bi-x me-1"></i> Error';
        btn.className = data.success ? 'btn btn-sm btn-success' : 'btn btn-sm btn-danger';
        setTimeout(() => {
            btn.innerHTML = '<i class="bi bi-save me-1"></i> Guardar Todo';
            btn.className = 'btn btn-sm btn-success';
            btn.disabled = false;
        }, 2000);
    } catch (e) {
        btn.innerHTML = '<i class="bi bi-x me-1"></i> Error de red';
        btn.className = 'btn btn-sm btn-danger';
        btn.disabled = false;
    }
}

// Init totals
document.querySelectorAll('#tabla-eval tbody tr').forEach(tr => calcTotal(tr.dataset.mat));
</script>
@endpush
@endsection
