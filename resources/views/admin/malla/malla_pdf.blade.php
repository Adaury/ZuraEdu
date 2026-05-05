<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 7px; color: #1e293b; }
@page { size: letter landscape; margin: .8cm 1cm; }

.header { text-align: center; margin-bottom: 10px; border-bottom: 2px solid #1e3a6e; padding-bottom: 7px; }
.header .inst  { font-size: 11px; font-weight: bold; color: #1e3a6e; text-transform: uppercase; }
.header .titulo{ font-size: 10px; font-weight: bold; color: #0f172a; margin-top: 4px; }
.header .sub   { font-size: 7.5px; color: #6b7280; margin-top: 3px; }

table { width: 100%; border-collapse: collapse; }
thead .row-grado th { background: #1e3a6e; color: #fff; font-size: 7px; font-weight: 700;
                      padding: 4px 3px; text-align: center; border: 1px solid #1e3a8a; }
thead .row-grado th.asig-col { background: #f8faff; color: #374151; text-align: left; padding-left: 5px; }

tbody tr { height: 18px; }
tbody tr:nth-child(even) { background: #f8faff; }
tbody td { border: 1px solid #e2e8f0; font-size: 7px; text-align: center; vertical-align: middle; padding: 2px 3px; }
tbody td.asig-name { text-align: left; padding-left: 5px; font-weight: 600; background: #f0f4ff; border-right: 2px solid #1e3a6e; }

.tick { color: #15803d; font-weight: 900; font-size: 8px; }
.horas { color: #1d4ed8; font-size: 6.5px; }
.tick-wrap { display: flex; flex-direction: column; align-items: center; gap: 1px; }

.footer { margin-top: 8px; border-top: 1px solid #e2e8f0; padding-top: 6px;
          display: flex; justify-content: space-between; font-size: 7px; color: #94a3b8; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">MALLA CURRICULAR INSTITUCIONAL</div>
    <div class="sub">Generado: {{ now()->format('d/m/Y') }}</div>
</div>

<table>
    <thead>
        <tr class="row-grado">
            <th class="asig-col" style="width:140px;">Asignatura</th>
            @foreach($grados as $grado)
            <th style="min-width:45px;max-width:65px;font-size:6.5px;">{{ mb_strimwidth($grado->nombre, 0, 12, '…') }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($asignaturas as $asig)
        @php
            $tieneAlguno = false;
            foreach ($grados as $g) {
                if (isset($mallaMap[$g->id . '_' . $asig->id])) { $tieneAlguno = true; break; }
            }
        @endphp
        @if($tieneAlguno)
        <tr>
            <td class="asig-name">{{ $asig->nombre }}</td>
            @foreach($grados as $grado)
            @php $entry = $mallaMap[$grado->id . '_' . $asig->id] ?? null; @endphp
            <td>
                @if($entry)
                <div class="tick-wrap">
                    <span class="tick">✓</span>
                    @if($entry->horas_semanales)<span class="horas">{{ $entry->horas_semanales }}h</span>@endif
                </div>
                @endif
            </td>
            @endforeach
        </tr>
        @endif
        @endforeach
    </tbody>
</table>

<div class="footer">
    <span>{{ $inst }} — Malla Curricular</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
