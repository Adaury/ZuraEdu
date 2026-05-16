<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Informe de Conducta — {{ $asignacion->asignatura?->nombre }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:7.5pt; color:#1a1a2e; }
@page { size:legal landscape; margin:.8cm 1cm; }

.hdr { border:2px solid #4c1d95; border-radius:3px; margin-bottom:.5rem; overflow:hidden; }
.hdr-top { background:#4c1d95; color:#fff; text-align:center; font-size:6pt; font-weight:700;
           letter-spacing:.15em; text-transform:uppercase; padding:2px 0; }
.hdr-body { background:#fff; padding:5px 8px; display:flex; align-items:center; gap:8px; }
.logo-box { width:40px; height:40px; border-radius:5px; background:#4c1d95; color:#fff;
            font-size:10pt; font-weight:900; display:flex; align-items:center;
            justify-content:center; flex-shrink:0; }
.logo-img  { height:38px; max-width:42px; object-fit:contain; }
.inst-name { font-size:10pt; font-weight:900; color:#4c1d95; }
.inst-sub  { font-size:6.5pt; color:#374151; }

.doc-title { text-align:center; font-size:9.5pt; font-weight:900; color:#4c1d95; margin:.3rem 0 .1rem; }
.doc-meta  { display:flex; justify-content:space-between; font-size:7pt; color:#6b7280;
             border-bottom:1.5px solid #4c1d95; padding-bottom:.25rem; margin-bottom:.4rem; }

table { width:100%; border-collapse:collapse; }
thead th { background:#4c1d95; color:#fff; font-size:6.5pt; font-weight:700;
           padding:3.5px 4px; text-align:center; border:1px solid #4c1d95; }
thead th.left { text-align:left; }
tbody td { border:1px solid #d1d5db; padding:3px 4px; font-size:7pt; vertical-align:middle; }
tbody tr:nth-child(even) td { background:#f9f6ff; }

.num  { width:18px; text-align:center; color:#6b7280; }
.nom  { min-width:110px; }
.ind  { width:48px; text-align:center; font-weight:700; }
.obs-col { min-width:90px; font-size:6.5pt; color:#374151; }

.chip { display:inline-block; padding:.1rem .35rem; border-radius:99px; color:#fff;
        font-size:6.5pt; font-weight:800; }

.legend { display:flex; gap:8px; margin-bottom:.4rem; flex-wrap:wrap; }
.legend-item { display:flex; align-items:center; gap:3px; font-size:6pt; }
.legend-dot { width:10px; height:10px; border-radius:50%; }

.footer { margin-top:.4rem; display:flex; justify-content:space-between;
          font-size:6pt; color:#9ca3af; border-top:1px solid #e5e7eb; padding-top:.2rem; }
.firma-box { text-align:center; width:170px; }
.firma-line { border-top:1px solid #374151; margin-bottom:.2rem; }
</style>
</head>
<body>

@php
    $logoPath = $config?->logo ? public_path('storage/' . $config->logo) : null;
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

<div class="doc-title">Informe de Conducta y Comportamiento</div>
<div class="doc-meta">
    <span>
        <strong>Materia:</strong> {{ $asignacion->asignatura?->nombre ?? '—' }}
        &nbsp;·&nbsp;
        <strong>Docente:</strong> {{ $docente->nombre_completo }}
    </span>
    <span>
        <strong>Grupo:</strong> {{ $asignacion->grupo?->grado?->nombre ?? '' }} {{ $asignacion->grupo?->seccion?->nombre ?? '' }}
        &nbsp;·&nbsp;
        <strong>Año:</strong> {{ $schoolYear?->nombre ?? '—' }}
        &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
    </span>
</div>

{{-- Leyenda --}}
<div class="legend">
    @foreach($escala as $val => $e)
    <div class="legend-item">
        <div class="legend-dot" style="background:{{ $e['color'] }};"></div>
        <span><strong>{{ $e['label'] }}</strong> = {{ $e['nombre'] }}</span>
    </div>
    @endforeach
</div>

<table>
    <thead>
        <tr>
            <th class="num">#</th>
            <th class="nom left">Estudiante</th>
            @foreach($periodos as $p)
            @foreach($indicadores as $campo => $ind)
            <th class="ind" style="font-size:5.5pt;">
                P{{ $p->numero }}<br>{{ $ind['label'] }}
            </th>
            @endforeach
            <th class="ind" style="background:#3b0764;font-size:6.5pt;">P{{ $p->numero }}<br>Concepto</th>
            @endforeach
            <th class="obs-col">Observaciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach($matriculas as $i => $mat)
        @php
            $est = $mat->estudiante;
            $obsAll = [];
        @endphp
        <tr>
            <td class="num">{{ $i + 1 }}</td>
            <td class="nom">
                <strong>{{ $est->apellidos ?? $est->apellido ?? '' }}</strong>,
                {{ $est->nombres ?? $est->nombre ?? '' }}
            </td>
            @foreach($periodos as $p)
            @php
                $key = $mat->id . '_' . $p->id;
                $reg = $registrosTodos[$key]?->first() ?? null;
                if ($reg?->observaciones) $obsAll[] = 'P'.$p->numero.': '.$reg->observaciones;
                $concepto = $reg?->concepto;
            @endphp
            @foreach($indicadores as $campo => $ind)
            @php $val = $reg?->{$campo}; $e = $val ? ($escala[$val] ?? null) : null; @endphp
            <td class="ind">
                @if($e)
                <span class="chip" style="background:{{ $e['color'] }};">{{ $e['label'] }}</span>
                @else
                <span style="color:#d1d5db;">—</span>
                @endif
            </td>
            @endforeach
            <td class="ind" style="background:#f5f3ff;">
                @if($concepto)
                @php $ce = $escala[$concepto]; @endphp
                <span class="chip" style="background:{{ $ce['color'] }};font-size:7.5pt;">{{ $ce['label'] }}</span>
                @else
                <span style="color:#d1d5db;">—</span>
                @endif
            </td>
            @endforeach
            <td class="obs-col">{{ implode(' | ', $obsAll) ?: '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div style="margin-top:1rem;display:flex;justify-content:space-around;">
    <div class="firma-box">
        <div class="firma-line"></div>
        <div style="font-size:7pt;font-weight:700;">{{ $docente->nombre_completo }}</div>
        <div style="font-size:6pt;color:#6b7280;">Firma del Docente</div>
    </div>
    <div class="firma-box">
        <div class="firma-line"></div>
        <div style="font-size:7pt;font-weight:700;">{{ \App\Models\ConfigInstitucional::get('nombre_director','') ?: '________________________________' }}</div>
        <div style="font-size:6pt;color:#6b7280;">Director/a del Centro</div>
    </div>
    <div class="firma-box">
        <div class="firma-line"></div>
        <div style="font-size:7pt;font-weight:700;">________________________________</div>
        <div style="font-size:6pt;color:#6b7280;">Coordinador/a Académico/a</div>
    </div>
</div>

<div class="footer">
    <span>{{ $si }} · Informe de Conducta generado por SGE · {{ now()->format('d/m/Y H:i') }}</span>
    <span>Total estudiantes: {{ $matriculas->count() }}</span>
</div>

</body>
</html>
