<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; }
    @page { margin: 0; }
    body { margin: 0; background: #ffffff; }
</style>
</head>
<body>
@include('admin.carnet._cara', ['carnet' => $carnet, 'qrContent' => $qrContent ?? null])
</body>
</html>
