@extends('layouts.admin')

@section('page-title', 'Configuración del Módulo de Horarios')

@push('styles')
<style>
/* ══════════════════════════════════════════
   CONFIGURACIÓN DE HORARIOS
══════════════════════════════════════════ */
.cfg-header {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%);
    border-radius: 16px;
    padding: 1.5rem 2rem;
    margin-bottom: 1.75rem;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    position: relative;
    overflow: hidden;
}
.cfg-header::after {
    content: '';
    position: absolute;
    right: -30px; top: -30px;
    width: 160px; height: 160px;
    border-radius: 50%;
    background: rgba(255,255,255,.05);
}
.cfg-header-icon {
    width: 52px; height: 52px;
    background: rgba(255,255,255,.15);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; color: #fff;
    flex-shrink: 0;
}
.cfg-header h1 { font-size: 1.2rem; font-weight: 800; color: #fff; margin: 0 0 .2rem; }
.cfg-header p  { font-size: .8rem; color: rgba(255,255,255,.7); margin: 0; }

/* ── Cards de sección ─── */
.cfg-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    margin-bottom: 1.25rem;
    overflow: hidden;
}
.cfg-card-header {
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    padding: .85rem 1.25rem;
    display: flex;
    align-items: center;
    gap: .65rem;
}
.cfg-card-header .icon {
    width: 30px; height: 30px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem;
    flex-shrink: 0;
}
.cfg-card-header h3 {
    font-size: .88rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}
.cfg-card-header p {
    font-size: .75rem;
    color: #6b7280;
    margin: 0;
}
.cfg-card-body { padding: 1.25rem; }

