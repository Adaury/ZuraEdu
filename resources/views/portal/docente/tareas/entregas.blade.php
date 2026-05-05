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
    border-radius: 10px;
    padding: .8rem 1rem;
    margin-bottom: .6rem;
    transition: box-shadow .15s;
}
.entrega-row:hover { box-shadow: 0 2px 10px rgba(59,130,246,.09); }
.estado-select {
    border: 1.5px solid #e2e8f0;
    border-radius: 7px;
    font-size: .78rem;
    font-weight: 600;
    padding: .25rem .5rem;
    cursor: pointer;
    background: #f8fafc;
    appearance: auto;
}
.badge-estado {
    display: inline-block;
    padding: .18rem .55rem;
    border-radius: 99px;
    font-size: .7rem;
    font-weight: 700;
    color: #fff;
}
.nota-inp {
    width: 70px;
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
.save-btn {
    font-size: .72rem;
    padding: .22rem .6rem;
    border-radius: 7px;
}
.saved-ok { color: #10b981; font-size: .7rem; font-weight: 700; display: none; }
</style>
@endpush

@push('scripts')
<script>
function calificarEntrega(estudianteId, tareaId, asignacionId) {
    return {
        estado:      '',
        calificacion:'',
        notas:       '',
        guardando:   false,
        guardado:    false,

        init(estado, calificacion, notas) {
            this.estado       = estado       || 'pendiente';
            this.calificacion = calificacion || '';
            this.notas        = notas        || '';
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
                            'Content-Type':  'application/json',
                            'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]').content,
                            'Accept':        'application/json',
                        },
                        body: JSON.stringify({
                            estudiante_id: estudianteId,
                            estado:        this.estado,
                            calificacion:  this.calificacion || null,
                            notas_docente: this.notas        || null,
                        }),
                    }
                );
                const data = await res.json();
                if (data.ok) {
                    this.guardado = true;
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
    <a href="{{ route('portal.docente.tareas.index', $asignacion) }}"
       class="btn btn-outline-secondary btn-sm mt-1" style="padding:.25rem .6rem;">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div style="flex:1;min-width:0;">
        <h2 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-people-fill me-2" style="color:#3b82f6;"></i>Entregas
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
</div>

{{-- Resumen --}}
@php
    $nTotal      = $matriculas->count();
    $nEntregadas = $entregas->whereIn('estado', ['entregada', 'revisada'])->count();
    $nRevisadas  = $entregas->where('estado', 'revisada')->count();
    $nPendientes = $nTotal - $nEntregadas;
@endphp
<div class="d-flex gap-2 flex-wrap mb-3">
    <div style="background:#eff6ff;border-radius:10px;padding:.5rem .9rem;flex:1;min-width:100px;">
        <div style="font-size:1.4rem;font-weight:800;color:#1d4ed8;">{{ $nTotal }}</div>
        <div style="font-size:.72rem;color:#3b82f6;font-weight:600;">Total</div>
    </div>
    <div style="background:#fef3c7;border-radius:10px;padding:.5rem .9rem;flex:1;min-width:100px;">
        <div style="font-size:1.4rem;font-weight:800;color:#d97706;">{{ $nPendientes }}</div>
        <div style="font-size:.72rem;color:#d97706;font-weight:600;">Pendientes</div>
    </div>
    <div style="background:#dbeafe;border-radius:10px;padding:.5rem .9rem;flex:1;min-width:100px;">
        <div style="font-size:1.4rem;font-weight:800;color:#2563eb;">{{ $nEntregadas }}</div>
        <div style="font-size:.72rem;color:#2563eb;font-weight:600;">Entregadas</div>
    </div>
    <div style="background:#d1fae5;border-radius:10px;padding:.5rem .9rem;flex:1;min-width:100px;">
        <div style="font-size:1.4rem;font-weight:800;color:#059669;">{{ $nRevisadas }}</div>
        <div style="font-size:.72rem;color:#059669;font-weight:600;">Revisadas</div>
    </div>
</div>

@if($matriculas->isEmpty())
<div style="text-align:center;padding:2rem;color:var(--prt-muted);">
    <i class="bi bi-people" style="font-size:2rem;opacity:.3;"></i>
    <p class="mt-2" style="font-size:.85rem;">No hay estudiantes activos en este grupo.</p>
</div>
@else

<div id="lista-entregas">
@foreach($matriculas as $matricula)
@php
    $est     = $matricula->estudiante;
    $entrega = $entregas->get($est?->id);
    $estado  = $entrega?->estado ?? 'pendiente';
    $calif   = $entrega?->calificacion ?? '';
    $notas   = $entrega?->notas_docente ?? '';
    $colors  = ['pendiente' => '#f59e0b', 'entregada' => '#3b82f6', 'revisada' => '#10b981'];
    $colorE  = $colors[$estado] ?? '#6b7280';
@endphp
<div class="entrega-row"
     x-data="calificarEntrega({{ $est?->id ?? 0 }}, {{ $tarea->id }}, {{ $asignacion->id }})"
     x-init="init('{{ $estado }}', '{{ $calif }}', `{{ addslashes($notas) }}`)">

    <div class="d-flex align-items-center gap-2 flex-wrap">
        {{-- Avatar --}}
        <div style="width:36px;height:36px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:800;font-size:.85rem;color:#475569;">
            {{ strtoupper(substr($est?->nombres ?? '?', 0, 1)) }}
        </div>

        {{-- Nombre --}}
        <div style="flex:1;min-width:120px;">
            <div style="font-size:.87rem;font-weight:700;color:#1e293b;">
                {{ $est?->nombre_completo ?? 'N/A' }}
            </div>
            @if($entrega?->fecha_entrega)
            <div style="font-size:.7rem;color:var(--prt-muted);">
                Entregado: {{ $entrega->fecha_entrega->format('d/m/Y H:i') }}
            </div>
            @endif
        </div>

        {{-- Estado --}}
        <select x-model="estado"
                class="estado-select"
                :style="`border-color:${
                    estado === 'revisada'  ? '#10b981' :
                    estado === 'entregada' ? '#3b82f6' : '#f59e0b'
                };color:${
                    estado === 'revisada'  ? '#059669' :
                    estado === 'entregada' ? '#1d4ed8' : '#d97706'
                };`">
            <option value="pendiente">Pendiente</option>
            <option value="entregada">Entregada</option>
            <option value="revisada">Revisada</option>
        </select>

        {{-- Calificación --}}
        @if($tarea->puntos_valor)
        <div class="d-flex align-items-center gap-1">
            <input type="number" x-model="calificacion"
                   class="nota-inp"
                   min="0" max="{{ $tarea->puntos_valor }}"
                   placeholder="0">
            <span style="font-size:.72rem;color:var(--prt-muted);">/ {{ $tarea->puntos_valor }}</span>
        </div>
        @else
        <input type="number" x-model="calificacion"
               class="nota-inp"
               min="0" max="100"
               placeholder="Nota">
        @endif

        {{-- Guardar --}}
        <button @click="guardar()" class="btn btn-primary save-btn" :disabled="guardando">
            <span x-show="!guardando"><i class="bi bi-floppy-fill"></i></span>
            <span x-show="guardando"><i class="bi bi-arrow-repeat"></i></span>
        </button>
        <span class="saved-ok" :style="guardado ? 'display:inline;' : 'display:none;'">
            <i class="bi bi-check-lg"></i> Guardado
        </span>
    </div>

    {{-- Notas docente --}}
    <div class="mt-2">
        <textarea x-model="notas" rows="1" placeholder="Retroalimentación al estudiante (opcional)…"
                  class="form-control form-control-sm" style="font-size:.78rem;resize:none;"></textarea>
    </div>
</div>
@endforeach
</div>

@endif

@endsection
