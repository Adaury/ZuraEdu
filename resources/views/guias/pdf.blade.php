<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Guía rápida — {{ $g['nombre'] }} — ZuraEdu</title>
<style>
    @page { margin: 0; }
    body { margin: 0; padding: 0; font-family: 'DejaVu Sans', sans-serif; background: #ffffff; }
</style>
</head>
<body>
@include('guias._flyer', ['g' => $g, 'pdf' => true])
@include('partials.marca.pie-pdf')
</body>
</html>
