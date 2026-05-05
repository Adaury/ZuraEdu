<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ficha Médica — {{ $estudiante->nombre_completo }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:9pt; color:#1a1a2e; }
@page { size:letter portrait; margin:1.3cm 1.6cm; }

/* ── Encabezado institucional ── */
.hdr { border:2px solid #dc2626; border-radius:4px; margin-bottom:1rem; overflow:hidden; }
.hdr-top { background:#dc2626; color:#fff; text-align:center; font-size:6.5pt; font-weight:700;
           letter-spacing:.15em; text-transform:uppercase; padding:3px 0; }
.hdr-body { background:#fff; padding:8px 12px; display:flex; align-items:center; gap:12px; }
.logo-box { width:52px; height:52px; border-radius:6px; background:#dc2626; color:#fff;
            font-size:13pt; font-weight:900; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.logo-img  { height:50px; max-width:52px; object-fit:contain; }
.inst-name { font-size:11pt; font-weight:900; color:#1e3a6e; }
.inst-sub  { font-size:7.5pt; color:#374151; margin-top:1px; }

.doc-title { text-align:center; font-size:12pt; font-weight:900; color:#dc2626;
             text-transform:uppercase; margin:.5rem 0 .75rem; }

/* ── Secciones ── */
.section { border:1.5px solid #e5e7eb; border-radius:6px; margin-bottom:.75rem; overflow:hidden; }
.section-title { color:#fff; font-size:7pt; font-weight:800;
                 text-transform:uppercase; letter-spacing:.07em; padding:.35rem .75rem; }
.title-rojo     { background:#dc2626; }
.title-naranja  { background:#d97706; }
.title-violeta  { background:#7c3aed; }
.title-azul     { background:#1e3a6e; }
.title-gris     { background:#475569; }

.grid { display:grid; grid-template-columns:1fr 1fr; }
.grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; }
.field { padding:.35rem .75rem; border-top:1px solid #f3f4f6; }
.field:nth-child(-n+2) { border-top:none; }
.field-full { grid-column:1/-1; }
.field:first-child { border-top:none; }
.field-label { font-size:7pt; font-weight:700; color:#6b7280; text-transform:uppercase;
               letter-spacing:.05em; margin-bottom:.1rem; }
.field-value { font-size:8.5pt; font-weight:600; color:#1e293b; }
.field-value.alerta { color:#dc2626; }
.field-value.vacio  { color:#9ca3af; font-style:italic; font-weight:400; }

/* Perfil estudiante */
.perfil-row { display:flex; align-items:flex-start; gap:12px; padding:.5rem .75rem; }
.foto-box  { width:72px; height:86px; border:1px solid #d1d5db; border-radius:5px;
             display:flex; align-items:center; justify-content:center; background:#f8faff;
             font-size:7pt; color:#9ca3af; flex-shrink:0; }
.foto-img  { width:72px; height:86px; object-fit:cover; border-radius:5px; }

/* Tabla incidentes */
.inc-table { width:100%; border-collapse:collapse; font-size:8pt; }
.inc-table th { background:#f0f4ff; font-weight:700; color:#374151; padding:.3rem .6rem;
                font-size:7.5pt; text-align:left; }
.inc-table td { padding:.3rem .6rem; border-bottom:1px solid #f3f4f6; vertical-align:top; }
.inc-table tr:nth-child(even) td { background:#fafbff; }

.badge { display:inline-block; padding:1px 6px; border-radius:8px; font-size:7pt; font-weight:700; }
.badge-accidente  { background:#fee2e2; color:#dc2626; }
.badge-enfermedad { background:#fef3c7; color:#d97706; }
.badge-alergia    { background:#ede9fe; color:#7c3aed; }
.badge-otro       { background:#e0f2fe; color:#0891b2; }

/* Firma */
.sigs { display:flex; justify-content:space-around; margin-top:1.5rem; }
.sig-block { text-align:center; width:150px; }
.sig-line { border-top:1px solid #374151; margin-bottom:.2rem; }
.sig-name { font-size:7.5pt; font-weight:700; }
.sig-role { font-size:6.5pt; color:#6b7280; }

.footer { text-align:center; font-size:6.5pt; color:#9ca3af; margin-top:.75rem;
          border-top:1px solid #e5e7eb; padding-top:.3rem; }

.aviso { background:#fff7ed; border:1px solid #fed7aa; border-radius:4px;
         padding:.4rem .75rem; margin-bottom:.75rem; font-size:7.5pt; color:#92400e; }
</style>
</head>
<body>

@php
    $logoPath   = $config?->logo ? public_path('storage/' . $config->logo) : null;
    $fotoPath   = $estudiante->foto ? public_path('storage/' . $estudiante->foto) : null;
    $matActual  = $estudiante->matriculas->where('estado','activa')->first();
    $si         = $inst;

    $tiposInfo = \App\Models\IncidenteMedico::TIPOS;
@endphp

{{-- Encabezado institucional --}}
<div class="hdr">
    <div class="hdr-top">República Dominicana · Ministerio de Educación · MINERD — Ficha Médica Escolar</div>
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

<div class="doc-title">Ficha de Salud Escolar</div>

{{-- Aviso de confidencialidad --}}
<div class="aviso">
    <strong>CONFIDENCIAL:</strong> Este documento contiene información médica sensible. Su uso está restringido
    al personal autorizado de la institución y al representante del estudiante.
</div>

{{-- Datos del estudiante --}}
<div class="section">
    <div class="section-title title-azul">Datos del Estudiante</div>
    <div class="perfil-row">
        @if($fotoPath && file_exists($fotoPath))
            <img src="{{ $fotoPath }}" class="foto-img" alt="Foto">
        @else
            <div class="foto-box">Sin foto</div>
        @endif
        <div style="flex:1;">
            <div class="grid">
                <div class="field" style="border-top:none;">
                    <div class="field-label">Apellidos</div>
                    <div class="field-value">{{ strtoupper($estudiante->apellidos) }}</div>
                </div>
                <div class="field" style="border-top:none;">
                    <div class="field-label">Nombres</div>
                    <div class="field-value">{{ strtoupper($estudiante->nombres) }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Cédula / RNE</div>
                    <div class="field-value {{ !$estudiante->cedula ? 'vacio' : '' }}">
                        {{ $estudiante->cedula ?? 'No registrado' }}
                    </div>
                </div>
                <div class="field">
                    <div class="field-label">No. Matrícula</div>
                    <div class="field-value">{{ $estudiante->numero_matricula ?? '—' }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Fecha de Nacimiento</div>
                    <div class="field-value">
                        {{ $estudiante->fecha_nacimiento?->format('d/m/Y') ?? '—' }}
                        ({{ $estudiante->edad ?? '—' }} años)
                    </div>
                </div>
                <div class="field">
                    <div class="field-label">Sexo</div>
                    <div class="field-value">
                        {{ $estudiante->sexo === 'M' ? 'Masculino' : ($estudiante->sexo === 'F' ? 'Femenino' : '—') }}
                    </div>
                </div>
                @if($matActual)
                <div class="field">
                    <div class="field-label">Grado / Sección</div>
                    <div class="field-value">
                        {{ $matActual->grupo?->grado?->nombre ?? '' }} {{ $matActual->grupo?->seccion?->nombre ?? '' }}
                    </div>
                </div>
                <div class="field">
                    <div class="field-label">Año Escolar</div>
                    <div class="field-value">{{ $matActual->schoolYear?->nombre ?? '—' }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Información Médica General --}}
<div class="section">
    <div class="section-title title-rojo">Información Médica General</div>
    <div class="grid-3">
        <div class="field" style="border-top:none;">
            <div class="field-label">Tipo de Sangre</div>
            <div class="field-value {{ !$ficha?->tipo_sangre ? 'vacio' : 'alerta' }}" style="font-size:11pt;">
                {{ $ficha?->tipo_sangre ?? 'No registrado' }}
            </div>
        </div>
        <div class="field" style="border-top:none;">
            <div class="field-label">Seguro Médico</div>
            <div class="field-value {{ !$ficha?->seguro_medico ? 'vacio' : '' }}">
                {{ $ficha?->seguro_medico ?? 'No registrado' }}
            </div>
        </div>
        <div class="field" style="border-top:none;">
            <div class="field-label">No. Póliza / Afiliado</div>
            <div class="field-value {{ !$ficha?->num_seguro ? 'vacio' : '' }}">
                {{ $ficha?->num_seguro ?? 'No registrado' }}
            </div>
        </div>
    </div>
    <div class="field field-full">
        <div class="field-label">Alergias Conocidas</div>
        <div class="field-value {{ !$ficha?->alergias ? 'vacio' : 'alerta' }}">
            {{ $ficha?->alergias ?? 'Ninguna registrada' }}
        </div>
    </div>
    <div class="field field-full">
        <div class="field-label">Condiciones Médicas Crónicas</div>
        <div class="field-value {{ !$ficha?->condiciones_medicas ? 'vacio' : '' }}">
            {{ $ficha?->condiciones_medicas ?? 'Ninguna registrada' }}
        </div>
    </div>
    <div class="field field-full">
        <div class="field-label">Medicamentos de Uso Regular</div>
        <div class="field-value {{ !$ficha?->medicamentos ? 'vacio' : '' }}">
            {{ $ficha?->medicamentos ?? 'Ninguno registrado' }}
        </div>
    </div>
</div>

{{-- Contacto de emergencia --}}
<div class="section">
    <div class="section-title title-naranja">Contacto de Emergencia</div>
    <div class="grid">
        <div class="field" style="border-top:none;">
            <div class="field-label">Nombre del Contacto</div>
            <div class="field-value {{ !$ficha?->contacto_emergencia ? 'vacio' : '' }}">
                {{ $ficha?->contacto_emergencia ?? 'No registrado' }}
            </div>
        </div>
        <div class="field" style="border-top:none;">
            <div class="field-label">Teléfono de Emergencia</div>
            <div class="field-value {{ !$ficha?->telefono_emergencia ? 'vacio' : '' }}">
                {{ $ficha?->telefono_emergencia ?? 'No registrado' }}
            </div>
        </div>
    </div>
    {{-- Tutor del estudiante como referencia --}}
    @if($estudiante->tutor_nombre)
    <div class="grid">
        <div class="field">
            <div class="field-label">Tutor / Representante</div>
            <div class="field-value">{{ $estudiante->tutor_nombre }}</div>
        </div>
        <div class="field">
            <div class="field-label">Tel. Tutor</div>
            <div class="field-value">{{ $estudiante->tutor_telefono ?? '—' }}</div>
        </div>
    </div>
    @endif
</div>

{{-- Historial de incidentes --}}
<div class="section">
    <div class="section-title title-violeta">
        Historial de Incidentes Médicos ({{ $incidentes->count() }} registros)
    </div>
    @if($incidentes->isNotEmpty())
    <table class="inc-table">
        <thead>
            <tr>
                <th style="width:65px;">Fecha</th>
                <th style="width:70px;">Tipo</th>
                <th>Descripción</th>
                <th>Acción Tomada</th>
                <th style="width:90px;">Remitido a</th>
            </tr>
        </thead>
        <tbody>
            @foreach($incidentes as $inc)
            @php $ti = $tiposInfo[$inc->tipo] ?? ['label' => ucfirst($inc->tipo), 'bg' => '#f1f5f9', 'color' => '#475569']; @endphp
            <tr>
                <td>{{ $inc->fecha->format('d/m/Y') }}</td>
                <td>
                    <span class="badge badge-{{ $inc->tipo }}">{{ $ti['label'] }}</span>
                </td>
                <td style="line-height:1.4;">{{ $inc->descripcion }}</td>
                <td style="line-height:1.4;color:#374151;">{{ $inc->accion_tomada }}</td>
                <td style="color:#6b7280;">{{ $inc->remitido_a ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div style="padding:.75rem; color:#9ca3af; font-style:italic; font-size:8pt;">
        No se han registrado incidentes médicos para este estudiante.
    </div>
    @endif
</div>

{{-- Firmas --}}
<div class="sigs">
    <div class="sig-block">
        <div style="height:40px;"></div>
        <div class="sig-line"></div>
        <div class="sig-name">{{ \App\Models\ConfigInstitucional::get('nombre_director','') ?: '________________________' }}</div>
        <div class="sig-role">Director/a de la Institución</div>
    </div>
    <div class="sig-block">
        <div style="height:40px;"></div>
        <div class="sig-line"></div>
        <div class="sig-name">{{ $estudiante->tutor_nombre ?: '________________________' }}</div>
        <div class="sig-role">Representante / Tutor</div>
    </div>
    <div class="sig-block">
        <div style="height:40px;"></div>
        <div class="sig-line"></div>
        <div class="sig-name">________________________</div>
        <div class="sig-role">Responsable de Enfermería</div>
    </div>
</div>

<div class="footer">
    Ficha de Salud Escolar generada por SGE PSAC · {{ now()->format('d/m/Y H:i') }} · {{ $si }}
    · DOCUMENTO CONFIDENCIAL
</div>

</body>
</html>
