@extends('layouts.admin')
@section('page-title', 'Docentes Rezagados en Notas')

@push('styles')
<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
.page-header h1 { font-size:1.3rem; font-weight:800; color:var(--primary); margin:0; }
.stat-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:1rem; margin-bottom:1.5rem; }
.stat-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:1rem 1.25rem; text-align:center; }
.stat-val { font-size:1.8rem; font-weight:900; line-height:1; }
.stat-lbl { font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-top:.3rem; }
.table-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; }
.table-card table { margin:0; }
.table-card thead th { background:#f8fafc; font-size:.73rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; border-bottom:1px solid #e5e7eb; padding:.75rem 1rem; }
.table-card tbody td { font-size:.83rem; padding:.7rem 1rem; vertical-align:middle; border-bottom:1px solid #f3f4f6; }
</style>
@endpush

@section('content')
@if(isset($sinAnio))
<div class="alert alert-warning">Sin año escolar activo.</div>
@else

<div class="page-header">
    <div>
        <h1><i class="bi bi-person-x-fill text-warning me-2"></i>Docentes Rezagados en Notas</h1>
        <span class="text-muted" style="font-size:.85rem;">{{ $schoolYear->nombre }}</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.rendimiento.rezagados.pdf', request()->query()) }}" target="_blank"
           class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.rendimiento.rezagados.excel', request()->query()) }}"
           class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
    </div>
</div>

{{-- Filtro de período --}}
<form method="GET" action="{{ route('admin.rendimiento.rezagados') }}" class="mb-4 d-flex gap-2 align-items-center">
    <label class="fw-semibold" style="font-size:.83rem;">Período:</label>
    <select name="periodo_id" class="form-select form-select-sm" style="max-width:200px;"
            onchange="this.form.submit()">
        @foreach($periodos as $p)
            <option value="{{ $p->id }}" {{ $periodoId == $p->id ? 'selected':'' }}>
                {{ $p->nombre }} {{ $p->activo ? '(activo)' : '' }}
            </option>
        @endforeach
    </select>
</form>

{{-- Stats --}}
<div class="stat-grid">
    <div class="stat-card" style="border-top:4px solid #2563eb;">
        <div class="stat-val" style="color:#1d4ed8;">{{ $resumen['total'] }}</div>
        <div class="stat-lbl">Total asignaciones</div>
    </div>
    <div class="stat-card" style="border-top:4px solid #10b981;">
        <div class="stat-val" style="color:#065f46;">{{ $resumen['publicados'] }}</div>
        <div class="stat-lbl">Publicadas ✓</div>
    </div>
    <div class="stat-card" style="border-top:4px solid #f59e0b;">
        <div class="stat-val" style="color:#92400e;">{{ $resumen['sin_publicar'] }}</div>
        <div class="stat-lbl">Sin publicar</div>
    </div>
    <div class="stat-card" style="border-top:4px solid #ef4444;background:#fff5f5;">
        <div class="stat-val" style="color:#991b1b;">{{ $resumen['sin_notas'] }}</div>
        <div class="stat-lbl">Sin notas</div>
    </div>
</div>

@if($rezagados->isEmpty())
<div class="alert alert-success py-3" style="border-radius:12px;">
    <i class="bi bi-check-circle-fill me-2"></i>
    <strong>¡Todo al día!</strong> Todas las asignaciones tienen calificaciones publicadas.
</div>
@else
<div class="table-card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Docente</th>
                <th>Materia</th>
                <th>Grupo</th>
                <th>Área</th>
                <th class="text-center">Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($rezagados as $item)
            @php $asig = $item['asignacion']; @endphp
            <tr style="{{ $item['estado'] === 'sin_notas' ? 'background:#fff5f5;' : 'background:#fffbeb;' }}">
                <td>
                    <div class="fw-semibold">{{ $asig->docente?->nombre_completo ?? 'Sin docente' }}</div>
                    @if($asig->docente?->email)
                    <div style="font-size:.72rem;color:#6b7280;">{{ $asig->docente->email }}</div>
                    @endif
                </td>
                <td>{{ $asig->asignatura?->nombre ?? '—' }}</td>
                <td style="font-size:.82rem;">{{ $asig->grupo?->grado?->nombre ?? '' }} {{ $asig->grupo?->seccion?->nombre ?? '' }}</td>
                <td>
                    <span class="badge" style="font-size:.68rem;background:{{ $asig->area === 'tecnica' ? '#ede9fe' : '#dbeafe' }};color:{{ $asig->area === 'tecnica' ? '#5b21b6' : '#1d4ed8' }};">
                        {{ $asig->area === 'tecnica' ? 'Técnica' : 'Académica' }}
                    </span>
                </td>
                <td class="text-center">
                    @if($item['estado'] === 'sin_notas')
                        <span class="badge text-bg-danger" style="font-size:.7rem;">Sin notas</span>
                    @else
                        <span class="badge text-bg-warning" style="font-size:.7rem;">Notas sin publicar</span>
                    @endif
                </td>
                <td>
                    @if($asig->docente_id)
                    <a href="{{ route('admin.perfiles.docente', $asig->docente_id) }}"
                       class="btn btn-sm btn-outline-secondary py-1" style="font-size:.75rem;">
                        <i class="bi bi-person"></i>
                    </a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endif
@endsection
