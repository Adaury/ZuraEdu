<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1e293b; margin: 0; padding: 0; }
    .header { text-align: center; margin-bottom: 14px; }
    .header .inst { font-size: 11pt; font-weight: bold; color: #1e3a6e; }
    .header .title { font-size: 9pt; color: #475569; margin-top: 2px; }
    .header .sub { font-size: 8pt; color: #64748b; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th { background: #1e3a6e; color: #fff; font-size: 8pt; padding: 5px 6px; text-align: left; }
    td { font-size: 8pt; padding: 4px 6px; border-bottom: 1px solid #e2e8f0; }
    tr.even td { background: #eff6ff; }
    .badge-tec { background: #dbeafe; color: #1d4ed8; border-radius: 4px; padding: 1px 5px; font-size: 7pt; font-weight: bold; }
    .badge-acad { background: #dcfce7; color: #15803d; border-radius: 4px; padding: 1px 5px; font-size: 7pt; font-weight: bold; }
    .badge-bas { background: #fef9c3; color: #92400e; border-radius: 4px; padding: 1px 5px; font-size: 7pt; }
    .footer { margin-top: 14px; text-align: right; font-size: 7pt; color: #94a3b8; }
</style>
</head>
<body>
<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="title">Lista de Asignaturas</div>
    <div class="sub">Generado el {{ now()->format('d/m/Y H:i') }} — Total: {{ $asignaturas->count() }} asignaturas</div>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>Código</th>
            <th>Área</th>
            <th>Familia Profesional</th>
            <th>Hrs/Sem.</th>
            <th>RAs</th>
            <th>Asignaciones</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($asignaturas as $i => $asig)
        <tr class="{{ $i % 2 === 1 ? 'even' : '' }}">
            <td>{{ $i + 1 }}</td>
            <td>
                {{ $asig->nombre }}
                @if($asig->es_basica) <span class="badge-bas">Básica</span> @endif
            </td>
            <td>{{ $asig->codigo ?? '—' }}</td>
            <td>
                @if($asig->area === 'tecnica')
                    <span class="badge-tec">Técnica</span>
                @else
                    <span class="badge-acad">Académica</span>
                @endif
            </td>
            <td>{{ $asig->familia?->nombre ?? '—' }}</td>
            <td style="text-align:center;">{{ $asig->horas_semanales ?? '—' }}</td>
            <td style="text-align:center;">{{ $asig->num_ra ?? 0 }}</td>
            <td style="text-align:center;">{{ $asig->asignaciones->count() }}</td>
            <td>
                @if($asig->activo)
                    <span style="color:#15803d;font-weight:bold;">Activa</span>
                @else
                    <span style="color:#dc2626;">Inactiva</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">{{ $inst }} — {{ now()->format('d/m/Y') }}</div>
</body>
</html>
