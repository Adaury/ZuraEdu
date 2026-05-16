@extends('layouts.portal')
@section('page-title', 'Aplicar Notas — ' . $periodo->nombre)
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'plan-evaluacion'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.instrumentos.index', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-clipboard-check-fill"></i>Instrum.
    </a>
    <a href="{{ route('portal.docente.plan-evaluacion.index', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-bar-chart-steps"></i>Plan Eval.
    </a>
@endsection

@push('styles')
<style>
.est-row { border-bottom: 1px solid #f1f5f9; transition: background .1s; }
.est-row:hover { background: #f8faff; }
.nota-inp {
    width: 70px; text-align: center;
    border: 1.5px solid #e2e8f0; border-radius: 7px;
    padding: .3rem .2rem; font-size: .88rem; font-weight: 700;
    background: #fff; color: #1e293b;
    transition: border-color .15s;
    -moz-appearance: textfield;
}
.nota-inp::-webkit-inner-spin-button,
.nota-inp::-webkit-outer-spin-button { -webkit-appearance: none; }
.nota-inp:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.18); }
.nota-inp.aprobado { border-color: #10b981; }
.nota-inp.reprobado { border-color: #ef4444; }
.nota-badge {
    display:inline-block; min-width:44px; text-align:center;
    font-weight:800; font-size:.85rem; border-radius:8px; padding:.2rem .4rem;
}
.nota-ok  { background:#dcfce7; color:#15803d; }
.nota-med { background:#fef9c3; color:#92400e; }
.nota-low { background:#fee2e2; color:#dc2626; }
.nota-nil { background:#f1f5f9; color:#94a3b8; }
</style>
@endpush

@section('content')

{{-- Cabecera --}}
<div style="display:flex;align-items:flex-start;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.plan-evaluacion.index', $asignacion) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;flex-shrink:0;margin-top:.1rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;min-width:0;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-calculator" style="color:#1e3a8a;"></i>
            Aplicar notas — {{ $periodo->nombre }}
        </h1>
        <div style="font-size:.75rem;color:#64748b;margin-top:.15rem;">
            {{ $asignacion->asignatura?->nombre }} &mdash; {{ $asignacion->grupo?->nombre_completo ?? '—' }}
        </div>
    </div>
</div>

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;padding:.65rem 1rem;margin-bottom:1rem;font-size:.82rem;color:#15803d;display:flex;align-items:center;gap:.5rem;">
    <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
</div>
@endif

{{-- Info del cálculo --}}
<div class="prt-card" style="margin-bottom:1rem;padding:.85rem 1rem;">
    <div style="display:flex;align-items:flex-start;gap:.75rem;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
            <div style="font-size:.75rem;font-weight:700;color:#374151;margin-bottom:.3rem;">
                <i class="bi bi-info-circle me-1" style="color:#3b82f6;"></i>¿Cómo se calcula?
            </div>
            <div style="font-size:.78rem;color:#64748b;line-height:1.5;">
                La nota propuesta es el <strong>promedio de las ponderaciones</strong> de todos los
                instrumentos evaluados en <strong>{{ $periodo->nombre }}</strong>. Cada instrumento
                ya tiene su ponderación en escala 0–100.
            </div>
        </div>
        <div style="min-width:160px;">
            <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.3rem;">Instrumentos del período</div>
            @if($instrumentos->isEmpty())
            <span style="font-size:.82rem;color:#dc2626;font-weight:600;">
                <i class="bi bi-exclamation-triangle me-1"></i>Ninguno evaluado
            </span>
            @else
            @foreach($instrumentos as $inst)
            <div style="font-size:.75rem;color:#374151;display:flex;align-items:center;gap:.35rem;margin-bottom:.2rem;">
                <i class="bi bi-check2-circle" style="color:#10b981;"></i>{{ $inst->titulo }}
            </div>
            @endforeach
            @endif
        </div>
        @if($plan)
        <div style="min-width:160px;">
            <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.3rem;">Pesos del plan</div>
            @foreach($categorias as $campo => $cat)
            @php $pct = $plan->$campo ?? 0; @endphp
            @if($pct > 0)
            <div style="font-size:.73rem;color:#374151;display:flex;align-items:center;gap:.35rem;margin-bottom:.15rem;">
                <i class="bi {{ $cat['icon'] }}" style="color:{{ $cat['color'] }};"></i>
                {{ $cat['label'] }}: <strong>{{ $pct }}%</strong>
            </div>
            @endif
            @endforeach
        </div>
        @endif
    </div>
</div>

@if($instrumentos->isEmpty())
<div class="prt-card" style="text-align:center;padding:2.5rem;">
    <i class="bi bi-clipboard-x" style="font-size:2rem;color:#94a3b8;display:block;margin-bottom:.75rem;"></i>
    <p style="color:#64748b;font-size:.88rem;margin:0;">
        No hay instrumentos evaluados en <strong>{{ $periodo->nombre }}</strong>.<br>
        <a href="{{ route('portal.docente.instrumentos.index', $asignacion) }}" style="color:#2563eb;">Ir a instrumentos</a> para evaluar primero.
    </p>
</div>
@else

<form method="POST" action="{{ route('portal.docente.plan-evaluacion.aplicar.guardar', [$asignacion, $periodo]) }}">
    @csrf

    <div class="prt-card">
        <div class="prt-card-header">
            <i class="bi bi-table" style="color:#10b981;"></i>
            <h3>Preview de notas — {{ $periodo->nombre }}</h3>
            <span style="margin-left:auto;font-size:.72rem;color:#64748b;">
                Puedes ajustar la nota antes de guardar
            </span>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                        <th style="padding:.5rem 1rem;text-align:left;font-size:.72rem;font-weight:700;color:#374151;min-width:170px;">Estudiante</th>
                        <th style="padding:.45rem .5rem;text-align:center;font-size:.68rem;font-weight:700;color:#64748b;min-width:90px;">Instr. evaluados</th>
                        <th style="padding:.45rem .5rem;text-align:center;font-size:.68rem;font-weight:700;color:#64748b;min-width:80px;">Nota actual</th>
                        <th style="padding:.45rem .5rem;text-align:center;font-size:.68rem;font-weight:700;color:#2563eb;min-width:100px;">Nota propuesta</th>
                        <th style="padding:.45rem .5rem;text-align:center;font-size:.68rem;font-weight:700;color:#10b981;min-width:100px;">Aplicar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview as $row)
                    @php
                        $mat    = $row['matricula'];
                        $prop   = $row['notaPropuesta'];
                        $actual = $row['notaActual'];
                        $propClass = $prop === null ? 'nota-nil' : ($prop >= 70 ? 'nota-ok' : ($prop >= 65 ? 'nota-med' : 'nota-low'));
                    @endphp
                    <tr class="est-row">
                        <td style="padding:.55rem 1rem;font-size:.82rem;font-weight:600;color:#1e293b;white-space:nowrap;">
                            {{ $mat->estudiante->apellidos }}, {{ $mat->estudiante->nombres }}
                        </td>
                        <td style="padding:.4rem .5rem;text-align:center;">
                            @if($row['evaluados'] === 0)
                            <span style="font-size:.72rem;color:#dc2626;"><i class="bi bi-dash-circle"></i> Sin evaluar</span>
                            @else
                            <span style="font-size:.78rem;color:#374151;">{{ $row['evaluados'] }}/{{ $row['total'] }}</span>
                            @endif
                        </td>
                        <td style="padding:.4rem .5rem;text-align:center;">
                            @if($actual !== null)
                            <span class="nota-badge {{ $actual >= 70 ? 'nota-ok' : ($actual >= 65 ? 'nota-med' : 'nota-low') }}">
                                {{ number_format($actual, 1) }}
                            </span>
                            @else
                            <span class="nota-badge nota-nil">—</span>
                            @endif
                        </td>
                        <td style="padding:.4rem .5rem;text-align:center;">
                            @if($prop !== null)
                            <span class="nota-badge {{ $propClass }}">{{ number_format($prop, 1) }}</span>
                            @else
                            <span class="nota-badge nota-nil">—</span>
                            @endif
                        </td>
                        <td style="padding:.4rem .5rem;text-align:center;">
                            <input type="number"
                                   name="notas[{{ $mat->id }}]"
                                   class="nota-inp {{ $prop !== null ? ($prop >= 65 ? 'aprobado' : 'reprobado') : '' }}"
                                   min="0" max="100" step="0.01"
                                   value="{{ $prop !== null ? number_format($prop, 2, '.', '') : '' }}"
                                   placeholder="—"
                                   oninput="colorInp(this)">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="padding:.85rem 1rem;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem;">
            <div style="font-size:.75rem;color:#64748b;">
                <i class="bi bi-exclamation-triangle me-1" style="color:#f59e0b;"></i>
                Esto sobreescribirá la nota de <strong>{{ $periodo->nombre }}</strong> en el libro de calificaciones.
            </div>
            <button type="submit"
                    style="background:linear-gradient(135deg,#1e3a8a,#2563eb);color:#fff;border:none;border-radius:9px;padding:.55rem 1.5rem;font-size:.85rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.5rem;">
                <i class="bi bi-check2-circle"></i>Confirmar y aplicar notas
            </button>
        </div>
    </div>

</form>

@endif

@endsection

@push('scripts')
<script>
function colorInp(inp) {
    const v = parseFloat(inp.value);
    inp.classList.remove('aprobado', 'reprobado');
    if (!isNaN(v)) inp.classList.add(v >= 65 ? 'aprobado' : 'reprobado');
}
</script>
@endpush
