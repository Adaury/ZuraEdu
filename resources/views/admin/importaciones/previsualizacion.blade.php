@extends('layouts.admin')
@section('page-title', 'Vista previa de la importación')

@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <div>
        <h1 class="mb-0" style="font-size:1.4rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-eye me-2" style="color:var(--secondary);"></i>Vista previa de la importación
        </h1>
        <div class="text-muted" style="font-size:.85rem;">
            {{ $nombreArchivo }} ·
            {{ $asignacion->grupo->grado->nombre ?? '' }} {{ $asignacion->grupo->seccion->nombre ?? '' }}
            · {{ $asignacion->asignatura->nombre ?? '—' }}
        </div>
    </div>
</div>

<div class="alert alert-warning" style="border-radius:10px;font-size:.85rem;">
    <i class="bi bi-shield-check me-1"></i>
    <strong>Todavía no se guardó nada.</strong> Revisa lo que se reconoció y, si es correcto, pulsa
    <em>Confirmar e importar</em>. Las notas existentes de cada estudiante se actualizarán con lo que traiga el archivo.
</div>

{{-- Resumen --}}
<div class="row g-3 mb-4">
    @foreach([
        ['listas', 'Filas listas', 'success', $resumen['listas']],
        ['notas', 'Notas a guardar', 'primary', $resumen['notas']],
        ['sin', 'Estudiante no encontrado', 'danger', $resumen['sin_estudiante']],
        ['amb', 'Nombre ambiguo', 'warning', $resumen['ambiguas']],
        ['adv', 'Con advertencias', 'secondary', $resumen['con_advertencias']],
    ] as [$k, $label, $color, $valor])
    <div class="col-6 col-md">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body py-3 text-center">
                <div class="fw-bold text-{{ $color }}" style="font-size:1.6rem;">{{ $valor }}</div>
                <div class="text-muted" style="font-size:.78rem;">{{ $label }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Columnas reconocidas --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
    <div class="card-body">
        <div class="fw-semibold mb-2" style="font-size:.9rem;">
            Columnas reconocidas
            <span class="text-muted fw-normal" style="font-size:.78rem;">
                (encabezados en la fila {{ $lectura['fila_encabezado'] }}@if($lectura['hoja']) · hoja «{{ $lectura['hoja'] }}»@endif)
            </span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @foreach($lectura['columnas'] as $col)
                @if($col['clave'] !== '' && preg_match('/^(numero_matricula|cedula|nombres|apellidos|nombre_completo|nota_final|periodo|comp[1-4]_p[1-4])$/', $col['clave']))
                    <span class="badge" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;font-weight:600;">
                        {{ $col['original'] ?: '(sin título)' }} <i class="bi bi-arrow-right-short"></i> <code>{{ $col['clave'] }}</code>
                    </span>
                @else
                    <span class="badge" style="background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb;font-weight:500;"
                          title="Esta columna no se usa">
                        {{ $col['original'] ?: '(sin título)' }} · ignorada
                    </span>
                @endif
            @endforeach
        </div>
    </div>
</div>

{{-- Filas --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0" style="font-size:.82rem;">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;">Fila</th>
                    <th>En el archivo</th>
                    <th>Estudiante del sistema</th>
                    <th>Notas</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
            @forelse($filas as $f)
                <tr class="{{ $f['estudiante'] ? '' : 'table-danger' }}">
                    <td>{{ $f['linea'] }}</td>
                    <td>{{ $f['archivo'] }}</td>
                    <td>
                        @if($f['estudiante'])
                            {{ $f['estudiante'] }}
                            <span class="badge bg-light text-dark border" style="font-size:.68rem;">por {{ $f['metodo'] }}</span>
                        @else
                            <span class="text-danger">—</span>
                        @endif
                    </td>
                    <td>
                        @foreach($f['notas'] as $etq => $n)
                            <span class="badge bg-primary-subtle text-primary-emphasis me-1">{{ $etq }}: {{ rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.') }}</span>
                        @endforeach
                    </td>
                    <td class="text-danger">
                        {{ $f['error'] }}
                        @foreach($f['problemas'] as $p) <div>{{ $p }}</div> @endforeach
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">El archivo no trae filas con estudiantes.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mb-5">
    <form method="POST" action="{{ route('admin.importaciones.calificaciones.descartar') }}">
        @csrf
        <button class="btn btn-outline-secondary" style="border-radius:8px;">
            <i class="bi bi-x-circle me-1"></i>Descartar
        </button>
    </form>
    <form method="POST" action="{{ route('admin.importaciones.calificaciones.confirmar') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <button class="btn fw-semibold px-4" style="background:var(--primary);color:#fff;border-radius:8px;"
                @disabled($resumen['listas'] === 0)>
            <i class="bi bi-check-circle me-1"></i>Confirmar e importar ({{ $resumen['listas'] }} estudiante(s))
        </button>
    </form>
</div>
@endsection
