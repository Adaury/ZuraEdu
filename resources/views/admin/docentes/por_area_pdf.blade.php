<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5pt; color: #1e293b; }

    .header { background: #1e3a6e; color: #fff; padding: 12px 18px; margin-bottom: 14px; }
    .header h1 { font-size: 13pt; font-weight: bold; margin-bottom: 2px; }
    .header p  { font-size: 8.5pt; opacity: .85; margin: 0; }

    .meta { display: flex; gap: 24px; margin-bottom: 14px; font-size: 8.5pt; color: #6b7280; }
    .meta span { background: #f1f5f9; padding: 3px 9px; border-radius: 4px; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    thead th { background: #1e3a6e; color: #fff; font-size: 8pt; font-weight: bold;
               padding: 5px 7px; text-align: left; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody td { padding: 5px 7px; font-size: 8.5pt; border-bottom: 1px solid #e5e7eb; vertical-align: top; }

    .docente-nombre { font-weight: 700; font-size: 9pt; color: #1e293b; }
    .asig-list { font-size: 7.5pt; color: #374151; margin-top: 2px; }
    .asig-item { margin-bottom: 1px; }
    .badge-activo { background: #d1fae5; color: #065f46; padding: 1px 6px; border-radius: 3px; font-size: 7pt; font-weight: 700; }
    .badge-inactivo { background: #fee2e2; color: #991b1b; padding: 1px 6px; border-radius: 3px; font-size: 7pt; font-weight: 700; }

    .footer { margin-top: 20px; font-size: 7.5pt; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ $inst }} — Docentes Área {{ $areaLabel }}</h1>
    <p>{{ $schoolYear?->nombre ?? '' }} &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}</p>
</div>

<div class="meta">
    <span><strong>Área:</strong> {{ $areaLabel }}</span>
    <span><strong>Total docentes:</strong> {{ $docentes->count() }}</span>
</div>

@if($docentes->isEmpty())
<p style="text-align:center;color:#9ca3af;margin-top:30px;">No hay docentes registrados en esta área.</p>
@else
<table>
    <thead>
        <tr>
            <th style="width:22px;">#</th>
            <th>Nombre</th>
            <th>Cédula</th>
            <th>Especialidad</th>
            <th>Materias / Grupos</th>
            <th style="width:55px;text-align:center;">Estado</th>
        </tr>
    </thead>
    <tbody>
    @foreach($docentes as $i => $docente)
    <tr>
        <td style="text-align:center;color:#9ca3af;">{{ $i + 1 }}</td>
        <td>
            <div class="docente-nombre">{{ $docente->apellidos }}, {{ $docente->nombres }}</div>
            @if($docente->cargo)
            <div style="font-size:7.5pt;color:#6b7280;">{{ $docente->cargo }}</div>
            @endif
        </td>
        <td style="font-size:8pt;color:#374151;">{{ $docente->cedula ?? '—' }}</td>
        <td style="font-size:8pt;color:#374151;">{{ $docente->especialidad ?? '—' }}</td>
        <td>
            <div class="asig-list">
                @forelse($docente->asignaciones as $asig)
                <div class="asig-item">
                    <strong>{{ $asig->asignatura?->nombre ?? '—' }}</strong>
                    @if($asig->grupo)
                    — {{ $asig->grupo->grado?->nombre ?? '' }} {{ $asig->grupo->seccion?->nombre ?? '' }}
                    @endif
                </div>
                @empty
                <span style="color:#9ca3af;">Sin asignaciones</span>
                @endforelse
            </div>
        </td>
        <td style="text-align:center;">
            @if($docente->estado === 'activo')
            <span class="badge-activo">Activo</span>
            @else
            <span class="badge-inactivo">{{ ucfirst($docente->estado ?? 'inactivo') }}</span>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; Docentes Área {{ $areaLabel }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
</div>
</body>
</html>
