@extends('layouts.admin')
@section('page-title', 'Confirmar Importación de Docentes')

@push('styles')
<style>
    .prev-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; }

    .hoja-tabs { display:flex; background:#f8fafc; border-bottom:2px solid #e5e7eb; overflow-x:auto; scrollbar-width:thin; }
    .hoja-tab {
        flex-shrink:0; padding:.65rem 1.1rem; font-size:.8rem; font-weight:700; cursor:pointer;
        border:none; background:transparent; color:#6b7280; border-bottom:3px solid transparent;
        margin-bottom:-2px; transition:color .15s,border-color .15s,background .15s;
        white-space:nowrap; display:flex; align-items:center; gap:.4rem;
    }
    .hoja-tab:hover { color:var(--primary); background:#f0f4fb; }
    .hoja-tab.active { color:var(--primary); border-bottom-color:var(--primary); background:#fff; }
    .tab-count { background:#e5e7eb; color:#374151; font-size:.67rem; padding:.1rem .38rem; border-radius:20px; font-weight:700; }
    .hoja-tab.active .tab-count { background:var(--primary); color:#fff; }

    .hoja-panel { display:none; padding:1.25rem 1.5rem; }
    .hoja-panel.active { display:block; }

    .doc-table th {
        background:var(--primary); color:#fff; font-size:.76rem; font-weight:600;
        white-space:nowrap; padding:.45rem .6rem; position:sticky; top:0; z-index:2;
    }
    .doc-table td { font-size:.8rem; vertical-align:middle; padding:.4rem .6rem; }
    .doc-table tbody tr:nth-child(even) { background:#f8fafc; }
    .doc-table tbody tr:hover { background:#eff6ff; }

    .cuenta-badge { font-size:.7rem; padding:.15rem .45rem; border-radius:20px; font-weight:700; white-space:nowrap; }
    .cuenta-ok   { background:#dcfce7; color:#166534; }
    .cuenta-skip { background:#f3f4f6; color:#6b7280; }

    .confirm-bar {
        background:#f8fafc; border-top:2px solid #e5e7eb;
        padding:.9rem 1.5rem; display:flex; align-items:center; gap:.75rem; flex-wrap:wrap;
    }
    [data-theme="dark"] .prev-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .hoja-tabs { background: #162032; border-bottom-color: #334155; }
    [data-theme="dark"] .hoja-tab { color: #64748b; }
    [data-theme="dark"] .hoja-tab:hover { background: #1e293b; color: #93c5fd; }
    [data-theme="dark"] .hoja-tab.active { background: #1e293b; color: #93c5fd; }
    [data-theme="dark"] .tab-count { background: #334155; color: #94a3b8; }
</style>
@endpush

@section('content')

@php
    $totalDoc   = collect($hojas)->sum(fn($h) => count($h['filas']));
    $conEmail   = collect($hojas)->sum(fn($h) => count(array_filter($h['filas'], fn($f) => !empty(trim($f['email'] ?? '')))));
    $sinEmail   = $totalDoc - $conEmail;
@endphp

{{-- Encabezado --}}
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.docentes.import') }}"
       class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <div>
        <h1 class="mb-0" style="font-size:1.3rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-eye me-2" style="color:var(--secondary);"></i>Confirmar Importación de Docentes
        </h1>
        <p class="text-muted mb-0 mt-1" style="font-size:.78rem;">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>
            <strong>{{ $origName }}</strong> &mdash;
            <strong>{{ $totalDoc }}</strong> docente{{ $totalDoc !== 1 ? 's' : '' }}
            @if($conEmail > 0)
                &mdash; <span style="color:#166534;font-weight:700;"><i class="bi bi-person-check me-1"></i>{{ $conEmail }} con cuenta de acceso</span>
            @endif
            @if($sinEmail > 0)
                &mdash; <span style="color:#92400e;font-weight:600;"><i class="bi bi-person-dash me-1"></i>{{ $sinEmail }} sin email (sin cuenta)</span>
            @endif
        </p>
    </div>
</div>

{{-- Aviso sobre contraseñas --}}
@if($conEmail > 0)
<div class="alert d-flex gap-2 align-items-start mb-3"
     style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;font-size:.83rem;">
    <i class="bi bi-shield-lock-fill" style="color:#16a34a;font-size:1.2rem;flex-shrink:0;margin-top:.1rem;"></i>
    <div>
        <strong>Se crearán cuentas de acceso automáticamente</strong> para los docentes con email.<br>
        Cada docente recibirá una <strong>contraseña temporal</strong> generada por el sistema.
        Al iniciar sesión por primera vez, el sistema le pedirá que la cambie.
        Las contraseñas temporales se mostrarán al terminar la importación.
    </div>
</div>
@endif

<form method="POST" action="{{ route('admin.docentes.importConfirm') }}" id="confirmForm">
    @csrf
    <input type="hidden" name="temp_path"  value="{{ $tempPath }}">
    <input type="hidden" name="extension"  value="{{ $extension }}">

    <div class="prev-card shadow-sm">

        {{-- Tabs --}}
        <div class="hoja-tabs">
            @foreach($hojas as $idx => $hoja)
                <button type="button"
                        class="hoja-tab {{ $idx === 0 ? 'active' : '' }}"
                        data-tab="{{ $idx }}"
                        onclick="mostrarHoja({{ $idx }})">
                    <i class="bi bi-person-lines-fill"></i>
                    {{ $hoja['nombre'] }}
                    <span class="tab-count">{{ count($hoja['filas']) }}</span>
                </button>
            @endforeach
        </div>

        {{-- Paneles --}}
        @foreach($hojas as $idx => $hoja)
            @php
                $filas      = $hoja['filas'];
                $primera    = $filas[0] ?? [];
                $tieneCed   = array_key_exists('cedula',    $primera);
                $tieneSexo  = array_key_exists('sexo',      $primera);
                $tieneEsp   = array_key_exists('especialidad', $primera);
            @endphp

            <div class="hoja-panel {{ $idx === 0 ? 'active' : '' }}" id="panel-{{ $idx }}">

                <div class="table-responsive" style="max-height:460px;overflow-y:auto;border-radius:8px;border:1px solid #e5e7eb;">
                    <table class="table table-sm table-bordered doc-table mb-0">
                        <thead>
                            <tr>
                                <th style="width:36px;">#</th>
                                <th>Apellidos</th>
                                <th>Nombres</th>
                                <th>Email / Cuenta</th>
                                @if($tieneCed)<th>Cédula</th>@endif
                                @if($tieneSexo)<th style="width:52px;text-align:center;">Sexo</th>@endif
                                @if($tieneEsp)<th>Especialidad</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($filas as $n => $fila)
                                @php
                                    $emailVal = trim($fila['email'] ?? '');
                                    $yaExiste = $emailVal && \App\Models\User::where('email', $emailVal)->exists();
                                @endphp
                                <tr>
                                    <td class="text-muted" style="font-size:.72rem;text-align:center;">{{ $n + 1 }}</td>
                                    <td class="fw-semibold">{{ $fila['apellidos'] ?? '' }}</td>
                                    <td>{{ $fila['nombres'] ?? '' }}</td>
                                    <td>
                                        @if($emailVal)
                                            <div style="font-size:.78rem;">{{ $emailVal }}</div>
                                            @if($yaExiste)
                                                <span class="cuenta-badge cuenta-skip">
                                                    <i class="bi bi-person-check me-1"></i>Ya tiene cuenta
                                                </span>
                                            @else
                                                <span class="cuenta-badge cuenta-ok">
                                                    <i class="bi bi-key me-1"></i>Se creará cuenta + contraseña temporal
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted" style="font-size:.76rem;">
                                                <i class="bi bi-dash me-1"></i>Sin email — sin cuenta
                                            </span>
                                        @endif
                                    </td>
                                    @if($tieneCed)
                                        <td class="text-muted" style="font-size:.76rem;">{{ $fila['cedula'] ?? '' ?: '—' }}</td>
                                    @endif
                                    @if($tieneSexo)
                                        <td style="text-align:center;">
                                            @php $sx = strtoupper($fila['sexo'] ?? ''); @endphp
                                            @if($sx === 'F')
                                                <span style="color:#ec4899;font-weight:800;font-size:.78rem;">F</span>
                                            @elseif($sx === 'M')
                                                <span style="color:#3b82f6;font-weight:800;font-size:.78rem;">M</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    @endif
                                    @if($tieneEsp)
                                        <td style="font-size:.76rem;">{{ $fila['especialidad'] ?? '' ?: '—' }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="text-muted mt-2 mb-0" style="font-size:.74rem;">
                    <strong>{{ count($filas) }}</strong> docente{{ count($filas) !== 1 ? 's' : '' }}
                    en la hoja <strong>"{{ $hoja['nombre'] }}"</strong>
                </p>
            </div>
        @endforeach

        {{-- Barra de confirmación --}}
        <div class="confirm-bar">
            <div style="font-size:.82rem;color:#374151;">
                <strong>{{ $totalDoc }}</strong> docente{{ $totalDoc !== 1 ? 's' : '' }} en total
                @if($conEmail > 0)
                    &mdash; <span style="color:#166534;font-weight:700;">{{ $conEmail }} cuenta{{ $conEmail !== 1 ? 's' : '' }} a crear</span>
                @endif
            </div>
            <div class="ms-auto d-flex gap-2">
                <a href="{{ route('admin.docentes.import') }}"
                   class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                    <i class="bi bi-x-lg me-1"></i>Cancelar
                </a>
                <button type="submit"
                        class="btn px-4 fw-semibold"
                        style="background:var(--primary);color:#fff;border-radius:8px;"
                        id="confirmBtn">
                    <span id="confirmSpinner" class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                    <i class="bi bi-cloud-arrow-up me-1" id="confirmIcon"></i>
                    Importar {{ $totalDoc }} docente{{ $totalDoc !== 1 ? 's' : '' }}
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
    document.querySelector('[data-tab="' + idx + '"]')?.classList.add('active');
}
document.getElementById('confirmForm').addEventListener('submit', function() {
    const btn  = document.getElementById('confirmBtn');
    const spin = document.getElementById('confirmSpinner');
    const icon = document.getElementById('confirmIcon');
    btn.disabled = true;
    spin.classList.remove('d-none');
    if (icon) icon.classList.add('d-none');
});
</script>
@endpush