/* ── Tipo institución ─── */
.tipo-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .75rem;
}
.tipo-option {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem;
    cursor: pointer;
    transition: all .18s;
    position: relative;
}
.tipo-option:hover { border-color: #a5b4fc; }
.tipo-option input[type="radio"] { position: absolute; opacity: 0; }
.tipo-option.selected {
    border-color: #4338ca;
    background: #eef2ff;
}
.tipo-icon { font-size: 1.5rem; margin-bottom: .4rem; }
.tipo-label { font-size: .88rem; font-weight: 700; color: #1e293b; }
.tipo-desc  { font-size: .74rem; color: #6b7280; margin-top: .2rem; }
.tipo-check {
    position: absolute;
    top: .6rem; right: .6rem;
    width: 18px; height: 18px;
    border-radius: 50%;
    background: #4338ca;
    color: #fff;
    font-size: .6rem;
    display: none;
    align-items: center; justify-content: center;
}
.tipo-option.selected .tipo-check { display: flex; }

/* ── Días de la semana ─── */
.dias-grid {
    display: flex;
    gap: .6rem;
    flex-wrap: wrap;
}
.dia-check {
    position: relative;
}
.dia-check input[type="checkbox"] { position: absolute; opacity: 0; }
.dia-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: .65rem .9rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    cursor: pointer;
    transition: all .18s;
    min-width: 64px;
    text-align: center;
}
.dia-btn:hover { border-color: #a5b4fc; background: #f5f3ff; }
.dia-check input:checked + .dia-btn {
    border-color: #4338ca;
    background: #eef2ff;
    color: #3730a3;
}
.dia-nombre { font-size: .8rem; font-weight: 700; }
.dia-sigla  { font-size: .65rem; color: #9ca3af; }
.dia-check input:checked + .dia-btn .dia-sigla { color: #6366f1; }

/* ── Inputs numéricos ─── */
.cfg-num-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}
.cfg-num-item label {
    font-size: .78rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: .35rem;
    display: block;
}
.cfg-num-item .desc {
    font-size: .7rem;
    color: #9ca3af;
    margin-top: .2rem;
}
.cfg-range-wrap {
    display: flex;
    align-items: center;
    gap: .75rem;
}
.cfg-range-wrap input[type="range"] {
    flex: 1;
    accent-color: #4338ca;
}
.cfg-range-val {
    font-size: .88rem;
    font-weight: 800;
    color: #312e81;
    min-width: 48px;
    text-align: center;
    background: #eef2ff;
    border-radius: 7px;
    padding: .2rem .5rem;
}

/* ── Toggle módulo ─── */
.modulo-toggle {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: .85rem 1rem;
    border: 1.5px solid #e5e7eb;
    border-radius: 10px;
    cursor: pointer;
    transition: border-color .18s;
}
.modulo-toggle:hover { border-color: #a5b4fc; }
.modulo-toggle.active-toggle { border-color: #4338ca; background: #fafbff; }
.modulo-info { flex: 1; }
.modulo-nombre { font-size: .84rem; font-weight: 700; color: #1e293b; }
.modulo-desc   { font-size: .73rem; color: #6b7280; margin-top: .1rem; }
.form-switch .form-check-input { width: 2.5em; cursor: pointer; }
.form-check-input:checked { background-color: #4338ca; border-color: #4338ca; }

/* ── Alerta institución privada ─── */
.privado-alert {
    background: #fffbeb;
    border: 1px solid #fcd34d;
    border-radius: 10px;
    padding: .7rem 1rem;
    font-size: .8rem;
    color: #92400e;
    display: none;
}
.privado-alert.show { display: flex; align-items: center; gap: .5rem; }

/* ── Botones ─── */
.btn-guardar {
    background: linear-gradient(135deg, #312e81, #4338ca);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: .65rem 1.75rem;
    font-size: .88rem;
    font-weight: 700;
    cursor: pointer;
    transition: opacity .18s;
}
.btn-guardar:hover { opacity: .9; }
.btn-guardar:disabled { opacity: .6; cursor: not-allowed; }

[data-theme="dark"] .cfg-card { background: #1e293b; border-color: #334155; }
</style>
@endpush

@section('content')

<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Horarios',  'url' => route('admin.horarios.index')],
    ['label' => 'Configuración', 'url' => ''],
]" />

{{-- ── Header ──────────────────────────────────────────────────── --}}
<div class="cfg-header">
    <div class="cfg-header-icon">
        <i class="bi bi-sliders2"></i>
    </div>
    <div>
        <h1>Configuración del Módulo de Horarios</h1>
        <p><i class="bi bi-info-circle me-1"></i>
            Estos parámetros controlan el comportamiento del generador y la interfaz para toda la institución.
            Los cambios se aplican en la próxima generación.
        </p>
    </div>
</div>

{{-- Alertas de sesión --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3"
         role="alert" style="border-radius:10px;font-size:.85rem;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST" action="{{ route('admin.horarios.configuracion.guardar') }}" id="formConfig">
@csrf

<div class="row g-3">
<div class="col-lg-8">

    {{-- ── Tipo de institución ─────────────────────────────────── --}}
    <div class="cfg-card">
        <div class="cfg-card-header">
            <div class="icon" style="background:#ede9fe;color:#5b21b6;"><i class="bi bi-building"></i></div>
            <div>
                <h3>Tipo de Institución</h3>
                <p>Define qué módulos y funcionalidades estarán disponibles</p>
            </div>
        </div>
        <div class="cfg-card-body">
            <div class="tipo-grid" id="tipoGrid">
                <div class="tipo-option {{ $config['tipo_institucion'] === 'publico' ? 'selected' : '' }}"
                     onclick="selectTipo('publico', this)">
                    <input type="radio" name="tipo_institucion" value="publico"
                           {{ $config['tipo_institucion'] === 'publico' ? 'checked' : '' }}>
                    <div class="tipo-check"><i class="bi bi-check"></i></div>
                    <div class="tipo-icon">🏫</div>
                    <div class="tipo-label">Centro Público</div>
                    <div class="tipo-desc">MINERD · Sin módulo de pagos · Interfaz simplificada</div>
                </div>
                <div class="tipo-option {{ $config['tipo_institucion'] === 'privado' ? 'selected' : '' }}"
                     onclick="selectTipo('privado', this)">
                    <input type="radio" name="tipo_institucion" value="privado"
                           {{ $config['tipo_institucion'] === 'privado' ? 'checked' : '' }}>
                    <div class="tipo-check"><i class="bi bi-check"></i></div>
                    <div class="tipo-icon">🎓</div>
                    <div class="tipo-label">Centro Privado</div>
                    <div class="tipo-desc">Módulo de pagos · Reportes avanzados · Multi-sede</div>
                </div>
            </div>
            <div class="privado-alert mt-3 {{ $config['tipo_institucion'] === 'privado' ? 'show' : '' }}" id="alertPrivado">
                <i class="bi bi-exclamation-triangle-fill"></i>
                Modo privado activo — asegúrate de activar el módulo de pagos si lo necesitas.
            </div>
        </div>
    </div>

    {{-- ── Días laborales ───────────────────────────────────────── --}}
    <div class="cfg-card">
        <div class="cfg-card-header">
            <div class="icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-calendar-week"></i></div>
            <div>
                <h3>Días Laborales</h3>
                <p>Selecciona los días en que se impartirán clases</p>
            </div>
        </div>
        <div class="cfg-card-body">
            <div class="dias-grid">
                @php
                    $diasOpciones = [
                        'lunes'     => ['L',  'Lunes'],
                        'martes'    => ['Ma', 'Martes'],
                        'miercoles' => ['Mi', 'Miércoles'],
                        'jueves'    => ['J',  'Jueves'],
                        'viernes'   => ['V',  'Viernes'],
                        'sabado'    => ['S',  'Sábado'],
                    ];
                @endphp
                @foreach($diasOpciones as $valor => $info)
                    <label class="dia-check">
                        <input type="checkbox" name="horario_dias[]" value="{{ $valor }}"
                               {{ in_array($valor, $config['horario_dias']) ? 'checked' : '' }}>
                        <div class="dia-btn">
                            <div class="dia-nombre">{{ $info[1] }}</div>
                            <div class="dia-sigla">{{ $info[0] }}</div>
                        </div>
                    </label>
                @endforeach
            </div>
            <div class="mt-2" style="font-size:.74rem;color:#9ca3af;">
                <i class="bi bi-info-circle me-1"></i>
                Al menos un día debe estar seleccionado. El generador solo usará estos días.
            </div>
        </div>
    </div>

    {{-- ── Límites por día ─────────────────────────────────────── --}}
    <div class="cfg-card">
        <div class="cfg-card-header">
            <div class="icon" style="background:#dcfce7;color:#15803d;"><i class="bi bi-sliders"></i></div>
            <div>
                <h3>Límites del Algoritmo</h3>
                <p>Restricciones que el generador respeta al crear el horario</p>
            </div>
        </div>
        <div class="cfg-card-body">
            <div class="cfg-num-grid">

                {{-- Duración del bloque --}}
                <div class="cfg-num-item">
                    <label><i class="bi bi-clock me-1"></i>Duración del bloque</label>
                    <div class="cfg-range-wrap">
                        <input type="range" name="duracion_bloque" id="rangeDuracion"
                               min="20" max="120" step="5"
                               value="{{ $config['duracion_bloque'] }}"
                               oninput="document.getElementById('valDuracion').textContent = this.value + ' min'">
                        <span class="cfg-range-val" id="valDuracion">{{ $config['duracion_bloque'] }} min</span>
                    </div>
                    <div class="desc">Minutos por bloque horario (20–120)</div>
                </div>

                {{-- Máx. horas/día docente --}}
                <div class="cfg-num-item">
                    <label><i class="bi bi-person-badge me-1"></i>Máx. horas/día docente</label>
                    <div class="cfg-range-wrap">
                        <input type="range" name="max_horas_dia_docente" id="rangeDocente"
                               min="1" max="10" step="1"
                               value="{{ $config['max_horas_dia_docente'] }}"
                               oninput="document.getElementById('valDocente').textContent = this.value + 'h'">
                        <span class="cfg-range-val" id="valDocente">{{ $config['max_horas_dia_docente'] }}h</span>
                    </div>
                    <div class="desc">Bloques máximos que un profesor puede dar en un día (1–10)</div>
                </div>

                {{-- Máx. horas/día grupo --}}
                <div class="cfg-num-item">
                    <label><i class="bi bi-people me-1"></i>Máx. horas/día grupo</label>
                    <div class="cfg-range-wrap">
                        <input type="range" name="max_horas_dia_grupo" id="rangeGrupo"
                               min="1" max="12" step="1"
                               value="{{ $config['max_horas_dia_grupo'] }}"
                               oninput="document.getElementById('valGrupo').textContent = this.value + 'h'">
                        <span class="cfg-range-val" id="valGrupo">{{ $config['max_horas_dia_grupo'] }}h</span>
                    </div>
                    <div class="desc">Bloques máximos de clase por día por grupo (1–12)</div>
                </div>

                {{-- Máx. repetición misma materia/día --}}
                <div class="cfg-num-item">
                    <label><i class="bi bi-arrow-repeat me-1"></i>Repetición misma materia/día</label>
                    <div class="cfg-range-wrap">
                        <input type="range" name="max_misma_materia_dia" id="rangeMateria"
                               min="1" max="4" step="1"
                               value="{{ $config['max_misma_materia_dia'] }}"
                               oninput="document.getElementById('valMateria').textContent = this.value + 'x'">
                        <span class="cfg-range-val" id="valMateria">{{ $config['max_misma_materia_dia'] }}x</span>
                    </div>
                    <div class="desc">Veces que la misma materia puede aparecer en el mismo día por grupo (1–4)</div>
                </div>

            </div>
        </div>
    </div>

</div>
<div class="col-lg-4">

    {{-- ── Módulos opcionales ───────────────────────────────────── --}}
    <div class="cfg-card">
        <div class="cfg-card-header">
            <div class="icon" style="background:#fef9c3;color:#854d0e;"><i class="bi bi-toggles"></i></div>
            <div>
                <h3>Módulos Opcionales</h3>
                <p>Activa o desactiva funcionalidades por tipo de centro</p>
            </div>
        </div>
        <div class="cfg-card-body d-flex flex-column gap-3">

            {{-- Módulo de pagos --}}
            <label class="modulo-toggle {{ $config['modulo_pagos_activo'] ? 'active-toggle' : '' }}" id="labelPagos"
                   onclick="this.classList.toggle('active-toggle')">
                <div class="modulo-info">
                    <div class="modulo-nombre"><i class="bi bi-credit-card me-1" style="color:#6366f1;"></i>Módulo de Pagos</div>
                    <div class="modulo-desc">Cobros, mensualidades y recibos (recomendado: centros privados)</div>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" name="modulo_pagos_activo"
                           id="switchPagos" {{ $config['modulo_pagos_activo'] ? 'checked' : '' }}>
                </div>
            </label>

        </div>
    </div>

    {{-- ── Resumen actual ───────────────────────────────────────── --}}
    <div class="cfg-card">
        <div class="cfg-card-header">
            <div class="icon" style="background:#f0fdf4;color:#15803d;"><i class="bi bi-check2-circle"></i></div>
            <div>
                <h3>Configuración activa</h3>
                <p>Valores que usa el generador actualmente</p>
            </div>
        </div>
        <div class="cfg-card-body">
            <table class="table table-sm mb-0" style="font-size:.78rem;">
                <tbody>
                    <tr>
                        <td class="text-muted">Tipo</td>
                        <td class="fw-semibold text-capitalize">{{ $config['tipo_institucion'] }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Días activos</td>
                        <td class="fw-semibold">{{ count($config['horario_dias']) }} días</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Duración bloque</td>
                        <td class="fw-semibold">{{ $config['duracion_bloque'] }} min</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Máx. horas docente/día</td>
                        <td class="fw-semibold">{{ $config['max_horas_dia_docente'] }}h</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Máx. horas grupo/día</td>
                        <td class="fw-semibold">{{ $config['max_horas_dia_grupo'] }}h</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Rep. materia/día</td>
                        <td class="fw-semibold">{{ $config['max_misma_materia_dia'] }}x</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Módulo pagos</td>
                        <td>
                            @if($config['modulo_pagos_activo'])
                                <span class="badge bg-success" style="font-size:.68rem;">Activo</span>
                            @else
                                <span class="badge bg-secondary" style="font-size:.68rem;">Inactivo</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Nota técnica ─────────────────────────────────────────── --}}
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:1rem;font-size:.76rem;color:#64748b;">
        <div class="d-flex align-items-center gap-2 mb-2" style="font-weight:700;color:#374151;">
            <i class="bi bi-cpu" style="color:#6366f1;"></i>Nota técnica
        </div>
        <ul class="mb-0 ps-3" style="line-height:1.7;">
            <li>Los cambios afectan la <strong>próxima</strong> generación.</li>
            <li>Horarios ya generados no se modifican automáticamente.</li>
            <li>Para ajustes finos usa también <strong>HORARIO_MAX_ITER</strong> en <code>.env</code>.</li>
        </ul>
    </div>

</div>
</div>

{{-- ── Botón guardar ────────────────────────────────────────────── --}}
<div class="d-flex align-items-center gap-3 mt-1 mb-4">
    <button type="submit" class="btn-guardar" id="btnGuardar">
        <i class="bi bi-floppy me-1"></i>Guardar configuración
    </button>
    <a href="{{ route('admin.horarios.index') }}" class="btn btn-sm"
       style="background:#f3f4f6;color:#374151;border-radius:9px;font-size:.84rem;padding:.5rem 1rem;">
        <i class="bi bi-arrow-left me-1"></i>Volver a Horarios
    </a>
</div>

</form>

@endsection

@push('scripts')
<script>
function selectTipo(valor, el) {
    document.querySelectorAll('.tipo-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input[type="radio"]').checked = true;

    const alertPrivado = document.getElementById('alertPrivado');
    if (valor === 'privado') {
        alertPrivado.classList.add('show');
    } else {
        alertPrivado.classList.remove('show');
    }
}

// Validar al menos un día seleccionado
document.getElementById('formConfig').addEventListener('submit', function(e) {
    const checked = document.querySelectorAll('input[name="horario_dias[]"]:checked');
    if (checked.length === 0) {
        e.preventDefault();
        alert('Debes seleccionar al menos un día laboral.');
        return;
    }
    document.getElementById('btnGuardar').disabled = true;
    document.getElementById('btnGuardar').innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';
});
</script>
@endpush
