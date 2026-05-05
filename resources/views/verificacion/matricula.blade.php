<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Verificación de Matrícula — {{ \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name')) }}</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:system-ui,'Segoe UI',sans-serif;background:linear-gradient(135deg,#0f2557 0%,#1d4ed8 60%,#3b82f6 100%);min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:1.5rem;}
.wrap{width:100%;max-width:540px;}

/* ── Card ── */
.card{background:#fff;border-radius:24px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;}
.card-header{background:linear-gradient(135deg,#0f2557,#1d4ed8);padding:2rem 2rem 1.5rem;text-align:center;color:#fff;}
.logo-box{width:68px;height:68px;border-radius:16px;background:rgba(255,255,255,.15);backdrop-filter:blur(4px);border:2px solid rgba(255,255,255,.3);font-size:1.6rem;font-weight:900;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;letter-spacing:-1px;}
.card-header h1{font-size:1.2rem;font-weight:800;margin-bottom:.2rem;}
.card-header p{font-size:.8rem;opacity:.8;}
.card-body{padding:1.75rem 2rem;}

/* ── Form ── */
.form-group{margin-bottom:1.1rem;}
label{display:block;font-size:.8rem;font-weight:700;color:#374151;margin-bottom:.35rem;text-transform:uppercase;letter-spacing:.04em;}
.input-wrap{position:relative;}
.input-wrap i{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:1rem;}
input[type=text]{width:100%;padding:.75rem 1rem .75rem 2.6rem;border:2px solid #e5e7eb;border-radius:12px;font-size:.95rem;outline:none;transition:border-color .2s,box-shadow .2s;background:#f9fafb;}
input[type=text]:focus{border-color:#1d4ed8;background:#fff;box-shadow:0 0 0 4px rgba(29,78,216,.08);}
.hint{font-size:.73rem;color:#9ca3af;margin-top:.35rem;display:flex;align-items:center;gap:.3rem;}
.btn-buscar{width:100%;background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff;border:none;border-radius:12px;padding:.85rem;font-size:.95rem;font-weight:700;cursor:pointer;transition:opacity .15s,transform .1s;display:flex;align-items:center;justify-content:center;gap:.5rem;margin-top:.25rem;}
.btn-buscar:hover{opacity:.9;transform:translateY(-1px);}
.btn-buscar:active{transform:translateY(0);}

/* ── Error ── */
.alert-err{background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:.65rem 1rem;font-size:.83rem;color:#dc2626;margin-bottom:1rem;display:flex;align-items:center;gap:.4rem;}

/* ── Result: Not found ── */
.result-fail{background:#fef2f2;border:1.5px solid #fca5a5;border-radius:16px;padding:1.5rem;text-align:center;margin-top:1.5rem;}
.result-fail .icon-fail{font-size:2.2rem;color:#dc2626;display:block;margin-bottom:.5rem;}
.result-fail strong{color:#991b1b;font-size:.95rem;}
.result-fail p{font-size:.82rem;color:#dc2626;margin-top:.3rem;}

/* ── Result: Found ── */
.result-ok{background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1.5px solid #86efac;border-radius:20px;padding:1.5rem;margin-top:1.5rem;}
.verified-badge{display:inline-flex;align-items:center;gap:.4rem;background:#16a34a;color:#fff;font-size:.72rem;font-weight:700;padding:.3rem .75rem;border-radius:100px;margin-bottom:1rem;text-transform:uppercase;letter-spacing:.06em;}

/* Student header */
.student-header{display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem;padding-bottom:1.25rem;border-bottom:1.5px solid rgba(134,239,172,.5);}
.student-photo{width:72px;height:72px;border-radius:50%;border:3px solid #16a34a;object-fit:cover;flex-shrink:0;}
.student-avatar{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff;font-size:1.4rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:3px solid #16a34a;}
.student-name{font-size:1.1rem;font-weight:800;color:#14532d;line-height:1.2;}
.student-sub{font-size:.78rem;color:#15803d;margin-top:.2rem;}

/* Detail grid */
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:.55rem;margin-bottom:1.25rem;}
.detail-item{background:rgba(255,255,255,.6);border-radius:10px;padding:.55rem .7rem;}
.detail-item .lbl{font-size:.68rem;font-weight:700;color:#15803d;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.15rem;}
.detail-item .val{font-size:.85rem;font-weight:700;color:#14532d;}
.detail-item.full{grid-column:span 2;}

/* Estado badge */
.estado-activa{display:inline-flex;align-items:center;gap:.3rem;background:#dcfce7;color:#15803d;font-size:.78rem;font-weight:700;padding:.2rem .6rem;border-radius:100px;}

/* QR section */
.qr-section{display:flex;align-items:center;gap:1rem;background:rgba(255,255,255,.7);border-radius:14px;padding:.9rem 1rem;margin-bottom:1rem;}
.qr-section .qr-info{flex:1;font-size:.75rem;color:#15803d;}
.qr-section .qr-info strong{display:block;font-size:.8rem;color:#14532d;margin-bottom:.15rem;}
#qr-canvas canvas,#qr-canvas img{border-radius:8px;display:block;}

/* Actions */
.actions{display:flex;gap:.6rem;margin-top:.25rem;}
.btn-print{flex:1;background:#1d4ed8;color:#fff;border:none;border-radius:10px;padding:.65rem;font-size:.82rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.4rem;transition:opacity .15s;}
.btn-print:hover{opacity:.88;}
.btn-new{flex:1;background:#fff;color:#1d4ed8;border:2px solid #1d4ed8;border-radius:10px;padding:.65rem;font-size:.82rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.4rem;text-decoration:none;transition:background .15s;}
.btn-new:hover{background:#eff6ff;}

/* Back */
.back-link{display:block;text-align:center;margin-top:1.25rem;font-size:.78rem;color:rgba(255,255,255,.7);text-decoration:none;}
.back-link:hover{color:#fff;}
.footer-txt{text-align:center;margin-top:1rem;font-size:.7rem;color:rgba(255,255,255,.5);}

/* ══════════════════ PRINT ══════════════════ */
@media print {
    *{-webkit-print-color-adjust:exact;print-color-adjust:exact;}
    body{background:#fff !important;padding:0;display:block;}
    .wrap{max-width:100%;}
    .no-print{display:none !important;}
    .card{box-shadow:none;border-radius:0;border:none;}
    .card-header{background:linear-gradient(135deg,#0f2557,#1d4ed8) !important;-webkit-print-color-adjust:exact;}
    .result-ok{border:1.5px solid #86efac;page-break-inside:avoid;}
    .cert-header{display:block !important;}
    .cert-footer{display:block !important;}
    .back-link,.footer-txt,.actions,.btn-print,.btn-new{display:none !important;}
}
.cert-header{display:none;text-align:center;padding:1rem 0 .5rem;border-bottom:2px solid #1d4ed8;margin-bottom:1rem;}
.cert-header h2{font-size:1rem;font-weight:800;color:#1d4ed8;}
.cert-header p{font-size:.75rem;color:#64748b;}
.cert-footer{display:none;text-align:center;padding:.75rem 0;border-top:1px solid #e5e7eb;margin-top:1rem;font-size:.7rem;color:#94a3b8;}
</style>
</head>
<body>
@php
    $si        = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
    $resultado = $resultado ?? session('resultado');
    $verifyUrl = url('/verificar-matricula');
@endphp

<div class="wrap">
<div class="card">

    {{-- Header --}}
    <div class="card-header no-print">
        <div class="logo-box">{{ strtoupper(substr($si, 0, 2)) }}</div>
        <h1>{{ $si }}</h1>
        <p>Verificación de Matrícula Estudiantil</p>
    </div>

    <div class="card-body">

        {{-- Print certificate header (only visible on print) --}}
        <div class="cert-header">
            <h2>{{ $si }}</h2>
            <p>Comprobante Oficial de Matrícula · Generado el {{ now()->format('d/m/Y H:i') }}</p>
        </div>

        {{-- Error de validación --}}
        @if($errors->any())
        <div class="alert-err no-print">
            <i class="bi bi-exclamation-circle-fill"></i>{{ $errors->first() }}
        </div>
        @endif

        {{-- Formulario de búsqueda --}}
        @if(!$resultado || !$resultado['encontrado'])
        <form method="POST" action="{{ route('verificar-matricula.buscar') }}" class="no-print">
            @csrf
            <div class="form-group">
                <label for="busqueda">Cédula, No. Matrícula o Nombre</label>
                <div class="input-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" id="busqueda" name="busqueda"
                           value="{{ old('busqueda', $resultado['busqueda'] ?? '') }}"
                           placeholder="Ej: 001-1234567-8 ó EST-2024-001 ó García"
                           autocomplete="off" autofocus>
                </div>
                <div class="hint">
                    <i class="bi bi-info-circle"></i>
                    Puedes buscar por cédula, número de matrícula o nombre del estudiante.
                </div>
            </div>
            <button type="submit" class="btn-buscar">
                <i class="bi bi-shield-check"></i> Verificar Matrícula
            </button>
        </form>
        @endif

        {{-- Resultado --}}
        @if($resultado)

            @if(!$resultado['encontrado'])
            {{-- No encontrado --}}
            <div class="result-fail">
                <i class="bi bi-x-circle-fill icon-fail"></i>
                <strong>No encontrado</strong>
                <p>{{ $resultado['msg'] ?? 'No se encontró un estudiante activo con esos datos.' }}</p>
            </div>
            <div style="margin-top:1rem;text-align:center;" class="no-print">
                <a href="{{ route('verificar-matricula') }}" class="btn-new" style="display:inline-flex;max-width:220px;">
                    <i class="bi bi-arrow-repeat"></i> Nueva búsqueda
                </a>
            </div>

            @else
            {{-- Encontrado --}}
            <div class="result-ok">
                @if(!empty($resultado['advertencia']))
                <div style="background:#fef3c7;border:1.5px solid #fcd34d;border-radius:10px;padding:.6rem .9rem;margin-bottom:.9rem;font-size:.78rem;color:#92400e;display:flex;align-items:flex-start;gap:.5rem;">
                    <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0;margin-top:.05rem;"></i>
                    <span>{{ $resultado['advertencia'] }}</span>
                </div>
                @endif

                <div class="verified-badge">
                    <i class="bi bi-patch-check-fill"></i> Estudiante Encontrado
                </div>

                {{-- Foto + nombre --}}
                <div class="student-header">
                    @if(!empty($resultado['foto_url']))
                        <img src="{{ $resultado['foto_url'] }}" alt="Foto" class="student-photo">
                    @else
                        <div class="student-avatar">
                            {{ strtoupper(substr($resultado['nombre'], 0, 1)) }}{{ strtoupper(substr(strstr($resultado['nombre'], ' '), 1, 1)) }}
                        </div>
                    @endif
                    <div>
                        <div class="student-name">{{ $resultado['nombre'] }}</div>
                        <div class="student-sub">
                            <i class="bi bi-person-vcard me-1"></i>
                            Cédula: {{ $resultado['cedula'] }}
                        </div>
                    </div>
                </div>

                {{-- Detalles en grid --}}
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="lbl"><i class="bi bi-hash"></i> No. Matrícula</div>
                        <div class="val">{{ $resultado['matricula_num'] }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="lbl"><i class="bi bi-mortarboard"></i> Grado</div>
                        <div class="val">{{ $resultado['grado'] }} &bull; {{ $resultado['seccion'] }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="lbl"><i class="bi bi-calendar3"></i> Año Escolar</div>
                        <div class="val">{{ $resultado['year'] }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="lbl"><i class="bi bi-calendar-check"></i> Fecha Matrícula</div>
                        <div class="val">{{ $resultado['fecha_matricula'] }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="lbl"><i class="bi bi-person-badge"></i> Tutor de Grupo</div>
                        <div class="val">{{ $resultado['tutor'] }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="lbl"><i class="bi bi-123"></i> No. Orden</div>
                        <div class="val">{{ $resultado['numero_orden'] !== '—' ? '#' . $resultado['numero_orden'] : '—' }}</div>
                    </div>
                    <div class="detail-item full">
                        <div class="lbl"><i class="bi bi-circle-fill" style="font-size:.5rem;color:#16a34a;"></i> Estado</div>
                        <div class="val">
                            @if($resultado['estado'] === 'activa')
                            <span class="estado-activa">
                                <i class="bi bi-check-circle-fill"></i> Matrícula Activa
                            </span>
                            @else
                            <span style="display:inline-flex;align-items:center;gap:.3rem;background:#fef3c7;color:#92400e;font-size:.78rem;font-weight:700;padding:.2rem .6rem;border-radius:100px;">
                                <i class="bi bi-exclamation-circle-fill"></i> {{ ucfirst($resultado['estado']) }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- QR Code --}}
                <div class="qr-section no-print">
                    <div id="qr-canvas"></div>
                    <div class="qr-info">
                        <strong><i class="bi bi-qr-code me-1"></i>Código QR</strong>
                        Escanea para verificar esta matrícula desde cualquier dispositivo.
                    </div>
                </div>

                {{-- Acciones --}}
                <div class="actions no-print">
                    <button class="btn-print" onclick="window.print()">
                        <i class="bi bi-printer-fill"></i> Imprimir Comprobante
                    </button>
                    <a href="{{ route('verificar-matricula') }}" class="btn-new">
                        <i class="bi bi-arrow-repeat"></i> Nueva búsqueda
                    </a>
                </div>
            </div>

            {{-- Print footer --}}
            <div class="cert-footer">
                Este documento es un comprobante de matrícula generado por el Sistema {{ $si }}.<br>
                Verificación válida al {{ now()->format('d/m/Y') }} · {{ url('/verificar-matricula') }}
            </div>

            @endif
        @endif

    </div>{{-- /card-body --}}
</div>{{-- /card --}}

<a href="{{ url('/') }}" class="back-link no-print">
    <i class="bi bi-arrow-left me-1"></i>Volver al inicio
</a>
<div class="footer-txt no-print">
    Sistema de Gestión Escolar · {{ $si }} · {{ now()->format('d/m/Y') }}
</div>
</div>{{-- /wrap --}}

@if(isset($resultado) && !empty($resultado['encontrado']))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const qrUrl = "{{ $verifyUrl }}?q={{ urlencode($resultado['matricula_num']) }}";
    new QRCode(document.getElementById('qr-canvas'), {
        text: qrUrl,
        width: 80,
        height: 80,
        colorDark: '#0f2557',
        colorLight: '#f0fdf4',
        correctLevel: QRCode.CorrectLevel.M,
    });
});
</script>
@endif

</body>
</html>
