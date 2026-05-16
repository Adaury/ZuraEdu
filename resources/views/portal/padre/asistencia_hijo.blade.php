@extends('layouts.portal')

@section('title', 'Asistencia de ' . $estudiante->nombres)

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'asistencia', 'estudiante' => $estudiante])
@endsection

@section('content')
<div class="prt-page-header">
    <div>
        <h4 class="prt-page-title">
            <i class="bi bi-clipboard-check me-2"></i>Asistencia — {{ $estudiante->nombre_completo }}
        </h4>
        @if($matricula)
        <p class="prt-page-subtitle">
            {{ $matricula->grupo?->nombre_completo }} — {{ $schoolYear?->nombre }}
        </p>
        @endif
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('portal.padre.hijo.asistencia.pdf', $estudiante) }}" target="_blank"
           class="btn btn-sm btn-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
        </a>
        <a href="{{ route('portal.padre.hijo.asistencia.excel', $estudiante) }}"
           class="btn btn-sm btn-success">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('portal.padre.hijo', $estudiante) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

@if(! $matricula || $resumenAsistencia['total'] === 0)
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-clipboard-x" style="font-size:2.5rem;opacity:.4;"></i>
        <p class="mt-3 mb-0">No hay registros de asistencia disponibles aún.</p>
    </div>
</div>
@else

{{-- Resumen general --}}
<div class="row g-3 mb-4">
    @php
        $pct = $resumenAsistencia['porcentaje'];
        $pctColor = $pct >= 80 ? '#10b981' : ($pct >= 60 ? '#f59e0b' : '#ef4444');
    @endphp
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div style="font-size:1.6rem;font-weight:800;color:#1e3a6e;">{{ $resumenAsistencia['total'] }}</div>
            <div style="font-size:.75rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Total clases</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div style="font-size:1.6rem;font-weight:800;color:#10b981;">{{ $resumenAsistencia['presentes'] }}</div>
            <div style="font-size:.75rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Presentes</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div style="font-size:1.6rem;font-weight:800;color:#ef4444;">{{ $resumenAsistencia['ausentes'] }}</div>
            <div style="font-size:.75rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Ausentes</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div style="font-size:1.6rem;font-weight:800;color:{{ $pctColor }};">{{ $pct !== null ? $pct . '%' : '—' }}</div>
            <div style="font-size:.75rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Asistencia</div>
        </div>
    </div>
</div>

{{-- Barra de progreso --}}
@if($pct !== null)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between mb-1" style="font-size:.8rem;">
            <span class="fw-semibold">Porcentaje de asistencia general</span>
            <span style="color:{{ $pctColor }};font-weight:700;">{{ $pct }}%</span>
        </div>
        <div style="height:10px;background:#e5e7eb;border-radius:99px;overflow:hidden;">
            <div style="height:100%;width:{{ $pct }}%;background:{{ $pctColor }};border-radius:99px;transition:width .4s;"></div>
        </div>
        @if($pct < 80)
        <div class="mt-2" style="font-size:.75rem;color:#ef4444;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>La asistencia está por debajo del 80% requerido.
        </div>
        @endif
    </div>
</div>
@endif

