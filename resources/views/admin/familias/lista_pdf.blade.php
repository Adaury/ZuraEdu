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
    th.c { text-align: center; }
    td { font-size: 8.5pt; padding: 4px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
    tr.even td { background: #eff6ff; }
    .badge-act { background: #dcfce7; color: #15803d; border-radius: 4px; padding: 1px 5px; font-size: 7pt; font-weight: bold; }
    .badge-ina { background: #fee2e2; color: #dc2626; border-radius: 4px; padding: 1px 5px; font-size: 7pt; }
    .asig-list { font-size: 7.5pt; color: #475569; margin-top: 2px; }
    .footer { margin-top: 14px; text-align: right; font-size: 7pt; color: #94a3b8; }
</style>
</head>
<body>
<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="title">Familias Profesionales — Área Técnica</div>
    <div class="sub">Generado el {{ now()->format('d/m/Y H:i') }} — {{ $familias->count() }} familia(s)</div>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Familia</th>
            <th>Descripción</th>
            <th class="c">Materias</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($familias as $i => $f)
        <tr class="{{ $i % 2 === 1 ? 'even' : '' }}">
            <td>{{ $i + 1 }}</td>
            <td>
                <strong>{{ $f->nombre }}</strong>
                @if($f->asignaturas->count())
                <div class="asig-list">{{ $f->asignaturas->pluck('nombre')->implode(' · ') }}</div>
                @endif
            </td>
            <td style="color:#64748b;">{{ $f->descripcion ?? '—' }}</td>
            <td style="text-align:center;"><strong>{{ $f->asignaturas_count }}</strong></td>
            <td>
                @if($f->activo)
                    <span class="badge-act">Activa</span>
                @else
                    <span class="badge-ina">Inactiva</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">{{ $inst }} — {{ now()->format('d/m/Y') }}</div>
</body>
</html>
