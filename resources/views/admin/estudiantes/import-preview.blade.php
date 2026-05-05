@extends('layouts.admin')
@section('page-title', 'Previsualizar Importación')

@push('styles')
<style>
    .prev-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    /* ── Tabs ─────────────────────────────────────────────────── */
    .hoja-tabs {
        display: flex;
        gap: 0;
        background: #f8fafc;
        border-bottom: 2px solid #e5e7eb;
        overflow-x: auto;
        scrollbar-width: thin;
    }
    .hoja-tab {
        flex-shrink: 0;
        padding: .65rem 1.1rem;
        font-size: .8rem;
        font-weight: 700;
        cursor: pointer;
        border: none;
        background: transparent;
        color: #6b7280;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        transition: color .15s, border-color .15s, background .15s;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: .4rem;
    }
    .hoja-tab:hover { color: var(--primary); background: #f0f4fb; }
    .hoja-tab.active { color: var(--primary); border-bottom-color: var(--primary); background: #fff; }

    .tab-count {
        background: #e5e7eb;
        color: #374151;
        font-size: .67rem;
        padding: .1rem .38rem;
        border-radius: 20px;
        font-weight: 700;
    }
    .hoja-tab.active .tab-count { background: var(--primary); color: #fff; }

    .tab-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #d1d5db;
        flex-shrink: 0;
    }
    .hoja-tab.estado-ok     .tab-dot { background: #22c55e; }
    .hoja-tab.estado-crear  .tab-dot { background: #3b82f6; }
    .hoja-tab.estado-warn   .tab-dot { background: #f59e0b; }

    /* ── Panel ────────────────────────────────────────────────── */
    .hoja-panel { display: none; padding: 1.25rem 1.5rem; }
    .hoja-panel.active { display: block; }

    /* ── Barra de estado del grupo ────────────────────────────── */
    .grupo-bar {
        border-radius: 10px;
        padding: .85rem 1rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: .75rem;
        flex-wrap: wrap;
        font-size: .84rem;
        font-weight: 600;
    }
    .grupo-bar.ok    { background: #f0fdf4; border: 1.5px solid #86efac; color: #15803d; }
    .grupo-bar.crear { background: #eff6ff; border: 1.5px solid #93c5fd; color: #1d4ed8; }
    .grupo-bar.warn  { background: #fffbeb; border: 1.5px solid #fcd34d; color: #92400e; }

    /* ── Tabla ────────────────────────────────────────────────── */
    .est-table th {
        background: var(--primary);
        color: #fff;
        font-size: .76rem;
        font-weight: 600;
        white-space: nowrap;
        padding: .45rem .6rem;
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .est-table td {
        font-size: .8rem;
        vertical-align: middle;
        padding: .38rem .6rem;
    }
    .est-table tbody tr:nth-child(even) { background: #f8fafc; }
    .est-table tbody tr:hover { background: #eff6ff; }

    /* ── Barra de confirmación ────────────────────────────────── */
    .confirm-bar {
        background: #f8fafc;
        border-top: 2px solid #e5e7eb;
        padding: .9rem 1.5rem;
        display: flex;
        align-items: center;
        gap: .75rem;
        flex-wrap: wrap;
    }
    .resumen-chips { display: flex; flex-wrap: wrap; gap: .35rem; }
    .chip {
        font-size: .72rem;
        font-weight: 700;
        padding: .2rem .55rem;
        border-radius: 20px;
        display: flex;
        align-items: center;
        gap: .3rem;
    }
    .chip.ok    { background: #dcfce7; color: #166534; }
    .chip.crear { background: #dbeafe; color: #1d4ed8; }
    .chip.warn  { background: #fef3c7; color: #92400e; }
    [data-theme="dark"] .prev-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .hoja-tabs { background: #162032; border-bottom-color: #334155; }
    [data-theme="dark"] .hoja-tab { color: #64748b; }
    [data-theme="dark"] .hoja-tab:hover { background: #1e293b; color: #93c5fd; }
    [data-theme="dark"] .hoja-tab.active { background: #1e293b; color: #93c5fd; }
    [data-theme="dark"] .chip.warn { background: #1c1000; color: #fcd34d; }
</style>
@endpush

@section('content')

@php
    $backParams  = array_filter(['ciclo' => $ciclo ?? null, 'area' => $area ?? null]);
    $totalEst    = collect($hojas)->sum(fn($h) => count($h['filas']));
    $cntOk       = collect($analisis)->filter(fn($a) => $a['grupo'] !== null)->count();
    $cntCrear    = collect($analisis)->filter(fn($a) => $a['grupo'] === null && $a['necesita_crear'])->count();
    $cntSinDetec = collect($analisis)->filter(fn($a) => $a['grado'] === null)->count();
@endphp

{{-- Encabezado --}}
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.estudiantes.import', $backParams) }}"
       class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <div>
        <h1 class="mb-0" style="font-size:1.3rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-eye me-2" style="color:var(--secondary);"></i>Confirmar Importación
        </h1>
        <p class="text-muted mb-0 mt-1" style="font-size:.78rem;">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>
            <strong>{{ $origName }}</strong> &mdash;
            <strong>{{ count($hojas) }}</strong> {{ count($hojas) === 1 ? 'sección' : 'secciones' }} &mdash;
            <strong>{{ $totalEst }}</strong> {{ $totalEst === 1 ? 'estudiante' : 'estudiantes' }}
            @if(isset($schoolYear) && $schoolYear)
                &mdash; Año: <strong>{{ $schoolYear->nombre }}</strong>
            @endif
        </p>
    </div>

    {{-- Resumen rápido --}}
    <div class="ms-auto d-flex gap-2 align-items-center flex-wrap">
        @if($cntOk > 0)
            <span class="chip ok"><i class="bi bi-check-circle-fill"></i>{{ $cntOk }} grupo{{ $cntOk !== 1 ? 's' : '' }} existente{{ $cntOk !== 1 ? 's' : '' }}</span>
        @endif
        @if($cntCrear > 0)
            <span class="chip crear"><i class="bi bi-plus-circle-fill"></i>{{ $cntCrear }} se creará{{ $cntCrear !== 1 ? 'n' : '' }}</span>
        @endif
        @if($cntSinDetec > 0)
            <span class="chip warn"><i class="bi bi-exclamation-triangle-fill"></i>{{ $cntSinDetec }} sin detectar</span>
        @endif
    </div>
</div>

{{-- Aviso si hay hojas no detectadas --}}
@if($cntSinDetec > 0)
<div class="alert d-flex align-items-center gap-2 mb-3"
     style="background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;font-size:.83rem;">
    <i class="bi bi-exclamation-triangle-fill" style="color:#d97706;font-size:1.1rem;flex-shrink:0;"></i>
    <div>
        <strong>{{ $cntSinDetec }} hoja(s) sin grupo detectado.</strong>
        Los estudiantes de esas hojas serán importados pero <strong>no tendrán matrícula</strong>.
        Asegúrate de que el nombre de la hoja incluya el grado y la sección (ej: <em>Primero A</em>, <em>3ro B</em>).
    </div>
</div>
@endif

{{-- Formulario de confirmación --}}
<form method="POST" action="{{ route('admin.estudiantes.importConfirm') }}" id="confirmForm">
    @csrf
    <input type="hidden" name="temp_path" value="{{ $tempPath }}">
    <input type="hidden" name="extension" value="{{ $extension }}">
    @if(!empty($ciclo))<input type="hidden" name="ciclo" value="{{ $ciclo }}">@endif
    @if(!empty($area)) <input type="hidden" name="area"  value="{{ $area }}">@endif

    <div class="prev-card shadow-sm">

        {{-- ── TABS ──────────────────────────────────────────── --}}
        <div class="hoja-tabs" id="hojaTabs">
            @foreach($hojas as $idx => $hoja)
                @php
                    $an    = $analisis[$idx] ?? ['grado' => null, 'necesita_crear' => false, 'grupo' => null];
                    $clase = $an['grupo'] ? 'estado-ok' : ($an['necesita_crear'] ? 'estado-crear' : 'estado-warn');
                @endphp
                <button type="button"
                        class="hoja-tab {{ $idx === 0 ? 'active' : '' }} {{ $clase }}"
                        id="tab-{{ $idx }}"
                        data-tab="{{ $idx }}"
                        onclick="mostrarHoja({{ $idx }})">
                    <span class="tab-dot"></span>
                    {{ $hoja['nombre'] }}
                    <span class="tab-count">{{ count($hoja['filas']) }}</span>
                </button>
            @endforeach
        </div>

        {{-- ── PANELES ─────────────────────────────────────────── --}}
        @foreach($hojas as $idx => $hoja)
            @php
                $filas       = $hoja['filas'];
                $an          = $analisis[$idx] ?? ['grado' => null, 'seccion' => null, 'grupo' => null, 'necesita_crear' => false, 'label' => ''];
                $primera     = $filas[0] ?? [];
                $tieneCedula = array_key_exists('cedula',   $primera);
                $tieneSexo   = array_key_exists('sexo',     $primera);
                $tieneSecCol = array_key_exists('_seccion', $primera);
            @endphp

            <div class="hoja-panel {{ $idx === 0 ? 'active' : '' }}" id="panel-{{ $idx }}">

                {{-- Barra de estado del grupo --}}
                @if(isset($schoolYear) && $schoolYear)
                    @if($an['grupo'])
                        <div class="grupo-bar ok">
                            <i class="bi bi-check-circle-fill" style="font-size:1.1rem;flex-shrink:0;"></i>
                            <div>
                                <div>Grupo existente: <strong>{{ $an['label'] }}</strong></div>
                                <div style="font-size:.76rem;font-weight:400;opacity:.85;margin-top:.1rem;">
                                    Los estudiantes se matricularán en este grupo.
                                </div>
                            </div>
                        </div>
                    @elseif($an['necesita_crear'])
                        <div class="grupo-bar crear">
                            <i class="bi bi-plus-circle-fill" style="font-size:1.1rem;flex-shrink:0;"></i>
                            <div>
                                <div>Se creará automáticamente: <strong>{{ $an['label'] }}</strong></div>
                                <div style="font-size:.76rem;font-weight:400;opacity:.85;margin-top:.1rem;">
                                    El grupo no existe todavía y será creado al confirmar la importación.
                                </div>
                            </div>
                        </div>
                    @else
                        @php
                            $gruposOrdenados = ($gruposExistentes ?? collect())
                                ->sortBy(fn($g) => ($g->grado->nivel ?? 99) . ($g->seccion->nombre ?? ''));
                        @endphp
                        <div class="grupo-bar warn" style="flex-direction:column;align-items:flex-start;gap:.65rem;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-exclamation-triangle-fill" style="font-size:1.1rem;flex-shrink:0;"></i>
                                <strong>No se pudo detectar el grupo automáticamente</strong>
                            </div>

                            {{-- Opción A: asignar a grupo existente --}}
                            <div style="font-size:.82rem;font-weight:600;width:100%;">
                                Asignar a grupo existente:
                                <select name="grupo_manual[{{ $idx }}]"
                                        id="grupoManual_{{ $idx }}"
                                        class="form-select form-select-sm mt-1"
                                        style="max-width:360px;border-color:#fcd34d;"
                                        onchange="toggleNuevoGrupo({{ $idx }}, this.value)">
                                    <option value="">— Sin asignar —</option>
                                    @foreach($gruposOrdenados as $gm)
                                        <option value="{{ $gm->id }}">
                                            {{ $gm->grado->nombre ?? 'Grado' }} — Sección {{ $gm->seccion->nombre ?? '?' }}
                                        </option>
                                    @endforeach
                                    <option value="__nuevo__">✚ Crear nuevo grupo...</option>
                                </select>
                            </div>

                            {{-- Opción B: crear nuevo grupo --}}
                            <div id="nuevoGrupoForm_{{ $idx }}" style="display:none;width:100%;">
                                <div style="font-size:.82rem;font-weight:600;margin-bottom:.4rem;">
                                    <i class="bi bi-plus-circle me-1"></i>Datos del nuevo grupo:
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <select name="nuevo_grado[{{ $idx }}]" class="form-select form-select-sm" style="max-width:200px;">
                                        <option value="">— Grado —</option>
                                        @foreach(($grados ?? collect()) as $gr)
                                            <option value="{{ $gr->id }}">{{ $gr->nombre }}</option>
                                        @endforeach
                                    </select>
                                    <select name="nuevo_seccion[{{ $idx }}]" class="form-select form-select-sm" style="max-width:140px;">
                                        <option value="">— Sección —</option>
                                        @foreach(($secciones ?? collect()) as $sc)
                                            <option value="{{ $sc->id }}">{{ $sc->nombre }}</option>
                                        @endforeach
                                        <option value="__nueva__">Nueva letra...</option>
                                    </select>
                                    <input type="text" name="nuevo_seccion_nombre[{{ $idx }}]"
                                           id="nuevaSecNombre_{{ $idx }}"
                                           class="form-control form-control-sm"
                                           placeholder="Letra (ej: D)"
                                           style="max-width:100px;display:none;"
                                           maxlength="3">
                                </div>
                                <div class="mt-1" style="font-size:.75rem;color:#92400e;">
                                    El grupo se creará automáticamente al confirmar la importación.
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                {{-- Tabla de estudiantes --}}
                <div class="table-responsive" style="max-height:430px;overflow-y:auto;border-radius:8px;border:1px solid #e5e7eb;">
                    <table class="table table-sm table-bordered est-table mb-0">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                @if($tieneSecCol)<th style="width:65px;">Sec.</th>@endif
                                <th>Apellidos</th>
                                <th>Nombres</th>
                                @if($tieneCedula)<th>Cédula</th>@endif
                                @if($tieneSexo)<th style="width:52px;text-align:center;">Sexo</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($filas as $n => $fila)
                                <tr>
                                    <td class="text-muted" style="font-size:.72rem;text-align:center;">{{ $n + 1 }}</td>
                                    @if($tieneSecCol)
                                        <td style="text-align:center;">
                                            <span style="background:var(--primary);color:#fff;padding:.1rem .4rem;border-radius:5px;font-size:.72rem;font-weight:700;">
                                                {{ strtoupper($fila['_seccion'] ?? '') }}
                                            </span>
                                        </td>
                                    @endif
                                    <td class="fw-semibold">{{ $fila['apellidos'] ?? '' }}</td>
                                    <td>{{ $fila['nombres'] ?? '' }}</td>
                                    @if($tieneCedula)
                                        <td class="text-muted" style="font-size:.76rem;">{{ $fila['cedula'] ?? '' ?: '—' }}</td>
                                    @endif
                                    @if($tieneSexo)
                                        <td style="text-align:center;">
                                            @php $sx = strtoupper($fila['sexo'] ?? ''); @endphp
                                            @if($sx === 'F')
                                                <span style="color:#ec4899;font-weight:800;font-size:.78rem;">F</span>
                                            @else
                                                <span style="color:#3b82f6;font-weight:800;font-size:.78rem;">M</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="text-muted mt-2 mb-0" style="font-size:.74rem;">
                    <strong>{{ count($filas) }}</strong>
                    estudiante{{ count($filas) !== 1 ? 's' : '' }} en la hoja
                    <strong>"{{ $hoja['nombre'] }}"</strong>
                </p>

            </div>
        @endforeach

        {{-- ── BARRA DE CONFIRMACIÓN ─────────────────────────── --}}
        <div class="confirm-bar">
            <div>
                <div class="resumen-chips">
                    @foreach($hojas as $idx => $hoja)
                        @php $an = $analisis[$idx] ?? ['grado' => null, 'necesita_crear' => false, 'grupo' => null, 'label' => '']; @endphp
                        @if($an['grupo'])
                            <span class="chip ok">
                                <i class="bi bi-check-circle-fill"></i>
                                {{ $hoja['nombre'] }}: {{ $an['label'] }}
                            </span>
                        @elseif($an['necesita_crear'])
                            <span class="chip crear">
                                <i class="bi bi-plus-circle-fill"></i>
                                {{ $hoja['nombre'] }}: crear {{ $an['label'] }}
                            </span>
                        @else
                            <span class="chip warn">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                {{ $hoja['nombre'] }}: sin grupo
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="ms-auto d-flex gap-2">
                <a href="{{ route('admin.estudiantes.import', $backParams) }}"
                   class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                    <i class="bi bi-x-lg me-1"></i>Cancelar
                </a>
                <button type="submit"
                        class="btn px-4 fw-semibold"
                        style="background:var(--primary);color:#fff;border-radius:8px;"
                        id="confirmBtn">
                    <span id="confirmSpinner" class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                    <i class="bi bi-cloud-arrow-up me-1" id="confirmIcon"></i>
                    Importar {{ $totalEst }} estudiante{{ $totalEst !== 1 ? 's' : '' }}
                </button>
            </div>
        </div>

    </div>
</form>

@endsection

@push('scripts')
<script>
function mostrarHoja(idx) {
    document.querySelectorAll('.hoja-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.hoja-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('panel-' + idx)?.classList.add('active');
    document.getElementById('tab-'   + idx)?.classList.add('active');
}

function toggleNuevoGrupo(idx, val) {
    const form = document.getElementById('nuevoGrupoForm_' + idx);
    if (form) form.style.display = (val === '__nuevo__') ? 'block' : 'none';
}

// Mostrar input de nueva letra de sección cuando se elige "Nueva letra..."
document.addEventListener('change', function(e) {
    if (e.target && e.target.name && e.target.name.startsWith('nuevo_seccion[')) {
        const match = e.target.name.match(/\[(\d+)\]/);
        if (!match) return;
        const idx = match[1];
        const input = document.getElementById('nuevaSecNombre_' + idx);
        if (input) input.style.display = (e.target.value === '__nueva__') ? 'inline-block' : 'none';
    }
});

document.getElementById('confirmForm').addEventListener('submit', function() {
    const btn    = document.getElementById('confirmBtn');
    const spin   = document.getElementById('confirmSpinner');
    const icon   = document.getElementById('confirmIcon');
    btn.disabled = true;
    spin.classList.remove('d-none');
    if (icon) icon.classList.add('d-none');
});
</script>
@endpush
