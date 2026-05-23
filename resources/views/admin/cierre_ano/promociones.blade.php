@extends('layouts.admin')

@section('page-title', 'Gestión de Promociones — ' . $grupo->nombre_completo)

@push('styles')
<style>
    .pg-header {
        background: linear-gradient(135deg, #1e3a6e 0%, #2563eb 100%);
        border-radius: 14px; padding: 1.3rem 1.6rem;
        color: #fff; margin-bottom: 1.5rem;
    }
    .pg-header h4 { font-weight: 800; font-size: 1.1rem; margin-bottom: .2rem; }
    .pg-header p  { font-size: .83rem; opacity: .8; margin: 0; }

    .legend-bar {
        display: flex; gap: .6rem; flex-wrap: wrap;
        background: #fff; border: 1px solid #e5e7eb;
        border-radius: 10px; padding: .6rem 1rem;
        margin-bottom: 1.25rem; font-size: .78rem;
    }
    .legend-dot {
        width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 3px;
    }

    .promo-table-wrap {
        background: #fff; border: 1px solid #e5e7eb;
        border-radius: 12px; overflow: hidden;
        box-shadow: 0 1px 6px rgba(30,58,110,.05);
    }
    .promo-table thead th {
        background: #f8fafc; border-bottom: 1px solid #e5e7eb;
        font-size: .68rem; font-weight: 700; letter-spacing: .07em;
        text-transform: uppercase; color: #2563eb;
        padding: .65rem .75rem; white-space: nowrap;
    }
    .promo-table tbody td {
        padding: .55rem .75rem; vertical-align: middle;
        border-bottom: 1px solid #f3f4f6; font-size: .82rem;
    }
    .promo-table tbody tr:last-child td { border-bottom: none; }
    .promo-table tbody tr:hover td { background: #fafbff; }

    .nota-cell { text-align: center; font-size: .78rem; font-weight: 600; }
    .nota-cell.baja { color: #dc2626; }
    .nota-cell.alta { color: #059669; }
    .nota-cell.media { color: #d97706; }

    .prom-cell {
        font-weight: 800; text-align: center; font-size: .88rem;
        background: #eff6ff !important;
    }
    .prom-cell.baja { color: #dc2626; background: #fee2e2 !important; }
    .prom-cell.alta { color: #059669; background: #d1fae5 !important; }
    .prom-cell.media { color: #d97706; background: #fef3c7 !important; }

    .estado-select {
        font-size: .78rem; border-radius: 8px;
        padding: .2rem .5rem; border: 1.5px solid #e5e7eb;
        background: #fff; cursor: pointer; min-width: 130px;
    }
    .estado-select.promovido   { border-color: #10b981; background: #f0fdf4; color: #065f46; }
    .estado-select.no_promovido{ border-color: #ef4444; background: #fef2f2; color: #991b1b; }
    .estado-select.condicionado{ border-color: #f59e0b; background: #fffbeb; color: #78350f; }
    .estado-select.pendiente   { border-color: #94a3b8; background: #f8fafc; color: #475569; }

    .obs-btn {
        font-size: .72rem; padding: .18rem .5rem; border-radius: 6px;
        border: 1px solid #e5e7eb; background: #f8fafc; color: #64748b; cursor: pointer;
    }
    .obs-btn:hover { background: #e0e7ff; color: #3730a3; }
    .saving-dot { display: none; }
    .saving-dot.show { display: inline-block; }

    .stats-mini {
        display: flex; gap: .75rem; flex-wrap: wrap; margin-bottom: 1.25rem;
    }
    .stat-mini-card {
        flex: 1; min-width: 120px;
        background: #fff; border: 1px solid #e5e7eb;
        border-radius: 10px; padding: .7rem 1rem;
        display: flex; align-items: center; gap: .6rem;
    }
    .stat-mini-icon {
        width: 36px; height: 36px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
    }
    .stat-mini-val  { font-size: 1.35rem; font-weight: 900; line-height: 1; }
    .stat-mini-lbl  { font-size: .67rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; }

    /* Dark mode */
    [data-theme="dark"] .promo-table-wrap,
    [data-theme="dark"] .stat-mini-card,
    [data-theme="dark"] .legend-bar { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .promo-table thead th { background: #0f172a; border-color: #334155; }
    [data-theme="dark"] .promo-table tbody td { border-color: #1e293b; color: #e2e8f0; }
    [data-theme="dark"] .promo-table tbody tr:hover td { background: #0f172a; }
    [data-theme="dark"] .prom-cell { background: #172035 !important; }
</style>
@endpush

@section('content')

<x-breadcrumb :items="[
    ['label' => 'Dashboard',      'url' => route('admin.dashboard')],
    ['label' => 'Cierre de Año', 'url' => route('admin.cierre-ano.index')],
    ['label' => 'Promociones — ' . $grupo->nombre_completo],
]" />

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Header --}}
<div class="pg-header">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div style="width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,.15);
                    display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-mortarboard-fill fs-4"></i>
        </div>
        <div>
            <h4>Gestión de Promociones — {{ $grupo->nombre_completo }}</h4>
            <p>
                Año {{ $schoolYear?->nombre ?? '—' }} &nbsp;·&nbsp;
                Tutor: {{ $grupo->tutor?->name ?? 'Sin tutor' }} &nbsp;·&nbsp;
                {{ count($filas) }} estudiantes
            </p>
        </div>
        <div class="ms-auto d-flex gap-2">
            <a href="{{ route('admin.cierre-ano.acta-pdf', $grupo) }}"
               target="_blank"
               class="btn btn-sm"
               style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25);">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i>Acta PDF
            </a>
            <a href="{{ route('admin.cierre-ano.index') }}"
               class="btn btn-sm"
               style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25);">
                <i class="bi bi-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>
</div>

{{-- Stats mini --}}
@php
    $cntPromovidos   = collect($filas)->where('estado_actual', 'promovido')->count();
    $cntNoPromovidos = collect($filas)->where('estado_actual', 'no_promovido')->count();
    $cntCondicionado = collect($filas)->where('estado_actual', 'condicionado')->count();
    $cntPendientes   = collect($filas)->where('estado_actual', 'pendiente')->count();
    $promedioGrupo   = collect($filas)->filter(fn($f) => $f['promedio_final'] !== null)->avg(fn($f) => $f['promedio_final']);
@endphp

<div class="stats-mini">
    <div class="stat-mini-card">
        <div class="stat-mini-icon" style="background:#eff6ff;"><i class="bi bi-people-fill text-primary"></i></div>
        <div><div class="stat-mini-val text-primary">{{ count($filas) }}</div><div class="stat-mini-lbl">Total</div></div>
    </div>
    <div class="stat-mini-card">
        <div class="stat-mini-icon" style="background:#d1fae5;"><i class="bi bi-check-circle-fill" style="color:#059669;"></i></div>
        <div><div class="stat-mini-val" style="color:#059669;">{{ $cntPromovidos }}</div><div class="stat-mini-lbl">Promovidos</div></div>
    </div>
    <div class="stat-mini-card">
        <div class="stat-mini-icon" style="background:#fee2e2;"><i class="bi bi-x-circle-fill text-danger"></i></div>
        <div><div class="stat-mini-val text-danger">{{ $cntNoPromovidos }}</div><div class="stat-mini-lbl">No Promovidos</div></div>
    </div>
    <div class="stat-mini-card">
        <div class="stat-mini-icon" style="background:#fef3c7;"><i class="bi bi-arrow-repeat" style="color:#d97706;"></i></div>
        <div><div class="stat-mini-val" style="color:#d97706;">{{ $cntCondicionado }}</div><div class="stat-mini-lbl">Condicionados</div></div>
    </div>
    <div class="stat-mini-card">
        <div class="stat-mini-icon" style="background:#f3f4f6;"><i class="bi bi-hourglass-split text-secondary"></i></div>
        <div><div class="stat-mini-val text-secondary">{{ $cntPendientes }}</div><div class="stat-mini-lbl">Pendientes</div></div>
    </div>
    <div class="stat-mini-card">
        <div class="stat-mini-icon" style="background:#eff6ff;"><i class="bi bi-bar-chart-fill text-primary"></i></div>
        <div>
            <div class="stat-mini-val text-primary">{{ $promedioGrupo ? number_format($promedioGrupo, 1) : '—' }}</div>
            <div class="stat-mini-lbl">Prom. Grupo</div>
        </div>
    </div>
</div>

{{-- Leyenda --}}
<div class="legend-bar">
    <span><span class="legend-dot" style="background:#10b981;"></span>Promovido (≥60)</span>
    <span><span class="legend-dot" style="background:#ef4444;"></span>No Promovido (&lt;60)</span>
    <span><span class="legend-dot" style="background:#f59e0b;"></span>Condicionado (recuperación)</span>
    <span><span class="legend-dot" style="background:#94a3b8;"></span>Pendiente</span>
    <span class="ms-auto text-muted">Los cambios se guardan automáticamente al cambiar el estado.</span>
</div>

{{-- Tabla principal --}}
<div class="promo-table-wrap" x-data="promoManager()">
    <div class="table-responsive">
        <table class="table promo-table mb-0">
            <thead>
                <tr>
                    <th style="width:28px;">#</th>
                    <th>Estudiante</th>
                    @foreach($asignaciones as $asi)
                    <th class="text-center" title="{{ $asi->asignatura?->nombre }}">
                        {{ \Illuminate\Support\Str::limit($asi->asignatura?->nombre ?? '—', 8) }}
                    </th>
                    @endforeach
                    <th class="text-center">Prom.</th>
                    <th>Estado</th>
                    <th class="text-center">Obs.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($filas as $idx => $fila)
                @php
                    $prom = $fila['promedio_final'];
                    $promClass = $prom === null ? '' : ($prom >= 70 ? 'alta' : ($prom >= 60 ? 'media' : 'baja'));
                    $mat = $fila['matricula'];
                    $est = $mat->estudiante;
                @endphp
                <tr id="row-{{ $mat->id }}">
                    <td class="text-muted" style="font-size:.75rem;">{{ $idx + 1 }}</td>
                    <td>
                        <div style="font-weight:600;font-size:.84rem;color:#1e293b;">
                            {{ $est?->apellidos }}, {{ $est?->nombres }}
                        </div>
                        @if($fila['estado_auto'] !== $fila['estado_actual'] && $fila['estado_actual'] !== 'pendiente')
                        <div style="font-size:.68rem;color:#7c3aed;">
                            <i class="bi bi-pencil-fill me-1"></i>Modificado manualmente
                        </div>
                        @endif
                    </td>

                    {{-- Notas por asignatura --}}
                    @foreach($asignaciones as $asi)
                    @php
                        $nota = $fila['notas_asignaciones'][$asi->id] ?? null;
                        $nc = $nota === null ? '' : ($nota >= 70 ? 'alta' : ($nota >= 60 ? 'media' : 'baja'));
                    @endphp
                    <td class="nota-cell {{ $nc }}">
                        {{ $nota !== null ? number_format($nota, 1) : '—' }}
                    </td>
                    @endforeach

                    {{-- Promedio --}}
                    <td class="prom-cell {{ $promClass }}">
                        {{ $prom !== null ? number_format($prom, 1) : '—' }}
                    </td>

                    {{-- Selector de estado --}}
                    <td>
                        <div style="display:flex;align-items:center;gap:.4rem;">
                            <select
                                class="estado-select {{ $fila['estado_actual'] }}"
                                data-matricula="{{ $mat->id }}"
                                @change="cambiarEstado($event, {{ $mat->id }})"
                                x-bind:disabled="saving == {{ $mat->id }}"
                            >
                                <option value="promovido"    {{ $fila['estado_actual'] === 'promovido'    ? 'selected' : '' }}>Promovido</option>
                                <option value="no_promovido" {{ $fila['estado_actual'] === 'no_promovido' ? 'selected' : '' }}>No Promovido</option>
                                <option value="condicionado" {{ $fila['estado_actual'] === 'condicionado' ? 'selected' : '' }}>Condicionado</option>
                                <option value="pendiente"    {{ $fila['estado_actual'] === 'pendiente'    ? 'selected' : '' }}>Pendiente</option>
                            </select>
                            <span class="saving-dot" :class="saving == {{ $mat->id }} ? 'show' : ''">
                                <span class="spinner-border spinner-border-sm text-primary" style="width:14px;height:14px;"></span>
                            </span>
                        </div>
                    </td>

                    {{-- Observación --}}
                    <td class="text-center">
                        <button type="button"
                                class="obs-btn"
                                @click="abrirObs({{ $mat->id }}, '{{ addslashes($fila['observacion']) }}')"
                                :title="observaciones[{{ $mat->id }}] ? 'Ver/editar observación' : 'Agregar observación'">
                            <i class="bi" :class="observaciones[{{ $mat->id }}] ? 'bi-chat-fill text-primary' : 'bi-chat'"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Modal observación --}}
    <div x-show="obsModalOpen" x-transition.opacity style="display:none;position:fixed;inset:0;
         background:rgba(0,0,0,.5);z-index:1055;display:flex;align-items:center;justify-content:center;"
         @click.self="obsModalOpen=false">
        <div class="bg-white rounded-3 shadow-lg p-4" style="max-width:460px;width:100%;" @click.stop>
            <h6 class="fw-bold mb-3"><i class="bi bi-chat-dots-fill me-2 text-primary"></i>Observación</h6>
            <textarea x-model="obsTexto" class="form-control mb-3" rows="3"
                      placeholder="Motivo del cambio o nota para el expediente..."></textarea>
            <div class="d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-sm btn-secondary" @click="obsModalOpen=false">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary" @click="guardarObs()">
                    <i class="bi bi-check-lg me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>

    {{-- Toast de feedback --}}
    <div x-show="toast" x-transition style="display:none;position:fixed;bottom:1.5rem;right:1.5rem;
         background:#1e293b;color:#fff;border-radius:10px;padding:.65rem 1.1rem;
         font-size:.83rem;font-weight:600;z-index:1060;box-shadow:0 4px 16px rgba(0,0,0,.2);">
        <i class="bi bi-check-circle-fill text-success me-2"></i>
        <span x-text="toastMsg"></span>
    </div>
</div>

@endsection

@push('scripts')
<script>
function promoManager() {
    return {
        saving: null,
        toast: false,
        toastMsg: '',
        obsModalOpen: false,
        obsMatriculaId: null,
        obsTexto: '',
        observaciones: @json(collect($filas)->mapWithKeys(fn ($f) => [$f['matricula']->id => $f['observacion']])),

        cambiarEstado(e, matriculaId) {
            const estado = e.target.value;
            const obs    = this.observaciones[matriculaId] ?? '';
            this.guardar(matriculaId, estado, obs, e.target);
        },

        abrirObs(matriculaId, obsActual) {
            this.obsMatriculaId = matriculaId;
            this.obsTexto       = this.observaciones[matriculaId] ?? obsActual;
            this.obsModalOpen   = true;
        },

        guardarObs() {
            const sel = document.querySelector(`select[data-matricula="${this.obsMatriculaId}"]`);
            const estado = sel ? sel.value : 'pendiente';
            this.guardar(this.obsMatriculaId, estado, this.obsTexto, sel);
            this.obsModalOpen = false;
        },

        guardar(matriculaId, estado, obs, selectEl) {
            this.saving = matriculaId;
            fetch(`{{ url('admin/cierre-ano/promocion') }}/${matriculaId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ estado, observacion: obs }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    if (selectEl) {
                        selectEl.className = 'estado-select ' + estado;
                    }
                    this.observaciones[matriculaId] = obs;
                    this.mostrarToast('Estado actualizado');
                }
            })
            .catch(() => this.mostrarToast('Error al guardar'))
            .finally(() => { this.saving = null; });
        },

        mostrarToast(msg) {
            this.toastMsg = msg;
            this.toast    = true;
            setTimeout(() => this.toast = false, 2500);
        },
    };
}
</script>
@endpush
