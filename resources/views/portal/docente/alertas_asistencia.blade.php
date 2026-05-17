@extends('layouts.portal')
@section('page-title', 'Alertas de Inasistencias — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'asistencia-alertas', 'asignacion' => $asignacion])
@endsection

@section('bottom-nav')
<a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item"><i class="bi bi-house-fill"></i>Inicio</a>
<a href="{{ route('portal.docente.asistencia', $asignacion) }}" class="prt-nav-item"><i class="bi bi-calendar-check-fill"></i>Asistencia</a>
<a href="{{ route('portal.docente.asistencia.alertas', $asignacion) }}" class="prt-nav-item active"><i class="bi bi-bell-fill"></i>Alertas</a>
@endsection

@push('styles')
<style>
.kpi-card {
    background: #fff; border: 1.5px solid #e2e8f0; border-radius: 12px;
    padding: .75rem .9rem; text-align: center;
}
.kpi-num { font-size: 1.55rem; font-weight: 900; line-height: 1.1; }
.kpi-lbl { font-size: .63rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-top: 3px; }

.umb-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 34px; height: 34px; border-radius: 8px; font-size: .78rem; font-weight: 800;
    cursor: pointer; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #475569;
    transition: all .12s; text-decoration: none;
}
.umb-btn:hover { background: #e2e8f0; }
.umb-btn.active { background: #1e3a8a; color: #fff; border-color: #1e3a8a; }

.alert-table { width: 100%; border-collapse: collapse; font-size: .78rem; }
.alert-table th {
    background: #1e293b; color: #fff; padding: .5rem .65rem;
    font-size: .67rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
    white-space: nowrap; text-align: center;
}
.alert-table th.left { text-align: left; }
.alert-table td { padding: .5rem .65rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; text-align: center; }
.alert-table td.left { text-align: left; }
.alert-table tr:hover td { background: #f8faff; }
.alert-table tr.critico td { background: #fff5f5; }
.alert-table tr.alerta td { background: #fffbeb; }
.alert-table tr.ok td { background: #f0fdf4; }

.asist-bar { height: 6px; border-radius: 99px; background: #e2e8f0; overflow: hidden; width: 60px; display: inline-block; vertical-align: middle; }
.asist-fill { height: 100%; border-radius: 99px; }

.estado-badge {
    display: inline-flex; align-items: center; gap: .2rem;
    padding: .18rem .5rem; border-radius: 99px; font-size: .68rem; font-weight: 700;
}
.eb-critico { background: #fee2e2; color: #dc2626; }
.eb-alerta  { background: #fef9c3; color: #92400e; }
.eb-ok      { background: #dcfce7; color: #15803d; }

.notif-btn {
    border: none; border-radius: 7px; padding: .28rem .65rem; font-size: .72rem; font-weight: 700;
    cursor: pointer; display: inline-flex; align-items: center; gap: .25rem;
    transition: all .15s;
}
.notif-btn:disabled { opacity: .5; cursor: not-allowed; }

/* Sparkline SVG bars */
.spark-wrap { display: inline-flex; align-items: flex-end; gap: 1.5px; height: 24px; }
.spark-bar  { border-radius: 2px 2px 0 0; min-width: 5px; }

/* Checkbox */
input[type=checkbox].row-check { cursor: pointer; width: 14px; height: 14px; accent-color: #1e3a8a; }
</style>
@endpush

@section('content')

{{-- Cabecera --}}
<div style="display:flex;align-items:flex-start;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.asistencia', $asignacion) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;flex-shrink:0;">
        <i class="bi bi-arrow-left"></i>Asistencia
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-bell-fill" style="color:#ef4444;margin-right:.3rem;"></i>
            Alertas de Inasistencias
        </h1>
        <div style="font-size:.75rem;color:#64748b;margin-top:.15rem;">
            {{ $asignacion->asignatura?->nombre }} &mdash; {{ $asignacion->grupo?->nombre_completo ?? '—' }}
            @if($schoolYear) · {{ $schoolYear->nombre }} @endif
        </div>
    </div>
    {{-- Notificar todos en alerta --}}
    <button id="btn-notif-all"
            onclick="notificarTodos()"
            class="notif-btn"
            style="background:#fef3c7;color:#d97706;font-size:.78rem;padding:.4rem .9rem;"
            @if($nEnAlerta === 0) disabled @endif>
        <i class="bi bi-bell-fill"></i>
        Notificar todos en alerta ({{ $nEnAlerta }})
    </button>
</div>

{{-- Selector de umbral --}}
<div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;padding:.65rem 1rem;margin-bottom:1rem;display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
    <span style="font-size:.74rem;font-weight:700;color:#475569;">Umbral de alerta (ausencias):</span>
    <div style="display:flex;gap:.3rem;flex-wrap:wrap;">
        @foreach([1,2,3,4,5,7,10,15,20] as $u)
        <a href="{{ route('portal.docente.asistencia.alertas', ['asignacion' => $asignacion->id, 'umbral' => $u]) }}"
           class="umb-btn {{ $umbral == $u ? 'active' : '' }}">{{ $u }}</a>
        @endforeach
    </div>
    <span style="font-size:.7rem;color:#94a3b8;">· "Crítico" = {{ $umbral * 2 }}+ ausencias o &lt;70% asistencia</span>
</div>

{{-- KPIs --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(115px,1fr));gap:.65rem;margin-bottom:1rem;">
    <div class="kpi-card">
        <div class="kpi-num" style="color:#1e293b;">{{ $filas->count() }}</div>
        <div class="kpi-lbl">Estudiantes</div>
    </div>
    <div class="kpi-card" style="border-color:#fde68a;">
        <div class="kpi-num" style="color:#d97706;">{{ $nEnAlerta }}</div>
        <div class="kpi-lbl">En alerta (≥{{ $umbral }})</div>
    </div>
    <div class="kpi-card" style="border-color:#fca5a5;">
        <div class="kpi-num" style="color:#dc2626;">{{ $nCriticos }}</div>
        <div class="kpi-lbl">Críticos</div>
    </div>
    <div class="kpi-card" style="border-color:#86efac;">
        <div class="kpi-num" style="color:{{ $promAsist === null ? '#94a3b8' : ($promAsist >= 90 ? '#15803d' : ($promAsist >= 80 ? '#d97706' : '#dc2626')) }};">
            {{ $promAsist !== null ? $promAsist . '%' : '—' }}
        </div>
        <div class="kpi-lbl">Prom. Asistencia</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-num" style="color:#3b82f6;">{{ $filas->sum('ausentes') }}</div>
        <div class="kpi-lbl">Total ausencias</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-num" style="color:#f59e0b;">{{ $filas->sum('tardanzas') }}</div>
        <div class="kpi-lbl">Total tardanzas</div>
    </div>
</div>

@if($filas->isEmpty())
<div style="text-align:center;padding:3rem;color:#94a3b8;">
    <i class="bi bi-calendar-check" style="font-size:2.5rem;opacity:.35;display:block;margin-bottom:.5rem;"></i>
    <p style="font-size:.9rem;margin:0;">No hay registros de asistencia para esta asignación.</p>
</div>
@else

{{-- Tabla de alertas --}}
<div class="prt-card" style="overflow:hidden;padding:0;">
    <div style="overflow-x:auto;">
        <table class="alert-table" id="tablaAlertas">
            <thead>
                <tr>
                    <th style="width:30px;"><input type="checkbox" id="check-all" onclick="toggleAll(this)" style="cursor:pointer;accent-color:#fff;"></th>
                    <th class="left" style="min-width:170px;">Estudiante</th>
                    <th>Clases</th>
                    <th>Ausencias</th>
                    <th>Tardanzas</th>
                    <th>% Asistencia</th>
                    @if($ultimasSemanas->count() >= 2)
                    <th>Trend semanal</th>
                    @endif
                    <th>Estado</th>
                    <th style="min-width:110px;">Acción</th>
                </tr>
            </thead>
            <tbody>
            @foreach($filas as $i => $fila)
            @php
                $rowClass = $fila['critico'] ? 'critico' : ($fila['enAlerta'] ? 'alerta' : 'ok');
                $pct = $fila['pctAsist'];
                $pctColor = $pct === null ? '#94a3b8'
                    : ($pct >= 90 ? '#15803d' : ($pct >= 80 ? '#d97706' : '#dc2626'));
                $fillColor = $pct === null ? '#e2e8f0'
                    : ($pct >= 90 ? '#22c55e' : ($pct >= 80 ? '#f59e0b' : '#ef4444'));
                $hasRep = !empty($fila['repUserIds']);
                $sparkMax = collect($fila['sparkData'])->max() ?: 1;
            @endphp
            <tr class="{{ $rowClass }}" id="row-mat-{{ $fila['matricula']->id }}">
                <td>
                    <input type="checkbox" class="row-check"
                           value="{{ $fila['matricula']->id }}"
                           data-rep="{{ $hasRep ? '1' : '0' }}"
                           {{ !$fila['enAlerta'] ? 'disabled' : '' }}>
                </td>
                <td class="left">
                    <span style="color:#94a3b8;font-size:.68rem;font-weight:700;margin-right:.3rem;">
                        {{ $fila['matricula']->numero_orden ?? ($i + 1) }}.
                    </span>
                    <span style="font-weight:700;color:#1e293b;">
                        {{ $fila['matricula']->estudiante?->apellidos }}, {{ $fila['matricula']->estudiante?->nombres }}
                    </span>
                    @if($fila['matricula']->estudiante?->tutor_nombre)
                    <div style="font-size:.63rem;color:#94a3b8;">
                        <i class="bi bi-person-fill"></i> {{ $fila['matricula']->estudiante->tutor_nombre }}
                        @if($fila['matricula']->estudiante->tutor_telefono)
                        · {{ $fila['matricula']->estudiante->tutor_telefono }}
                        @endif
                    </div>
                    @endif
                    @if(!$hasRep)
                    <div style="font-size:.6rem;color:#94a3b8;font-style:italic;">Sin cuenta de representante</div>
                    @endif
                </td>
                <td style="font-weight:700;">{{ $fila['total'] ?: '—' }}</td>
                <td>
                    <span style="font-size:.88rem;font-weight:900;color:{{ $fila['ausentes'] > 0 ? '#dc2626' : '#15803d' }};">
                        {{ $fila['ausentes'] }}
                    </span>
                </td>
                <td style="color:#f59e0b;font-weight:700;">{{ $fila['tardanzas'] ?: '—' }}</td>
                <td>
                    @if($pct !== null)
                    <div style="display:inline-flex;flex-direction:column;align-items:center;gap:2px;">
                        <span style="font-size:.8rem;font-weight:800;color:{{ $pctColor }};">{{ $pct }}%</span>
                        <div class="asist-bar">
                            <div class="asist-fill" style="width:{{ $pct }}%;background:{{ $fillColor }};"></div>
                        </div>
                    </div>
                    @else
                    <span style="color:#94a3b8;font-size:.72rem;">—</span>
                    @endif
                </td>
                @if($ultimasSemanas->count() >= 2)
                <td>
                    @php
                        $sparkData = $fila['sparkData'];
                        $sparkMax  = collect($sparkData)->max() ?: 1;
                    @endphp
                    <div class="spark-wrap" title="{{ implode(', ', $sparkData) }} (últimas semanas)">
                        @foreach($sparkData as $sv)
                        @php
                            $h = $sparkMax > 0 ? max(2, round(($sv / $sparkMax) * 20)) : 2;
                            $c = $sv === 0 ? '#dcfce7' : ($sv >= 2 ? '#fca5a5' : '#fde68a');
                        @endphp
                        <div class="spark-bar" style="height:{{ $h }}px;background:{{ $c }};"></div>
                        @endforeach
                    </div>
                </td>
                @endif
                <td>
                    @if($fila['critico'])
                    <span class="estado-badge eb-critico"><i class="bi bi-exclamation-triangle-fill"></i>Crítico</span>
                    @elseif($fila['enAlerta'])
                    <span class="estado-badge eb-alerta"><i class="bi bi-exclamation-circle-fill"></i>Alerta</span>
                    @else
                    <span class="estado-badge eb-ok"><i class="bi bi-check-circle-fill"></i>OK</span>
                    @endif
                </td>
                <td>
                    <button class="notif-btn"
                            id="nbtn-{{ $fila['matricula']->id }}"
                            onclick="notificarEst({{ $fila['matricula']->id }}, this)"
                            style="background:#dbeafe;color:#1d4ed8;"
                            @if(!$hasRep) disabled title="Sin representante registrado" @endif
                            @if(!$fila['enAlerta'] && !$fila['critico']) style="background:#f1f5f9;color:#94a3b8;" @endif>
                        <i class="bi bi-bell-fill"></i>Avisar
                    </button>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Leyenda --}}
<div style="display:flex;flex-wrap:wrap;gap:.85rem;margin-top:.85rem;font-size:.7rem;color:#64748b;">
    <span style="display:flex;align-items:center;gap:.3rem;">
        <span class="estado-badge eb-critico" style="font-size:.65rem;">Crítico</span>
        ≥{{ $umbral * 2 }} ausencias o &lt;70% asistencia
    </span>
    <span style="display:flex;align-items:center;gap:.3rem;">
        <span class="estado-badge eb-alerta" style="font-size:.65rem;">Alerta</span>
        ≥{{ $umbral }} ausencias
    </span>
    <span style="display:flex;align-items:center;gap:.3rem;">
        <span class="estado-badge eb-ok" style="font-size:.65rem;">OK</span>
        &lt;{{ $umbral }} ausencias
    </span>
    <span>· Barras = ausencias por semana (últimas {{ $ultimasSemanas->count() }})</span>
</div>

@endif

{{-- Toast --}}
<div id="toast-alerta"
     style="display:none;position:fixed;bottom:1.5rem;right:1.5rem;background:#1e293b;color:#fff;
            border-radius:10px;padding:.75rem 1.1rem;font-size:.82rem;font-weight:600;
            z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.25);max-width:320px;">
    <div style="display:flex;align-items:center;gap:.5rem;">
        <i class="bi bi-bell-fill" style="color:#f59e0b;"></i>
        <span id="toast-msg">Notificación enviada.</span>
    </div>
</div>

@endsection

@push('scripts')
<script>
const CSRF_ALERTAS = '{{ csrf_token() }}';
const URL_NOTIF   = '{{ route('portal.docente.asistencia.alertas.notificar', $asignacion) }}';

async function notificar(matIds) {
    const r = await fetch(URL_NOTIF, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF_ALERTAS, 'Accept':'application/json' },
        body: JSON.stringify({ matricula_ids: matIds }),
    });
    const d = await r.json();
    toast(d.mensaje ?? 'Notificación enviada.');
    return d;
}

async function notificarEst(matId, btn) {
    if (!confirm('¿Enviar alerta de inasistencia al representante de este estudiante?')) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Enviando...';
    try {
        await notificar([matId]);
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Enviado';
        btn.style.background = '#dcfce7';
        btn.style.color = '#15803d';
    } catch {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-bell-fill"></i>Avisar';
    }
}

async function notificarTodos() {
    const checks = [...document.querySelectorAll('.row-check:not(:disabled):checked')];
    const ids = checks.length > 0
        ? checks.map(c => parseInt(c.value))
        : [...document.querySelectorAll('.row-check:not(:disabled)')].map(c => parseInt(c.value));

    if (ids.length === 0) { toast('No hay estudiantes en alerta con representante registrado.'); return; }
    if (!confirm(`¿Enviar alerta a los representantes de ${ids.length} estudiante(s) en alerta?`)) return;

    const btn = document.getElementById('btn-notif-all');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Enviando...';
    try {
        const d = await notificar(ids);
        btn.innerHTML = `<i class="bi bi-check-lg"></i> ${d.mensaje}`;
        btn.style.background = '#dcfce7';
        btn.style.color = '#15803d';
    } catch {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-bell-fill"></i>Notificar todos en alerta ({{ $nEnAlerta }})';
    }
}

function toggleAll(master) {
    document.querySelectorAll('.row-check:not(:disabled)').forEach(c => c.checked = master.checked);
}

function toast(msg) {
    const el = document.getElementById('toast-alerta');
    document.getElementById('toast-msg').textContent = msg;
    el.style.display = 'block';
    setTimeout(() => el.style.display = 'none', 3500);
}
</script>
@endpush
