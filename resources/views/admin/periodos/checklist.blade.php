@extends('layouts.admin')
@section('page-title', 'Cierre de Período — ' . $periodo->nombre)

@push('styles')
<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
.page-header h1 { font-size:1.3rem; font-weight:800; color:var(--primary); margin:0; }

.resumen-bar { display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem; }
.res-chip { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:.85rem 1.1rem; flex:1; min-width:130px; text-align:center; }
.res-chip .val { font-size:1.4rem; font-weight:900; line-height:1; }
.res-chip .lbl { font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-top:.25rem; }

.table-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; margin-bottom:1.5rem; }
.table-card table { margin:0; }
.table-card thead th { background:#f8fafc; font-size:.73rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; border-bottom:1px solid #e5e7eb; padding:.75rem 1rem; }
.table-card tbody td { font-size:.83rem; padding:.7rem 1rem; vertical-align:middle; border-bottom:1px solid #f3f4f6; }
.table-card tbody tr:last-child td { border-bottom:none; }
.table-card tbody tr:hover { background:#f9fafb; }

.check-ok   { color:#10b981; font-size:1rem; }
.check-warn { color:#f59e0b; font-size:1rem; }
.check-fail { color:#ef4444; font-size:1rem; }

.cierre-card { background:linear-gradient(135deg,#1e3a6e,#2563eb); border-radius:14px; padding:1.5rem; color:#fff; margin-bottom:1.5rem; }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1>
        <a href="{{ route('admin.periodos.index') }}" class="text-decoration-none me-2" style="color:#6b7280;"><i class="bi bi-arrow-left"></i></a>
        Checklist de Cierre — {{ $periodo->nombre }}
    </h1>
    <div class="d-flex gap-2">
        @if(!$periodo->cerrado)
        <form method="POST" action="{{ route('admin.periodos.cerrar', $periodo) }}"
              onsubmit="return confirm('¿Cerrar el período {{ $periodo->nombre }}? Esta acción activará el período siguiente si existe.')">
            @csrf
            <button type="submit" class="btn btn-danger btn-sm px-4">
                <i class="bi bi-lock-fill me-1"></i>Cerrar Período
            </button>
        </form>
        @else
        <span class="badge text-bg-secondary py-2 px-3" style="font-size:.82rem;border-radius:8px;">
            <i class="bi bi-lock-fill me-1"></i>Período Cerrado
        </span>
        @endif
    </div>
</div>

{{-- Alerta si ya está cerrado --}}
@if($periodo->cerrado)
<div class="alert alert-secondary py-2 mb-4" style="border-radius:10px;font-size:.83rem;">
    <i class="bi bi-lock me-1"></i>Este período ya fue cerrado. Solo puedes consultar el estado.
</div>
@endif

{{-- Resumen --}}
<div class="resumen-bar">
    <div class="res-chip">
        <div class="val" style="color:#1d4ed8;">{{ $resumen['total'] }}</div>
        <div class="lbl">Asignaciones</div>
    </div>
    <div class="res-chip">
        <div class="val" style="color:#10b981;">{{ $resumen['completas'] }}</div>
        <div class="lbl">Completas</div>
    </div>
    <div class="res-chip">
        <div class="val" style="color:#ef4444;">{{ $resumen['sin_notas'] }}</div>
        <div class="lbl">Sin Notas</div>
    </div>
    <div class="res-chip">
        <div class="val" style="color:#f59e0b;">{{ $resumen['sin_publi'] }}</div>
        <div class="lbl">Sin Publicar</div>
    </div>
    <div class="res-chip">
        <div class="val" style="color:#374151;">{{ $totalMatriculas }}</div>
        <div class="lbl">Estudiantes activos</div>
    </div>
</div>

@if($resumen['sin_notas'] === 0 && $resumen['sin_publi'] === 0)
<div class="alert alert-success py-2 mb-4" style="border-radius:10px;font-size:.83rem;">
    <i class="bi bi-check-circle-fill me-1"></i>
    <strong>¡Todo listo!</strong> Todas las asignaciones tienen notas registradas y publicadas.
</div>
@else
<div class="alert alert-warning py-2 mb-4" style="border-radius:10px;font-size:.83rem;">
    <i class="bi bi-exclamation-triangle-fill me-1"></i>
    Hay <strong>{{ $resumen['sin_notas'] + $resumen['sin_publi'] }}</strong> asignación(es) pendientes.
    Revisa la tabla antes de cerrar el período.
</div>
@endif

{{-- Tabla detalle --}}
<div class="table-card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Asignatura</th>
                <th>Grupo</th>
                <th>Docente</th>
                <th>Tipo</th>
                <th class="text-center">Notas</th>
                <th class="text-center">Publicadas</th>
                <th class="text-center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items->sortBy(fn($i) => $i['ok_ingreso'] && $i['ok_publi'] ? 1 : 0) as $item)
            @php
                $asig = $item['asignacion'];
                $ok   = $item['ok_ingreso'] && $item['ok_publi'];
                $warn = $item['total_cal'] > 0 && !$item['ok_publi'];
                $fail = $item['total_cal'] === 0;
            @endphp
            <tr style="{{ $fail ? 'background:#fff5f5;' : ($warn ? 'background:#fffbeb;' : '') }}">
                <td class="fw-semibold">{{ $asig->asignatura?->nombre ?? '—' }}</td>
                <td style="font-size:.8rem;">{{ $asig->grupo?->grado?->nombre ?? '' }} {{ $asig->grupo?->seccion?->nombre ?? '' }}</td>
                <td style="font-size:.8rem;color:#374151;">{{ $asig->docente?->nombre_completo ?? '—' }}</td>
                <td>
                    <span class="badge" style="font-size:.68rem;background:{{ $asig->area === 'tecnica' ? '#ede9fe' : '#dbeafe' }};color:{{ $asig->area === 'tecnica' ? '#5b21b6' : '#1d4ed8' }};">
                        {{ $asig->area === 'tecnica' ? 'Técnica' : 'Académica' }}
                    </span>
                </td>
                <td class="text-center">
                    @if($item['total_cal'] === 0)
                        <i class="bi bi-x-circle-fill check-fail" title="Sin notas"></i>
                    @else
                        <span class="fw-bold" style="color:#374151;">{{ $item['total_cal'] }}</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($item['publicadas'] === 0)
                        <i class="bi bi-dash-circle check-warn" title="Sin publicar"></i>
                    @elseif($item['ok_publi'])
                        <i class="bi bi-check-circle-fill check-ok" title="Publicadas"></i>
                    @else
                        <span class="text-warning fw-bold">{{ $item['publicadas'] }}/{{ $item['total_cal'] }}</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($ok)
                        <span class="badge text-bg-success" style="font-size:.7rem;">Listo</span>
                    @elseif($fail)
                        <span class="badge text-bg-danger" style="font-size:.7rem;">Sin notas</span>
                    @else
                        <span class="badge text-bg-warning" style="font-size:.7rem;">Pendiente</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
