<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; }
    @page { margin: 0; }
    body { margin: 0; background: #ffffff; font-family: 'DejaVu Sans', sans-serif; }
</style>
</head>
<body>
@forelse($carnets as $carnet)
<div style="width:85.6mm;height:53.5mm;{{ $loop->last ? '' : 'page-break-after:always;' }}">
    @include('admin.carnet._cara', ['carnet' => $carnet])
</div>
@empty
<div style="width:85.6mm;height:53.5mm;text-align:center;">
    <p style="padding-top:20mm;font-size:9pt;color:#6b7280;">No hay carnets activos de estudiante para este grupo.</p>
</div>
@endforelse
</body>
</html>
