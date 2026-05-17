@extends('layouts.portal')
@section('page-title', 'Entregas — ' . $tarea->titulo)
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'tareas'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.tareas.index', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-check2-square"></i>Tareas
    </a>
@endsection

@push('styles')
<style>
.entrega-row {
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: .7rem;
    overflow: hidden;
    transition: box-shadow .15s;
}
.entrega-row:hover { box-shadow: 0 2px 12px rgba(59,130,246,.1); }
.entrega-row.con-feedback { border-color: #6ee7b7; }

.entrega-head {
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .7rem 1rem;
    flex-wrap: wrap;
}

.estado-select {
    border: 1.5px solid #e2e8f0;
    border-radius: 7px;
    font-size: .78rem;
    font-weight: 600;
    padding: .25rem .5rem;
    cursor: pointer;
    background: #f8fafc;
}
.nota-inp {
    width: 68px;
    text-align: center;
    border: 1.5px solid #e2e8f0;
    border-radius: 7px;
    padding: .25rem .3rem;
    font-size: .84rem;
    font-weight: 700;
    -moz-appearance: textfield;
}
.nota-inp::-webkit-inner-spin-button,
.nota-inp::-webkit-outer-spin-button { -webkit-appearance: none; }
.nota-inp:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }

/* Sección de feedback */
.feedback-section {
    border-top: 1px solid #f1f5f9;
    padding: .6rem 1rem .75rem;
    background: #f8faff;
}
.entrega-row.con-feedback .feedback-section {
    background: #f0fdf4;
    border-top-color: #bbf7d0;
}
.feedback-toggle {
    display: flex;
    align-items: center;
    gap: .4rem;
    font-size: .73rem;
    font-weight: 700;
    cursor: pointer;
    color: #6b7280;
    user-select: none;
    margin-bottom: 0;
}
.entrega-row.con-feedback .feedback-toggle { color: #059669; }

.feedback-body { margin-top: .5rem; }

.feedback-textarea {
    width: 100%;
    border: 1.5px solid #d1fae5;
    border-radius: 8px;
    font-size: .78rem;
    padding: .45rem .6rem;
    resize: vertical;
    min-height: 60px;
    background: #fff;
    color: #1e293b;
    font-family: inherit;
    transition: border-color .15s, box-shadow .15s;
}
.feedback-textarea:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16,185,129,.12);
}
.entrega-row:not(.con-feedback) .feedback-textarea {
    border-color: #e2e8f0;
}
.entrega-row:not(.con-feedback) .feedback-textarea:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,.12);
}

