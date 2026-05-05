<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family: DejaVu Sans, sans-serif; font-size:8.5px; color:#1e293b; }
@page { size:letter portrait; margin:1cm 1.2cm; }

/* ── Encabezado institucional ─────────────────────────────────────────── */
.header { text-align:center; margin-bottom:14px; border-bottom:3px solid #1e3a6e; padding-bottom:8px; }
.header .inst  { font-size:13px; font-weight:bold; color:#1e3a6e; text-transform:uppercase; letter-spacing:.5px; }
.header .doc-title { font-size:11px; font-weight:bold; color:#dc2626; margin-top:5px; text-transform:uppercase; letter-spacing:.3px; }
.header .sub   { font-size:7.5px; color:#6b7280; margin-top:3px; }

/* ── Datos del estudiante ─────────────────────────────────────────────── */
.student-box {
    border:1px solid #e2e8f0;
    border-radius:4px;
    padding:8px 10px;
    margin-bottom:12px;
    background:#f8faff;
    display:table;
    width:100%;
}
.student-box .field { display:table-cell; vertical-align:top; width:33%; }
.student-box .field .lbl { font-size:7px; color:#6b7280; font-weight:700; text-transform:uppercase; margin-bottom:2px; }
.student-box .field .val { font-size:8.5px; font-weight:600; color:#0f172a; }

/* ── Resumen conteos ──────────────────────────────────────────────────── */
.summary { display:table; width:100%; margin-bottom:12px; border-collapse:separate; border-spacing:4px; }
.summary-cell {
    display:table-cell;
    width:25%;
    text-align:center;
    padding:6px 4px;
    border-radius:4px;
}
.summary-cell .count { font-size:16px; font-weight:bold; }
.summary-cell .lbl   { font-size:7px; font-weight:700; text-transform:uppercase; margin-top:2px; }

/* ── Tabla de historial ───────────────────────────────────────────────── */
table { width:100%; border-collapse:collapse; }
thead tr th {
    background:#1e3a6e;
    color:#fff;
    font-size:7.5px;
    font-weight:700;
    padding:4px 5px;
    text-align:left;
}
tbody tr td { padding:5px; font-size:7.5px; border-bottom:1px solid #f0f4f8; vertical-align:top; }
tbody tr:nth-child(even) { background:#f8faff; }

/* ── Badges ───────────────────────────────────────────────────────────── */
.badge {
    display:inline-block; padding:2px 6px;
    border-radius:8px; font-size:6.5px; font-weight:700;
}
.badge-tardanza    { background:#fef3c7; color:#92400e; }
.badge-falta_leve  { background:#ffedd5; color:#9a3412; }
.badge-falta_grave { background:#fee2e2; color:#991b1b; }
.badge-suspension  { background:#ede9fe; color:#5b21b6; }
.badge-resuelto    { background:#d1fae5; color:#065f46; }
.badge-pendiente   { background:#fff7ed; color:#9a3412; }

/* ── Footer ───────────────────────────────────────────────────────────── */
.footer {
    margin-top:14px;
    border-top:1px solid #e2e8f0;
    padding-top:6px;
    display:table;
    width:100%;
    font-size:7px;
    color:#94a3b8;
}
.footer-l { display:table-cell; }
.footer-r { display:table-cell; text-align:right; }

/* ── Firma ────────────────────────────────────────────────────────────── */
.firma-area {
    display:table;
    width:100%;
    margin-top:20px;
}
.firma-cell {
    display:table-cell;
    width:40%;
    text-align:center;
    padding:0 10px;
}
.firma-line {
    border-top:1px solid #94a3b8;
    margin-top:30px;
    padding-top:4px;
    font-size:7px;
    color:#6b7280;
}
</style>
</head>
<body>

{{-- Encabezado institucional --}}
<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="doc-title">Expediente Disciplinario Estudiantil</div>
    <div class="sub">
        @if($schoolYear) Año Escolar: {{ $schoolYear->nombre }} — @endif
        Generado: {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

{{-- Datos del estudiante --}}
<div class="student-box">
    <div class="field">
        <div class="lbl">Estudiante</div>
        <div class="val">{{ $estudiante->nombre_completo }}</div>
    </div>
    <div class="field">
        <div class="lbl">No. Matrícula</div>
        <div class="val">{{ $estudiante->numero_matricula ?? '—' }}</div>
    </div>
    <div class="field">
        <div class="lbl">Cédula</div>
        <div class="val">{{ $estudiante->cedula ?? '—' }}</div>
    </div>
    <div class="field" style="margin-top:6px;">
        <div class="lbl">Tutor / Representante</div>
        <div class="val">{{ $estudiante->tutor_nombre ?? '—' }}</div>
    </div>
    <div class="field" style="margin-top:6px;">
        <div class="lbl">Tel. Tutor</div>
        <div class="val">{{ $estudiante->tutor_telefono ?? '—' }}</div>
    </div>
    <div class="field" style="margin-top:6px;">
        <div class="lbl">Total de Faltas</div>
        <div class="val" style="color:#dc2626;">{{ $faltas->count() }}</div>
    </div>
</div>

{{-- Resumen por tipo --}}
@php
$ordenTipos = ['tardanza','falta_leve','falta_grave','suspension'];
@endphp
<div class="summary">
    @foreach($ordenTipos as $key)
    @php
        $t     = $tipos[$key] ?? ['label' => ucfirst($key), 'color' => '#6b7280', 'bg' => '#f1f5f9'];
        $total = $conteosPorTipo[$key] ?? 0;
    @endphp
    <div class="summary-cell" style="background:{{ $t['bg'] }};">
        <div class="count" style="color:{{ $t['color'] }};">{{ $total }}</div>
        <div class="lbl"   style="color:{{ $t['color'] }};">{{ $t['label'] }}</div>
    </div>
    @endforeach
</div>

{{-- Tabla de historial --}}
@if($faltas->isNotEmpty())
<table>
    <thead>
        <tr>
            <th style="width:20px;">#</th>
            <th style="width:58px;">Fecha</th>
            <th style="width:70px;">Tipo</th>
            <th>Descripción</th>
            <th style="width:90px;">Docente</th>
            <th style="width:50px;">Estado</th>
            <th style="width:110px;">Notas Resolución</th>
        </tr>
    </thead>
    <tbody>
        @foreach($faltas as $i => $f)
        @php
            $tipoKey = $f->tipo;
            $ti      = $tipos[$tipoKey] ?? ['label' => ucfirst($tipoKey), 'color' => '#6b7280'];
        @endphp
        <tr>
            <td style="text-align:center;color:#9ca3af;">{{ $i + 1 }}</td>
            <td>{{ $f->fecha->format('d/m/Y') }}</td>
            <td>
                <span class="badge badge-{{ $tipoKey }}">{{ $ti['label'] }}</span>
            </td>
            <td style="line-height:1.4;">{{ $f->descripcion }}</td>
            <td style="font-size:7px;">{{ $f->docente?->nombre_completo ?? '—' }}</td>
            <td>
                @if($f->resuelto)
                <span class="badge badge-resuelto">Resuelto</span>
                @else
                <span class="badge badge-pendiente">Pendiente</span>
                @endif
            </td>
            <td style="font-size:7px;color:#374151;">{{ $f->notas_resolucion ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<div style="text-align:center;padding:1rem;color:#9ca3af;font-size:8px;border:1px dashed #e2e8f0;border-radius:4px;">
    Sin faltas disciplinarias registradas para este estudiante.
</div>
@endif

{{-- Área de firmas --}}
<div class="firma-area">
    <div class="firma-cell">
        <div class="firma-line">Director(a) del Plantel</div>
    </div>
    <div class="firma-cell" style="width:20%;"></div>
    <div class="firma-cell">
        <div class="firma-line">Orientador(a) Escolar</div>
    </div>
</div>

{{-- Footer --}}
<div class="footer">
    <div class="footer-l">{{ $inst }} — Expediente Disciplinario: {{ $estudiante->nombre_completo }}</div>
    <div class="footer-r">{{ now()->format('d/m/Y H:i') }}</div>
</div>

</body>
</html>
