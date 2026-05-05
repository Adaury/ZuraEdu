@extends('layouts.portal')
@section('page-title', 'Nuevo Instrumento de Evaluación')
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
    <div class="mb-4">
        <h2 class="h4 mb-1">Nuevo Instrumento de Evaluación</h2>
        <p class="text-muted small mb-0">
            {{ $asignacion->asignatura->nombre }} — {{ $asignacion->grupo->nombre_completo ?? '' }}
        </p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('portal.docente.instrumentos.store', $asignacion) }}" id="form-instrumento">
        @csrf
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header fw-semibold"><i class="bi bi-info-circle me-1"></i>Información General</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Título <span class="text-danger">*</span></label>
                            <input type="text" name="titulo" class="form-control @error('titulo') is-invalid @enderror"
                                value="{{ old('titulo') }}" required maxlength="200">
                            @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select name="tipo" id="tipo-instrumento" class="form-select @error('tipo') is-invalid @enderror" required>
                                <option value="">-- Selecciona --</option>
                                @foreach($tipos as $k => $v)
                                    <option value="{{ $k }}" @selected(old('tipo')==$k)>{{ $v }}</option>
                                @endforeach
                            </select>
                            @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Competencia / Propósito</label>
                            <textarea name="competencia" class="form-control" rows="2">{{ old('competencia') }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Indicadores de Logro</label>
                            <textarea name="indicadores_logro" class="form-control" rows="2">{{ old('indicadores_logro') }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción / Instrucciones</label>
                            <textarea name="descripcion" class="form-control" rows="2">{{ old('descripcion') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold"><i class="bi bi-list-check me-1"></i>Criterios de Evaluación</span>
                        <button type="button" class="btn btn-sm btn-success" onclick="agregarCriterio()">
                            <i class="bi bi-plus-circle me-1"></i> Agregar
                        </button>
                    </div>
                    <div class="card-body" id="criterios-container">
                        <div class="text-muted text-center py-2 small" id="criterios-empty">
                            Haz clic en "Agregar" para añadir criterios.
                        </div>
                    </div>
                </div>

                <div id="niveles-section" class="card shadow-sm mb-4" style="display:none">
                    <div class="card-header fw-semibold"><i class="bi bi-bar-chart me-1"></i>Niveles de Desempeño</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        @foreach($niveles as $nivel)
                                            <th class="text-center small">{{ $nivel['label'] }}<br>
                                            <span class="fw-normal text-muted">({{ $nivel['valor'] }})</span></th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        @foreach($niveles as $nivel)
                                            <td class="text-center small text-muted">{{ $nivel['descripcion'] }}</td>
                                        @endforeach
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Crear Instrumento
                    </button>
                    <a href="{{ route('portal.docente.instrumentos.index', $asignacion) }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
let criterioCount = 0;

function agregarCriterio() {
    document.getElementById('criterios-empty').style.display = 'none';
    const i = criterioCount++;
    const div = document.createElement('div');
    div.className = 'border rounded p-3 mb-3 criterio-item';
    div.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong class="small">Criterio #${i+1}</strong>
            <button type="button" class="btn btn-sm btn-outline-danger"
                onclick="this.closest('.criterio-item').remove(); actualizarNumeracion()">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div class="row g-2">
            <div class="col-12">
                <input type="text" name="criterios[${i}][nombre]" class="form-control form-control-sm"
                    placeholder="Nombre del criterio *" required>
            </div>
            <div class="col-12">
                <input type="text" name="criterios[${i}][descripcion]" class="form-control form-control-sm"
                    placeholder="Descripción (opcional)">
            </div>
            <div class="col-md-4">
                <input type="number" name="criterios[${i}][peso_max]" class="form-control form-control-sm"
                    placeholder="Peso / Puntos" min="1" value="1">
            </div>
        </div>`;
    document.getElementById('criterios-container').appendChild(div);
}

function actualizarNumeracion() {
    document.querySelectorAll('.criterio-item').forEach((el, idx) => {
        el.querySelector('strong').textContent = `Criterio #${idx+1}`;
    });
    if (!document.querySelectorAll('.criterio-item').length)
        document.getElementById('criterios-empty').style.display = '';
}

document.getElementById('tipo-instrumento').addEventListener('change', function () {
    document.getElementById('niveles-section').style.display =
        this.value === 'rubrica' ? '' : 'none';
});
</script>
@endpush
@endsection
