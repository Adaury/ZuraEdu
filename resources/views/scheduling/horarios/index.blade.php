@extends('layouts.admin')
@section('page-title', 'Horarios')

@section('content')

{{-- Alertas --}}
@foreach(['success','error','warning'] as $t)
@if(session($t))
<div class="alert alert-{{ $t === 'error' ? 'danger' : $t }} alert-dismissible fade show" role="alert" style="border-radius:12px;">
    {{ session($t) }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@endforeach

{{-- Conflictos de la última generación --}}
@if(session('conflictos'))
<div class="card border-warning mb-4" style="border-radius:12px;">
    <div class="card-header bg-warning bg-opacity-10 py-2 px-3">
        <strong style="font-size:.85rem;"><i class="bi bi-exclamation-triangle me-1"></i>Clases sin asignar</strong>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:.8rem;">
                <thead><tr><th class="ps-3">Materia</th><th>Profesor</th><th>Curso</th><th>Motivo</th></tr></thead>
                <tbody>
                @foreach(session('conflictos') as $c)
                <tr>
                    <td class="ps-3">{{ $c['materia'] }}</td>
                    <td>{{ $c['profesor'] }}</td>
                    <td>{{ $c['curso'] }}</td>
                    <td class="text-muted" style="font-size:.75rem;">{{ $c['motivo'] }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Generador de Horarios</h4>
        <div style="font-size:.8rem;color:#64748b;">Backtracking + Heurísticas (MRV, Forward Checking)</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('scheduling.configuracion') }}" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
            <i class="bi bi-gear me-1"></i>Configuración
        </a>
        <button type="button" class="btn btn-primary btn-sm" style="border-radius:8px;" data-bs-toggle="modal" data-bs-target="#modalGenerar">
            <i class="bi bi-magic me-1"></i>Generar Horario
        </button>
    </div>
</div>

{{-- Lista de horarios --}}
@if($horarios->isEmpty())
<div class="card border-0 text-center py-5" style="border-radius:20px;box-shadow:0 4px 24px rgba(0,0,0,.06);">
    <i class="bi bi-calendar-x" style="font-size:3rem;color:#cbd5e1;"></i>
    <div style="font-size:1rem;font-weight:600;color:#374151;margin-top:1rem;">Sin horarios generados</div>
    <div style="font-size:.85rem;color:#64748b;margin-top:.3rem;">
        Primero configura materias, profesores y asignaciones, luego genera el horario.
    </div>
    <div class="mt-3">
        <a href="{{ route('scheduling.configuracion') }}" class="btn btn-primary btn-sm" style="border-radius:8px;">
            <i class="bi bi-gear me-1"></i>Ir a Configuración
        </a>
    </div>
