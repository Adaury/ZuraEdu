@extends('layouts.portal')
@section('page-title', 'Vista previa — Importar Calificaciones')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'calificaciones', 'asignacion' => $asignacion])
@endsection

@section('bottom-nav')
<a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item"><i class="bi bi-house-fill"></i>Inicio</a>
<a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item active"><i class="bi bi-journal-check"></i>Notas</a>
@endsection

@push('styles')
<style>
.prev-table th, .prev-table td {
    padding:.4rem .6rem;font-size:.78rem;vertical-align:middle;border-bottom:1px solid #f1f5f9;
}
.prev-table th { background:#f8fafc;font-weight:700;color:#475569;border-bottom:2px solid #e2e8f0; }
.row-ok          { background:#f0fdf4; }
.row-advertencia { background:#fffbeb; }
.row-error       { background:#fef2f2; }
.status-dot {
    width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:4px;
}
.dot-ok   { background:#10b981; }
.dot-warn { background:#f59e0b; }
.dot-err  { background:#ef4444; }
.nota-chip {
    display:inline-block;min-width:32px;text-align:center;
    padding:.1rem .35rem;border-radius:4px;font-size:.75rem;font-weight:700;
}
.nc-ok  { background:#dcfce7;color:#166534; }
.nc-low { background:#fee2e2;color:#991b1b; }
.nc-nil { background:#f1f5f9;color:#94a3b8; }
.nc-warn{ background:#fef3c7;color:#92400e; }
.final-ok  { color:#10b981;font-weight:800; }
.final-low { color:#ef4444;font-weight:800; }
.final-nil { color:#94a3b8; }
.kpi-bar { display:flex;gap:.6rem;margin-bottom:1rem;flex-wrap:wrap; }
.kpi-mini {
    flex:1;min-width:90px;border-radius:8px;padding:.6rem .8rem;text-align:center;
}
.err-list { margin:.25rem 0 0 .8rem;padding:0;font-size:.72rem;color:#991b1b; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div style="display:flex;align-items:center;gap:.7rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}"
       style="color:#6366f1;text-decoration:none;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:.3rem;">
        <i class="bi bi-arrow-left"></i>Calificaciones
    </a>
    <span style="color:#cbd5e1;">›</span>
    <h2 style="font-size:.95rem;font-weight:800;margin:0;flex:1;">
        Vista Previa — Importación de Notas
    </h2>
    <span style="font-size:.76rem;color:#64748b;">
        {{ $asignacion->asignatura?->nombre }} · {{ $asignacion->grupo?->nombre }}
    </span>
</div>

{{-- KPIs de resumen --}}
<div class="kpi-bar">
    <div class="kpi-mini" style="background:#f0fdf4;border:1px solid #86efac;">
        <div style="font-size:1.4rem;font-weight:800;color:#16a34a;">{{ $totales['ok'] }}</div>
        <div style="font-size:.68rem;color:#166534;">Listos para importar</div>
    </div>
    @if($totales['advertencia'] > 0)
    <div class="kpi-mini" style="background:#fffbeb;border:1px solid #fde68a;">
        <div style="font-size:1.4rem;font-weight:800;color:#d97706;">{{ $totales['advertencia'] }}</div>
        <div style="font-size:.68rem;color:#92400e;">Sobreescribirán notas</div>
    </div>
    @endif
    @if($totales['error'] > 0)
    <div class="kpi-mini" style="background:#fef2f2;border:1px solid #fca5a5;">
        <div style="font-size:1.4rem;font-weight:800;color:#dc2626;">{{ $totales['error'] }}</div>
        <div style="font-size:.68rem;color:#991b1b;">Con errores (omitidas)</div>
    </div>
    @endif
    <div class="kpi-mini" style="background:#f8fafc;border:1px solid #e2e8f0;">
        <div style="font-size:1.4rem;font-weight:800;color:#475569;">{{ $totales['total'] }}</div>
        <div style="font-size:.68rem;color:#64748b;">Filas en el archivo</div>
    </div>
</div>

@if($totales['ok'] + $totales['advertencia'] === 0)
{{-- Solo hay errores —  no hay nada que importar --}}
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:1rem;margin-bottom:1rem;text-align:center;">
    <i class="bi bi-exclamation-triangle-fill" style="font-size:1.5rem;color:#dc2626;display:block;margin-bottom:.4rem;"></i>
    <div style="font-weight:700;color:#991b1b;">Ninguna fila válida para importar.</div>
    <div style="font-size:.8rem;color:#991b1b;margin-top:.3rem;">
        Revisa los errores en la tabla, corrige el archivo y vuelve a subirlo.
    </div>
</div>
@else
{{-- Advertencia de sobreescritura --}}
@if($totales['advertencia'] > 0)
<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:.7rem 1rem;margin-bottom:.9rem;font-size:.8rem;color:#92400e;display:flex;align-items:flex-start;gap:.5rem;">
    <i class="bi bi-exclamation-circle-fill" style="margin-top:.15rem;flex-shrink:0;"></i>
    <div>
        <strong>{{ $totales['advertencia'] }} estudiante(s) ya tienen notas guardadas.</strong>
        Las filas en amarillo sobreescribirán las notas existentes si confirmas la importación.
        Las filas en verde son nuevas.
    </div>
</div>
@endif

{{-- Botones de acción --}}
<div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;margin-bottom:1rem;">
    <form method="POST"
          action="{{ route('portal.docente.calificaciones.importar.confirmar', $asignacion) }}"
          style="margin:0;"
          onsubmit="return confirmarImport();">
        @csrf
        <button type="submit"
                style="background:#16a34a;color:#fff;border:none;border-radius:8px;padding:.5rem 1.2rem;font-size:.85rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.45rem;">
            <i class="bi bi-cloud-upload-fill"></i>
            Confirmar e importar
            <strong>({{ $totales['ok'] + $totales['advertencia'] }})</strong>
        </button>
    </form>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}"
       style="background:#f1f5f9;color:#475569;border-radius:8px;padding:.5rem 1rem;font-size:.82rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.35rem;">
        <i class="bi bi-x-lg"></i>Cancelar
    </a>
    <span style="font-size:.75rem;color:#64748b;margin-left:auto;">
        @if($totales['error'] > 0)
        <i class="bi bi-info-circle me-1"></i>Las {{ $totales['error'] }} fila(s) con error se omitirán automáticamente.
        @endif
    </span>
</div>
@endif

{{-- Tabla de preview --}}
<div class="prt-card" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
        <table class="prev-table" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="width:28px;">#</th>
                    <th>Estudiante</th>
                    @foreach($columnas as $col)
                    <th style="text-align:center;min-width:42px;">
                        {{ strtoupper($col) }}
                    </th>
                    @endforeach
                    <th style="text-align:center;min-width:52px;">Final</th>
                    <th style="min-width:80px;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($filas as $fila)
                <tr class="row-{{ $fila['status'] }}">
                    <td style="color:#94a3b8;font-size:.72rem;">{{ $fila['linea'] }}</td>
                    <td>
                        <div style="font-weight:600;font-size:.82rem;line-height:1.2;">
                            {{ $fila['nombre'] }}
                        </div>
                        <div style="font-size:.68rem;color:#94a3b8;">
                            {{ $fila['numMat'] ?: $fila['cedula'] }}
                        </div>
                    </td>
                    @foreach($columnas as $col)
                    @php $v = $fila['notas'][$col] ?? null; @endphp
                    <td style="text-align:center;">
                        @if($v !== null)
                            @php
                                $cls = $v >= 70 ? 'nc-ok' : ($v >= 50 ? 'nc-warn' : 'nc-low');
                            @endphp
                            <span class="nota-chip {{ $cls }}">{{ $v }}</span>
                        @else
                            <span class="nota-chip nc-nil">—</span>
                        @endif
                    </td>
                    @endforeach
                    <td style="text-align:center;">
                        @if($fila['notaFinal'] !== null)
                            <span class="{{ $fila['notaFinal'] >= 70 ? 'final-ok' : 'final-low' }}" style="font-size:.82rem;">
                                {{ $fila['notaFinal'] }}
                            </span>
                        @else
                            <span class="final-nil">—</span>
                        @endif
                    </td>
                    <td>
                        @if($fila['status'] === 'ok')
                            <span style="font-size:.72rem;font-weight:700;color:#16a34a;display:flex;align-items:center;gap:.25rem;">
                                <span class="status-dot dot-ok"></span>Nuevo
                            </span>
                        @elseif($fila['status'] === 'advertencia')
                            <span style="font-size:.72rem;font-weight:700;color:#d97706;display:flex;align-items:center;gap:.25rem;">
                                <span class="status-dot dot-warn"></span>Sobreescribir
                            </span>
                        @else
                            <span style="font-size:.72rem;font-weight:700;color:#dc2626;display:flex;align-items:center;gap:.25rem;">
                                <span class="status-dot dot-err"></span>Error
                            </span>
                            @if($fila['errores'])
                            <ul class="err-list">
                                @foreach($fila['errores'] as $e)
                                <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                            @endif
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Leyenda --}}
<div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:.75rem;font-size:.72rem;color:#64748b;align-items:center;">
    <span><span class="status-dot dot-ok" style="display:inline-block;"></span> Nuevo registro</span>
    <span><span class="status-dot dot-warn" style="display:inline-block;"></span> Sobreescribirá nota existente</span>
    <span><span class="status-dot dot-err" style="display:inline-block;"></span> Error — se omite</span>
    <span style="margin-left:auto;">
        Chips: <span class="nota-chip nc-ok" style="font-size:.65rem;">≥70</span>
        <span class="nota-chip nc-warn" style="font-size:.65rem;">50–69</span>
        <span class="nota-chip nc-low" style="font-size:.65rem;">&lt;50</span>
        <span class="nota-chip nc-nil" style="font-size:.65rem;">—</span>
    </span>
</div>

@endsection

@push('scripts')
<script>
function confirmarImport() {
    const validas = {{ $totales['ok'] + $totales['advertencia'] }};
    const sobrescriben = {{ $totales['advertencia'] }};
    if (sobrescriben > 0) {
        return confirm(
            '¿Confirmar importación?\n\n' +
            '• ' + validas + ' nota(s) se importarán.\n' +
            '• ' + sobrescriben + ' nota(s) existentes serán sobreescritas.\n\n' +
            'Esta acción no se puede deshacer.'
        );
    }
    return true;
}
</script>
@endpush
