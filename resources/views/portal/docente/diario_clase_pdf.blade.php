<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { margin:0;padding:0;box-sizing:border-box; }
body { font-family:DejaVu Sans,sans-serif;font-size:10pt;color:#1e293b;background:#fff; }
.page-header { border-bottom:2.5px solid #0ea5e9;padding-bottom:10px;margin-bottom:18px; }
.inst-name { font-size:12pt;font-weight:bold;color:#0ea5e9; }
.doc-title { font-size:10pt;color:#64748b;margin-top:2px; }
.meta-row { display:flex;gap:20px;margin-top:8px;font-size:9pt;color:#475569; }
.meta-item { display:inline-flex;gap:5px; }
.meta-label { font-weight:bold; }
.mes-header {
    background:#e0f2fe;border-left:4px solid #0ea5e9;
    padding:5px 10px;font-size:9.5pt;font-weight:bold;
    color:#0369a1;margin:16px 0 8px;border-radius:3px;
}
.entrada { border:1px solid #e2e8f0;border-radius:5px;padding:10px 12px;margin-bottom:8px; }
.entrada-fecha { font-size:9pt;font-weight:bold;color:#0ea5e9;margin-bottom:6px; }
.campo-label { font-size:7.5pt;font-weight:bold;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:2px; }
.campo-val { font-size:9pt;color:#334155;margin-bottom:6px; }
.incidencia-box { background:#fef2f2;border:1px solid #fca5a5;border-radius:4px;padding:5px 8px;margin-top:4px; }
.incidencia-box span { font-size:8pt;font-weight:bold;color:#dc2626; }
.footer { margin-top:24px;border-top:1px solid #e2e8f0;padding-top:8px;display:flex;justify-content:space-between;font-size:8pt;color:#94a3b8; }
</style>
</head>
<body>

<div class="page-header">
    <div class="inst-name">{{ $inst }}</div>
    <div class="doc-title">Diario de Clase</div>
    <table style="margin-top:8px;width:100%;font-size:9pt;color:#475569;">
        <tr>
            <td><strong>Docente:</strong> {{ $docente->nombre_completo ?? $docente->user?->name }}</td>
            <td><strong>Asignatura:</strong> {{ $asignacion->asignatura?->nombre }}</td>
            <td><strong>Grupo:</strong> {{ $asignacion->grupo?->nombre_completo }}</td>
            <td><strong>Período:</strong>
                @if($mes)
                    {{ \Carbon\Carbon::create($anio, $mes)->translatedFormat('F Y') }}
                @else
                    Año {{ $anio }}
                @endif
            </td>
        </tr>
    </table>
</div>

@php $mesActual = null; @endphp
@forelse($entradas as $entrada)
    @php $mesEntrada = $entrada->fecha->format('Y-m'); @endphp
    @if($mesEntrada !== $mesActual)
        @php $mesActual = $mesEntrada; @endphp
        <div class="mes-header">{{ $entrada->fecha->translatedFormat('F Y') }}</div>
    @endif

    <div class="entrada">
        <div class="entrada-fecha">
            {{ $entrada->fecha->translatedFormat('l, d \d\e F \d\e Y') }}
            @if($entrada->asistentes !== null)
                &nbsp;·&nbsp; {{ $entrada->asistentes }} asistentes
            @endif
        </div>

        <span class="campo-label">Tema</span>
        <div class="campo-val" style="font-weight:bold;">{{ $entrada->tema }}</div>

        @if($entrada->actividades)
        <span class="campo-label">Actividades</span>
        <div class="campo-val">{{ $entrada->actividades }}</div>
        @endif

        @if($entrada->observaciones)
        <span class="campo-label">Observaciones</span>
        <div class="campo-val">{{ $entrada->observaciones }}</div>
        @endif

        @if($entrada->incidencias)
        <div class="incidencia-box">
            <span>⚠ Incidencia</span>
            <div style="font-size:9pt;color:#dc2626;margin-top:3px;">{{ $entrada->incidencias }}</div>
        </div>
        @endif
    </div>
@empty
    <p style="color:#94a3b8;text-align:center;padding:20px;">No hay entradas para el período seleccionado.</p>
@endforelse

<div class="footer">
    <span>{{ $inst }}</span>
    <span>Generado el {{ now()->translatedFormat('d \d\e F \d\e Y, H:i') }}</span>
    <span>{{ $entradas->count() }} entrada{{ $entradas->count() !== 1 ? 's' : '' }}</span>
</div>
</body>
</html>
