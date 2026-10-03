@extends('layouts.admin')
@section('page-title', 'Entregas — '.$material->titulo)
@section('content')

<div class="mb-4 d-flex align-items-center gap-3">
    <a href="{{ route('portal.docente.classroom.show', $claseVirtual) }}" class="btn btn-outline-secondary btn-sm">← Volver al Aula</a>
    <div>
        <h5 class="fw-bold mb-0">{{ $material->titulo }}</h5>
        <small class="text-muted">{{ $claseVirtual->nombre }} &bull; {{ $entregas->where('estado','entregado')->count() + $entregas->where('estado','calificado')->count() }}/{{ $matriculas->count() }} entregas</small>
    </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius:16px;">
<div class="card-body p-0">
<div class="table-responsive">
{{-- data-no-dt: cada fila lleva su propio formulario de nota; el layout aplicaría una DataTable con scroll virtual (caja de 340 px) --}}
<table class="table table-hover mb-0" data-no-dt>
<thead style="background:#F8FAFC;">
    <tr>
        <th class="px-4 py-3 fw-semibold text-muted" style="font-size:.8rem;text-transform:uppercase;">Estudiante</th>
        <th class="py-3 fw-semibold text-muted" style="font-size:.8rem;text-transform:uppercase;">Estado</th>
        <th class="py-3 fw-semibold text-muted" style="font-size:.8rem;text-transform:uppercase;">Entrega</th>
        <th class="py-3 fw-semibold text-muted" style="font-size:.8rem;text-transform:uppercase;">Calificación</th>
        <th class="py-3"></th>
    </tr>
</thead>
<tbody>
@foreach($matriculas as $matricula)
@php $entrega = $entregas->get($matricula->id); @endphp
<tr>
    <td class="px-4 py-3">
        <div class="fw-semibold" style="font-size:.9rem;">{{ $matricula->estudiante->nombre_completo ?? $matricula->estudiante->nombres.' '.$matricula->estudiante->apellidos }}</div>
    </td>
    <td class="py-3">
        @if(!$entrega)
            <span class="badge bg-warning text-dark">Pendiente</span>
        @elseif($entrega->estado === 'calificado')
            <span class="badge bg-success">Calificado</span>
        @else
            <span class="badge bg-info">Entregado</span>
        @endif
    </td>
    <td class="py-3">
        @if($entrega)
        <div class="small text-muted">{{ \Carbon\Carbon::parse($entrega->fecha_entrega)->format('d/m H:i') }}</div>
        @if($entrega->url_entrega)<a href="{{ $entrega->url_entrega }}" target="_blank" class="small text-primary"><i class="bi bi-link-45deg"></i> Ver enlace</a>@endif
        @if($entrega->contenido)<p class="small text-muted mb-0 mt-1" style="max-width:220px;">{{ Str::limit($entrega->contenido, 80) }}</p>@endif
        @else
        <span class="text-muted small">—</span>
        @endif
    </td>
    <td class="py-3">
        @if($entrega)
        <form method="POST" action="{{ route('portal.docente.classroom.calificar_entrega', [$claseVirtual, $entrega]) }}" class="d-flex align-items-center gap-2">
            @csrf @method('PATCH')
            {{-- Igual que el detalle: si el material tiene período y la entrega aún no está calificada, la nota pasa al libro de notas --}}
            @if($material->periodo_id && $entrega->estado !== 'calificado')<input type="hidden" name="sincronizar_notas" value="1">@endif
            <input type="number" name="calificacion" class="form-control form-control-sm" style="width:80px;" min="0" max="{{ $material->puntos ?? 100 }}" step="0.01" value="{{ $entrega->calificacion }}" placeholder="0" required>
            <input type="text" name="comentario_docente" class="form-control form-control-sm" style="width:120px;" maxlength="1000" value="{{ $entrega->comentario_docente }}" placeholder="Comentario">
            <button class="btn btn-sm btn-primary">Guardar</button>
        </form>
        @else
        <span class="text-muted small">Sin entrega</span>
        @endif
    </td>
    <td class="py-3 text-end pe-3">
        @if($entrega)<a href="{{ route('portal.docente.classroom.entrega_detalle', [$claseVirtual, $material, $entrega]) }}" class="btn btn-sm btn-outline-secondary" title="Ver la entrega completa, archivos y rúbrica"><i class="bi bi-eye"></i></a>@endif
    </td>
</tr>
@endforeach
</tbody>
</table>
</div>
</div>
</div>
@endsection
