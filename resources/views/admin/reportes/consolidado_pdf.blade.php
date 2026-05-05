<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1e293b; }

/* ── Header ── */
.header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #1e40af; padding-bottom: 10px; }
.header .inst  { font-size: 12px; font-weight: bold; color: #1e40af; text-transform: uppercase; }
.header .sub   { font-size: 9px; color: #475569; margin-top: 3px; }
.header .titulo{ font-size: 11px; font-weight: bold; color: #0f172a; margin-top: 6px; }

/* ── Meta row ── */
.meta { display: flex; justify-content: space-between; margin-bottom: 10px;
        background: #f1f5f9; padding: 6px 10px; border-radius: 4px; font-size: 8px; }
.meta span { color: #475569; }
.meta strong { color: #0f172a; }

/* ── Table ── */
table { width: 100%; border-collapse: collapse; }
thead tr { background: #1e40af; color: #fff; }
thead th { padding: 5px 4px; text-align: center; font-size: 7.5px; border: 1px solid #1e40af; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #f8faff; }
tbody tr:hover { background: #eff6ff; }
tbody td { padding: 4px; border: 1px solid #e2e8f0; font-size: 8px; text-align: center; }
tbody td.name { text-align: left; font-weight: 600; }
tbody td.ord  { color: #94a3b8; font-size: 7.5px; }

/* ── Nota badges ── */
.nota-a  { color: #15803d; font-weight: 700; }
.nota-b  { color: #1d4ed8; font-weight: 700; }
.nota-p  { color: #b45309; font-weight: 600; }
.nota-i  { color: #dc2626; font-weight: 700; }
.nota-nr { color: #94a3b8; }

/* ── Situacion ── */
.sit-a { color: #15803d; font-weight: bold; }
.sit-r { color: #dc2626; font-weight: bold; }

/* ── Footer ── */
.footer { margin-top: 12px; border-top: 1px solid #e2e8f0; padding-top: 8px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
.leyenda { margin-top: 8px; font-size: 7.5px; color: #64748b; }
.leyenda span { margin-right: 12px; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="inst">{{ $boletinConfig?->nombre_institucion ?? config('app.name') }}</div>
    <div class="sub">{{ $boletinConfig?->director ? 'Director: ' . $boletinConfig->director : '' }}</div>
    <div class="titulo">REPORTE CONSOLIDADO DE CALIFICACIONES</div>
    <div class="sub" style="margin-top:4px;">
        {{ $grupo->grado->nombre ?? '' }} {{ $grupo->seccion->nombre ?? '' }}
        &nbsp;|&nbsp;
        Año Escolar: {{ $schoolYear?->nombre ?? '—' }}
    </div>
</div>

{{-- Meta --}}
<div class="meta">
    <div><span>Grupo: </span><strong>{{ $grupo->nombre_completo }}</strong></div>
    <div><span>Total estudiantes: </span><strong>{{ count($registros) }}</strong></div>
    <div><span>Generado: </span><strong>{{ now()->format('d/m/Y H:i') }}</strong></div>
</div>

{{-- Tabla --}}
<table>
    <thead>
        <tr>
            <th class="left" style="width:22px;">#</th>
            <th class="left" style="width:140px;">Estudiante</th>
            @foreach($asignaciones as $asi)
                <th style="width:40px; max-width:50px;">
                    {{ \Illuminate\Support\Str::limit($asi->asignatura?->nombre ?? '—', 12) }}
                </th>
            @endforeach
            <th style="width:40px;">Promedio</th>
            <th style="width:42px;">Situación</th>
        </tr>
    </thead>
    <tbody>
    @foreach($registros as $id => $reg)
        @php
            $est  = $reg['estudiante'];
            $cals = $reg['academicas']->keyBy('asignacion_id');
            $prom = $reg['promedio'] ?? null;
            $sit  = $reg['situacion'] ?? null;
            $i    = $loop->iteration;
        @endphp
        <tr>
            <td class="ord">{{ $i }}</td>
            <td class="name">{{ $est?->apellidos }}, {{ $est?->nombres }}</td>
            @foreach($asignaciones as $asi)
                @php
                    $cal     = $cals[$asi->id] ?? null;
                    $nota    = $cal?->nota_final;
                    $indic   = $cal?->indicador ?? '';
                    $clase   = match(true) {
                        $indic === 'Excelente'  => 'nota-a',
                        $indic === 'Bueno'      => 'nota-b',
                        $indic === 'En proceso' => 'nota-p',
                        $indic === 'Insuficiente'=> 'nota-i',
                        default                  => 'nota-nr',
                    };
                @endphp
                <td class="{{ $clase }}">
                    {{ $nota !== null ? number_format($nota, 1) : '—' }}
                </td>
            @endforeach
            <td style="font-weight:700; color:{{ $prom !== null ? ($prom >= 60 ? '#15803d' : '#dc2626') : '#94a3b8' }};">
                {{ $prom !== null ? number_format($prom, 2) : '—' }}
            </td>
            <td class="{{ $sit === 'A' ? 'sit-a' : ($sit === 'R' ? 'sit-r' : '') }}">
                {{ $sit === 'A' ? 'Aprobado' : ($sit === 'R' ? 'Reprobado' : 'S/R') }}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

{{-- Leyenda --}}
<div class="leyenda">
    <strong>Indicadores: </strong>
    <span class="nota-a">■ Excelente (90-100)</span>
    <span class="nota-b">■ Bueno (75-89)</span>
    <span class="nota-p">■ En proceso (60-74)</span>
    <span class="nota-i">■ Insuficiente (0-59)</span>
</div>

{{-- Footer --}}
<div class="footer">
    <span>{{ config('app.name') }}</span>
    <span>Documento generado automáticamente — {{ now()->format('d/m/Y H:i:s') }}</span>
</div>

</body>
</html>
