@extends('layouts.admin')

@section('page-title', 'Matriz Curricular')

@push('styles')
<style>
    .matriz-table th, .matriz-table td {
        font-size: .75rem;
        white-space: nowrap;
        padding: .45rem .65rem;
        vertical-align: middle;
    }
    .matriz-table th { background: #f8fafc; color: #4b5563; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .grado-header { background: #1e3a6e !important; color: #fff !important; text-align: center; font-weight: 800; }
    .celda-si { background: #dcfce7; color: #16a34a; text-align: center; font-size: .8rem; font-weight: 700; }
    .celda-tecnica { background: #fee2e2; color: #dc2626; text-align: center; font-size: .8rem; font-weight: 700; }
    .celda-no { text-align: center; color: #e5e7eb; }
    .sticky-col { position: sticky; left: 0; background: #fff; z-index: 2; border-right: 2px solid #e5e7eb; }
    .sticky-col.header { background: #f8fafc; z-index: 3; }

    [data-theme="dark"] .matriz-table th { background: #162032; color: #94a3b8; }
    [data-theme="dark"] .celda-si { background: #052e16; color: #4ade80; }
    [data-theme="dark"] .celda-tecnica { background: #1c0000; color: #f87171; }
    [data-theme="dark"] .celda-no { color: #334155; }
    [data-theme="dark"] .sticky-col { background: #1e293b; border-right-color: #334155; }
    [data-theme="dark"] .sticky-col.header { background: #162032; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1e3a6e;">
            <i class="bi bi-table me-2"></i>Matriz Curricular Completa
        </h4>
        <p class="text-muted mb-0" style="font-size:.875rem;">
            Vista cruzada de asignaturas por grado — Verde=Académica · Rojo=Técnica
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.malla.matriz.pdf') }}" target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.malla.matriz.excel') }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('admin.malla.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver a Malla
        </a>
        <a href="{{ route('admin.malla.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Agregar
        </a>
    </div>
</div>

@if($asignaturas->isEmpty() || $grados->isEmpty())
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    No hay suficientes datos para mostrar la matriz. Agrega asignaturas y entradas a la malla primero.
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered matriz-table mb-0">
                <thead>
                    <tr>
                        <th class="sticky-col header" style="min-width:200px;">Asignatura</th>
                        @foreach($grados as $grado)
                        <th class="grado-header" style="min-width:80px;">
                            {{ $grado->nivel }}°
                            <div style="font-size:.62rem;opacity:.8;font-weight:400;">{{ Str::limit($grado->nombre, 8) }}</div>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($asignaturas as $asig)
                    @php $tieneAlguna = false; @endphp
                    @foreach($grados as $grado)
                        @if($mallaMap->has($grado->id . '_' . $asig->id))
                            @php $tieneAlguna = true; break; @endphp
                        @endif
                    @endforeach

                    @if($tieneAlguna)
                    <tr>
                        <td class="sticky-col fw-semibold" style="color:#1e293b;">{{ $asig->nombre }}</td>
                        @foreach($grados as $grado)
                        @php $entry = $mallaMap->get($grado->id . '_' . $asig->id); @endphp
                        @if($entry)
                            @if($entry->area === 'academica')
                            <td class="celda-si">{{ $entry->horas_semanales }}h</td>
                            @else
                            <td class="celda-tecnica">{{ $entry->horas_semanales }}h</td>
                            @endif
                        @else
                        <td class="celda-no">·</td>
                        @endif
                        @endforeach
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex gap-4 mt-3" style="font-size:.78rem;color:#6b7280;">
    <span><span style="display:inline-block;width:12px;height:12px;background:#dcfce7;border-radius:2px;margin-right:4px;"></span>Área Académica</span>
    <span><span style="display:inline-block;width:12px;height:12px;background:#fee2e2;border-radius:2px;margin-right:4px;"></span>Área Técnica</span>
    <span><span style="display:inline-block;width:12px;height:12px;background:#f0f4f8;border-radius:2px;margin-right:4px;"></span>No aplica en ese grado</span>
    <span>El número indica horas semanales.</span>
</div>
@endif
@endsection
