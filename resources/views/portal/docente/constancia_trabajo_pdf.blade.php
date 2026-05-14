<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'DejaVu Serif', serif; font-size: 11pt; color: #111; background: #fff; }
  .page { padding: 2cm 2.2cm; }

  /* Encabezado institucional */
  .header { text-align: center; border-bottom: 3px double #1a3a6e; padding-bottom: 10px; margin-bottom: 18px; }
  .header .inst-name { font-size: 15pt; font-weight: bold; color: #1a3a6e; letter-spacing: .5px; }
  .header .inst-sub  { font-size: 9pt; color: #444; margin-top: 3px; }
  .header .doc-type  { font-size: 13pt; font-weight: bold; color: #2a5caa; margin-top: 8px; text-transform: uppercase; letter-spacing: 1px; }

  /* Cuerpo */
  .body-text { text-align: justify; line-height: 1.8; font-size: 11.5pt; margin-bottom: 14px; }
  .highlight  { font-weight: bold; }

  /* Tabla asignaciones */
  table { width: 100%; border-collapse: collapse; margin: 18px 0; font-size: 9.5pt; }
  thead tr { background: #1a3a6e; color: #fff; }
  thead th { padding: 6px 8px; text-align: left; }
  tbody tr:nth-child(even) { background: #f0f4fb; }
  tbody td { padding: 5px 8px; border-bottom: 1px solid #dce3f0; }

  /* Firmas */
  .firma-section { margin-top: 48px; display: flex; justify-content: space-between; }
  .firma-box { text-align: center; width: 42%; }
  .firma-line { border-top: 1.5px solid #333; margin-bottom: 4px; }
  .firma-name { font-weight: bold; font-size: 10pt; }
  .firma-role { font-size: 9pt; color: #555; }

  /* Pie */
  .footer { margin-top: 30px; font-size: 8.5pt; color: #777; text-align: center; border-top: 1px solid #ccc; padding-top: 6px; }

  /* Sello */
  .sello { text-align: center; margin: 10px 0 4px; }
  .sello-circle { display: inline-block; width: 90px; height: 90px; border: 3px solid #1a3a6e; border-radius: 50%; line-height: 84px; font-size: 8pt; color: #1a3a6e; font-weight: bold; }
</style>
</head>
<body>
<div class="page">

  {{-- Encabezado --}}
  <div class="header">
    <div class="inst-name">{{ strtoupper($inst) }}</div>
    <div class="inst-sub">Sistema de Gestión Escolar — SGE PSAC</div>
    <div class="doc-type">Constancia de Trabajo</div>
  </div>

  {{-- Cuerpo principal --}}
  <p class="body-text">
    Quien suscribe, Director(a) de <span class="highlight">{{ $inst }}</span>,
    hace constar por medio de la presente que:
  </p>

  <p class="body-text" style="margin-left:20px; border-left:4px solid #2a5caa; padding-left:12px; background:#f5f8ff; padding-top:6px; padding-bottom:6px;">
    El/La docente <span class="highlight">{{ $docente->nombre_completo }}</span>,
    portador(a) de la cédula de identidad
    <span class="highlight">{{ $docente->cedula }}</span>,
    @if($docente->especialidad)
    con especialidad en <span class="highlight">{{ $docente->especialidad }}</span>,
    @endif
    labora en esta institución de forma activa al momento de emitirse esta constancia.
  </p>

  @if($asignaciones->count() > 0)
  <p class="body-text">
    Durante el año escolar
    <span class="highlight">{{ $schoolYear?->nombre ?? now()->year }}</span>,
    el/la docente tiene asignadas las siguientes materias:
  </p>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Asignatura</th>
        <th>Grado / Sección</th>
      </tr>
    </thead>
    <tbody>
      @foreach($asignaciones as $i => $asig)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $asig->asignatura?->nombre ?? '—' }}</td>
        <td>
          {{ $asig->grupo?->grado?->nombre ?? '' }}
          {{ $asig->grupo?->seccion?->nombre ?? '' }}
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif

  <p class="body-text">
    La presente constancia se expide a petición del/la interesado(a) para los fines legales
    que estime conveniente.
  </p>

  <p class="body-text" style="margin-top:10px;">
    Emitida en <span class="highlight">{{ $inst }}</span>,
    a los <span class="highlight">{{ $fecha->day }}</span> días del mes de
    <span class="highlight">{{ $fecha->translatedFormat('F') }}</span>
    del año <span class="highlight">{{ $fecha->year }}</span>.
  </p>

  {{-- Sección de firmas --}}
  <div class="firma-section">
    <div class="firma-box">
      <br><br>
      <div class="firma-line"></div>
      <div class="firma-name">{{ $director ?: 'Director/a del Plantel' }}</div>
      <div class="firma-role">Director(a) — {{ $inst }}</div>
    </div>
    <div class="firma-box" style="text-align:center;">
      <div class="sello">
        <div class="sello-circle">SELLO<br>OFICIAL</div>
      </div>
    </div>
    <div class="firma-box">
      <br><br>
      <div class="firma-line"></div>
      <div class="firma-name">{{ $docente->nombre_completo }}</div>
      <div class="firma-role">Docente — Firma del Interesado(a)</div>
    </div>
  </div>

  <div class="footer">
    Documento generado electrónicamente por SGE PSAC el {{ $fecha->format('d/m/Y H:i') }}
    · Este documento tiene validez oficial con sello institucional y firma autógrafa.
  </div>

</div>
</body>
</html>
