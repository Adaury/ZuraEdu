<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
{{-- dompdf NO soporta display:flex, gradientes ni rgba(): la cuadrícula es una tabla de 3 columnas y los colores son sólidos. --}}
<style>
@page { margin: 14mm 10mm; }
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }
table.rejilla { width: 100%; border-collapse: separate; border-spacing: 6px; }
table.rejilla td.celda { width: 33.33%; vertical-align: top; }
table.carnet { width: 100%; border: 2px solid #1e3a6e; border-collapse: collapse; page-break-inside: avoid; background: #ffffff; }
.carnet-top { background: #1e3a6e; color: #ffffff; padding: 5px 7px; text-align: center; }
.carnet-top .inst-name { font-size: 6.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .03em; line-height: 1.2; }
.carnet-top .carnet-title { font-size: 7px; font-weight: bold; margin-top: 2px; letter-spacing: .05em; color: #cfe0ff; }
.avatar { width: 34px; height: 22px; padding-top: 12px; line-height: 1; background: #dbeafe; color: #1d4ed8; border: 1px solid #93c5fd; border-radius: 6px; text-align: center; font-size: 13px; font-weight: bold; }
.carnet-nombre { font-size: 8.5px; font-weight: bold; color: #0f172a; line-height: 1.2; }
.carnet-mat { font-size: 7px; color: #6b7280; margin-top: 1px; }
.carnet-grupo { font-size: 7.5px; font-weight: bold; color: #1d4ed8; margin-top: 2px; }
.carnet-footer td { background: #eff6ff; border-top: 1px solid #bfdbfe; padding: 4px 7px; vertical-align: bottom; }
.carnet-footer .año { font-size: 8px; color: #475569; }
.carnet-footer .firma-line { border-bottom: 1px solid #94a3b8; font-size: 5.5px; color: #94a3b8; text-align: center; padding-top: 8px; }
</style>
</head>
<body>

<div style="text-align:center;margin-bottom:12px;border-bottom:1px solid #e2e8f0;padding-bottom:8px;">
    <div style="font-size:11px;font-weight:bold;color:#1e3a6e;text-transform:uppercase;">{{ $inst }}</div>
    <div style="font-size:9px;color:#6b7280;margin-top:2px;">
        CARNETS ESTUDIANTILES — {{ $grupo->grado->nombre ?? '' }} {{ $grupo->seccion->nombre ?? '' }}
        &nbsp;·&nbsp; Año: {{ $grupo->schoolYear->nombre ?? '' }}
    </div>
</div>

@php $logoBlanco = \App\Support\Marca::logoDataUri('png-blanco'); @endphp
<table class="rejilla" cellspacing="6">
@foreach($grupo->matriculas->chunk(3) as $fila)
    <tr>
    @foreach($fila as $mat)
        @php
            $est     = $mat->estudiante;
            $nombre  = $est->nombres ?? $est->nombre ?? '';
            $apell   = $est->apellidos ?? $est->apellido ?? '';
            $inicial = strtoupper(mb_substr($apell, 0, 1) . mb_substr($nombre, 0, 1));
        @endphp
        <td class="celda">
            <table class="carnet" cellspacing="0" cellpadding="0">
                <tr><td class="carnet-top">
                    <img src="{{ $logoBlanco }}" alt="ZuraEdu" style="height:9px;width:auto;"><br>
                    <div class="inst-name" style="margin-top:3px;">{{ mb_strimwidth($inst, 0, 30, '...') }}</div>
                    <div class="carnet-title">CARNET ESTUDIANTIL</div>
                </td></tr>
                <tr><td style="padding:8px 7px 6px;">
                    <table style="width:100%;" cellspacing="0" cellpadding="0"><tr>
                        <td style="width:42px;vertical-align:top;"><div class="avatar">{{ $inicial }}</div></td>
                        <td style="vertical-align:top;">
                            <div class="carnet-nombre">{{ mb_strimwidth($apell, 0, 14, '.') }}, {{ mb_strimwidth($nombre, 0, 12, '.') }}</div>
                            <div class="carnet-mat">Mat: {{ $est->matricula ?? '—' }}</div>
                            @if($est->cedula)
                            <div class="carnet-mat">Céd: {{ $est->cedula }}</div>
                            @endif
                            <div class="carnet-grupo">{{ $grupo->grado->nombre ?? '' }} {{ $grupo->seccion->nombre ?? '' }}</div>
                        </td>
                    </tr></table>
                </td></tr>
                <tr class="carnet-footer"><td>
                    <table style="width:100%;" cellspacing="0" cellpadding="0"><tr>
                        <td style="width:50%;background:none;border:0;padding:0;"><div class="año">A.E. {{ $grupo->schoolYear?->nombre ?? '' }}</div></td>
                        <td style="width:50%;background:none;border:0;padding:0;"><div class="firma-line">Director/a</div></td>
                    </tr></table>
                </td></tr>
            </table>
        </td>
    @endforeach
    @for($i = $fila->count(); $i < 3; $i++)<td class="celda"></td>@endfor
    </tr>
@endforeach
</table>

</body>
</html>