</div>
@else
<div class="card border-0" style="border-radius:20px;box-shadow:0 4px 24px rgba(0,0,0,.06);overflow:hidden;">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead style="background:#f8faff;">
                <tr>
                    <th class="ps-4" style="font-size:.76rem;font-weight:700;text-transform:uppercase;color:#64748b;letter-spacing:.05em;">Nombre</th>
                    <th class="text-center" style="font-size:.76rem;font-weight:700;text-transform:uppercase;color:#64748b;">Estado</th>
                    <th class="text-center" style="font-size:.76rem;font-weight:700;text-transform:uppercase;color:#64748b;">Puntaje</th>
                    <th class="text-center" style="font-size:.76rem;font-weight:700;text-transform:uppercase;color:#64748b;">Clases</th>
                    <th class="text-center" style="font-size:.76rem;font-weight:700;text-transform:uppercase;color:#64748b;">Generado</th>
                    <th class="text-center" style="font-size:.76rem;font-weight:700;text-transform:uppercase;color:#64748b;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @foreach($horarios as $h)
            <tr>
                <td class="ps-4 fw-semibold" style="font-size:.88rem;">
                    {{ $h->nombre }}
                    @if(!empty($h->conflictos))
                    <span class="badge bg-warning text-dark ms-2" style="font-size:.68rem;border-radius:20px;">
                        {{ count($h->conflictos) }} sin asignar
                    </span>
                    @endif
                </td>
                <td class="text-center">
                    @if($h->estado === 'publicado')
                    <span class="badge" style="background:#dcfce7;color:#15803d;border-radius:20px;font-size:.73rem;padding:.3rem .75rem;">
                        <i class="bi bi-check-circle-fill me-1"></i>Publicado
                    </span>
                    @else
                    <span class="badge" style="background:#f1f5f9;color:#64748b;border-radius:20px;font-size:.73rem;padding:.3rem .75rem;">
                        Borrador
                    </span>
                    @endif
                </td>
                <td class="text-center">
                    @php
                        $sc = $h->score;
                        $scColor = $sc >= 90 ? '#16a34a' : ($sc >= 70 ? '#d97706' : '#dc2626');
                    @endphp
                    <span style="font-weight:700;font-size:.9rem;color:{{ $scColor }};">{{ $sc }}%</span>
                </td>
                <td class="text-center" style="font-size:.85rem;">
                    {{ $h->detalles()->count() }}
                </td>
                <td class="text-center" style="font-size:.8rem;color:#64748b;">
                    {{ $h->generado_en?->format('d/m/Y H:i') ?? '—' }}
                </td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <a href="{{ route('scheduling.horarios.show', $h) }}"
                           class="btn btn-sm btn-outline-primary" style="border-radius:7px;font-size:.75rem;">
                            <i class="bi bi-eye"></i>
                        </a>
                        <form action="{{ route('scheduling.horarios.publicar', $h) }}" method="POST">
                            @csrf
                            <button class="btn btn-sm {{ $h->estado === 'publicado' ? 'btn-outline-secondary' : 'btn-outline-success' }}"
                                    style="border-radius:7px;font-size:.75rem;"
                                    title="{{ $h->estado === 'publicado' ? 'Despublicar' : 'Publicar' }}">
                                <i class="bi bi-{{ $h->estado === 'publicado' ? 'eye-slash' : 'check-circle' }}"></i>
                            </button>
                        </form>
                        <form action="{{ route('scheduling.horarios.destroy', $h) }}" method="POST"
                              onsubmit="return confirm('¿Eliminar este horario?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" style="border-radius:7px;font-size:.75rem;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Modal Generar --}}
<div class="modal fade" id="modalGenerar" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('scheduling.horarios.generar') }}" method="POST">
            @csrf
            <div class="modal-content" style="border-radius:16px;border:none;">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold"><i class="bi bi-magic me-2"></i>Generar Horario Automático</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.85rem;">Nombre del horario</label>
                        <input type="text" name="nombre" class="form-control" style="border-radius:8px;"
                               placeholder="Horario {{ now()->format('d/m/Y') }}" maxlength="100">
                    </div>
                    <div class="p-3 rounded-3" style="background:#eff6ff;font-size:.8rem;color:#1e40af;">
                        <i class="bi bi-info-circle me-1"></i>
                        El algoritmo usa <strong>Backtracking + MRV + Forward Checking</strong> con hasta
                        3 reintentos automáticos para maximizar el score.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal" style="border-radius:8px;">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm" style="border-radius:8px;" id="btnGenerar">
                        <span id="btnGenerarSpinner" class="spinner-border spinner-border-sm me-1 d-none"></span>
                        <i class="bi bi-magic me-1" id="btnGenerarIcon"></i>Generar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.getElementById('btnGenerar')?.closest('form')?.addEventListener('submit', function() {
    document.getElementById('btnGenerarSpinner').classList.remove('d-none');
    document.getElementById('btnGenerarIcon').classList.add('d-none');
    document.getElementById('btnGenerar').disabled = true;
});
</script>
@endpush
