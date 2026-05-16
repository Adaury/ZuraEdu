@extends('layouts.portal')
@section('page-title', 'Asistencia Rápida')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'asistencia-rapida'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.asistencia-rapida') }}" class="prt-nav-item active">
        <i class="bi bi-lightning-charge-fill"></i>Asistencia
    </a>
    <a href="{{ route('portal.docente.horario') }}" class="prt-nav-item">
        <i class="bi bi-calendar3"></i>Horario
    </a>
    <a href="{{ route('portal.docente.mis-estadisticas') }}" class="prt-nav-item">
        <i class="bi bi-bar-chart-fill"></i>Stats
    </a>
@endsection

@push('styles')
<style>
.ar-estados {
    display: flex;
    gap: .35rem;
    flex-shrink: 0;
}
.ar-btn {
    width: 52px;
    height: 42px;
    border: 2px solid #e2e8f0;
    border-radius: 9px;
    background: #f8fafc;
    color: #94a3b8;
    font-size: .65rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1px;
    transition: all .12s;
    line-height: 1.1;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
}
.ar-btn:hover { border-color: #94a3b8; color: #374151; }
.ar-btn.ar-p  { border-color: #15803d; background: #dcfce7; color: #15803d; }
.ar-btn.ar-t  { border-color: #b45309; background: #fef9c3; color: #b45309; }
.ar-btn.ar-e  { border-color: #1d4ed8; background: #dbeafe; color: #1d4ed8; }
.ar-btn.ar-a  { border-color: #991b1b; background: #fee2e2; color: #991b1b; }
.ar-btn .ar-ico { font-size: .9rem; line-height: 1; }
.ar-btn .ar-lbl { font-size: .6rem; }
.ar-row {
    display: flex;
    align-items: center;
    gap: .65rem;
    padding: .6rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    transition: background .1s;
}
.ar-row.ar-guardado { background: #f0fdf4; }
.ar-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: .82rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.ar-nombre {
    flex: 1;
    min-width: 0;
    font-size: .84rem;
    font-weight: 600;
    color: #1e293b;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.ar-saving {
    width: 18px;
    height: 18px;
    border: 2px solid #e2e8f0;
    border-top-color: #2563eb;
    border-radius: 50%;
    animation: spin .5s linear infinite;
    flex-shrink: 0;
    display: none;
}
@keyframes spin { to { transform: rotate(360deg); } }
.ar-ok { color: #15803d; font-size: .85rem; flex-shrink: 0; display: none; }
.ar-clase-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(30,58,110,.05);
}
.ar-clase-card.completo { border-color: #bbf7d0; }
.ar-clase-header {
    padding: .8rem 1rem;
    background: linear-gradient(135deg, #1d4ed8, #2563eb);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    cursor: pointer;
    user-select: none;
}
.ar-clase-card.completo .ar-clase-header {
    background: linear-gradient(135deg, #15803d, #16a34a);
}
.ar-prog-pill {
    background: rgba(255,255,255,.22);
    border-radius: 99px;
    padding: .18rem .6rem;
    font-size: .7rem;
    font-weight: 700;
    white-space: nowrap;
    flex-shrink: 0;
}
.ar-prog-bar-wrap {
    background: #f1f5f9;
    height: 4px;
}
.ar-prog-bar {
    height: 4px;
    background: #2563eb;
    transition: width .3s;
}
.ar-clase-card.completo .ar-prog-bar { background: #16a34a; }
.ar-todos-btns {
    display: flex;
    gap: .4rem;
    padding: .5rem 1rem;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}
.ar-todos-btn {
    font-size: .68rem;
    font-weight: 700;
    border: none;
    border-radius: 6px;
    padding: .25rem .6rem;
    cursor: pointer;
}
.ar-date-selector {
    display: flex;
    align-items: center;
    gap: .5rem;
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: .4rem .85rem;
    font-size: .82rem;
    font-weight: 600;
    color: #374151;
    text-decoration: none;
}
</style>
@endpush

@section('content')

{{-- Cabecera ─────────────────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.1rem;flex-wrap:wrap;">
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-lightning-charge-fill" style="color:#f59e0b;"></i>
            Asistencia Rápida
        </h1>
        <div style="font-size:.75rem;color:#64748b;margin-top:.1rem;">
            Toca el estado de cada estudiante — se guarda automáticamente
        </div>
    </div>
    {{-- Selector de fecha --}}
    <div style="display:flex;align-items:center;gap:.4rem;">
        <input type="date" id="fechaInput" value="{{ $fecha }}"
               onchange="cambiarFecha(this.value)"
               style="border:1.5px solid #e2e8f0;border-radius:9px;padding:.38rem .65rem;font-size:.82rem;color:#374151;font-weight:600;">
    </div>
</div>

{{-- KPI global ───────────────────────────────────────────────────────────── --}}
@php
    $totalClases   = $data->count();
    $clasesListas  = $data->where('completo', true)->count();
    $totalEstTotal = $data->sum('total');
    $totalMarcados = $data->sum('marcados');
@endphp
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.6rem;margin-bottom:1.1rem;">
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:.7rem;text-align:center;">
        <div style="font-size:1.4rem;font-weight:800;color:#2563eb;">{{ $totalClases }}</div>
        <div style="font-size:.67rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Clases</div>
    </div>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:.7rem;text-align:center;">
        <div style="font-size:1.4rem;font-weight:800;color:#16a34a;">{{ $clasesListas }}</div>
        <div style="font-size:.67rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Completas</div>
    </div>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:.7rem;text-align:center;">
        <div style="font-size:1.4rem;font-weight:800;color:#374151;">
            {{ $totalEstTotal > 0 ? round($totalMarcados / $totalEstTotal * 100) : 0 }}%
        </div>
        <div style="font-size:.67rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Progreso</div>
    </div>
</div>

@if($data->isEmpty())
<div style="text-align:center;padding:3rem 1rem;color:#94a3b8;">
    <i class="bi bi-calendar-x" style="font-size:2.5rem;display:block;margin-bottom:.75rem;color:#d1d5db;"></i>
    <h6 style="font-weight:600;color:#6b7280;margin-bottom:.4rem;">Sin asignaciones activas</h6>
    <p style="font-size:.83rem;">No tienes clases registradas para este año escolar.</p>
</div>
@else

{{-- Leyenda de estados ────────────────────────────────────────────────────── --}}
<div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;font-size:.7rem;font-weight:700;">
    <span style="background:#dcfce7;color:#15803d;border-radius:6px;padding:.2rem .55rem;">P = Presente</span>
    <span style="background:#fef9c3;color:#b45309;border-radius:6px;padding:.2rem .55rem;">T = Tarde</span>
    <span style="background:#dbeafe;color:#1d4ed8;border-radius:6px;padding:.2rem .55rem;">E = Excusa</span>
    <span style="background:#fee2e2;color:#991b1b;border-radius:6px;padding:.2rem .55rem;">A = Ausente</span>
</div>

@foreach($data as $item)
@php
    $asig      = $item['asignacion'];
    $pct       = $item['total'] > 0 ? round($item['marcados'] / $item['total'] * 100) : 0;
    $completo  = $item['completo'];
@endphp
<div class="ar-clase-card {{ $completo ? 'completo' : '' }}"
     id="card-{{ $asig->id }}">

    {{-- Header colapsable --}}
    <div class="ar-clase-header" onclick="toggleClase({{ $asig->id }})">
        <div style="flex:1;min-width:0;">
            <div style="font-size:.9rem;font-weight:800;line-height:1.2;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                @if($completo)<i class="bi bi-check-circle-fill me-1" style="font-size:.85rem;"></i>@endif
                {{ $asig->asignatura?->nombre ?? 'Asignatura' }}
            </div>
            <div style="font-size:.72rem;opacity:.85;margin-top:.1rem;">
                {{ $asig->grupo?->nombre_completo ?? '—' }}
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:.5rem;flex-shrink:0;">
            <span class="ar-prog-pill" id="pill-{{ $asig->id }}">
                {{ $item['marcados'] }}/{{ $item['total'] }}
            </span>
            <i class="bi bi-chevron-{{ $completo ? 'down' : 'up' }}" id="chev-{{ $asig->id }}" style="font-size:.85rem;opacity:.8;"></i>
        </div>
    </div>

    {{-- Barra de progreso --}}
    <div class="ar-prog-bar-wrap">
        <div class="ar-prog-bar" id="bar-{{ $asig->id }}" style="width:{{ $pct }}%;"></div>
    </div>

    {{-- Lista de estudiantes --}}
    <div id="lista-{{ $asig->id }}" style="{{ $completo ? 'display:none;' : '' }}">
        {{-- Botones "Marcar todos" --}}
        <div class="ar-todos-btns">
            <span style="font-size:.7rem;color:#64748b;font-weight:600;align-self:center;">Marcar todos:</span>
            <button type="button" class="ar-todos-btn" style="background:#dcfce7;color:#15803d;"
                    onclick="marcarTodosAjax({{ $asig->id }}, 'presente', '{{ $fecha }}', {{ $item['matriculas']->pluck('id')->toJson() }})">
                ✔ Presentes
            </button>
            <button type="button" class="ar-todos-btn" style="background:#fee2e2;color:#991b1b;"
                    onclick="marcarTodosAjax({{ $asig->id }}, 'ausente', '{{ $fecha }}', {{ $item['matriculas']->pluck('id')->toJson() }})">
                ✖ Ausentes
            </button>
        </div>

        @foreach($item['matriculas'] as $m)
        @php $est = $m->estudiante; $estadoActual = $item['registradas'][$m->id]?->estado ?? null; @endphp
        <div class="ar-row {{ $estadoActual ? 'ar-guardado' : '' }}" id="row-{{ $asig->id }}-{{ $m->id }}">
            <div class="ar-avatar">{{ strtoupper(substr($est?->nombres ?? 'E', 0, 1)) }}</div>
            <div class="ar-nombre">{{ $est?->nombre_completo ?? '—' }}</div>
            <div class="ar-estados">
                @foreach(['presente' => ['P','ar-p','↑'], 'tarde' => ['T','ar-t','⏰'], 'excusa' => ['E','ar-e','📋'], 'ausente' => ['A','ar-a','✖']] as $val => [$lbl, $cls, $ico])
                <button type="button"
                        class="ar-btn {{ $estadoActual === $val ? $cls : '' }}"
                        id="btn-{{ $asig->id }}-{{ $m->id }}-{{ $val }}"
                        onclick="guardar({{ $asig->id }}, {{ $m->id }}, '{{ $val }}', '{{ $fecha }}', this)">
                    <span class="ar-ico">{{ $ico }}</span>
                    <span class="ar-lbl">{{ $lbl }}</span>
                </button>
                @endforeach
            </div>
            <div class="ar-saving" id="spin-{{ $asig->id }}-{{ $m->id }}"></div>
            <i class="bi bi-check-circle-fill ar-ok" id="ok-{{ $asig->id }}-{{ $m->id }}"
               style="{{ $estadoActual ? 'display:inline;' : '' }}"></i>
        </div>
        @endforeach
    </div>

    {{-- Enlace a la vista completa --}}
    <div style="padding:.5rem 1rem;background:#f8fafc;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:.68rem;color:#94a3b8;" id="status-{{ $asig->id }}">
            {{ $item['marcados'] }} de {{ $item['total'] }} marcados
        </span>
        <a href="{{ route('portal.docente.asistencia', $asig) }}?fecha={{ $fecha }}"
           style="font-size:.72rem;color:#2563eb;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:.3rem;">
            Ver historial <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</div>
@endforeach

@endif

@endsection

@push('scripts')
<script>
const GUARDAR_URL = '{{ route('portal.docente.asistencia-rapida.guardar') }}';
const CSRF        = '{{ csrf_token() }}';

function toggleClase(asigId) {
    const lista = document.getElementById('lista-' + asigId);
    const chev  = document.getElementById('chev-' + asigId);
    if (!lista) return;
    const open = lista.style.display !== 'none';
    lista.style.display = open ? 'none' : '';
    chev.className = 'bi bi-chevron-' + (open ? 'down' : 'up');
}

async function guardar(asigId, matriculaId, estado, fecha, btn) {
    const spin = document.getElementById(`spin-${asigId}-${matriculaId}`);
    const ok   = document.getElementById(`ok-${asigId}-${matriculaId}`);
    const row  = document.getElementById(`row-${asigId}-${matriculaId}`);

    // Visual: mostrar spinner
    spin.style.display = 'block';
    ok.style.display   = 'none';

    // Quitar estado activo de los 4 botones de esta fila
    ['presente','tarde','excusa','ausente'].forEach(v => {
        const b = document.getElementById(`btn-${asigId}-${matriculaId}-${v}`);
        if (b) b.className = 'ar-btn';
    });

    try {
        const resp = await fetch(GUARDAR_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ asignacion_id: asigId, matricula_id: matriculaId, estado, fecha }),
        });
        const data = await resp.json();

        if (data.ok) {
            // Marcar botón activo
            const clsMap = { presente: 'ar-p', tarde: 'ar-t', excusa: 'ar-e', ausente: 'ar-a' };
            btn.className = 'ar-btn ' + (clsMap[estado] || '');
            row.classList.add('ar-guardado');

            // Actualizar pill y barra
            actualizarProgreso(asigId, data.marcados, data.total, data.completo);
        }
    } catch (e) {
        console.error(e);
    } finally {
        spin.style.display = 'none';
        ok.style.display   = 'inline';
    }
}

async function marcarTodosAjax(asigId, estado, fecha, matriculas) {
    for (const mId of matriculas) {
        const btn = document.getElementById(`btn-${asigId}-${mId}-${estado}`);
        if (btn) await guardar(asigId, mId, estado, fecha, btn);
    }
}

function actualizarProgreso(asigId, marcados, total, completo) {
    const pill   = document.getElementById('pill-' + asigId);
    const bar    = document.getElementById('bar-' + asigId);
    const card   = document.getElementById('card-' + asigId);
    const status = document.getElementById('status-' + asigId);

    if (pill)   pill.textContent = marcados + '/' + total;
    if (bar)    bar.style.width  = (total > 0 ? Math.round(marcados / total * 100) : 0) + '%';
    if (status) status.textContent = marcados + ' de ' + total + ' marcados';

    if (completo && card) {
        card.classList.add('completo');
        const hdr = card.querySelector('.ar-clase-header');
        if (hdr) hdr.style.background = 'linear-gradient(135deg,#15803d,#16a34a)';
    }
}

function cambiarFecha(val) {
    window.location.href = '?fecha=' + val;
}
</script>
@endpush
