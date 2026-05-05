<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 7pt; color: #1e293b; margin: 0; padding: 0; }
    .header { text-align: center; margin-bottom: 10px; }
    .header .inst { font-size: 10pt; font-weight: bold; color: #1e3a6e; }
    .header .title { font-size: 8.5pt; font-weight: bold; margin-top: 2px; }
    .header .sub { font-size: 7pt; color: #64748b; margin-top: 2px; }
    table { width: 100%; border-collapse: collapse; font-size: 6pt; }
    th { background: #1e3a6e; color: #fff; padding: 3px 2px; text-align: center; }
    th.left { text-align: left; }
    td { padding: 2px; border-bottom: 1px solid #e2e8f0; text-align: center; }
    td.left { text-align: left; white-space: nowrap; }
    .p  { background: #d1fae5; color: #065f46; }
    .a  { background: #fee2e2; color: #991b1b; }
    .t  { background: #fef9c3; color: #92400e; }
    .e  { background: #dbeafe; color: #1e40af; }
    .r  { background: #f3f4f6; color: #6b7280; }
    .alerta { color: #dc2626; font-weight: bold; }
    .footer { margin-top: 8px; text-align: right; font-size: 6.5pt; color: #94a3b8; }
    .leyenda { margin-top: 8px; font-size: 6.5pt; display: flex; gap: 10px; }
    .leyenda span { display: inline-block; padding: 1px 4px; border-radius: 3px; margin-right: 4px; }
</style>
</head>
<body>
<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="title">Historial de Asistencia — {{ $asignacion->asignatura?->nombre }} — {{ $asignacion->grupo?->nombre_completo }}</div>
    <div class="sub">Docente: {{ $asignacion->docente?->nombre_completo ?? '—' }} · Generado el {{ now()->format('d/m/Y H:i') }}</div>
</div>

<table>
    <thead>
        <tr>
            <th class="left">#</th>
            <th class="left">Estudiante</th>
            @foreach($fechas as $fecha)
            <th>{{ \Carbon\Carbon::parse($fecha)->format('d/m') }}</th>
            @endforeach
            <th>Total</th>
            <th>%</th>
        </tr>
    </thead>
    <tbody>
        @foreach($matriculas as $i => $mat)
        @php
            $st       = $stats[$mat->id];
            $pct      = $st['pct'];
            $total    = $st['total'];
            $presentes= $st['presentes'];
        @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td class="left">{{ $mat->estudiante?->nombre_completo ?? '—' }}</td>
            @foreach($fechas as $fecha)
            @php
                $fechaStr = \Carbon\Carbon::parse($fecha)->format('Y-m-d');
                $estado   = $matriz[$mat->id][$fechaStr] ?? null;
                $cls      = match($estado) { 'presente' => 'p', 'ausente' => 'a', 'tarde' => 't', 'excusa' => 'e', 'retiro' => 'r', default => '' };
                $sigla    = match($estado) { 'presente' => 'P', 'ausente' => 'A', 'tarde' => 'T', 'excusa' => 'E', 'retiro' => 'R', default => '—' };
            @endphp
            <td class="{{ $cls }}">{{ $sigla }}</td>
            @endforeach
            <td>{{ $presentes }}/{{ $total }}</td>
            <td class="{{ $pct !== null && $pct < 75 ? 'alerta' : '' }}">{{ $pct !== null ? $pct . '%' : '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div style="margin-top:8px;font-size:6.5pt;">
    <span class="p" style="padding:1px 4px;border-radius:3px;">P = Presente</span>
    <span class="a" style="padding:1px 4px;border-radius:3px;">A = Ausente</span>
    <span class="t" style="padding:1px 4px;border-radius:3px;">T = Tarde</span>
    <span class="e" style="padding:1px 4px;border-radius:3px;">E = Excusa</span>
    <span class="r" style="padding:1px 4px;border-radius:3px;">R = Retiro</span>
    · <span class="alerta">Rojo = &lt;75%</span>
</div>

<div class="footer">{{ $inst }} — {{ now()->format('d/m/Y') }}</div>
</body>
</html>
