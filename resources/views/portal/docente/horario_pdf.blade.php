<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mi Horario — {{ $docente->nombre_completo }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:8.5pt; color:#1a1a2e; }
@page { size:letter landscape; margin:1cm 1.4cm; }

.hdr { border:2px solid #1e3a6e; border-radius:4px; margin-bottom:.75rem; overflow:hidden; }
.hdr-top { background:#1e3a6e; color:#fff; text-align:center; font-size:7pt; font-weight:700;
           letter-spacing:.15em; text-transform:uppercase; padding:3px 0; }
.hdr-body { background:#fff; padding:7px 12px; display:flex; align-items:center; gap:12px; }
.logo-box { width:50px; height:50px; border-radius:7px; background:#1e3a6e; color:#fff;
            font-size:12pt; font-weight:900; display:flex; align-items:center;
            justify-content:center; flex-shrink:0; }
.logo-img  { height:48px; max-width:52px; object-fit:contain; }
.inst-name { font-size:11pt; font-weight:900; color:#1e3a6e; }
.inst-sub  { font-size:7.5pt; color:#374151; margin-top:1px; }

.doc-title { text-align:center; margin:.5rem 0 .2rem; font-size:11pt; font-weight:900; color:#1e3a6e; }
.doc-meta  { text-align:center; font-size:8pt; color:#6b7280; margin-bottom:.6rem; }

table { width:100%; border-collapse:collapse; }
th { background:#1e3a6e; color:#fff; font-size:7.5pt; font-weight:700;
     padding:5px 7px; text-align:center; }
.franja-col { background:#f0f4ff; font-size:7.5pt; font-weight:700; color:#374151;
              padding:5px 7px; text-align:center; border-right:2px solid #1e3a6e;
              white-space:nowrap; }
td { border:1px solid #d1d5db; padding:4px 6px; vertical-align:top; min-height:35px; }
.cell-inner { min-height:28px; }
.mat-name { font-size:7.5pt; font-weight:700; color:#1e293b; line-height:1.25; }
.mat-grupo { font-size:6.5pt; color:#6b7280; margin-top:1px; }
.mat-aula  { font-size:6.5pt; color:#1d4ed8; font-style:italic; margin-top:1px; }
td:nth-child(even) { background:#f8faff; }

.footer { margin-top:.5rem; display:flex; justify-content:space-between;
          font-size:7pt; color:#9ca3af; border-top:1px solid #e5e7eb; padding-top:.3rem; }
</style>
</head>
<body>

@php
    $logoPath = $config?->logo ? public_path('storage/' . $config->logo) : null;
    $diasLabel = [
        'lunes'     => 'Lunes',
        'martes'    => 'Martes',
        'miercoles' => 'Miércoles',
        'jueves'    => 'Jueves',
        'viernes'   => 'Viernes',
        'sabado'    => 'Sábado',
    ];
@endphp

<div class="hdr">
    <div class="hdr-top">República Dominicana · Ministerio de Educación · MINERD</div>
    <div class="hdr-body">
        @if($logoPath && file_exists($logoPath))
            <img src="{{ $logoPath }}" alt="Logo" class="logo-img">
        @else
            <div class="logo-box">{{ strtoupper(substr($si, 0, 2)) }}</div>
        @endif
        <div>
            <div class="inst-name">{{ $si }}</div>
            <div class="inst-sub">{{ \App\Models\ConfigInstitucional::get('nivel_educativo','') }}</div>
        </div>
    </div>
</div>

<div class="doc-title">Horario de Clases</div>
<div class="doc-meta">
    <strong>{{ $docente->nombre_completo }}</strong>
    @if($docente->especialidad) · {{ $docente->especialidad }} @endif
    &nbsp;·&nbsp; Año: <strong>{{ $schoolYear?->nombre ?? '—' }}</strong>
    &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y') }}
</div>

@if($franjas->isEmpty() || empty($grid))
<p style="text-align:center;padding:1.5rem;color:#9ca3af;">Sin horario publicado.</p>
@else
<table>
    <thead>
        <tr>
            <th style="width:70px;">Hora</th>
            @foreach($dias as $dia)
            <th>{{ $diasLabel[$dia] ?? ucfirst($dia) }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($franjas as $franja)
        <tr>
            <td class="franja-col">
                {{ $franja->hora_inicio ?? '' }}<br>
                <span style="font-size:6.5pt;color:#6b7280;">{{ $franja->hora_fin ?? '' }}</span>
            </td>
            @foreach($dias as $dia)
            @php $celda = $grid[$franja->id][$dia] ?? null; @endphp
            <td>
                @if($celda)
                <div class="cell-inner">
                    <div class="mat-name">{{ $celda->asignacion?->asignatura?->nombre ?? '—' }}</div>
                    <div class="mat-grupo">
                        {{ $celda->asignacion?->grupo?->grado?->nombre ?? '' }}
                        {{ $celda->asignacion?->grupo?->seccion?->nombre ?? '' }}
                    </div>
                    @if($celda->aula)
                    <div class="mat-aula">{{ $celda->aula->nombre ?? $celda->aula }}</div>
                    @endif
                </div>
                @endif
            </td>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    <span>{{ $si }} · Año escolar {{ $schoolYear?->nombre }}</span>
    <span>Documento generado por SGE · {{ now()->format('d/m/Y H:i') }}</span>
</div>

</body>
</html>
