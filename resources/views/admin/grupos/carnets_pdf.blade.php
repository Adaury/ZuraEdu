<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }

/* 3 carnets por fila × N filas */
.carnet-grid { display: flex; flex-wrap: wrap; gap: 8px; }
.carnet {
    width: 170px;
    min-height: 105px;
    border: 2px solid #1e3a6e;
    border-radius: 8px;
    overflow: hidden;
    page-break-inside: avoid;
    background: #fff;
}
.carnet-top {
    background: linear-gradient(135deg, #1e3a6e, #1d4ed8);
    color: #fff;
    padding: 5px 7px;
    text-align: center;
}
.carnet-top .inst-name { font-size: 6.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; line-height: 1.2; }
.carnet-top .carnet-title { font-size: 7px; font-weight: 700; margin-top: 2px; letter-spacing: .05em; opacity: .85; }

.carnet-body { padding: 6px 7px; display: flex; gap: 6px; align-items: flex-start; }
.avatar {
    width: 32px; height: 38px; border-radius: 4px;
    background: #dbeafe; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 800; color: #1d4ed8;
    border: 1px solid #bfdbfe;
}
.carnet-info { flex: 1; }
.carnet-nombre { font-size: 8px; font-weight: 800; color: #0f172a; line-height: 1.2; margin-bottom: 2px; }
.carnet-mat    { font-size: 7px; color: #6b7280; margin-bottom: 1px; }
.carnet-grupo  { font-size: 7px; color: #1d4ed8; font-weight: 700; }

.carnet-footer {
    background: #eff6ff;
    border-top: 1px solid #bfdbfe;
    padding: 3px 7px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.carnet-footer .año { font-size: 6.5px; color: #6b7280; }
.carnet-footer .firma-line { width: 55px; border-bottom: 1px solid #94a3b8; font-size: 5.5px; color: #94a3b8; text-align: center; padding-top: 2px; }

@page { margin: 1.2cm 1.4cm; }
</style>
</head>
<body>

<div style="text-align:center;margin-bottom:12px;border-bottom:1px solid #e2e8f0;padding-bottom:8px;">
    <div style="font-size:11px;font-weight:800;color:#1e3a6e;text-transform:uppercase;">{{ $inst }}</div>
    <div style="font-size:9px;color:#6b7280;margin-top:2px;">
        CARNETS ESTUDIANTILES — {{ $grupo->grado->nombre ?? '' }} {{ $grupo->seccion->nombre ?? '' }}
        &nbsp;·&nbsp; Año: {{ $grupo->schoolYear->nombre ?? '' }}
    </div>
</div>

<div class="carnet-grid">
    @foreach($grupo->matriculas as $mat)
    @php
        $est    = $mat->estudiante;
        $nombre = $est->nombres ?? $est->nombre ?? '';
        $apell  = $est->apellidos ?? $est->apellido ?? '';
        $inicial = strtoupper(mb_substr($apell, 0, 1) . mb_substr($nombre, 0, 1));
    @endphp
    <div class="carnet">
        <div class="carnet-top">
            <div class="inst-name">{{ mb_strimwidth($inst, 0, 30, '...') }}</div>
            <div class="carnet-title">CARNET ESTUDIANTIL</div>
        </div>
        <div class="carnet-body">
            <div class="avatar">{{ $inicial }}</div>
            <div class="carnet-info">
                <div class="carnet-nombre">{{ mb_strimwidth($apell, 0, 14, '.') }}, {{ mb_strimwidth($nombre, 0, 12, '.') }}</div>
                <div class="carnet-mat">Mat: {{ $est->matricula ?? '—' }}</div>
                @if($est->cedula)
                <div class="carnet-mat">Céd: {{ $est->cedula }}</div>
                @endif
                <div class="carnet-grupo">{{ $grupo->grado->nombre ?? '' }} {{ $grupo->seccion->nombre ?? '' }}</div>
            </div>
        </div>
        <div class="carnet-footer">
            <div class="año">A.E. {{ $grupo->schoolYear?->nombre ?? '' }}</div>
            <div class="firma-line">Director/a</div>
        </div>
    </div>
    @endforeach
</div>

</body>
</html>
