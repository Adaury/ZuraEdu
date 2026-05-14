@extends('layouts.admin')
@section('title', 'SIGERD - Integracion MINERD')
@section('content')
<div class="container-fluid py-4">
    @if(!$config)
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle-fill me-2"></i>Configure el modulo SIGERD.
    <a href="{{ route('admin.sigerd.configuracion') }}" class="alert-link">Ir a Configuracion</a></div>
    @else
    <div class="card border-0 shadow-sm mb-4" style="background-color:#1e3a6e;">
        <div class="card-body text-white p-3 d-flex justify-content-between align-items-center">
            <div><h4 class="mb-0"><i class="bi bi-building me-2"></i>{{ $config->nombre_centro ?? 'Centro Educativo' }}</h4>
            <small>Codigo: <strong>{{ $config->codigo_centro }}</strong></small></div>
            <a href="{{ route('admin.sigerd.configuracion') }}" class="btn btn-light btn-sm"><i class="bi bi-gear"></i> Configuracion</a>
        </div>
    </div>
    @endif
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card border-0 shadow-sm text-center"><div class="card-body">
            <i class="bi bi-people-fill fs-2 text-primary"></i>
            <div class="h4 mt-2">{{ $grupos->count() }}</div><small class="text-muted">Grupos Activos</small>
        </div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm text-center"><div class="card-body">
            <i class="bi bi-collection-fill fs-2 text-success"></i>
            <div class="h4 mt-2">{{ $periodos->count() }}</div><small class="text-muted">Periodos</small>
        </div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm text-center"><div class="card-body">
            <i class="bi bi-clock-history fs-2 text-warning"></i>
            <div class="h5 mt-2">{{ $ultimosLogs->first()?->created_at?->format('d/m/Y H:i') ?? 'Nunca' }}</div>
            <small class="text-muted">Ultimo Export</small>
        </div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm text-center"><div class="card-body">
            <i class="bi bi-shield-check fs-2 text-info"></i>
            <div class="h4 mt-2">{{ $ultimosLogs->count() }}</div><small class="text-muted">Exportaciones</small>
        </div></div></div>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-people-fill me-2"></i>Nomina de Matricula
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.sigerd.exportar') }}">
                        @csrf
                        <input type="hidden" name="tipo" value="nomina_matricula">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Grupo</label>
                            <select name="grupo_id" class="form-select form-select-sm">
                                <option value="">Todos los grupos</option>
                                @foreach($grupos as $grupo)
                                <option value="{{ $grupo->id }}">{{ $grupo->nombre_completo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Formato</label>
                            <select name="formato" class="form-select form-select-sm">
                                <option value="excel">Excel (.xlsx)</option>
                                <option value="csv">CSV</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" id="btn-validar-nomina" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-check-circle"></i> Validar
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-download"></i> Exportar
                            </button>
                        </div>
                    </form>
                    <div id="resultado-validacion-nomina" class="mt-2"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-journal-check me-2"></i>Libro de Calificaciones
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.sigerd.exportar') }}">
                        @csrf
                        <input type="hidden" name="tipo" value="calificaciones">
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Grupo <span class="text-danger">*</span></label>
                            <select name="grupo_id" class="form-select form-select-sm" required>
                                <option value="">-- Seleccionar grupo --</option>
                                @foreach($grupos as $g)
                                <option value="{{ $g->id }}">{{ $g->nombre_completo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Periodo</label>
                            <select name="periodo_id" class="form-select form-select-sm">
                                <option value="">Todos los periodos</option>
                                @foreach($periodos as $p)
                                <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Formato</label>
                            <select name="formato" class="form-select form-select-sm">
                                <option value="excel">Excel</option>
                                <option value="csv">CSV</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-download"></i> Exportar
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header text-white" style="background-color:#6f42c1;">
                    <i class="bi bi-person-badge me-2"></i>Nomina de Docentes
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.sigerd.exportar') }}">
                        @csrf
                        <input type="hidden" name="tipo" value="docentes">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Formato</label>
                            <select name="formato" class="form-select form-select-sm">
                                <option value="excel">Excel</option>
                                <option value="csv">CSV</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm text-white" style="background-color:#6f42c1;">
                            <i class="bi bi-download"></i> Exportar
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-warning text-dark">
                    <i class="bi bi-calendar-check me-2"></i>Registro de Asistencia
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.sigerd.exportar') }}">
                        @csrf
                        <input type="hidden" name="tipo" value="asistencia">
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Grupo</label>
                            <select name="grupo_id" class="form-select form-select-sm">
                                <option value="">Todos los grupos</option>
                                @foreach($grupos as $g)
                                <option value="{{ $g->id }}">{{ $g->nombre_completo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Desde</label>
                                <input type="date" name="desde" class="form-control form-control-sm" value="{{ now()->startOfYear()->toDateString() }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Hasta</label>
                                <input type="date" name="hasta" class="form-control form-control-sm" value="{{ now()->toDateString() }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Formato</label>
                            <select name="formato" class="form-select form-select-sm">
                                <option value="excel">Excel</option>
                                <option value="csv">CSV</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-warning btn-sm">
                            <i class="bi bi-download"></i> Exportar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @if($ultimosLogs->count())
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold"><i class="bi bi-clock-history me-2"></i>Historial Reciente</div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Tipo</th><th>Grupo</th><th>Formato</th><th>Fecha</th><th>Usuario</th><th>Registros</th></tr>
                </thead>
                <tbody>
                @foreach($ultimosLogs as $log)
                <tr>
                    <td><span class="badge bg-secondary">{{ $log->tipo }}</span></td>
                    <td>{{ $log->grupo?->nombre_completo ?? 'Todos' }}</td>
                    <td><span class="badge bg-info text-dark">{{ strtoupper($log->formato) }}</span></td>
                    <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $log->user?->name ?? 'N/A' }}</td>
                    <td>{{ $log->total_registros }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@push('scripts')
<script>
document.getElementById('btn-validar-nomina').addEventListener('click', function() {
    const card = this.closest('.card');
    const grupoId = card.querySelector('[name=grupo_id]').value;
    const resultDiv = document.getElementById('resultado-validacion-nomina');
    resultDiv.innerHTML = '<span class="text-secondary"><i class="bi bi-hourglass-split"></i> Validando...</span>';
    fetch('{{ route("admin.sigerd.validar") }}?tipo=nomina_matricula&grupo_id=' + grupoId)
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                resultDiv.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill"></i> ' + data.total + ' registros sin errores</span>';
            } else {
                let html = '<div class="alert alert-danger p-2"><strong>' + data.errores.length + ' errores:</strong><ul class="mb-0 ps-3">';
                data.errores.forEach(e => { html += '<li>' + (e.descripcion || e) + '</li>'; });
                html += '</ul></div>';
                resultDiv.innerHTML = html;
            }
        }).catch(() => { resultDiv.innerHTML = '<span class="text-danger">Error al validar</span>'; });
});
</script>
@endpush
@endsection
