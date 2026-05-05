<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Acta de Notas — {{ $asignacion->asignatura?->nombre }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:8pt; color:#1a1a2e; }
@page { size:letter landscape; margin:1cm 1.3cm; }

.hdr { border:2px solid #1e3a6e; border-radius:3px; margin-bottom:.65rem; overflow:hidden; }
.hdr-top { background:#1e3a6e; color:#fff; text-align:center; font-size:6.5pt; font-weight:700;
           letter-spacing:.15em; text-transform:uppercase; padding:2px 0; }
.hdr-body { background:#fff; padding:6px 10px; display:flex; align-items:center; gap:10px; }
.logo-box { width:46px; height:46px; border-radius:6px; background:#1e3a6e; color:#fff;
            font-size:11pt; font-weight:900; display:flex; align-items:center;
            justify-content:center; flex-shrink:0; }
.logo-img  { height:44px; max-width:48px; object-fit:contain; }
.inst-name { font-size:11pt; font-weight:900; color:#1e3a6e; }
.inst-sub  { font-size:7pt; color:#374151; }

.doc-title { text-align:center; font-size:10pt; font-weight:900; color:#1e3a6e; margin:.4rem 0 .15rem; }
.doc-meta  { display:flex; justify-content:space-between; font-size:7.5pt; color:#6b7280;
             border-bottom:1.5px solid #1e3a6e; padding-bottom:.3rem; margin-bottom:.5rem; }

table { width:100%; border-collapse:collapse; }
thead th { background:#1e3a6e; color:#fff; font-size:7pt; font-weight:700;
           padding:4px 5px; text-align:center; border:1px solid #1e3a6e; }
tbody td { border:1px solid #d1d5db; padding:3.5px 5px; font-size:7.5pt; vertical-align:middle; }
tbody tr:nth-child(even) td { background:#f8faff; }
.num { width:22px; text-align:center; color:#6b7280; }
.nom { min-width:130px; }
.nota { width:40px; text-align:center; font-weight:700; }
.aprobado { color:#065f46; }
.reprobado { color:#991b1b; }
.final-col { background:#eff6ff !important; font-weight:800; font-size:8pt; }

.footer { margin-top:.5rem; display:flex; justify-content:space-between;
          font-size:6.5pt; color:#9ca3af; border-top:1px solid #e5e7eb; padding-top:.25rem; }
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

<div class="doc-title">Acta de Calificaciones</div>
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
        &nbsp;·&nbsp;
        <strong>Área:</strong> {{ $esTecnica ? 'Técnica' : 'Académica' }}
        &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
    </span>
</div>

<table>
    <thead>
        <tr>
            <th class="num">#</th>
            <th class="nom" style="text-align:left;">Estudiante</th>
            <th style="width:65px;">Matrícula</th>
            @if($esTecnica)
                @foreach($periodos as $p)
                    <th>{{ $p->nombre }}</th>
                @endforeach
            @else
                @foreach($periodos as $p)
                    @php $n = $p->numero; @endphp
                    <th>P{{ $n }}</th>
                @endforeach
            @endif
            <th class="final-col">Promedio</th>
            <th>Situación</th>
        </tr>
    </thead>
    <tbody>
        @foreach($matriculas as $i => $mat)
        @php
            $est = $mat->estudiante;
            if ($esTecnica) {
                $notasPeriodos = [];
                foreach ($periodos as $p) {
                    $key = $mat->id . '_' . $p->id;
                    $cal = $calificaciones[$key] ?? null;
                    $notasPeriodos[$p->id] = $cal?->nota_final;
                }
                $validas = array_filter($notasPeriodos, fn($v) => $v !== null);
                $promedio = count($validas) ? round(array_sum($validas) / count($validas), 2) : null;
                $situacion = $promedio !== null ? ($promedio >= 65 ? 'A' : 'R') : null;
            } else {
                $cal = $calificaciones[$mat->id] ?? null;
                $notasPeriodos = [];
                foreach ($periodos as $p) {
                    $n = $p->numero;
                    $vals = [];
                    for ($ci = 1; $ci <= 4; $ci++) {
                        $pb = $cal?->{"comp{$ci}_p{$n}"};
                        if ($pb !== null) {
                            $rv = $cal?->{"comp{$ci}_r{$n}"};
                            $pb = (float)$pb;
                            $cv = ($rv !== null && $pb < 70) ? round($pb + min((float)$rv, max(0.0, 100.0 - $pb)), 2) : round($pb, 2);
                            $vals[] = $cv;
                        }
                    }
                    $notasPeriodos[$p->id] = $vals ? round(array_sum($vals) / count($vals), 2) : null;
                }
                $promedio  = $cal?->nota_extraordinaria ?? $cal?->nota_completiva ?? $cal?->nota_final;
                $situacion = $cal?->situacion;
            }
        @endphp
        <tr>
            <td class="num">{{ $i + 1 }}</td>
            <td class="nom"><strong>{{ $est->apellidos ?? $est->apellido ?? '' }}</strong>, {{ $est->nombres ?? $est->nombre ?? '' }}</td>
            <td style="text-align:center;font-size:7pt;color:#374151;">{{ $est->matricula ?? '—' }}</td>
            @foreach($periodos as $p)
            <td class="nota {{ isset($notasPeriodos[$p->id]) ? ($notasPeriodos[$p->id] >= 65 ? 'aprobado' : 'reprobado') : '' }}">
                {{ $notasPeriodos[$p->id] ?? '—' }}
            </td>
            @endforeach
            <td class="nota final-col {{ $promedio !== null ? ($promedio >= 65 ? 'aprobado' : 'reprobado') : '' }}">
                {{ $promedio ?? '—' }}
            </td>
            <td style="text-align:center;font-size:7.5pt;font-weight:700;">
                @if($situacion === 'A')
                    <span style="color:#065f46;">Aprobado</span>
                @elseif($situacion === 'R')
                    <span style="color:#991b1b;">Reprobado</span>
                @else
                    —
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div style="margin-top:1.5rem;display:flex;justify-content:space-around;">
    <div style="text-align:center;width:180px;">
        <div style="border-top:1px solid #374151;margin-bottom:.25rem;"></div>
        <div style="font-size:7.5pt;font-weight:700;">{{ $docente->nombre_completo }}</div>
        <div style="font-size:6.5pt;color:#6b7280;">Firma del Docente</div>
    </div>
    <div style="text-align:center;width:180px;">
        <div style="border-top:1px solid #374151;margin-bottom:.25rem;"></div>
        <div style="font-size:7.5pt;font-weight:700;">{{ \App\Models\ConfigInstitucional::get('nombre_director','') ?: '________________________________' }}</div>
        <div style="font-size:6.5pt;color:#6b7280;">Director/a del Centro</div>
    </div>
</div>

<div class="footer">
    <span>{{ $si }} · Acta generada por SGE · {{ now()->format('d/m/Y H:i') }}</span>
    <span>Total estudiantes: {{ $matriculas->count() }}</span>
</div>

</body>
</html>
