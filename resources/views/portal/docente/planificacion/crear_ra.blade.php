@extends('layouts.portal')
@section('page-title', isset($planificacion) ? 'Editar Planificación por RA' : 'Nueva Planificación por RA')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'planificacion'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.planificacion.index', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-journal-text"></i>Planif.
    </a>
    <a href="{{ route('portal.docente.boletines', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-file-earmark-text"></i>Boletines
    </a>
@endsection

@push('styles')
<style>
.prt-field-lbl { font-size:.75rem; font-weight:700; color:#374151; margin-bottom:.25rem; display:block; }
.prt-inp { width:100%; border:1px solid #cbd5e1; border-radius:7px; padding:.45rem .7rem; font-size:.82rem; background:#fff; color:#1e293b; }
.prt-inp:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 2px #bfdbfe; }
[data-theme="dark"] .prt-inp { background:#1e293b; border-color:#334155; color:#e2e8f0; }
.ra-bloque-portal { border:1px solid #e2e8f0; border-radius:8px; padding:.85rem; margin-bottom:.75rem; background:#f8faff; }
[data-theme="dark"] .ra-bloque-portal { background:#1e293b; border-color:#334155; }
.section-bar { background:#1d4ed8; color:#fff; border-radius:6px; padding:.4rem .85rem; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; margin-bottom:.7rem; }
.section-bar.green { background:#15803d; }
.zura-ia-panel { transition: all .2s; }
[data-theme="dark"] .zura-ia-panel { background:#0c2a3e !important; border-color:#0ea5e9 !important; }
@keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.planificacion.index', $asignacion) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div>
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-journal-text" style="color:#1d4ed8;"></i>
            {{ isset($planificacion) ? 'Editar Planificación por RA' : 'Nueva Planificación por RA' }}
        </h1>
        <div style="font-size:.75rem;color:#64748b;">
            {{ $asignacion->asignatura?->nombre }} — {{ $asignacion->grupo?->nombre_completo }}
        </div>
    </div>
</div>

@if($errors->any())
<div style="background:#fee2e2;color:#dc2626;border-radius:8px;padding:.65rem 1rem;margin-bottom:.75rem;font-size:.8rem;">
    <ul style="margin:0;padding-left:1.2rem;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

{{-- Banner IA --}}
<div style="background:linear-gradient(135deg,#eff6ff,#f5f3ff);border:1.5px solid #c4b5fd;border-radius:10px;padding:.7rem 1rem;margin-bottom:.75rem;display:flex;align-items:center;gap:.65rem;flex-wrap:wrap;">
    <i class="bi bi-stars" style="color:#7c3aed;font-size:1.1rem;flex-shrink:0;"></i>
    <div style="flex:1;min-width:0;">
        <div style="font-size:.76rem;font-weight:800;color:#5b21b6;">ZuraIA disponible en cada RA</div>
        <div style="font-size:.73rem;color:#6d28d9;line-height:1.4;">Haz clic en <strong>"Generar con ZuraIA"</strong> dentro de cada bloque RA para que la inteligencia artificial complete la descripción, elementos de capacidad, actividades, instrumentos y contenidos automáticamente.</div>
    </div>
</div>

@if(isset($planificacion))
<form method="POST" action="{{ route('portal.docente.planificacion.update', [$asignacion, $planificacion]) }}">
@csrf @method('PUT')
@else
<form method="POST" action="{{ route('portal.docente.planificacion.store-ra', $asignacion) }}">
@csrf
@endif

{{-- Encabezado --}}
<div class="prt-card" style="margin-bottom:.75rem;">
    <div class="prt-card-header">
        <i class="bi bi-info-circle" style="color:#1d4ed8;font-size:1rem;"></i>
        <h3>Datos del Módulo</h3>
    </div>
    <div style="padding:.85rem;display:grid;grid-template-columns:1fr 1fr;gap:.65rem;">
        <div style="grid-column:1/-1;">
            <label class="prt-field-lbl">Familia Profesional</label>
@php $plan = $planificacion ?? null; @endphp
            <input type="text" name="familia_profesional" class="prt-inp"
                   value="{{ old('familia_profesional', $plan?->familia_profesional ?? 'Informática y Comunicaciones') }}">
        </div>
        <div>
            <label class="prt-field-lbl">Denominación</label>
            <input type="text" name="denominacion" class="prt-inp" value="{{ old('denominacion', $plan?->denominacion) }}">
        </div>
        <div>
            <label class="prt-field-lbl">Módulo</label>
            <input type="text" name="modulo_nombre" class="prt-inp"
                   value="{{ old('modulo_nombre', $plan?->modulo_nombre ?? $asignacion->asignatura?->nombre) }}">
        </div>
        <div>
            <label class="prt-field-lbl">Código MF</label>
            <input type="text" name="mf_codigo" class="prt-inp" placeholder="MF_060_3" value="{{ old('mf_codigo', $plan?->mf_codigo) }}">
        </div>
        <div>
            <label class="prt-field-lbl">Sesión</label>
            <input type="text" name="sesion" class="prt-inp"
                   value="{{ old('sesion', $plan?->sesion ?? $asignacion->grupo?->nombre_completo) }}" placeholder="6to A">
        </div>
        <div>
            <label class="prt-field-lbl">Nivel</label>
            <input type="text" name="nivel" class="prt-inp" placeholder="3" value="{{ old('nivel', $plan?->nivel) }}">
        </div>
        <div>
            <label class="prt-field-lbl">Horas</label>
            <input type="number" name="horas" class="prt-inp" step="0.5" min="0"
                   value="{{ old('horas', $plan?->horas ?? $asignacion->horas_semana) }}">
        </div>
        <div>
            <label class="prt-field-lbl">Fecha Inicio</label>
            <input type="date" name="fecha_inicio" class="prt-inp" value="{{ old('fecha_inicio', $plan?->fecha_inicio?->format('Y-m-d')) }}">
        </div>
        <div>
            <label class="prt-field-lbl">Fecha Final</label>
            <input type="date" name="fecha_fin" class="prt-inp" value="{{ old('fecha_fin', $plan?->fecha_fin?->format('Y-m-d')) }}">
        </div>
        <div style="grid-column:1/-1;">
            <label class="prt-field-lbl">Unidad de Competencia (UC)</label>
            <textarea name="uc_codigo" class="prt-inp" rows="2" placeholder="UC_060_Desarrollar e implementar…">{{ old('uc_codigo', $plan?->uc_codigo) }}</textarea>
        </div>
    </div>
</div>

{{-- RA Items --}}
<div class="prt-card" style="margin-bottom:.75rem;">
    <div class="prt-card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <div style="display:flex;align-items:center;gap:.5rem;">
            <i class="bi bi-list-check" style="color:#15803d;font-size:1rem;"></i>
            <h3 style="margin:0;">Resultados de Aprendizaje</h3>
        </div>
        <button type="button" onclick="agregarRA()"
                style="background:#15803d;color:#fff;border:none;border-radius:7px;padding:.3rem .75rem;font-size:.75rem;font-weight:700;cursor:pointer;">
            <i class="bi bi-plus-circle me-1"></i>Agregar RA
        </button>
    </div>
    <div id="ra-container" style="padding:.85rem;">
        @php
            if (old('ra')) {
                $raItems = old('ra');
            } elseif ($plan && $plan->raItems->isNotEmpty()) {
                $raItems = $plan->raItems->map(fn($item) => [
                    'ra_codigo'               => $item->ra_codigo,
                    'ra_descripcion'          => $item->ra_descripcion,
                    'nivel_taxonomico'        => $item->nivel_taxonomico,
                    'elementos_capacidad'     => collect($item->elementos_capacidad ?? [])->pluck('descripcion')->implode("\n"),
                    'fechas_desde'            => collect($item->fechas ?? [])->pluck('desde')->toArray(),
                    'fechas_hasta'            => collect($item->fechas ?? [])->pluck('hasta')->toArray(),
                    'actividades'             => $item->actividades,
                    'instrumentos_evaluacion' => $item->instrumentos_evaluacion,
                    'contenidos'              => $item->contenidos,
                ])->toArray();
            } else {
                $raItems = [[]];
            }
        @endphp
        @foreach($raItems as $idx => $raItem)
        @include('portal.docente.planificacion._ra_item_portal', ['idx' => $idx, 'raItem' => $raItem])
        @endforeach
    </div>
</div>

{{-- Botones --}}
<div style="display:flex;justify-content:flex-end;gap:.5rem;margin-bottom:1.5rem;">
    <a href="{{ route('portal.docente.planificacion.index', $asignacion) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.5rem 1.2rem;font-size:.82rem;font-weight:700;text-decoration:none;">
        Cancelar
    </a>
    <button type="submit"
            style="background:#1d4ed8;color:#fff;border:none;border-radius:8px;padding:.5rem 1.4rem;font-size:.82rem;font-weight:700;cursor:pointer;">
        <i class="bi bi-save me-1"></i>{{ isset($planificacion) ? 'Actualizar Planificación' : 'Guardar Planificación' }}
    </button>
</div>

</form>

<template id="ra-template">
    @include('portal.docente.planificacion._ra_item_portal', ['idx' => '__IDX__', 'raItem' => []])
</template>

@push('scripts')
<script>
const IA_RA_URL = "{{ route('portal.docente.planificacion.ia.ra', $asignacion) }}";
const IA_CSRF   = document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';

let raCount = {{ count($raItems) }};
function agregarRA() {
    const tpl = document.getElementById('ra-template').innerHTML.replace(/__IDX__/g, raCount);
    document.getElementById('ra-container').insertAdjacentHTML('beforeend', tpl);
    raCount++;
}
function eliminarRA(btn) {
    if (document.querySelectorAll('.ra-bloque-portal').length > 1) {
        btn.closest('.ra-bloque-portal').remove();
    }
}
function agregarFecha(btn) {
    const cont = btn.closest('.fechas-container').querySelector('.fechas-list');
    const idx  = btn.dataset.idx;
    cont.insertAdjacentHTML('beforeend', `<div class="fecha-row" style="display:flex;gap:.4rem;margin-bottom:.4rem;align-items:center;">
        <input type="date" name="ra[${idx}][fechas_desde][]" class="prt-inp" style="flex:1;">
        <input type="date" name="ra[${idx}][fechas_hasta][]" class="prt-inp" style="flex:1;">
        <button type="button" onclick="this.closest('.fecha-row').remove()"
            style="background:#fee2e2;color:#dc2626;border:none;border-radius:6px;padding:.25rem .5rem;cursor:pointer;font-size:.8rem;"><i class="bi bi-x"></i></button>
    </div>`);
}

// ── ZuraIA: abrir/cerrar panel ────────────────────────────────────────────
function abrirIaRA(btn) {
    const bloque = btn.closest('.ra-bloque-portal');
    const panel  = bloque.querySelector('.zura-ia-panel');
    const visible = panel.style.display !== 'none';
    panel.style.display = visible ? 'none' : 'block';
    // Pre-llenar código y nivel si el docente ya los puso
    if (!visible) {
        const codVal = bloque.querySelector('.campo-ra-codigo')?.value;
        const nivVal = bloque.querySelector('.campo-nivel')?.value;
        if (codVal) panel.querySelector('.ia-ra-codigo').value = codVal;
        if (nivVal) {
            const sel = panel.querySelector('.ia-nivel');
            [...sel.options].forEach(o => { o.selected = o.text.toLowerCase().includes(nivVal.toLowerCase()); });
        }
    }
}

// ── ZuraIA: ejecutar generación RA ────────────────────────────────────────
async function ejecutarIaRA(btn) {
    const panel   = btn.closest('.zura-ia-panel');
    const bloque  = btn.closest('.ra-bloque-portal');
    const spinner = panel.querySelector('.ia-spinner');
    const errEl   = panel.querySelector('.ia-error');
    const btnTxt  = panel.querySelector('.ia-btn-txt');

    const payload = {
        ra_hint:          panel.querySelector('.ia-hint').value.trim(),
        ra_codigo:        panel.querySelector('.ia-ra-codigo').value.trim(),
        nivel_taxonomico: panel.querySelector('.ia-nivel').value,
        contexto:         panel.querySelector('.ia-contexto').value.trim(),
        familia_profesional: document.querySelector('input[name="familia_profesional"]')?.value ?? '',
        modulo:           document.querySelector('input[name="modulo_nombre"]')?.value ?? '',
    };

    btn.disabled = true;
    btnTxt.textContent = 'Generando…';
    spinner.style.display = 'inline-flex';
    errEl.style.display = 'none';

    try {
        const res = await fetch(IA_RA_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': IA_CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const json = await res.json();

        if (!res.ok || json.error) {
            errEl.textContent = json.error ?? 'Error al generar. Intente de nuevo.';
            errEl.style.display = 'inline';
            return;
        }

        // Aplicar al formulario del bloque RA
        if (json.ra_descripcion)
            bloque.querySelector('.campo-ra-descripcion').value = json.ra_descripcion;
        if (json.elementos_capacidad)
            bloque.querySelector('.campo-elementos').value = Array.isArray(json.elementos_capacidad)
                ? json.elementos_capacidad.join('\n') : json.elementos_capacidad;
        if (json.actividades)
            bloque.querySelector('.campo-actividades').value = json.actividades;
        if (json.instrumentos_evaluacion)
            bloque.querySelector('.campo-instrumentos').value = json.instrumentos_evaluacion;
        if (json.contenidos)
            bloque.querySelector('.campo-contenidos').value = json.contenidos;

        // Autocompletar código y nivel si estaban vacíos
        const codInput = bloque.querySelector('.campo-ra-codigo');
        const nivInput = bloque.querySelector('.campo-nivel');
        if (!codInput.value && payload.ra_codigo) codInput.value = payload.ra_codigo;
        if (!nivInput.value) nivInput.value = payload.nivel_taxonomico;

        // Cerrar panel y dar feedback visual
        panel.style.display = 'none';
        bloque.style.borderColor = '#22c55e';
        setTimeout(() => { bloque.style.borderColor = ''; }, 2000);

    } catch (e) {
        errEl.textContent = 'Error de conexión. Verifique su red.';
        errEl.style.display = 'inline';
    } finally {
        btn.disabled = false;
        btnTxt.textContent = 'Generar con IA';
        spinner.style.display = 'none';
    }
}
</script>
@endpush
@endsection