/* Frases rápidas */
.frase-chip {
    display: inline-block;
    background: #ede9fe;
    color: #6d28d9;
    border-radius: 99px;
    font-size: .67rem;
    font-weight: 600;
    padding: .18rem .55rem;
    cursor: pointer;
    transition: background .12s;
    white-space: nowrap;
}
.frase-chip:hover { background: #ddd6fe; }

.save-btn { font-size: .72rem; padding: .22rem .7rem; border-radius: 7px; }
.feedback-guardado { font-size: .7rem; font-weight: 700; color: #10b981; display: none; }

/* Progress bar de feedback */
.fb-progress {
    height: 5px;
    border-radius: 99px;
    background: #dcfce7;
    overflow: hidden;
    margin-top: .35rem;
}
.fb-progress-fill {
    height: 100%;
    background: #10b981;
    border-radius: 99px;
    transition: width .4s;
}
</style>
@endpush

@push('scripts')
<script>
async function ejecutarPasarNota() {
    const btn = document.getElementById('btn-pasar-confirmar');
    const msg = document.getElementById('pasar-msg');
    if (!confirm('¿Confirmar que se guardarán estas notas en el libro de calificaciones? Esta acción sobreescribirá el campo seleccionado.')) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Guardando...';
    msg.style.display = 'none';

    const esTecnica = {{ $esTecnica ? 'true' : 'false' }};
    const body = {};
    if (esTecnica) {
        body.periodo_id = document.getElementById('pasar-periodo-id')?.value;
        body.campo      = document.getElementById('pasar-campo')?.value;
    } else {
        body.componente  = document.getElementById('pasar-componente')?.value;
        body.periodo_num = document.getElementById('pasar-periodo-num')?.value;
    }

    try {
        const r = await fetch(
            '{{ route('portal.docente.tareas.entregas.pasar-notas', [$asignacion, $tarea]) }}',
            { method:'POST', headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept':'application/json' }, body: JSON.stringify(body) }
        );
        const d = await r.json();
        msg.style.display = 'block';
        if (d.ok) {
            msg.style.color = '#059669'; msg.style.background = '#d1fae5'; msg.style.padding = '.4rem .7rem'; msg.style.borderRadius = '8px';
            msg.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>' + d.mensaje;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Listo';
            btn.style.background = '#059669';
        } else {
            msg.style.color = '#b91c1c'; msg.style.background = '#fee2e2'; msg.style.padding = '.4rem .7rem'; msg.style.borderRadius = '8px';
            msg.textContent = d.mensaje ?? 'Error al guardar.';
            btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i> Confirmar';
        }
    } catch(e) {
        msg.style.display = 'block'; msg.style.color = '#b91c1c';
        msg.textContent = 'Error de conexión.';
        btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i> Confirmar';
    }
}

async function enviarRecordatorioGlobal() {
    const btn = document.getElementById('btn-recordatorio-global');
    if (!btn) return;
    if (!confirm('¿Enviar recordatorio a todos los estudiantes que aún no han entregado?')) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Enviando...';
    try {
        const r = await fetch(
            `/portal/docente/asignacion/{{ $asignacion->id }}/tareas/{{ $tarea->id }}/recordatorio`,
            { method:'POST', headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept':'application/json' }, body:'{}' }
        );
        const d = await r.json();
        btn.innerHTML = '<i class="bi bi-check-lg"></i> ' + (d.mensaje ?? 'Enviado');
        btn.style.background = '#d1fae5'; btn.style.color = '#065f46'; btn.style.borderColor = '#6ee7b7';
    } catch(e) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-bell-fill"></i> Recordatorio';
    }
}

function calificarEntrega(estudianteId, tareaId, asignacionId) {
    return {
        estado:       '',
        calificacion: '',
        notas:        '',
        fbAbierto:    false,
        guardando:    false,
        guardado:     false,

        init(estado, calificacion, notas) {
            this.estado       = estado       || 'pendiente';
            this.calificacion = calificacion || '';
            this.notas        = notas        || '';
            this.fbAbierto    = !!notas;
        },

        abrirFeedback() {
            this.fbAbierto = true;
            this.$nextTick(() => {
                const ta = this.$el.querySelector('.feedback-textarea');
                if (ta) ta.focus();
            });
        },

        insertarFrase(frase) {
            this.fbAbierto = true;
            if (this.notas && !this.notas.endsWith(' ')) this.notas += ' ';
            this.notas += frase;
            this.$nextTick(() => {
                const ta = this.$el.querySelector('.feedback-textarea');
                if (ta) { ta.focus(); ta.selectionStart = ta.selectionEnd = this.notas.length; }
            });
        },

        async guardar() {
            this.guardando = true;
            this.guardado  = false;
            try {
                const res = await fetch(
                    `/portal/docente/asignacion/${asignacionId}/tareas/${tareaId}/calificar`,
                    {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept':       'application/json',
                        },
                        body: JSON.stringify({
                            estudiante_id: estudianteId,
                            estado:        this.estado,
                            calificacion:  this.calificacion || null,
                            notas_docente: this.notas || null,
                        }),
                    }
                );
                const data = await res.json();
                if (data.ok) {
                    this.guardado = true;
                    // Actualizar borde si hay feedback
                    if (this.notas) {
                        this.$el.classList.add('con-feedback');
                    } else {
                        this.$el.classList.remove('con-feedback');
                    }
                    setTimeout(() => this.guardado = false, 2500);
                }
            } catch(e) { console.error(e); }
            finally { this.guardando = false; }
        }
    };
}
</script>
@endpush

@section('content')

{{-- Encabezado --}}
<div class="d-flex align-items-start gap-2 mb-3 flex-wrap">
    <a href="{{ route('portal.docente.tareas.seguimiento', $asignacion) }}"
       class="btn btn-outline-secondary btn-sm mt-1" style="padding:.25rem .6rem;">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div style="flex:1;min-width:0;">
        <h2 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-people-fill me-2" style="color:#3b82f6;"></i>Entregas y Retroalimentación
        </h2>
        <p style="font-size:.78rem;color:var(--prt-muted);margin:.1rem 0 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            <strong>{{ $tarea->titulo }}</strong>
            &mdash; Límite: {{ $tarea->fecha_limite->format('d/m/Y') }}
            @if($tarea->fecha_limite->isPast())
            <span style="color:#ef4444;font-weight:700;font-size:.7rem;margin-left:.35rem;">
                <i class="bi bi-clock-history"></i> Vencida
            </span>
            @endif
        </p>
    </div>
    @php $nPendEntregas = $matriculas->count() - $entregas->whereIn('estado',['entregada','revisada'])->count(); @endphp
    {{-- Exportar PDF --}}
    <a href="{{ route('portal.docente.tareas.entregas.pdf', [$asignacion, $tarea]) }}"
       target="_blank"
       style="background:#fee2e2;color:#b91c1c;border:1.5px solid #fca5a5;border-radius:8px;padding:.38rem .85rem;font-size:.78rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:.35rem;flex-shrink:0;text-decoration:none;">
        <i class="bi bi-file-earmark-pdf-fill"></i>PDF
    </a>
    {{-- Exportar CSV --}}
    <a href="{{ route('portal.docente.tareas.entregas.csv', [$asignacion, $tarea]) }}"
       style="background:#d1fae5;color:#065f46;border:1.5px solid #6ee7b7;border-radius:8px;padding:.38rem .85rem;font-size:.78rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:.35rem;flex-shrink:0;text-decoration:none;">
        <i class="bi bi-filetype-csv"></i>CSV
    </a>
    {{-- Pasar nota a calificaciones --}}
    @if($tarea->puntos_valor)
    <button onclick="document.getElementById('modal-pasar-nota').style.display='flex'"
        style="background:#ede9fe;color:#6d28d9;border:1.5px solid #c4b5fd;border-radius:8px;padding:.38rem .85rem;font-size:.78rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:.35rem;flex-shrink:0;">
        <i class="bi bi-arrow-up-right-square-fill"></i>Pasar Nota
    </button>
    @endif
    @if($nPendEntregas > 0)
    <button id="btn-recordatorio-global" onclick="enviarRecordatorioGlobal()"
        style="background:#fef3c7;color:#d97706;border:1.5px solid #fde68a;border-radius:8px;padding:.38rem .85rem;font-size:.78rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:.35rem;flex-shrink:0;">
        <i class="bi bi-bell-fill"></i>Recordatorio ({{ $nPendEntregas }})
    </button>
    @endif
</div>

{{-- Modal: Pasar nota a calificaciones --}}
@if($tarea->puntos_valor)
<div id="modal-pasar-nota" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:16px;padding:1.4rem 1.6rem;max-width:420px;width:100%;box-shadow:0 8px 32px rgba(0,0,0,.18);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h3 style="font-size:.95rem;font-weight:800;margin:0;color:#1e293b;">
                <i class="bi bi-arrow-up-right-square-fill me-2" style="color:#7c3aed;"></i>Pasar Nota a Calificaciones
            </h3>
            <button onclick="document.getElementById('modal-pasar-nota').style.display='none'"
                style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#6b7280;">✕</button>
        </div>
        <p style="font-size:.75rem;color:#64748b;margin-bottom:1rem;">
            La nota de cada estudiante (sobre <strong>{{ $tarea->puntos_valor }}</strong> puntos) se convertirá a escala de 100 antes de guardarse.
            Solo se procesan los estudiantes con calificación asignada.
        </p>

        @if($esTecnica)
        {{-- Técnica: período + campo --}}
        <div style="margin-bottom:.8rem;">
            <label style="font-size:.75rem;font-weight:700;color:#374151;display:block;margin-bottom:.3rem;">Período</label>
            <select id="pasar-periodo-id" style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.4rem .6rem;font-size:.82rem;">
                @foreach($periodos as $per)
                <option value="{{ $per->id }}">{{ $per->nombre ?? 'Período ' . $per->numero }}</option>
                @endforeach
            </select>
        </div>
        <div style="margin-bottom:1rem;">
            <label style="font-size:.75rem;font-weight:700;color:#374151;display:block;margin-bottom:.3rem;">Campo a actualizar</label>
            <select id="pasar-campo" style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.4rem .6rem;font-size:.82rem;">
                <option value="tareas"        {{ $tarea->tipo === 'tarea'       ? 'selected' : '' }}>Tareas</option>
                <option value="practicas"     {{ $tarea->tipo === 'actividad'   ? 'selected' : '' }}>Prácticas</option>
                <option value="participacion"                                                      >Participación</option>
                <option value="proyecto"      {{ $tarea->tipo === 'proyecto'    ? 'selected' : '' }}>Proyecto</option>
                <option value="examen"        {{ $tarea->tipo === 'evaluacion'  ? 'selected' : '' }}>Examen</option>
            </select>
        </div>
        @else
        {{-- Académica: componente + número de período --}}
        <div style="margin-bottom:.8rem;">
            <label style="font-size:.75rem;font-weight:700;color:#374151;display:block;margin-bottom:.3rem;">Componente</label>
            <select id="pasar-componente" style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.4rem .6rem;font-size:.82rem;">
                <option value="1">1 — Comunicativa</option>
                <option value="2">2 — Pensamiento Lógico</option>
                <option value="3">3 — Científica y Tecnológica</option>
                <option value="4">4 — Ética y Ciudadana</option>
            </select>
        </div>
        <div style="margin-bottom:1rem;">
            <label style="font-size:.75rem;font-weight:700;color:#374151;display:block;margin-bottom:.3rem;">Período</label>
            <select id="pasar-periodo-num" style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.4rem .6rem;font-size:.82rem;">
                <option value="1">Período 1</option>
                <option value="2">Período 2</option>
                <option value="3">Período 3</option>
                <option value="4">Período 4</option>
            </select>
        </div>
        @endif

        <div id="pasar-msg" style="font-size:.77rem;margin-bottom:.7rem;display:none;"></div>

        <div style="display:flex;gap:.6rem;justify-content:flex-end;">
            <button onclick="document.getElementById('modal-pasar-nota').style.display='none'"
                style="background:#f1f5f9;color:#374151;border:1.5px solid #e2e8f0;border-radius:8px;padding:.4rem 1rem;font-size:.8rem;font-weight:600;cursor:pointer;">
                Cancelar
            </button>
            <button id="btn-pasar-confirmar" onclick="ejecutarPasarNota()"
                style="background:#7c3aed;color:#fff;border:none;border-radius:8px;padding:.4rem 1.1rem;font-size:.8rem;font-weight:700;cursor:pointer;">
                <i class="bi bi-check-lg"></i> Confirmar
            </button>
        </div>
    </div>
</div>
@endif

{{-- Resumen KPIs --}}
@php
    $nTotal      = $matriculas->count();
    $nEntregadas = $entregas->whereIn('estado', ['entregada', 'revisada'])->count();
    $nRevisadas  = $entregas->where('estado', 'revisada')->count();
    $nPendientes = $nTotal - $nEntregadas;
    $nConFeedback = $entregas->filter(fn($e) => !empty($e->notas_docente))->count();
    $pctFeedback  = $nEntregadas > 0 ? round($nConFeedback / $nEntregadas * 100) : 0;
@endphp
<div class="d-flex gap-2 flex-wrap mb-3">
    <div style="background:#eff6ff;border-radius:10px;padding:.5rem .9rem;flex:1;min-width:90px;">
        <div style="font-size:1.3rem;font-weight:800;color:#1d4ed8;">{{ $nTotal }}</div>
        <div style="font-size:.7rem;color:#3b82f6;font-weight:600;">Total</div>
    </div>
    <div style="background:#fef3c7;border-radius:10px;padding:.5rem .9rem;flex:1;min-width:90px;">
        <div style="font-size:1.3rem;font-weight:800;color:#d97706;">{{ $nPendientes }}</div>
        <div style="font-size:.7rem;color:#d97706;font-weight:600;">Pendientes</div>
    </div>
    <div style="background:#dbeafe;border-radius:10px;padding:.5rem .9rem;flex:1;min-width:90px;">
        <div style="font-size:1.3rem;font-weight:800;color:#2563eb;">{{ $nEntregadas }}</div>
        <div style="font-size:.7rem;color:#2563eb;font-weight:600;">Entregadas</div>
    </div>
    <div style="background:#d1fae5;border-radius:10px;padding:.5rem .9rem;flex:1;min-width:90px;">
        <div style="font-size:1.3rem;font-weight:800;color:#059669;">{{ $nRevisadas }}</div>
        <div style="font-size:.7rem;color:#059669;font-weight:600;">Revisadas</div>
    </div>
    <div style="background:#ede9fe;border-radius:10px;padding:.5rem .9rem;flex:1;min-width:90px;">
        <div style="font-size:1.3rem;font-weight:800;color:#6d28d9;">{{ $nConFeedback }}</div>
        <div style="font-size:.7rem;color:#7c3aed;font-weight:600;">Con feedback</div>
        @if($nEntregadas > 0)
        <div class="fb-progress">
            <div class="fb-progress-fill" style="width:{{ $pctFeedback }}%;"></div>
        </div>
        @endif
    </div>
</div>

@if($matriculas->isEmpty())
<div style="text-align:center;padding:2rem;color:var(--prt-muted);">
    <i class="bi bi-people" style="font-size:2rem;opacity:.3;"></i>
    <p class="mt-2" style="font-size:.85rem;">No hay estudiantes activos en este grupo.</p>
</div>
@else

{{-- Frases rápidas globales (referencia) --}}
@php
$frases = [
    'Excelente trabajo.',
    'Buen esfuerzo, sigue así.',
    'Necesita mejorar la presentación.',
    'Revisar los conceptos del tema.',
    'Falta desarrollar más la idea.',
    'Muy bien redactado.',
    'Faltan las referencias bibliográficas.',
    'Entregado tarde, pero aceptado.',
];
@endphp

<div id="lista-entregas">
@foreach($matriculas as $matricula)
@php
    $est     = $matricula->estudiante;
    $entrega = $entregas->get($est?->id);
    $estado  = $entrega?->estado ?? 'pendiente';
    $calif   = $entrega?->calificacion !== null ? (float) $entrega->calificacion : '';
    $notas   = $entrega?->notas_docente ?? '';
    $tieneFeedback = !empty($notas);

    $colorEstado = match($estado) {
        'revisada'  => '#10b981',
        'entregada' => '#3b82f6',
        default     => '#f59e0b',
    };
@endphp
<div class="entrega-row {{ $tieneFeedback ? 'con-feedback' : '' }}"
     x-data="calificarEntrega({{ $est?->id ?? 0 }}, {{ $tarea->id }}, {{ $asignacion->id }})"
     x-init="init('{{ $estado }}', '{{ $calif }}', `{{ addslashes($notas) }}`)">

    {{-- Fila principal --}}
    <div class="entrega-head">

        {{-- Avatar --}}
        <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:800;font-size:.85rem;color:#fff;">
            {{ strtoupper(substr($est?->nombres ?? '?', 0, 1)) }}
        </div>

        {{-- Nombre --}}
        <div style="flex:1;min-width:120px;">
            <div style="font-size:.85rem;font-weight:700;color:#1e293b;">
                {{ $est?->nombre_completo ?? 'N/A' }}
            </div>
            <div style="font-size:.68rem;color:var(--prt-muted);display:flex;gap:.6rem;flex-wrap:wrap;">
                @if($entrega?->fecha_entrega)
                <span><i class="bi bi-send-check me-1"></i>{{ $entrega->fecha_entrega->format('d/m/Y H:i') }}</span>
                @endif
                @if($tieneFeedback)
                <span style="color:#059669;font-weight:600;"><i class="bi bi-chat-left-text-fill me-1"></i>Con retroalimentación</span>
                @endif
            </div>
        </div>

        {{-- Estado --}}
        <select x-model="estado"
                class="estado-select"
                :style="`border-color:${estado==='revisada'?'#10b981':estado==='entregada'?'#3b82f6':'#f59e0b'};color:${estado==='revisada'?'#059669':estado==='entregada'?'#1d4ed8':'#d97706'};`">
            <option value="pendiente">Pendiente</option>
            <option value="entregada">Entregada</option>
            <option value="revisada">Revisada</option>
        </select>

        {{-- Calificación --}}
        <div class="d-flex align-items-center gap-1">
            <input type="number" x-model="calificacion"
                   class="nota-inp"
                   min="0" max="{{ $tarea->puntos_valor ?? 100 }}"
                   placeholder="Nota">
            <span style="font-size:.7rem;color:var(--prt-muted);">/ {{ $tarea->puntos_valor ?? 100 }}</span>
        </div>

        {{-- Guardar --}}
        <button @click="guardar()" class="btn btn-primary save-btn" :disabled="guardando">
            <span x-show="!guardando"><i class="bi bi-floppy-fill"></i> Guardar</span>
            <span x-show="guardando"><i class="bi bi-arrow-repeat"></i></span>
        </button>
        <span class="feedback-guardado" :style="guardado ? 'display:inline-flex;align-items:center;gap:.25rem;' : 'display:none;'">
            <i class="bi bi-check-circle-fill"></i> Guardado
        </span>
    </div>

    {{-- Sección de retroalimentación --}}
    <div class="feedback-section">

        {{-- Toggle header --}}
        <div class="feedback-toggle" @click="fbAbierto = !fbAbierto">
            <template x-if="!notas">
                <span>
                    <i class="bi bi-chat-left-dots me-1"></i>
                    Agregar retroalimentación
                    <i :class="fbAbierto ? 'bi bi-chevron-up' : 'bi bi-chevron-down'" class="ms-1" style="font-size:.65rem;"></i>
                </span>
            </template>
            <template x-if="notas">
                <span>
                    <i class="bi bi-chat-left-text-fill me-1"></i>
                    Retroalimentación enviada
                    <i :class="fbAbierto ? 'bi bi-chevron-up' : 'bi bi-chevron-down'" class="ms-1" style="font-size:.65rem;"></i>
                </span>
            </template>
        </div>

        {{-- Cuerpo del feedback (expandible) --}}
        <div class="feedback-body" x-show="fbAbierto" x-transition>

            {{-- Frases rápidas --}}
            <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:.5rem;">
                @foreach($frases as $frase)
                <span class="frase-chip" @click="insertarFrase('{{ $frase }}')" title="Insertar frase">{{ $frase }}</span>
                @endforeach
            </div>

            <textarea
                x-model="notas"
                class="feedback-textarea"
                placeholder="Escribe retroalimentación para el estudiante… (se notificará cuando guardes)"
                rows="3"
                maxlength="1000"
                @click="abrirFeedback()"></textarea>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.3rem;">
                <span style="font-size:.65rem;color:#94a3b8;" x-text="(notas.length || 0) + '/1000 caracteres'"></span>
                <span style="font-size:.68rem;color:#94a3b8;">
                    <i class="bi bi-bell-fill me-1" style="color:#6366f1;"></i>
                    El estudiante recibirá notificación al guardar
                </span>
            </div>
        </div>
    </div>

</div>
@endforeach
</div>

@endif

@endsection
