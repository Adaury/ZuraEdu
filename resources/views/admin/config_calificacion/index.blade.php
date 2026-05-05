@extends('layouts.admin')
@section('page-title', 'Configuración de Notas')

@push('styles')
<style>
    .config-card {
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,.08);
        border: none;
    }
    .comp-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: .85rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        transition: background .15s;
    }
    .comp-row:last-child { border-bottom: none; }
    .comp-row:hover { background: #f8faff; }
    .comp-icon {
        width: 38px; height: 38px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }
    .comp-name {
        flex: 1;
        font-weight: 600;
        font-size: .92rem;
        color: #1e293b;
    }
    .comp-subname {
        font-size: .75rem;
        color: #9ca3af;
        font-weight: 400;
    }
    .peso-input-wrap {
        display: flex;
        align-items: center;
        gap: .35rem;
    }
    .peso-input {
        width: 90px;
        text-align: center;
        font-weight: 700;
        font-size: .95rem;
        border-radius: 8px;
        border: 2px solid #e2e8f0;
        padding: .42rem .6rem;
        transition: border-color .15s, box-shadow .15s;
    }
    .peso-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30,58,110,.12);
    }
    .peso-pct {
        font-weight: 700;
        color: #64748b;
        font-size: .9rem;
    }

    .total-bar {
        border-radius: 10px;
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: background .2s;
        margin-top: 1.25rem;
    }
    .total-bar.ok     { background: #dcfce7; border: 2px solid #86efac; }
    .total-bar.bad    { background: #fee2e2; border: 2px solid #fca5a5; }
    .total-bar.warn   { background: #fef3c7; border: 2px solid #fde68a; }

    .total-label { font-weight: 700; font-size: .88rem; }
    .total-value { font-size: 1.35rem; font-weight: 900; }

    .bar-visual {
        height: 8px;
        border-radius: 4px;
        background: #e2e8f0;
        overflow: hidden;
        margin-top: .5rem;
    }
    .bar-fill {
        height: 100%;
        border-radius: 4px;
        transition: width .3s, background .3s;
    }
    .switch-label {
        display: flex;
        align-items: center;
        cursor: pointer;
        gap: .4rem;
        font-size: .82rem;
        color: #64748b;
        font-weight: 500;
    }
    .form-check-input { cursor: pointer; }

    [data-theme="dark"] .comp-row { border-bottom-color: #334155; }
    [data-theme="dark"] .comp-row:hover { background: #162032; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.82rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Inicio</a></li>
        <li class="breadcrumb-item active">Config. Notas</li>
    </ol>
</nav>

{{-- Page header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:var(--primary);">
            <i class="bi bi-sliders me-2"></i>Configuración de Pesos — Calificaciones
        </h4>
        <p class="text-muted mb-0" style="font-size:.86rem;">
            Define el peso porcentual de cada componente de evaluación. Los pesos deben sumar exactamente 100%.
        </p>
    </div>
</div>

<div class="row g-4">

    {{-- Left: config form --}}
    <div class="col-lg-8">
        <div class="card config-card">
            <div class="card-header border-0 pb-0 pt-4 px-4" style="background:transparent;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-0" style="color:var(--primary);">Pesos por Componente</h6>
                        <p class="text-muted mb-0" style="font-size:.78rem;">Año escolar: <strong>{{ $schoolYear?->nombre ?? 'N/A' }}</strong></p>
                    </div>
                    {{-- Year switcher --}}
                    @if($schoolYears->count() > 1)
                    <form method="GET" action="{{ route('admin.config.calificacion') }}" class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0 text-muted" style="font-size:.78rem;white-space:nowrap;">Cambiar año:</label>
                        <select name="year_id" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                            @foreach($schoolYears as $sy)
                                <option value="{{ $sy->id }}" {{ $sy->id === $schoolYear?->id ? 'selected' : '' }}>
                                    {{ $sy->nombre }} {{ $sy->activo ? '(Activo)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                    @endif
                </div>
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible mx-4 mt-3 mb-0" style="border-radius:8px;">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if($errors->has('total'))
            <div class="alert alert-danger alert-dismissible mx-4 mt-3 mb-0" style="border-radius:8px;">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first('total') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <div class="card-body pt-3 px-0 pb-0">
                <form method="POST" action="{{ route('admin.config.calificacion.update') }}" id="form-config">
                    @csrf
                    <input type="hidden" name="school_year_id" value="{{ $schoolYear?->id }}">

                    @php
                        $componentesInfo = [
                            'tareas'        => ['label' => 'Tareas',        'sub' => 'Trabajos en casa / deberes',    'icon' => 'bi-pencil-square',  'color' => '#dbeafe', 'ic' => '#1d4ed8'],
                            'practicas'     => ['label' => 'Prácticas',     'sub' => 'Trabajos prácticos en clase',   'icon' => 'bi-tools',          'color' => '#dcfce7', 'ic' => '#15803d'],
                            'participacion' => ['label' => 'Participación', 'sub' => 'Intervención oral y actitudinal','icon' => 'bi-hand-index-thumb','color' => '#fef3c7', 'ic' => '#92400e'],
                            'proyecto'      => ['label' => 'Proyecto',      'sub' => 'Proyectos y trabajos grupales', 'icon' => 'bi-lightbulb',      'color' => '#ede9fe', 'ic' => '#7c3aed'],
                            'examen'        => ['label' => 'Examen',        'sub' => 'Evaluación formal escrita',     'icon' => 'bi-file-earmark-check','color' => '#fee2e2', 'ic' => '#991b1b'],
                        ];
                    @endphp

                    @foreach($componentesInfo as $comp => $info)
                    @php $cfg = $configs[$comp] ?? null; @endphp
                    <div class="comp-row">
                        <div class="comp-icon" style="background:{{ $info['color'] }};color:{{ $info['ic'] }};">
                            <i class="bi {{ $info['icon'] }}"></i>
                        </div>
                        <div class="comp-name">
                            {{ $info['label'] }}
                            <div class="comp-subname">{{ $info['sub'] }}</div>
                        </div>
                        <div class="peso-input-wrap">
                            <input type="number"
                                   name="componentes[{{ $comp }}][peso]"
                                   class="form-control peso-input peso-field"
                                   id="peso_{{ $comp }}"
                                   min="0" max="100" step="0.5"
                                   value="{{ old("componentes.{$comp}.peso", $cfg?->peso ?? 0) }}"
                                   oninput="recalcTotal()">
                            <span class="peso-pct">%</span>
                        </div>
                        <label class="switch-label ms-3">
                            <input type="checkbox"
                                   name="componentes[{{ $comp }}][activo]"
                                   class="form-check-input"
                                   {{ old("componentes.{$comp}.activo", $cfg?->activo ?? true) ? 'checked' : '' }}>
                            Activo
                        </label>
                    </div>
                    @endforeach

                    {{-- Total bar --}}
                    <div class="px-4 pb-4">
                        <div id="total-bar" class="total-bar warn">
                            <div>
                                <div class="total-label">Total de pesos</div>
                                <div class="bar-visual mt-2" style="width:200px;">
                                    <div class="bar-fill" id="bar-fill" style="width:0%;background:#f59e0b;"></div>
                                </div>
                            </div>
                            <div class="total-value" id="total-display">0%</div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" id="btn-save" class="btn btn-primary px-5 fw-bold" disabled>
                                <i class="bi bi-floppy me-2"></i>Guardar Configuración
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Right: info panel --}}
    <div class="col-lg-4">
        <div class="card config-card">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3" style="color:var(--primary);">
                    <i class="bi bi-info-circle me-2"></i>¿Cómo funciona?
                </h6>
                <ul class="list-unstyled" style="font-size:.85rem;color:#475569;line-height:1.7;">
                    <li class="mb-2"><i class="bi bi-check2 me-2 text-success"></i>Los pesos determinan la ponderación de cada componente en la nota final.</li>
                    <li class="mb-2"><i class="bi bi-check2 me-2 text-success"></i>La suma de todos los pesos activos debe ser exactamente <strong>100%</strong>.</li>
                    <li class="mb-2"><i class="bi bi-check2 me-2 text-success"></i>Los componentes inactivos no se consideran en el cálculo.</li>
                    <li class="mb-2"><i class="bi bi-check2 me-2 text-success"></i>Los cambios aplican a las nuevas entradas de calificaciones.</li>
                </ul>

                <hr class="my-3">

                <h6 class="fw-bold mb-3" style="color:var(--primary);">
                    <i class="bi bi-award me-2"></i>Escala de indicadores
                </h6>
                <div class="d-flex flex-column gap-2" style="font-size:.83rem;">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 rounded" style="background:#dcfce7;">
                        <span class="fw-semibold" style="color:#15803d;">Excelente</span>
                        <span style="color:#15803d;">90 – 100</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 rounded" style="background:#dbeafe;">
                        <span class="fw-semibold" style="color:#1d4ed8;">Bueno</span>
                        <span style="color:#1d4ed8;">75 – 89</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 rounded" style="background:#fef3c7;">
                        <span class="fw-semibold" style="color:#92400e;">En proceso</span>
                        <span style="color:#92400e;">60 – 74</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 rounded" style="background:#fee2e2;">
                        <span class="fw-semibold" style="color:#991b1b;">Insuficiente</span>
                        <span style="color:#991b1b;">0 – 59</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
function recalcTotal() {
    let total = 0;
    document.querySelectorAll('.peso-field').forEach(inp => {
        const v = parseFloat(inp.value) || 0;
        total += v;
    });
    total = Math.round(total * 100) / 100;

    const displayEl = document.getElementById('total-display');
    const barEl     = document.getElementById('total-bar');
    const fillEl    = document.getElementById('bar-fill');
    const btnSave   = document.getElementById('btn-save');

    displayEl.textContent = total + '%';
    fillEl.style.width = Math.min(total, 100) + '%';

    const diff = Math.abs(total - 100);

    if (diff <= 0.01) {
        barEl.className = 'total-bar ok';
        fillEl.style.background = '#22c55e';
        displayEl.style.color   = '#15803d';
        btnSave.disabled = false;
    } else if (total > 100) {
        barEl.className = 'total-bar bad';
        fillEl.style.background = '#ef4444';
        displayEl.style.color   = '#991b1b';
        btnSave.disabled = true;
    } else {
        barEl.className = 'total-bar warn';
        fillEl.style.background = '#f59e0b';
        displayEl.style.color   = '#92400e';
        btnSave.disabled = true;
    }
}

document.addEventListener('DOMContentLoaded', recalcTotal);
</script>
@endpush
