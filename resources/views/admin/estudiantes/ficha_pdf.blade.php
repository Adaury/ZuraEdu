<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ficha Estudiantil — {{ $estudiante->nombre_completo }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:9pt; color:#1a1a2e; }
@page { size:letter portrait; margin:1.3cm 1.6cm; }

.hdr { border:2px solid #1e3a6e; border-radius:4px; margin-bottom:1rem; overflow:hidden; }
.hdr-top { background:#1e3a6e; color:#fff; text-align:center; font-size:6.5pt; font-weight:700;
           letter-spacing:.15em; text-transform:uppercase; padding:3px 0; }
.hdr-body { background:#fff; padding:8px 12px; display:flex; align-items:center; gap:12px; }
.logo-box { width:55px; height:55px; border-radius:8px; background:#1e3a6e; color:#fff;
            font-size:13pt; font-weight:900; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.logo-img  { height:52px; max-width:55px; object-fit:contain; }
.inst-name { font-size:11pt; font-weight:900; color:#1e3a6e; }
.inst-sub  { font-size:7.5pt; color:#374151; margin-top:1px; }

.doc-title { text-align:center; font-size:12pt; font-weight:900; color:#1e3a6e;
             text-transform:uppercase; margin:.5rem 0 .75rem; }

.section { border:1.5px solid #e5e7eb; border-radius:6px; margin-bottom:.75rem; overflow:hidden; }
.section-title { background:#1e3a6e; color:#fff; font-size:7pt; font-weight:800;
                 text-transform:uppercase; letter-spacing:.07em; padding:.35rem .75rem; }
.grid { display:grid; grid-template-columns:1fr 1fr; }
.field { padding:.35rem .75rem; border-top:1px solid #f3f4f6; }
.field:nth-child(-n+2) { border-top:none; }
.field-label { font-size:7pt; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.1rem; }
.field-value { font-size:8.5pt; font-weight:600; color:#1e293b; }
.field-full { grid-column:1/-1; }

.foto-cell { width:90px; text-align:center; border-right:1px solid #e5e7eb; padding:.5rem; flex-shrink:0; }
.foto-box  { width:75px; height:90px; border:1px solid #d1d5db; border-radius:5px;
             display:flex; align-items:center; justify-content:center; background:#f8faff;
             font-size:7pt; color:#9ca3af; margin:0 auto; }
.foto-img  { width:75px; height:90px; object-fit:cover; border-radius:5px; }

.matriculas-table { width:100%; border-collapse:collapse; font-size:8pt; }
.matriculas-table th { background:#f0f4ff; font-weight:700; color:#374151; padding:.3rem .65rem; font-size:7.5pt; }
.matriculas-table td { padding:.3rem .65rem; border-bottom:1px solid #f3f4f6; }

.sigs { display:flex; justify-content:space-around; margin-top:1.5rem; }
.sig-block { text-align:center; width:150px; }
.sig-line { border-top:1px solid #374151; margin-bottom:.2rem; }
.sig-name { font-size:7.5pt; font-weight:700; }
.sig-role { font-size:6.5pt; color:#6b7280; }

.footer { text-align:center; font-size:6.5pt; color:#9ca3af; margin-top:.75rem;
          border-top:1px solid #e5e7eb; padding-top:.3rem; }
</style>
</head>
<body>

@php
    $logoPath = $config?->logo ? public_path('storage/' . $config->logo) : null;
    $reps     = $estudiante->representantes;
    $matActual= $estudiante->matriculas->where('estado','activa')->first();
    $fotoPath = $estudiante->foto ? public_path('storage/' . $estudiante->foto) : null;
@endphp

<div class="hdr">
    <div class="hdr-top">República Dominicana · Ministerio de Educación · MINERD</div>
    <div class="hdr-body">
        @if($logoPath && file_exists($logoPath))
            <img src="{{ $logoPath }}" alt="Logo" class="logo-img">
        @else
            <div class="logo-box">{{ strtoupper(substr($si,0,2)) }}</div>
        @endif
        <div>
            <div class="inst-name">{{ $si }}</div>
            <div class="inst-sub">{{ \App\Models\ConfigInstitucional::get('nivel_educativo','') }}</div>
        </div>
    </div>
</div>

<div class="doc-title">Ficha Estudiantil</div>

{{-- Datos personales --}}
<div class="section">
    <div class="section-title">Datos Personales</div>
    <div style="display:flex;">
        <div class="foto-cell">
            @if($fotoPath && file_exists($fotoPath))
                <img src="{{ $fotoPath }}" class="foto-img" alt="Foto">
            @else
                <div class="foto-box">Sin foto</div>
            @endif
        </div>
        <div style="flex:1;">
            <div class="grid">
                <div class="field">
                    <div class="field-label">Apellidos</div>
                    <div class="field-value">{{ strtoupper($estudiante->apellidos ?? $estudiante->apellido ?? '—') }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Nombres</div>
                    <div class="field-value">{{ strtoupper($estudiante->nombres ?? $estudiante->nombre ?? '—') }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Cédula / RNE</div>
                    <div class="field-value">{{ $estudiante->cedula ?? '—' }}</div>
                </div>
                <div class="field">
                    <div class="field-label">No. Matrícula</div>
                    <div class="field-value">{{ $estudiante->matricula ?? '—' }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Fecha de nacimiento</div>
                    <div class="field-value">{{ $estudiante->fecha_nacimiento ? \Carbon\Carbon::parse($estudiante->fecha_nacimiento)->format('d/m/Y') : '—' }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Sexo</div>
                    <div class="field-value">{{ $estudiante->sexo ?? '—' }}</div>
                </div>
                @if($matActual)
                <div class="field">
                    <div class="field-label">Grado / Sección</div>
                    <div class="field-value">{{ $matActual->grupo?->grado?->nombre ?? '' }} {{ $matActual->grupo?->seccion?->nombre ?? '' }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Año Escolar</div>
                    <div class="field-value">{{ $matActual->schoolYear?->nombre ?? '—' }}</div>
                </div>
                @endif
                @if($estudiante->direccion)
                <div class="field field-full">
                    <div class="field-label">Dirección</div>
                    <div class="field-value">{{ $estudiante->direccion }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Representantes --}}
@if($reps->isNotEmpty())
<div class="section">
    <div class="section-title">Representante(s)</div>
    @foreach($reps as $rep)
    <div class="grid" style="{{ !$loop->first ? 'border-top:1.5px solid #e5e7eb;' : '' }}">
        <div class="field">
            <div class="field-label">Nombre</div>
            <div class="field-value">{{ $rep->nombres }} {{ $rep->apellidos }}</div>
        </div>
        <div class="field">
            <div class="field-label">Cédula</div>
            <div class="field-value">{{ $rep->cedula ?? '—' }}</div>
        </div>
        <div class="field">
            <div class="field-label">Teléfono</div>
            <div class="field-value">{{ $rep->telefono ?? '—' }}</div>
        </div>
        <div class="field">
            <div class="field-label">Parentesco</div>
            <div class="field-value">{{ $rep->parentesco ?? '—' }}</div>
        </div>
        @if($rep->email)
        <div class="field field-full">
            <div class="field-label">Correo electrónico</div>
            <div class="field-value">{{ $rep->email }}</div>
        </div>
        @endif
    </div>
    @endforeach
</div>
@endif

{{-- Historial de matrículas --}}
@if($estudiante->matriculas->isNotEmpty())
<div class="section">
    <div class="section-title">Historial de Matrículas</div>
    <table class="matriculas-table">
        <thead><tr><th>Año Escolar</th><th>Grado</th><th>Sección</th><th>Estado</th></tr></thead>
        <tbody>
            @foreach($estudiante->matriculas->sortByDesc(fn($m) => $m->schoolYear?->id) as $mat)
            <tr>
                <td>{{ $mat->schoolYear?->nombre ?? '—' }}</td>
                <td>{{ $mat->grupo?->grado?->nombre ?? '—' }}</td>
                <td>{{ $mat->grupo?->seccion?->nombre ?? '—' }}</td>
                <td><span style="font-weight:700;color:{{ $mat->estado === 'activa' ? '#15803d' : '#6b7280' }};">{{ ucfirst($mat->estado) }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="sigs">
    <div class="sig-block">
        <div style="height:40px;"></div>
        <div class="sig-line"></div>
        <div class="sig-name">{{ \App\Models\ConfigInstitucional::get('nombre_director','') ?: '________________________' }}</div>
        <div class="sig-role">Director/a</div>
    </div>
    <div class="sig-block">
        <div style="height:40px;"></div>
        <div class="sig-line"></div>
        <div class="sig-name">{{ $reps->first() ? $reps->first()->nombres . ' ' . $reps->first()->apellidos : '________________________' }}</div>
        <div class="sig-role">Representante</div>
    </div>
</div>

<div class="footer">
    Ficha generada por SGE PSAC · {{ now()->format('d/m/Y H:i') }} · {{ $si }}
</div>

</body>
</html>
