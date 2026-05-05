<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Verificación de Matrícula — {{ \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name')) }}</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:system-ui,'Segoe UI',sans-serif; background:linear-gradient(135deg,#1e3a6e 0%,#2563eb 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:1.5rem; }
.card { background:#fff; border-radius:20px; box-shadow:0 16px 48px rgba(0,0,0,.2); padding:2.5rem 2rem; max-width:480px; width:100%; }
.logo-box { width:64px; height:64px; border-radius:14px; background:#1e3a6e; color:#fff; font-size:1.5rem; font-weight:900; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; }
h1 { text-align:center; font-size:1.3rem; font-weight:800; color:#1e3a6e; margin-bottom:.35rem; }
.subtitle { text-align:center; font-size:.85rem; color:#6b7280; margin-bottom:1.75rem; }

.form-group { margin-bottom:1rem; }
label { display:block; font-size:.83rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
input[type=text] { width:100%; padding:.7rem 1rem; border:1.5px solid #d1d5db; border-radius:10px; font-size:.95rem; outline:none; transition:border-color .15s; }
input[type=text]:focus { border-color:#2563eb; }
.hint { font-size:.75rem; color:#9ca3af; margin-top:.3rem; }
button[type=submit] { width:100%; background:#2563eb; color:#fff; border:none; border-radius:10px; padding:.8rem; font-size:.95rem; font-weight:700; cursor:pointer; transition:opacity .15s; margin-top:.5rem; }
button[type=submit]:hover { opacity:.88; }

.result-ok { background:#f0fdf4; border:1.5px solid #86efac; border-radius:14px; padding:1.25rem 1.5rem; margin-top:1.5rem; }
.result-ok .check { color:#16a34a; font-size:2rem; text-align:center; display:block; margin-bottom:.5rem; }
.result-ok h2 { font-size:1.05rem; font-weight:800; color:#15803d; text-align:center; margin-bottom:1rem; }
.result-fail { background:#fff5f5; border:1.5px solid #fca5a5; border-radius:14px; padding:1.25rem 1.5rem; margin-top:1.5rem; text-align:center; color:#dc2626; }

.detail-row { display:flex; justify-content:space-between; font-size:.85rem; padding:.4rem 0; border-bottom:1px solid #dcfce7; }
.detail-row:last-child { border-bottom:none; }
.detail-label { color:#15803d; font-weight:600; }
.detail-val { color:#1e293b; font-weight:700; }

.back-link { display:block; text-align:center; margin-top:1.5rem; font-size:.82rem; color:#6b7280; text-decoration:none; }
.back-link:hover { color:#2563eb; }
</style>
</head>
<body>
@php
    $si       = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
    $resultado = session('resultado');
@endphp

<div class="card">
    <div class="logo-box">{{ strtoupper(substr($si, 0, 2)) }}</div>
    <h1>{{ $si }}</h1>
    <p class="subtitle">Verificación de Matrícula Estudiantil</p>

    @if($errors->any())
    <div style="background:#fee2e2;border-radius:10px;padding:.65rem 1rem;font-size:.83rem;color:#dc2626;margin-bottom:1rem;">
        <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
    </div>
    @endif

    <form method="POST" action="{{ route('verificar-matricula.buscar') }}">
        @csrf
        <div class="form-group">
            <label for="busqueda">Cédula o Número de Matrícula</label>
            <input type="text" id="busqueda" name="busqueda"
                   value="{{ old('busqueda') }}"
                   placeholder="Ej: 001-1234567-8 ó EST-2024-001"
                   autocomplete="off">
            <div class="hint"><i class="bi bi-info-circle me-1"></i>Ingresa la cédula de identidad o el número de matrícula del estudiante.</div>
        </div>
        <button type="submit"><i class="bi bi-search me-2"></i>Verificar Matrícula</button>
    </form>

    @if($resultado)
        @if($resultado['encontrado'])
        <div class="result-ok">
            <i class="bi bi-check-circle-fill check"></i>
            <h2>¡Matrícula Verificada!</h2>
            <div class="detail-row">
                <span class="detail-label">Estudiante</span>
                <span class="detail-val">{{ $resultado['nombre'] }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">No. Matrícula</span>
                <span class="detail-val">{{ $resultado['matricula_num'] }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Grado</span>
                <span class="detail-val">{{ $resultado['grado'] }} {{ $resultado['seccion'] }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Año Escolar</span>
                <span class="detail-val">{{ $resultado['year'] }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Estado</span>
                <span class="detail-val" style="color:#16a34a;">✓ Matrícula Activa</span>
            </div>
        </div>
        @else
        <div class="result-fail">
            <i class="bi bi-x-circle-fill" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
            <strong>No encontrado</strong><br>
            <span style="font-size:.85rem;">{{ $resultado['msg'] ?? 'No se encontró un estudiante activo con esos datos.' }}</span>
        </div>
        @endif
    @endif

    <a href="{{ url('/') }}" class="back-link"><i class="bi bi-arrow-left me-1"></i>Volver al inicio</a>
    <div style="text-align:center;margin-top:1.5rem;font-size:.72rem;color:#d1d5db;">
        Sistema de Gestión Escolar · {{ $si }}<br>
        Consulta oficial — {{ now()->format('d/m/Y') }}
    </div>
</div>
</body>
</html>
