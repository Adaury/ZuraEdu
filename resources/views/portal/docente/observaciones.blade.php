@extends('layouts.portal')
@section('page-title', 'Observaciones — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'observaciones'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.asistencia', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-calendar-check"></i>Asistencia
    </a>
    <a href="{{ route('portal.docente.observaciones', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-chat-square-text"></i>Obs.
    </a>
    <a href="{{ route('portal.docente.boletines', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-file-earmark-text"></i>Boletines
    </a>
@endsection

@push('styles')
<style>
/* ── Botón guardar obs: ancho completo en móvil ── */
@media (max-width: 480px) {
    #btnGuardarObs { width: 100%; justify-content: center; }
}

/* ── Observaciones dark mode ── */
[data-theme="dark"] .obs-tipo-btn-default {
    border-color: #334155 !important;
    background: #1e293b !important;
    color: #64748b !important;
}
[data-theme="dark"] .obs-grupo-header {
    background: #162032 !important;
    border-bottom-color: #334155 !important;
    color: #94a3b8 !important;
}
[data-theme="dark"] .obs-item-row {
    border-bottom-color: #334155 !important;
}
[data-theme="dark"] .obs-item-text { color: #cbd5e1 !important; }
[data-theme="dark"] .obs-count-badge {
    background: #1e293b !important;
    color: #94a3b8 !important;
}
[data-theme="dark"] select:not(.form-select) {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-theme="dark"] .form-label-obs { color: #94a3b8 !important; }
[data-theme="dark"] #obsAlert[style*="background:#dcfce7"] {
    background: #052e16 !important;
    color: #4ade80 !important;
}
[data-theme="dark"] #obsAlert[style*="background:#fee2e2"] {
    background: #1c0000 !important;
    color: #f87171 !important;
}
</style>
@endpush

@section('content')
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.dashboard') }}" class="btn-back"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div>
        <h1 style="font-size:1rem;font-weight:800;margin:0;">Observaciones — {{ $asignacion->asignatura?->nombre }}</h1>
        <div class="dm-text-muted" style="font-size:.75rem;color:#64748b;">{{ $asignacion->grupo?->nombre_completo ?? '—' }}</div>
    </div>
</div>

{{-- Formulario nueva observación --}}
<div class="prt-card" style="margin-bottom:1rem;">
    <div class="prt-card-header">
        <i class="bi bi-plus-circle" style="color:#92400e;font-size:1rem;"></i>
        <h3>Nueva Observación</h3>
    </div>
    <form id="formObs" style="padding:1rem;display:flex;flex-direction:column;gap:.85rem;">
        @csrf
        <div>
            <label class="form-label-obs" style="font-size:.78rem;font-weight:600;color:#374151;display:block;margin-bottom:.35rem;">Estudiante *</label>
            <select name="estudiante_id" id="selectEstudiante" required
                    style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:.45rem .75rem;font-size:.83rem;color:#1e293b;background:#fff;">
                <option value="">— Seleccionar estudiante —</option>
                @foreach($matriculas as $m)
                <option value="{{ $m->estudiante_id }}"
                    {{ request('estudiante') == $m->estudiante_id ? 'selected' : '' }}>
                    {{ $m->estudiante?->nombre_completo ?? '—' }}
                </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label-obs" style="font-size:.78rem;font-weight:600;color:#374151;display:block;margin-bottom:.35rem;">Tipo *</label>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                @foreach(['academica' => ['#1d4ed8','#eff6ff','bi-book','Académica'], 'conductual' => ['#b45309','#fef9c3','bi-exclamation-triangle','Conductual'], 'positiva' => ['#15803d','#dcfce7','bi-hand-thumbs-up','Positiva'], 'general' => ['#6b7280','#f1f5f9','bi-chat-dots','General']] as $val => $info)
                <label style="cursor:pointer;flex:1;min-width:90px;">
                    <input type="radio" name="tipo" value="{{ $val }}" style="display:none;" class="radio-tipo"
                           {{ $loop->first ? 'checked' : '' }}>
                    <span class="tipo-btn {{ !$loop->first ? 'obs-tipo-btn-default' : '' }}" data-val="{{ $val }}"
                          style="display:flex;align-items:center;justify-content:center;gap:.35rem;padding:.45rem .5rem;border-radius:8px;font-size:.73rem;font-weight:700;border:1.5px solid {{ $loop->first ? $info[0] : '#e2e8f0' }};background:{{ $loop->first ? $info[1] : '#fff' }};color:{{ $loop->first ? $info[0] : '#64748b' }};transition:all .15s;">
                        <i class="bi {{ $info[2] }}"></i>{{ $info[3] }}
                    </span>
                </label>
                @endforeach
            </div>
        </div>

        <div>
            <label class="form-label-obs" style="font-size:.78rem;font-weight:600;color:#374151;display:block;margin-bottom:.35rem;">Observación *</label>
            <textarea name="texto" id="textoObs" rows="4" required minlength="10" maxlength="1000"
                      placeholder="Describa la observación (mínimo 10 caracteres)..."
                      style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:.5rem .75rem;font-size:.83rem;color:#1e293b;resize:vertical;box-sizing:border-box;"></textarea>
            <div style="font-size:.68rem;color:#9ca3af;text-align:right;margin-top:.2rem;">
                <span id="charCount">0</span>/1000
            </div>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;">
            <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.78rem;color:#374151;">
                <input type="checkbox" name="privada" id="checkPrivada" style="width:15px;height:15px;accent-color:#6366f1;">
                <span>Observación privada <span style="color:#9ca3af;">(solo visible para administración)</span></span>
            </label>
            <button type="submit" id="btnGuardarObs"
                    style="background:linear-gradient(135deg,#92400e,#b45309);color:#fff;border:none;border-radius:9px;padding:.55rem 1.5rem;font-size:.83rem;font-weight:700;cursor:pointer;">
                <i class="bi bi-send me-1"></i>Guardar observación
            </button>
        </div>

        <div id="obsAlert" style="display:none;padding:.6rem .85rem;border-radius:8px;font-size:.78rem;font-weight:600;"></div>
    </form>
</div>

{{-- Observaciones registradas --}}
<div class="prt-card">
    <div class="prt-card-header">
        <i class="bi bi-list-ul" style="color:#6366f1;font-size:1rem;"></i>
        <h3>Historial de Observaciones</h3>
        <span class="obs-count-badge" style="margin-left:auto;font-size:.75rem;color:#64748b;background:#f1f5f9;border-radius:6px;padding:.2rem .6rem;">
            {{ $observaciones->flatten()->count() }} total
        </span>
        <a href="{{ route('portal.docente.observaciones.pdf', $asignacion) }}" target="_blank"
           style="margin-left:.75rem;background:#dc2626;color:#fff;border-radius:8px;padding:.3rem .75rem;font-size:.75rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.35rem;">
            <i class="bi bi-file-earmark-pdf"></i>PDF
        </a>
        <a href="{{ route('portal.docente.observaciones.excel', $asignacion) }}"
           style="margin-left:.5rem;background:#16a34a;color:#fff;border-radius:8px;padding:.3rem .75rem;font-size:.75rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.35rem;">
            <i class="bi bi-file-earmark-excel"></i>Excel
        </a>
    </div>

    @php
        $tiposInfo = [
            'academica'  => ['color' => '#1d4ed8', 'bg' => '#eff6ff',  'icon' => 'bi-book',              'label' => 'Académica'],
            'conductual' => ['color' => '#b45309', 'bg' => '#fef9c3',  'icon' => 'bi-exclamation-triangle','label' => 'Conductual'],
            'positiva'   => ['color' => '#15803d', 'bg' => '#dcfce7',  'icon' => 'bi-hand-thumbs-up',    'label' => 'Positiva'],
            'general'    => ['color' => '#6b7280', 'bg' => '#f1f5f9',  'icon' => 'bi-chat-dots',         'label' => 'General'],
        ];
    @endphp

    @if($observaciones->flatten()->isEmpty())
    <div style="padding:2.5rem;text-align:center;color:#9ca3af;">
        <i class="bi bi-chat-square" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
        No hay observaciones registradas aún.
    </div>
    @else
    {{-- Filtro por estudiante --}}
    <div class="dm-toolbar" style="padding:.6rem 1rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
        <select id="filtroEstudiante" onchange="filtrarObs()"
                style="border:1px solid #e2e8f0;border-radius:7px;padding:.3rem .65rem;font-size:.78rem;color:#374151;background:#fff;">
            <option value="">Todos los estudiantes</option>
            @foreach($matriculas as $m)
            <option value="{{ $m->estudiante_id }}"
                {{ request('estudiante') == $m->estudiante_id ? 'selected' : '' }}>
                {{ $m->estudiante?->nombre_completo ?? '—' }}
            </option>
            @endforeach
        </select>
    </div>

    <div id="obsListado" style="padding:0;">
        @foreach($observaciones as $estudianteId => $obsGrupo)
        @php $nombreEst = $obsGrupo->first()->estudiante?->nombre_completo ?? '—'; @endphp
        <div class="obs-grupo" data-estudiante="{{ $estudianteId }}"
             style="{{ request('estudiante') && request('estudiante') != $estudianteId ? 'display:none;' : '' }}">
            <div class="obs-grupo-header" style="padding:.5rem 1rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:.75rem;font-weight:700;color:#374151;">
                <i class="bi bi-person me-1"></i>{{ $nombreEst }}
                <span class="dm-text-muted" style="color:#9ca3af;font-weight:400;">({{ $obsGrupo->count() }} obs.)</span>
            </div>
            @foreach($obsGrupo as $obs)
            @php $ti = $tiposInfo[$obs->tipo] ?? $tiposInfo['general']; @endphp
            <div class="obs-item-row" style="padding:.85rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;gap:.85rem;align-items:flex-start;">
                <div style="width:34px;height:34px;border-radius:8px;background:{{ $ti['bg'] }};color:{{ $ti['color'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.85rem;">
                    <i class="bi {{ $ti['icon'] }}"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.25rem;flex-wrap:wrap;">
                        <span style="font-size:.71rem;font-weight:700;color:{{ $ti['color'] }};background:{{ $ti['bg'] }};border-radius:5px;padding:.1rem .4rem;">
                            {{ $ti['label'] }}
                        </span>
                        @if($obs->privada)
                        <span style="font-size:.68rem;color:#6366f1;background:#ede9fe;border-radius:5px;padding:.1rem .4rem;">
                            <i class="bi bi-lock me-1"></i>Privada
                        </span>
                        @endif
                        <span class="dm-text-muted" style="font-size:.68rem;color:#9ca3af;margin-left:auto;">
                            {{ $obs->created_at->format('d/m/Y H:i') }}
                        </span>
                    </div>
                    <div class="obs-item-text" style="font-size:.8rem;color:#374151;line-height:1.5;">{{ $obs->texto }}</div>
                </div>
            </div>
            @endforeach
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// Tipos de color para botones
const tiposConfig = {
    academica:  { color: '#1d4ed8', bg: '#eff6ff' },
    conductual: { color: '#b45309', bg: '#fef9c3' },
    positiva:   { color: '#15803d', bg: '#dcfce7' },
    general:    { color: '#6b7280', bg: '#f1f5f9' },
};

// Selector de tipo visual
document.querySelectorAll('.radio-tipo').forEach(radio => {
    radio.addEventListener('change', function () {
        document.querySelectorAll('.tipo-btn').forEach(btn => {
            btn.style.border        = '1.5px solid #e2e8f0';
            btn.style.background    = '#fff';
            btn.style.color         = '#64748b';
        });
        const cfg = tiposConfig[this.value] || tiposConfig.general;
        const span = this.nextElementSibling;
        span.style.border      = `1.5px solid ${cfg.color}`;
        span.style.background  = cfg.bg;
        span.style.color       = cfg.color;
    });
});

// Contador de caracteres
document.getElementById('textoObs').addEventListener('input', function () {
    document.getElementById('charCount').textContent = this.value.length;
});

// Enviar observación por AJAX
document.getElementById('formObs').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn   = document.getElementById('btnGuardarObs');
    const alert = document.getElementById('obsAlert');
    btn.disabled = true;
    btn.textContent = 'Guardando…';

    const fd = new FormData(this);

    try {
        const r = await fetch('{{ route("portal.docente.observaciones.guardar", $asignacion) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: fd,
        });

        const data = await r.json();

        if (data.ok) {
            alert.style.display     = 'block';
            alert.style.background  = '#dcfce7';
            alert.style.color       = '#15803d';
            alert.textContent       = '✔ Observación guardada correctamente. Recargando…';
            setTimeout(() => window.location.reload(), 1200);
        } else {
            throw new Error(data.message || 'Error al guardar');
        }
    } catch (err) {
        alert.style.display     = 'block';
        alert.style.background  = '#fee2e2';
        alert.style.color       = '#991b1b';
        alert.textContent       = '✖ ' + (err.message || 'Error al guardar la observación.');
        btn.disabled  = false;
        btn.innerHTML = '<i class="bi bi-send me-1"></i>Guardar observación';
    }
});

// Filtrar por estudiante
function filtrarObs() {
    const val = document.getElementById('filtroEstudiante').value;
    document.querySelectorAll('.obs-grupo').forEach(g => {
        g.style.display = (!val || g.dataset.estudiante == val) ? '' : 'none';
    });
}

// Pre-seleccionar estudiante desde URL param
const urlEst = new URLSearchParams(window.location.search).get('estudiante');
if (urlEst) {
    const sel = document.getElementById('filtroEstudiante');
    if (sel) { sel.value = urlEst; filtrarObs(); }
    const selEst = document.getElementById('selectEstudiante');
    if (selEst) selEst.value = urlEst;
}
</script>
@endpush