{{-- Por materia --}}
@if(isset($resumenAsistencia['por_materia']) && $resumenAsistencia['por_materia']->isNotEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h6 class="fw-bold mb-0"><i class="bi bi-journal-check me-2 text-primary"></i>Asistencia por materia</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.83rem;">
                <thead class="table-light">
                    <tr>
                        <th>Materia</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Presentes</th>
                        <th class="text-center">Ausentes</th>
                        <th style="min-width:120px;">Asistencia</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($resumenAsistencia['por_materia'] as $mat)
                @php
                    $mp = $mat['porcentaje'];
                    $mc = $mp >= 80 ? '#10b981' : ($mp >= 60 ? '#f59e0b' : '#ef4444');
                @endphp
                <tr>
                    <td class="fw-semibold">{{ $mat['asignatura'] }}</td>
                    <td class="text-center">{{ $mat['total'] }}</td>
                    <td class="text-center text-success fw-semibold">{{ $mat['presentes'] }}</td>
                    <td class="text-center text-danger fw-semibold">{{ $mat['ausentes'] }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="flex:1;height:7px;background:#e5e7eb;border-radius:99px;overflow:hidden;">
                                <div style="height:100%;width:{{ $mp ?? 0 }}%;background:{{ $mc }};border-radius:99px;"></div>
                            </div>
                            <span style="font-size:.75rem;font-weight:700;color:{{ $mc }};min-width:35px;">{{ $mp !== null ? $mp . '%' : '—' }}</span>
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ── Ausencias recientes y solicitudes de justificación ────────────── --}}
@if(isset($ausenciasDetalle) && $ausenciasDetalle->isNotEmpty())
<div class="prt-card" style="margin-top:1rem;">
    <div class="prt-card-header">
        <i class="bi bi-calendar-x" style="color:#f59e0b;"></i>
        <h3>Ausencias recientes</h3>
    </div>
    <div class="prt-card-body" style="padding:.5rem 0 0;">

        @if(session('success'))
        <div style="background:#d1fae5;color:#065f46;border-radius:8px;padding:.6rem .9rem;font-size:.8rem;font-weight:700;margin:.5rem 1rem;">
            <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
        </div>
        @endif

        @foreach($ausenciasDetalle as $aus)
        @php
            $tipoLabel = $aus->justificacion_tipo
                ? ($tiposJustificacion[$aus->justificacion_tipo] ?? $aus->justificacion_tipo)
                : null;
        @endphp
        <div style="border-top:1px solid #f1f5f9;padding:.6rem 1rem;" x-data="{ solicitar: false }">
            <div style="display:flex;align-items:flex-start;gap:.6rem;flex-wrap:wrap;">
                <div style="flex:1;min-width:120px;">
                    <div style="font-size:.8rem;font-weight:700;color:#1e293b;">
                        {{ $aus->fecha->format('d/m/Y') }}
                        <span style="font-size:.7rem;color:#94a3b8;font-weight:400;margin-left:.4rem;">
                            {{ $aus->asignacion?->asignatura?->nombre ?? '—' }}
                        </span>
                    </div>
                    @if($aus->justificacion)
                    <div style="font-size:.72rem;color:#059669;margin-top:.2rem;">
                        <i class="bi bi-patch-check-fill me-1"></i>
                        Justificada: {{ $aus->justificacion }}
                        @if($tipoLabel) · <em>{{ $tipoLabel }}</em> @endif
                    </div>
                    @endif
                </div>
                @if(!$aus->justificacion)
                <span style="background:#fee2e2;color:#dc2626;font-size:.68rem;font-weight:700;padding:.15rem .5rem;border-radius:6px;flex-shrink:0;">Ausente</span>
                <button @click="solicitar = !solicitar"
                        style="background:#fef3c7;color:#d97706;border:1px solid #fde68a;border-radius:8px;font-size:.7rem;font-weight:700;padding:.22rem .6rem;cursor:pointer;flex-shrink:0;">
                    <i class="bi bi-send-fill me-1"></i>Solicitar justificación
                </button>
                @else
                <span style="background:#d1fae5;color:#059669;font-size:.68rem;font-weight:700;padding:.15rem .5rem;border-radius:6px;flex-shrink:0;">
                    <i class="bi bi-patch-check-fill me-1"></i>Justificada
                </span>
                @endif
            </div>

            {{-- Formulario de solicitud --}}
            @if(!$aus->justificacion)
            <div x-show="solicitar" x-transition style="margin-top:.5rem;background:#fefce8;border-radius:8px;border:1px solid #fde68a;padding:.65rem .8rem;">
                <form method="POST" action="{{ route('portal.padre.hijo.solicitar-justificacion', $estudiante) }}">
                    @csrf
                    <input type="hidden" name="fecha_evento" value="{{ $aus->fecha->format('Y-m-d') }}">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.45rem;margin-bottom:.45rem;">
                        <div>
                            <label style="font-size:.7rem;font-weight:700;color:#92400e;display:block;margin-bottom:.2rem;">Tipo</label>
                            <select name="tipo" style="width:100%;border:1.5px solid #fde68a;border-radius:7px;font-size:.75rem;padding:.28rem .4rem;background:#fff;">
                                <option value="">-- Seleccione --</option>
                                @foreach($tiposJustificacion as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="grid-column:1/-1;">
                            <label style="font-size:.7rem;font-weight:700;color:#92400e;display:block;margin-bottom:.2rem;">Descripción <span style="color:#dc2626;">*</span></label>
                            <textarea name="descripcion" rows="2" required maxlength="1000"
                                      placeholder="Explica el motivo de la ausencia…"
                                      style="width:100%;border:1.5px solid #fde68a;border-radius:7px;font-size:.75rem;padding:.3rem .45rem;background:#fff;resize:vertical;"></textarea>
                        </div>
                    </div>
                    <div style="display:flex;gap:.5rem;">
                        <button type="submit"
                                style="background:#10b981;color:#fff;border:none;border-radius:8px;font-size:.73rem;font-weight:700;padding:.28rem .75rem;cursor:pointer;">
                            <i class="bi bi-send me-1"></i>Enviar solicitud
                        </button>
                        <button type="button" @click="solicitar = false"
                                style="background:#f1f5f9;color:#64748b;border:none;border-radius:8px;font-size:.7rem;padding:.28rem .6rem;cursor:pointer;">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

@endif
@endsection
