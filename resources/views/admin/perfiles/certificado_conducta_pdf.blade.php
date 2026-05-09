<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1e293b; }

.border-page { border: 3px solid #166534; padding: 20px; min-height: 680px; }
.inner { border: 1px solid #bbf7d0; padding: 16px; min-height: 638px; }

.header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #166534; padding-bottom: 14px; }
.header .inst  { font-size: 14px; font-weight: bold; color: #166534; text-transform: uppercase; letter-spacing: .04em; }
.header .dir   { font-size: 9px; color: #475569; margin-top: 3px; }
.header .label { font-size: 11px; font-weight: 800; color: #0f172a; margin-top: 10px;
                 background: #dcfce7; padding: 5px 20px; border-radius: 5px; display: inline-block; letter-spacing: .05em; }

.estudiante-card { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px;
                   padding: 10px 14px; margin-bottom: 18px; }
.est-row { display: flex; gap: 20px; flex-wrap: wrap; }
.est-col { flex: 1; }
.est-lbl { font-size: 7.5px; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 1px; }
.est-val { font-size: 10.5px; font-weight: 700; color: #1e293b; }

.cuerpo { font-size: 11px; line-height: 1.9; color: #1e293b; text-align: justify; margin: 18px 0 24px; }
.cuerpo strong { color: #166534; }

.datos-adicionales { border: 1px solid #bbf7d0; border-radius: 5px; padding: 10px 14px; margin-bottom: 20px; }
.dato-row { display: flex; gap: 10px; margin-bottom: 6px; }
.dato-lbl { font-size: 8px; font-weight: 700; text-transform: uppercase; color: #166534; width: 130px; flex-shrink: 0; }
.dato-val { font-size: 9.5px; color: #1e293b; flex: 1; border-bottom: 1px dotted #d1fae5; padding-bottom: 1px; }

.firmas { display: flex; gap: 30px; margin-top: 28px; }
.firma  { flex: 1; text-align: center; }
.firma-line { border-top: 1px solid #166534; padding-top: 6px; font-size: 8.5px; color: #374151; margin-top: 30px; }
.firma-name { font-weight: 700; font-size: 9.5px; }

.footer { border-top: 1px solid #e2e8f0; padding-top: 8px; margin-top: 18px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
</style>
</head>
<body>
<div class="border-page">
<div class="inner">

<div class="header">
    <div class="inst">{{ $si }}</div>
    @if($dir)<div class="dir">Director/a: {{ $dir }}</div>@endif
    @if($cod)<div class="dir">Código: {{ $cod }}</div>@endif
    <br>
    <span class="label">CERTIFICADO DE BUENA CONDUCTA</span>
</div>

<div class="estudiante-card">
    <div class="est-row">
        <div class="est-col">
            <div class="est-lbl">Estudiante</div>
            <div class="est-val">{{ $estudiante->nombre_completo }}</div>
        </div>
        <div class="est-col">
            <div class="est-lbl">No. Matrícula</div>
            <div class="est-val">{{ $estudiante->matricula ?? '—' }}</div>
        </div>
        <div class="est-col">
            <div class="est-lbl">Cédula / ID</div>
            <div class="est-val">{{ $estudiante->cedula ?? '—' }}</div>
        </div>
    </div>
    <div class="est-row" style="margin-top:6px;">
        <div class="est-col">
            <div class="est-lbl">Grado y Sección</div>
            <div class="est-val">{{ ($matricula->grupo->grado->nombre ?? '') . ' ' . ($matricula->grupo->seccion->nombre ?? '') }}</div>
        </div>
        <div class="est-col">
            <div class="est-lbl">Año Escolar</div>
            <div class="est-val">{{ $schoolYear->nombre }}</div>
        </div>
        <div class="est-col">
            <div class="est-lbl">Fecha de Expedición</div>
            <div class="est-val">{{ now()->format('d/m/Y') }}</div>
        </div>
    </div>
</div>

<div class="cuerpo">
    &nbsp;&nbsp;&nbsp;&nbsp;Por medio del presente documento, <strong>{{ $si }}</strong>,
    certifica que el/la estudiante <strong>{{ strtoupper($estudiante->nombre_completo) }}</strong>,
    con número de matrícula <strong>{{ $estudiante->matricula ?? '—' }}</strong>,
    quien cursa el <strong>{{ ($matricula->grupo->grado->nombre ?? '') . ' ' . ($matricula->grupo->seccion->nombre ?? '') }}</strong>
    durante el Año Escolar <strong>{{ $schoolYear->nombre }}</strong>,
    ha mostrado una conducta <strong>{{ $nivelConducta ?? 'EXCELENTE' }}</strong> dentro de las instalaciones de este
    centro educativo, demostrando respeto, responsabilidad y disciplina en todo momento.
    <br><br>
    &nbsp;&nbsp;&nbsp;&nbsp;La presente certificación se expide a solicitud del interesado/a para los fines que
    estime convenientes.
</div>

<div class="datos-adicionales">
    <div class="dato-row">
        <span class="dato-lbl">Nivel de Conducta</span>
        <span class="dato-val">{{ $nivelConducta ?? 'Excelente' }} ✓</span>
    </div>
    <div class="dato-row">
        <span class="dato-lbl">Período Evaluado</span>
        <span class="dato-val">Año Escolar {{ $schoolYear->nombre }}</span>
    </div>
    <div class="dato-row">
        <span class="dato-lbl">Observaciones</span>
        <span class="dato-val">
            @if(isset($faltas) && $faltas > 0)
                {{ $faltas }} anotacion(es) registrada(s) en el período
            @else
                Sin anotaciones disciplinarias registradas
            @endif
        </span>
    </div>
</div>

<div style="text-align:right;font-size:9.5px;color:#475569;margin-top:6px;">
    {{ $si }}, {{ now()->format('d') }} de {{ now()->translatedFormat('F') }} de {{ now()->format('Y') }}.
</div>

<div class="firmas">
    <div class="firma">
        <div class="firma-line">
            <div class="firma-name">{{ $dir ?: 'Director/a del Centro' }}</div>
            Director/a
        </div>
    </div>
    <div style="flex:1;text-align:center;margin-top:0;">
        <div style="width:72px;height:72px;border:2px dashed #166534;border-radius:50%;display:inline-block;margin-top:6px;line-height:72px;font-size:8px;color:#a7f3d0;">SELLO</div>
    </div>
    <div class="firma">
        <div class="firma-line">
            Encargado/a de Orientación
        </div>
    </div>
</div>

<div class="footer">
    <span>{{ $si }} — Certificado de Buena Conducta</span>
    <span>Emitido: {{ now()->format('d/m/Y') }}</span>
</div>

</div>
</div>
</body>
</html>
