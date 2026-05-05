<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 7.5px; color: #1e293b; }
@page { size: letter landscape; margin: .8cm 1cm; }

.header { text-align: center; margin-bottom: 10px; border-bottom: 2px solid #1e40af; padding-bottom: 8px; }
.header .inst  { font-size: 11px; font-weight: bold; color: #1e40af; text-transform: uppercase; }
.header .titulo{ font-size: 10px; font-weight: bold; color: #0f172a; margin-top: 4px; }
.header .sub   { font-size: 7.5px; color: #6b7280; margin-top: 3px; }

table { width: 100%; border-collapse: collapse; }
thead tr { background: #1e40af; color: #fff; }
thead th { padding: 3px 4px; font-size: 7px; border: 1px solid #1e3a8a; text-align: center; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #f0f7ff; }
tbody td { padding: 3px 4px; border: 1px solid #bfdbfe; font-size: 7.5px; text-align: center; vertical-align: middle; }
tbody td.name { text-align: left; }

.e  { background: #dcfce7; color: #15803d; font-weight: 700; border-radius: 3px; padding: 1px 4px; font-size: 7px; }
.b  { background: #dbeafe; color: #1d4ed8; font-weight: 700; border-radius: 3px; padding: 1px 4px; font-size: 7px; }
.ep { background: #fef3c7; color: #d97706; font-weight: 700; border-radius: 3px; padding: 1px 4px; font-size: 7px; }
.i  { background: #fee2e2; color: #dc2626; font-weight: 700; border-radius: 3px; padding: 1px 4px; font-size: 7px; }

.leyenda { display: flex; gap: 12px; margin: 8px 0; font-size: 7.5px; }

.footer { margin-top: 8px; border-top: 1px solid #e2e8f0; padding-top: 6px;
          display: flex; justify-content: space-between; font-size: 7px; color: #94a3b8; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">EVALUACIÓN DE INDICADORES DE APRENDIZAJE — {{ strtoupper($asignacion->asignatura?->nombre ?? '') }}</div>
    <div class="sub">
        {{ $asignacion->grupo?->grado?->nombre ?? '' }} {{ $asignacion->grupo?->seccion?->nombre ?? '' }}
        &nbsp;·&nbsp; Período: {{ $periodo->nombre }}
        &nbsp;·&nbsp; Docente: {{ $asignacion->docente?->nombre_completo ?? '—' }}
        &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y') }}
    </div>
</div>

<div class="leyenda">
    <span class="e">E</span> Excelente &nbsp;
    <span class="b">B</span> Bueno &nbsp;
    <span class="ep">EP</span> En Proceso &nbsp;
    <span class="i">I</span> Insuficiente
</div>

<table>
    <thead>
        <tr>
            <th style="width:20px;">#</th>
            <th class="left" style="width:130px;">Estudiante</th>
            @foreach($indicadores as $ind)
            <th style="width:{{ max(25, intval(430 / max($indicadores->count(),1))) }}px;font-size:6px;" title="{{ $ind->descripcion }}">
                {{ mb_strimwidth($ind->descripcion ?? 'Ind.', 0, 18, '…') }}
            </th>
            @endforeach
            <th style="width:50px;">Resumen</th>
        </tr>
    </thead>
    <tbody>
        @foreach($matriculas as $i => $mat)
        @php
            $evMat = $evaluaciones[$mat->id] ?? collect();
            $niveles = ['Excelente' => 0, 'Bueno' => 0, 'En proceso' => 0, 'Insuficiente' => 0];
            foreach ($indicadores as $ind) {
                $ev = $evMat[$ind->id] ?? null;
                if ($ev && isset($niveles[$ev->nivel])) $niveles[$ev->nivel]++;
            }
            $total = array_sum($niveles);
        @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td class="name">{{ $mat->estudiante?->apellidos ?? $mat->estudiante?->apellido ?? '' }}, {{ mb_substr($mat->estudiante?->nombres ?? $mat->estudiante?->nombre ?? '', 0, 12) }}</td>
            @foreach($indicadores as $ind)
            @php
                $ev    = $evMat[$ind->id] ?? null;
                $nivel = $ev?->nivel ?? '';
                $cls   = match($nivel) { 'Excelente' => 'e', 'Bueno' => 'b', 'En proceso' => 'ep', 'Insuficiente' => 'i', default => '' };
                $sigla = match($nivel) { 'Excelente' => 'E', 'Bueno' => 'B', 'En proceso' => 'EP', 'Insuficiente' => 'I', default => '' };
            @endphp
            <td>
                @if($cls)<span class="{{ $cls }}">{{ $sigla }}</span>@endif
            </td>
            @endforeach
            <td style="font-size:6.5px;line-height:1.3;">
                @if($total > 0)
                @if($niveles['Excelente']>0)<span class="e">{{ $niveles['Excelente'] }}E</span> @endif
                @if($niveles['Bueno']>0)<span class="b">{{ $niveles['Bueno'] }}B</span> @endif
                @if($niveles['En proceso']>0)<span class="ep">{{ $niveles['En proceso'] }}EP</span> @endif
                @if($niveles['Insuficiente']>0)<span class="i">{{ $niveles['Insuficiente'] }}I</span> @endif
                @else <span style="color:#94a3b8;">—</span> @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    <span>{{ $inst }} — Evaluación de Indicadores: {{ $asignacion->asignatura?->nombre }} | {{ $periodo->nombre }}</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
