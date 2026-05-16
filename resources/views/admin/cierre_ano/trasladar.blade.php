@extends('layouts.admin')

@section('page-title', 'Traslado de Estudiantes')

@push('styles')
<style>
    .transfer-header {
        background: linear-gradient(135deg, #1e3a6e 0%, #2563eb 100%);
        border-radius: 14px;
        padding: 1.3rem 1.6rem;
        color: #fff;
        margin-bottom: 1.5rem;
    }
    .transfer-header h4 { font-weight: 800; font-size: 1.1rem; margin-bottom: .25rem; }
    .transfer-header p  { font-size: .84rem; opacity: .8; margin: 0; }

    .section-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 6px rgba(30,58,110,.05);
        margin-bottom: 1.5rem;
    }
    .section-header {
        padding: .85rem 1.2rem;
        border-bottom: 1px solid #e5e7eb;
        background: #f8fafc;
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .section-header .badge-count {
        background: #e0e7ff;
        color: #3730a3;
        border-radius: 20px;
        font-size: .7rem;
        font-weight: 700;
        padding: .18rem .55rem;
    }
    .student-row {
        display: grid;
        grid-template-columns: auto 1fr auto auto;
        align-items: center;
        gap: .75rem;
        padding: .65rem 1.2rem;
        border-bottom: 1px solid #f3f4f6;
        font-size: .83rem;
    }
    .student-row:last-child { border-bottom: none; }
    .student-row:hover { background: #fafbff; }
    .student-row.omitido { opacity: .45; pointer-events: none; }

    .student-avatar {
        width: 34px; height: 34px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 700; font-size: .78rem;
        flex-shrink: 0;
    }
    .student-name { font-weight: 600; color: #1e293b; }
    .student-meta { font-size: .72rem; color: #64748b; }

    .grupo-select {
        font-size: .8rem;
        border-radius: 8px;
        min-width: 180px;
        max-width: 260px;
    }

    .sticky-footer {
        position: sticky;
        bottom: 0;
        background: #fff;
        border-top: 1px solid #e5e7eb;
        box-shadow: 0 -4px 16px rgba(30,58,110,.08);
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        z-index: 50;
        flex-wrap: wrap;
    }
    .progress-label {
        font-size: .82rem;
        font-weight: 600;
        color: #374151;
    }

    /* Dark mode */
    [data-theme="dark"] .section-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .section-header { background: #0f172a; border-color: #334155; }
    [data-theme="dark"] .student-row { border-color: #1e293b; }
    [data-theme="dark"] .student-row:hover { background: #0f172a; }
    [data-theme="dark"] .student-name { color: #e2e8f0; }
    [data-theme="dark"] .sticky-footer { background: #1e293b; border-color: #334155; }
</style>
@endpush

@section('content')

<x-breadcrumb :items="[
    ['label' => 'Dashboard',          'url' => route('admin.dashboard')],
    ['label' => 'Cierre de Año',      'url' => route('admin.cierre-ano.index')],
    ['label' => 'Traslado de Alumnos'],
]" />

{{-- Header --}}
<div class="transfer-header">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div style="width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-arrow-right-circle-fill fs-4"></i>
        </div>
        <div>
            <h4>Traslado de Estudiantes</h4>
            <p>
                <strong>{{ $anoBase->nombre }}</strong>
                <i class="bi bi-arrow-right mx-1"></i>
                <strong>{{ $anoNuevo->nombre }}</strong>
                &nbsp;·&nbsp;
                {{ $promovidos->count() }} promovidos · {{ $noPromovidos->count() }} no promovidos
            </p>
        </div>
        <div class="ms-auto">
            <a href="{{ route('admin.cierre-ano.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25);">
                <i class="bi bi-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>
</div>

{{-- Alertas --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form method="POST" action="{{ route('admin.cierre-ano.ejecutar-traslado') }}" id="formTraslado"
      x-data="trasladoWizard()" x-init="init()">
    @csrf
    <input type="hidden" name="ano_nuevo_id" value="{{ $anoNuevo->id }}">

    {{-- ─── PROMOVIDOS ─── --}}
    <div class="section-card">
        <div class="section-header">
            <i class="bi bi-mortarboard-fill text-success"></i>
            <span class="fw-bold" style="font-size:.88rem;">Estudiantes Promovidos</span>
            <span class="badge-count">{{ $promovidos->count() }}</span>
            <span class="text-muted ms-auto" style="font-size:.75rem;">Pasan al siguiente grado</span>
            <button type="button" class="btn btn-sm btn-outline-success py-0 px-2"
                    @click="selectAll('promovido')">Seleccionar todos</button>
        </div>

        @forelse($promovidos as $mat)
        @php
            $est = $mat->estudiante;
            $yaMatric = in_array($est->id, $yaMatriculados);
            $gradoActId = $mat->grupo?->grado_id;
            $sigGradoId = $siguienteGradoMap[$gradoActId] ?? null;
            $gruposDisp = $sigGradoId ? ($gruposPorGrado->get($sigGradoId) ?? collect()) : collect();
            $key = 'prom_' . $mat->id;
        @endphp
        <div class="student-row {{ $yaMatric ? 'omitido' : '' }}" data-type="promovido" data-key="{{ $key }}">

            {{-- Checkbox --}}
            <div>
                @if($yaMatric)
                    <span title="Ya matriculado en {{ $anoNuevo->nombre }}" style="color:#94a3b8;font-size:.75rem;">
                        <i class="bi bi-check-circle-fill text-success"></i> Ya matriculado
                    </span>
                @else
                <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;margin:0;">
                    <input type="checkbox" name="traslados[{{ $key }}][estudiante_id]"
                           value="{{ $est->id }}"
                           class="form-check-input mt-0"
                           data-key="{{ $key }}"
                           @change="onCheck($event)"
                           checked>
                </label>
                @endif
            </div>

            {{-- Avatar + nombre --}}
            <div style="display:flex;align-items:center;gap:.6rem;min-width:0;">
                <div class="student-avatar">{{ strtoupper(substr($est->nombre ?? '?', 0, 1)) }}</div>
                <div style="min-width:0;">
                    <div class="student-name text-truncate">{{ $est->nombre_completo }}</div>
                    <div class="student-meta">
                        {{ $mat->grupo?->nombre_completo ?? '—' }}
                        @if($mat->promocion?->promedio_final)
                            &nbsp;·&nbsp; Prom: <strong>{{ number_format($mat->promocion->promedio_final, 1) }}</strong>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Flecha --}}
            <div style="color:#94a3b8;font-size:.85rem; display: {{ $yaMatric ? 'none' : 'block' }}">
                <i class="bi bi-arrow-right"></i>
            </div>

            {{-- Selector de grupo destino --}}
            @if(! $yaMatric)
            <div>
                @if($gruposDisp->isNotEmpty())
                <select name="traslados[{{ $key }}][grupo_id]"
                        class="form-select grupo-select"
                        data-key="{{ $key }}"
                        @change="onSelect($event)">
                    @foreach($gruposDisp as $g)
                    <option value="{{ $g->id }}">{{ $g->nombre_completo }}</option>
                    @endforeach
                </select>
                @else
                <span class="badge bg-warning text-dark" style="font-size:.7rem;">
                    <i class="bi bi-exclamation-triangle me-1"></i>Sin grupo disponible
                </span>
                <input type="hidden" name="traslados[{{ $key }}][grupo_id]" value="">
                @endif
            </div>
            @else
            <div></div>
            @endif
        </div>
        @empty
        <div class="text-center py-4 text-muted" style="font-size:.85rem;">
            <i class="bi bi-inbox d-block fs-4 mb-1"></i>No hay estudiantes promovidos.
        </div>
        @endforelse
    </div>

    {{-- ─── NO PROMOVIDOS ─── --}}
    @if($noPromovidos->isNotEmpty())
    <div class="section-card">
        <div class="section-header">
            <i class="bi bi-arrow-repeat text-warning"></i>
            <span class="fw-bold" style="font-size:.88rem;">Estudiantes No Promovidos (Repiten)</span>
            <span class="badge-count" style="background:#fef3c7;color:#92400e;">{{ $noPromovidos->count() }}</span>
            <span class="text-muted ms-auto" style="font-size:.75rem;">Permanecen en el mismo grado</span>
            <button type="button" class="btn btn-sm btn-outline-warning py-0 px-2"
                    @click="selectAll('no_promovido')">Seleccionar todos</button>
        </div>

        @foreach($noPromovidos as $mat)
        @php
            $est = $mat->estudiante;
            $yaMatric = in_array($est->id, $yaMatriculados);
            $gradoActId = $mat->grupo?->grado_id;
            $gruposDisp = $gruposPorGrado->get($gradoActId) ?? collect();
            $key = 'noprom_' . $mat->id;
        @endphp
        <div class="student-row {{ $yaMatric ? 'omitido' : '' }}" data-type="no_promovido" data-key="{{ $key }}">

            <div>
                @if($yaMatric)
                    <span style="color:#94a3b8;font-size:.75rem;">
                        <i class="bi bi-check-circle-fill text-success"></i> Ya matriculado
                    </span>
                @else
                <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;margin:0;">
                    <input type="checkbox" name="traslados[{{ $key }}][estudiante_id]"
                           value="{{ $est->id }}"
                           class="form-check-input mt-0"
                           data-key="{{ $key }}"
                           @change="onCheck($event)"
                           checked>
                </label>
                @endif
            </div>

            <div style="display:flex;align-items:center;gap:.6rem;min-width:0;">
                <div class="student-avatar" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
                    {{ strtoupper(substr($est->nombre ?? '?', 0, 1)) }}
                </div>
                <div style="min-width:0;">
                    <div class="student-name text-truncate">{{ $est->nombre_completo }}</div>
                    <div class="student-meta">
                        {{ $mat->grupo?->nombre_completo ?? '—' }}
                        <span class="badge bg-warning text-dark ms-1" style="font-size:.62rem;">Repite</span>
                    </div>
                </div>
            </div>

            <div style="color:#94a3b8;font-size:.85rem; display: {{ $yaMatric ? 'none' : 'block' }}">
                <i class="bi bi-arrow-clockwise" title="Mismo grado"></i>
            </div>

            @if(! $yaMatric)
            <div>
                @if($gruposDisp->isNotEmpty())
                <select name="traslados[{{ $key }}][grupo_id]"
                        class="form-select grupo-select"
                        data-key="{{ $key }}"
                        @change="onSelect($event)">
                    @foreach($gruposDisp as $g)
                    <option value="{{ $g->id }}">{{ $g->nombre_completo }}</option>
                    @endforeach
                </select>
                @else
                <span class="badge bg-warning text-dark" style="font-size:.7rem;">
                    <i class="bi bi-exclamation-triangle me-1"></i>Sin grupo disponible
                </span>
                <input type="hidden" name="traslados[{{ $key }}][grupo_id]" value="">
                @endif
            </div>
            @else
            <div></div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- ─── Sticky Footer ─── --}}
    <div class="sticky-footer">
        <div>
            <div class="progress-label">
                <span x-text="selected"></span> estudiante(s) seleccionados para trasladar
            </div>
            <div style="font-size:.72rem;color:#64748b;margin-top:.1rem;">
                Revisa los grupos destino antes de ejecutar.
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.cierre-ano.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle me-1"></i>Cancelar
            </a>
            <button type="submit"
                    class="btn btn-success fw-bold px-4"
                    :disabled="selected === 0"
                    x-data x-bind:disabled="selected === 0">
                <i class="bi bi-arrow-right-circle-fill me-2"></i>
                Ejecutar Traslado (<span x-text="selected"></span>)
            </button>
        </div>
    </div>

</form>

@endsection

@push('scripts')
<script>
function trasladoWizard() {
    return {
        selected: 0,
        init() {
            this.recalc();
        },
        recalc() {
            this.selected = document.querySelectorAll('input[type="checkbox"]:checked').length;
        },
        onCheck(e) {
            const key = e.target.dataset.key;
            const row = document.querySelector(`[data-key="${key}"]`);
            // Enable/disable the select in the same row
            const sel = row ? row.querySelector('select') : null;
            if (sel) sel.disabled = !e.target.checked;
            this.recalc();
        },
        onSelect(e) {
            // Nothing special needed; value goes with the form
        },
        selectAll(type) {
            document.querySelectorAll(`[data-type="${type}"] input[type="checkbox"]`)
                .forEach(cb => { cb.checked = true; cb.dispatchEvent(new Event('change')); });
            this.recalc();
        },
    };
}
</script>
@endpush
