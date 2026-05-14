<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #111; background: #fff; }
  .page { padding: 1.6cm 2cm; }

  /* Encabezado */
  .header { border-bottom: 3px double #1a3a6e; padding-bottom: 10px; margin-bottom: 16px; }
  .header-top { display: flex; justify-content: space-between; align-items: flex-start; }
  .header-left .inst-name { font-size: 14pt; font-weight: bold; color: #1a3a6e; }
  .header-left .inst-sub  { font-size: 8pt; color: #555; margin-top: 2px; }
  .header-right { text-align: right; font-size: 8.5pt; color: #555; }
  .doc-title { text-align: center; font-size: 13pt; font-weight: bold; color: #2a5caa;
               text-transform: uppercase; letter-spacing: .8px; margin-top: 8px; }

  /* Info docente */
  .docente-card { background: #f0f4fb; border: 1px solid #c5d3ec; border-radius: 4px;
                  padding: 10px 14px; margin-bottom: 16px; }
  .docente-card table { width: 100%; border: none; }
  .docente-card td { padding: 2px 6px; font-size: 9.5pt; border: none; background: transparent; }
  .label { color: #555; font-weight: bold; width: 130px; }

  /* Resumen stats */
  .stats-row { display: flex; gap: 10px; margin-bottom: 16px; }
  .stat-box { flex: 1; border: 1px solid #c5d3ec; border-radius: 4px; padding: 8px 10px; text-align: center; background: #f8faff; }
  .stat-value { font-size: 16pt; font-weight: bold; color: #1a3a6e; }
  .stat-label { font-size: 7.5pt; color: #666; }

  /* Tabla por asignatura */
  h3 { font-size: 10pt; font-weight: bold; color: #1a3a6e; border-bottom: 1.5px solid #1a3a6e;
       padding-bottom: 3px; margin-bottom: 8px; margin-top: 16px; }
  table.main { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
  table.main thead tr { background: #1a3a6e; color: #fff; }
  table.main thead th { padding: 5px 7px; text-align: left; }
  table.main tbody tr:nth-child(even) { background: #f4f7fb; }
  table.main tbody td { padding: 4px 7px; border-bottom: 1px solid #dce3f0; vertical-align: middle; }

  /* Barra de progreso mini */
  .bar-wrap { background: #dce3f0; border-radius: 3px; height: 7px; width: 80px; display: inline-block; vertical-align: middle; }
  .bar-fill  { background: #2a5caa; border-radius: 3px; height: 7px; }

  /* Firmas */
  .firma-section { margin-top: 36px; display: flex; justify-content: space-between; }
  .firma-box { text-align: center; width: 42%; }
  .firma-line { border-top: 1.5px solid #333; margin-bottom: 4px; }
  .firma-name { font-weight: bold; font-size: 9.5pt; }
  .firma-role { font-size: 8.5pt; color: #555; }

  /* Pie */
  .footer { margin-top: 22px; font-size: 7.5pt; color: #888; text-align: center;
            border-top: 1px solid #ccc; padding-top: 5px; }
</style>
</head>
<body>
<div class="page">

  {{-- Encabezado --}}
  <div class="header">
    <div class="header-top">
      <div class="header-left">
        <div class="inst-name">{{ $inst }}</div>
        <div class="inst-sub">Sistema de Gestión Escolar — SGE PSAC</div>
      </div>
      <div class="header-right">
        Año Escolar: <strong>{{ $schoolYear?->nombre ?? now()->year }}</strong><br>
        Fecha: {{ $fecha->format('d/m/Y') }}
      </div>
    </div>
    <div class="doc-title">Ficha de Actividad Docente</div>
  </div>

  {{-- Info docente --}}
  <div class="docente-card">
    <table>
      <tr>
        <td class="label">Docente:</td>
        <td><strong>{{ $docente->nombre_completo }}</strong></td>
        <td class="label">Cédula:</td>
        <td>{{ $docente->cedula }}</td>
      </tr>
      <tr>
        <td class="label">Especialidad:</td>
        <td>{{ $docente->especialidad ?? '—' }}</td>
        <td class="label">Email:</td>
        <td>{{ $docente->email ?? auth()->user()->email }}</td>
      </tr>
      @if($docente->telefono)
      <tr>
        <td class="label">Teléfono:</td>
        <td>{{ $docente->telefono }}</td>
        <td></td><td></td>
      </tr>
      @endif
    </table>
  </div>

  {{-- Resumen general --}}
  @php
    $totalMaterias   = $asignaciones->count();
    $totalEstudiantes = array_sum(array_column($stats, 'total_estudiantes'));
    $promedioGlobal  = collect($stats)->whereNotNull('promedio')->avg('promedio');
    $pctGlobal       = collect($stats)->whereNotNull('pct_aprobacion')->avg('pct_aprobacion');
  @endphp

  <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
    <tr>
      <td style="width:25%;text-align:center;border:1px solid #c5d3ec;padding:8px;border-radius:4px;background:#f0f4fb;">
        <div style="font-size:18pt;font-weight:bold;color:#1a3a6e;">{{ $totalMaterias }}</div>
        <div style="font-size:8pt;color:#666;">Materias asignadas</div>
      </td>
      <td style="width:5%;"></td>
      <td style="width:25%;text-align:center;border:1px solid #c5d3ec;padding:8px;border-radius:4px;background:#f0f4fb;">
        <div style="font-size:18pt;font-weight:bold;color:#1a3a6e;">{{ $totalEstudiantes }}</div>
        <div style="font-size:8pt;color:#666;">Total estudiantes</div>
      </td>
      <td style="width:5%;"></td>
      <td style="width:25%;text-align:center;border:1px solid #c5d3ec;padding:8px;border-radius:4px;background:#f0f4fb;">
        <div style="font-size:18pt;font-weight:bold;color:#1a3a6e;">
          {{ $promedioGlobal ? number_format($promedioGlobal,1) : '—' }}
        </div>
        <div style="font-size:8pt;color:#666;">Promedio global</div>
      </td>
      <td style="width:5%;"></td>
      <td style="width:25%;text-align:center;border:1px solid #c5d3ec;padding:8px;border-radius:4px;background:#f0f4fb;">
        <div style="font-size:18pt;font-weight:bold;color:#{{ $pctGlobal >= 70 ? '16a34a' : ($pctGlobal >= 50 ? 'd97706' : 'dc2626') }};">
          {{ $pctGlobal ? number_format($pctGlobal,1).'%' : '—' }}
        </div>
        <div style="font-size:8pt;color:#666;">% aprobación global</div>
      </td>
    </tr>
  </table>

  {{-- Detalle por asignatura --}}
  <h3>Detalle por Asignatura</h3>
  <table class="main">
    <thead>
      <tr>
        <th>Asignatura</th>
        <th>Grado / Sección</th>
        <th style="text-align:center;">Estudiantes</th>
        <th style="text-align:center;">Promedio</th>
        <th style="text-align:center;">Aprobados</th>
        <th style="text-align:center;">% Aprob.</th>
        <th style="text-align:center;">Clases<br>Registradas</th>
      </tr>
    </thead>
    <tbody>
      @forelse($asignaciones as $asig)
      @php $s = $stats[$asig->id] ?? []; @endphp
      <tr>
        <td><strong>{{ $asig->asignatura?->nombre ?? '—' }}</strong></td>
        <td>{{ $asig->grupo?->grado?->nombre ?? '' }} {{ $asig->grupo?->seccion?->nombre ?? '' }}</td>
        <td style="text-align:center;">{{ $s['total_estudiantes'] ?? '—' }}</td>
        <td style="text-align:center;">
          @if(isset($s['promedio']) && $s['promedio'] !== null)
            <span style="font-weight:bold;color:#{{ $s['promedio'] >= 70 ? '16a34a' : ($s['promedio'] >= 60 ? 'd97706' : 'dc2626') }};">
              {{ number_format($s['promedio'],1) }}
            </span>
          @else
            <span style="color:#999;">—</span>
          @endif
        </td>
        <td style="text-align:center;">
          {{ isset($s['aprobados']) ? $s['aprobados'] : '—' }}
          @if(isset($s['total_estudiantes']) && $s['total_estudiantes'] > 0)
          / {{ $s['total_estudiantes'] }}
          @endif
        </td>
        <td style="text-align:center;">
          @if(isset($s['pct_aprobacion']) && $s['pct_aprobacion'] !== null)
            @php $pct = $s['pct_aprobacion']; $barW = max(0, min(80, round($pct * 80 / 100))); @endphp
            <span style="color:#{{ $pct >= 70 ? '16a34a' : ($pct >= 50 ? 'd97706' : 'dc2626') }};font-weight:bold;">{{ $pct }}%</span><br>
            <div class="bar-wrap"><div class="bar-fill" style="width:{{ $barW }}px;background:#{{ $pct >= 70 ? '16a34a' : ($pct >= 50 ? 'd97706' : 'dc2626') }};"></div></div>
          @else
            <span style="color:#999;">—</span>
          @endif
        </td>
        <td style="text-align:center;">{{ $s['clases_registradas'] ?? '—' }}</td>
      </tr>
      @empty
      <tr>
        <td colspan="7" style="text-align:center;color:#999;padding:16px;">
          No hay asignaciones registradas para este período.
        </td>
      </tr>
      @endforelse
    </tbody>
  </table>

  {{-- Notas / observaciones opcionales --}}
  @if($docente->observaciones ?? false)
  <h3>Observaciones del Docente</h3>
  <p style="font-size:9.5pt;line-height:1.6;white-space:pre-line;">{{ $docente->observaciones }}</p>
  @endif

  {{-- Firmas --}}
  <div class="firma-section">
    <div class="firma-box">
      <br><br>
      <div class="firma-line"></div>
      <div class="firma-name">{{ $director ?: 'Director/a del Plantel' }}</div>
      <div class="firma-role">Director(a) — {{ $inst }}</div>
    </div>
    <div class="firma-box">
      <br><br>
      <div class="firma-line"></div>
      <div class="firma-name">{{ $docente->nombre_completo }}</div>
      <div class="firma-role">Firma del Docente</div>
    </div>
  </div>

  <div class="footer">
    Documento generado electrónicamente por SGE PSAC el {{ $fecha->format('d/m/Y H:i') }}
    · Este documento refleja los datos registrados en el sistema hasta la fecha de emisión.
  </div>

</div>
</body>
</html>
