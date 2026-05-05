@extends('layouts.admin')
@section('page-title', 'Nueva Evaluación Docente')

@push('styles')
<style>
    .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
    .page-header h1 { font-size:1.45rem; font-weight:800; color:var(--primary); margin:0; }

    /* Criterio card */
    .criterio-card {
        background:#f8faff;
        border:1px solid #e2e8f0;
        border-radius:12px;
        padding:1rem 1.25rem;
        margin-bottom:.75rem;
        transition:border-color .2s;
    }
    .criterio-card:focus-within { border-color:var(--primary); background:#eff6ff; }
    .criterio-label { font-weight:700; font-size:.9rem; color:#1e293b; margin-bottom:.6rem; display:block; }
    .criterio-desc  { font-size:.78rem; color:#6b7280; margin-bottom:.75rem; }

    /* Radio stars */
    .star-group { display:flex; gap:.5rem; align-items:center; }
    .star-group input[type="radio"] { display:none; }
    .star-group label {
        font-size:1.6rem;
        color:#d1d5db;
        cursor:pointer;
        line-height:1;
        transition:color .15s, transform .1s;
    }
    .star-group label:hover,
    .star-group label.active { color:#f59e0b; }
    .star-group label:hover { transform:scale(1.15); }

    .score-badge {
        display:inline-block;
        min-width:38px;
        text-align:center;
        font-size:1.1rem;
        font-weight:800;
        color:var(--primary);
        background:#dbeafe;
        border-radius:8px;
        padding:.15rem .5rem;
        margin-left:.75rem;
    }

    /* Promedio preview */
    .promedio-preview {
        background: linear-gradient(135deg,#1e40af,#3b82f6);
        color:#fff;
        border-radius:16px;
        padding:1.25rem 1.75rem;
        text-align:center;
    }
    .promedio-preview .num { font-size:2.8rem; font-weight:900; line-height:1; }
    .promedio-preview .lbl { font-size:.82rem; opacity:.85; margin-top:.25rem; }
    .promedio-preview .nivel-tag {
        display:inline-block;
        background:rgba(255,255,255,.2);
        border-radius:20px;
        padding:.2rem .8rem;
        font-size:.8rem;
        font-weight:700;
        margin-top:.5rem;
    }

    [data-theme="dark"] .criterio-card { background:#1e293b; border-color:#334155; }
    [data-theme="dark"] .criterio-card:focus-within { border-color:#3b82f6; background:#172554; }
    [data-theme="dark"] .criterio-label { color:#f1f5f9; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1><i class="bi bi-clipboard2-plus me-2" style="color:var(--secondary);"></i>Nueva Evaluación</h1>
        <p class="text-muted mb-0" style="font-size:.85rem;">Evaluación de desempeño docente</p>
    </div>
    <a href="{{ route('admin.evaluaciones-docentes.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Corrige los siguientes errores:</strong>
    <ul class="mb-0 mt-1">
        @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form method="POST" action="{{ route('admin.evaluaciones-docentes.store') }}" id="formEval">
@csrf

<div class="row g-4">

    {{-- Columna izquierda: datos y criterios --}}
    <div class="col-lg-8">

        {{-- Datos generales --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
            <div class="card-header border-0 pb-0 pt-4 px-4">
                <h6 class="fw-700 mb-0" style="color:var(--primary);font-size:.95rem;">
                    <i class="bi bi-person-badge me-2"></i>Datos Generales
                </h6>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-600" style="font-size:.85rem;">Docente <span class="text-danger">*</span></label>
                        <select name="docente_id" id="docente_id" class="form-select @error('docente_id') is-invalid @enderror" required style="border-radius:8px;">
                            <option value="">Seleccionar docente…</option>
                            @foreach($docentes as $d)
                                <option value="{{ $d->id }}"
                                    {{ (old('docente_id', $docentePresel?->id) == $d->id) ? 'selected' : '' }}>
                                    {{ $d->nombre_completo }}
                                    @if($d->especialidad) — {{ $d->especialidad }} @endif
                                </option>
                            @endforeach
                        </select>
                        @error('docente_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-600" style="font-size:.85rem;">Período Evaluado <span class="text-danger">*</span></label>
                        <input type="text" name="periodo_evaluado"
                               value="{{ old('periodo_evaluado') }}"
                               placeholder="Ej: 2024-2025 · 1er Trim."
                               class="form-control @error('periodo_evaluado') is-invalid @enderror"
                               required style="border-radius:8px;">
                        @error('periodo_evaluado')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Criterios de evaluación --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
            <div class="card-header border-0 pb-0 pt-4 px-4">
                <h6 class="fw-700 mb-0" style="color:var(--primary);font-size:.95rem;">
                    <i class="bi bi-star-half me-2"></i>Criterios de Evaluación
                </h6>
                <p class="text-muted mb-0 mt-1" style="font-size:.78rem;">Selecciona una puntuación del 1 al 5 por cada criterio (5 = Excelente)</p>
            </div>
            <div class="card-body px-4 pb-4 pt-3">

                @php
                $criterios = [
                    ['name' => 'puntualidad',          'label' => 'Puntualidad y Asistencia',     'desc' => 'Cumplimiento de horarios, asistencia regular y puntualidad al inicio de clases.'],
                    ['name' => 'dominio_contenido',     'label' => 'Dominio del Contenido',        'desc' => 'Conocimiento profundo de la asignatura que imparte y manejo de conceptos clave.'],
                    ['name' => 'metodologia',           'label' => 'Metodología de Enseñanza',     'desc' => 'Estrategias didácticas, uso de recursos y dinamismo en la clase.'],
                    ['name' => 'relacion_estudiantes',  'label' => 'Relación con Estudiantes',     'desc' => 'Empatía, comunicación efectiva y trato respetuoso con el alumnado.'],
                    ['name' => 'planificacion',         'label' => 'Planificación Docente',        'desc' => 'Organización de contenidos, elaboración de planes y cumplimiento curricular.'],
                ];
                @endphp

                @foreach($criterios as $crit)
                @php $oldVal = old($crit['name'], 0); @endphp
                <div class="criterio-card" x-data="{ score: {{ $oldVal }} }">
                    <span class="criterio-label">{{ $crit['label'] }}</span>
                    <p class="criterio-desc mb-2">{{ $crit['desc'] }}</p>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <div class="star-group" id="stars_{{ $crit['name'] }}">
                            @for($i = 1; $i <= 5; $i++)
                                <input type="radio"
                                       name="{{ $crit['name'] }}"
                                       id="{{ $crit['name'] }}_{{ $i }}"
                                       value="{{ $i }}"
                                       {{ $oldVal == $i ? 'checked' : '' }}
                                       required>
                                <label for="{{ $crit['name'] }}_{{ $i }}"
                                       class="{{ $oldVal >= $i ? 'active' : '' }}"
                                       title="{{ $i }}: {{ ['','Deficiente','Regular','Bueno','Muy Bueno','Excelente'][$i] }}">
                                    &#9733;
                                </label>
                            @endfor
                        </div>
                        <span class="score-badge" id="badge_{{ $crit['name'] }}">
                            {{ $oldVal ?: '—' }}
                        </span>
                        <small class="text-muted" id="lbl_{{ $crit['name'] }}" style="font-size:.75rem;">
                            {{ $oldVal ? ['','Deficiente','Regular','Bueno','Muy Bueno','Excelente'][$oldVal] : '' }}
                        </small>
                    </div>
                    @error($crit['name'])
                        <div class="text-danger mt-1" style="font-size:.8rem;">{{ $message }}</div>
                    @enderror
                </div>
                @endforeach

            </div>
        </div>

        {{-- Observaciones --}}
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-header border-0 pb-0 pt-4 px-4">
                <h6 class="fw-700 mb-0" style="color:var(--primary);font-size:.95rem;">
                    <i class="bi bi-chat-square-text me-2"></i>Observaciones
                </h6>
            </div>
            <div class="card-body px-4 pb-4">
                <textarea name="observaciones" rows="4"
                          class="form-control @error('observaciones') is-invalid @enderror"
                          placeholder="Comentarios adicionales sobre el desempeño del docente…"
                          style="border-radius:8px;font-size:.9rem;">{{ old('observaciones') }}</textarea>
                @error('observaciones')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

    </div>

    {{-- Columna derecha: preview del promedio --}}
    <div class="col-lg-4">
        <div class="sticky-top" style="top:80px;">

            {{-- Promedio en tiempo real --}}
            <div class="promedio-preview mb-4">
                <div class="lbl" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.5rem;opacity:.7;">Promedio Calculado</div>
                <div class="num" id="previewPromedio">—</div>
                <div class="nivel-tag" id="previewNivel">Sin puntuaciones</div>
            </div>

            {{-- Escala de referencia --}}
            <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
                <div class="card-body px-4 py-3">
                    <p class="fw-700 mb-2" style="font-size:.82rem;color:#374151;text-transform:uppercase;letter-spacing:.04em;">Escala de Niveles</p>
                    @foreach([
                        ['Excelente',  '≥ 4.5', '#dcfce7', '#166534'],
                        ['Bueno',      '≥ 3.5', '#dbeafe', '#1e40af'],
                        ['Regular',    '≥ 2.5', '#fef9c3', '#854d0e'],
                        ['Deficiente', '< 2.5', '#fee2e2', '#991b1b'],
                    ] as [$lbl, $rng, $bg, $txt])
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span style="font-size:.82rem;font-weight:600;background:{{ $bg }};color:{{ $txt }};padding:.2rem .6rem;border-radius:20px;">{{ $lbl }}</span>
                        <span style="font-size:.8rem;color:#6b7280;">{{ $rng }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Escala puntaje --}}
            <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
                <div class="card-body px-4 py-3">
                    <p class="fw-700 mb-2" style="font-size:.82rem;color:#374151;text-transform:uppercase;letter-spacing:.04em;">Puntaje por Estrella</p>
                    @foreach([1=>'Deficiente',2=>'Regular',3=>'Bueno',4=>'Muy Bueno',5=>'Excelente'] as $n => $lbl)
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span style="font-size:1rem;color:#f59e0b;">{{ str_repeat('★', $n) }}{{ str_repeat('☆', 5-$n) }}</span>
                        <span style="font-size:.78rem;color:#6b7280;">{{ $lbl }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="btn w-100 py-2 fw-700"
                    style="background:var(--primary);color:#fff;border-radius:10px;font-size:.95rem;">
                <i class="bi bi-save me-2"></i>Guardar Evaluación
            </button>
        </div>
    </div>

</div>
</form>

@endsection

@push('scripts')
<script>
(function () {
    const criterios = ['puntualidad','dominio_contenido','metodologia','relacion_estudiantes','planificacion'];
    const niveles   = ['','Deficiente','Regular','Bueno','Muy Bueno','Excelente'];
    const nivText   = {5:'Excelente',4.5:'Excelente',4:'Bueno',3.5:'Bueno',3:'Regular',2.5:'Regular',0:'Deficiente'};

    function getNivel(p) {
        if (p >= 4.5) return 'Excelente';
        if (p >= 3.5) return 'Bueno';
        if (p >= 2.5) return 'Regular';
        return 'Deficiente';
    }

    function updateStars(name, val) {
        const labels = document.querySelectorAll(`#stars_${name} label`);
        labels.forEach((lbl, i) => {
            lbl.classList.toggle('active', i < val);
        });
        const badge = document.getElementById(`badge_${name}`);
        const lbl2  = document.getElementById(`lbl_${name}`);
        if (badge) badge.textContent = val || '—';
        if (lbl2)  lbl2.textContent  = val ? niveles[val] : '';
    }

    function updatePromedio() {
        let sum = 0, count = 0;
        criterios.forEach(c => {
            const checked = document.querySelector(`input[name="${c}"]:checked`);
            if (checked) { sum += parseInt(checked.value); count++; }
        });

        const previewNum  = document.getElementById('previewPromedio');
        const previewNiv  = document.getElementById('previewNivel');

        if (count === 0) {
            previewNum.textContent = '—';
            previewNiv.textContent = 'Sin puntuaciones';
            return;
        }

        const avg = sum / criterios.length; // siempre sobre 5 criterios
        previewNum.textContent = avg.toFixed(2);
        previewNiv.textContent = count < criterios.length
            ? `${count}/5 criterios evaluados`
            : getNivel(avg);
    }

    // Inicializar y enlazar eventos
    criterios.forEach(name => {
        const radios = document.querySelectorAll(`input[name="${name}"]`);
        radios.forEach(radio => {
            radio.addEventListener('change', () => {
                updateStars(name, parseInt(radio.value));
                updatePromedio();
            });
        });
        // Estado inicial (valores old())
        const checked = document.querySelector(`input[name="${name}"]:checked`);
        if (checked) updateStars(name, parseInt(checked.value));
    });

    updatePromedio();
})();
</script>
@endpush
