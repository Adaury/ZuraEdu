<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>ZuraEdu Carnet+ — Modo Kiosco</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
        background: #0f172a;
        color: #f1f5f9;
        font-family: 'Segoe UI', system-ui, sans-serif;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        user-select: none;
        overflow: hidden;
    }

    /* Header */
    .kiosco-header {
        background: linear-gradient(135deg, #1e3a6e 0%, #1e40af 100%);
        padding: 1rem 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 2px solid #1e40af;
    }
    .kiosco-brand { font-size: 1.3rem; font-weight: 800; letter-spacing: .05em; }
    .kiosco-clock { font-size: 1.8rem; font-weight: 700; font-variant-numeric: tabular-nums; }

    /* Main area */
    .kiosco-main {
        flex: 1;
        display: grid;
        grid-template-columns: 1fr 420px;
        gap: 0;
    }

    /* Zona de resultado */
    .result-zone {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        transition: background 0.4s ease;
        min-height: 0;
    }
    .result-zone.idle    { background: #0f172a; }
    .result-zone.success { background: linear-gradient(135deg, #052e16 0%, #14532d 100%); }
    .result-zone.warning { background: linear-gradient(135deg, #1c1003 0%, #451a03 100%); }
    .result-zone.danger  { background: linear-gradient(135deg, #1c0203 0%, #450a0a 100%); }
    .result-zone.info    { background: linear-gradient(135deg, #0c1a2e 0%, #1e3a5f 100%); }

    .result-icon {
        font-size: 5rem;
        margin-bottom: 1rem;
        transition: all .3s ease;
    }
    .result-foto {
        width: 180px; height: 180px;
        border-radius: 50%;
        object-fit: cover;
        border: 6px solid rgba(255,255,255,.2);
        margin-bottom: 1rem;
        box-shadow: 0 0 40px rgba(255,255,255,.1);
    }
    .result-avatar {
        width: 180px; height: 180px;
        border-radius: 50%;
        background: rgba(255,255,255,.1);
        display: flex; align-items: center; justify-content: center;
        font-size: 4rem; font-weight: 800;
        color: rgba(255,255,255,.6);
        margin-bottom: 1rem;
    }
    .result-nombre {
        font-size: 2.8rem;
        font-weight: 800;
        text-align: center;
        line-height: 1.1;
        margin-bottom: .4rem;
    }
    .result-grupo {
        font-size: 1.2rem;
        color: rgba(255,255,255,.65);
        margin-bottom: .5rem;
    }
    .result-carnet {
        font-family: monospace;
        font-size: 1rem;
        background: rgba(255,255,255,.1);
        padding: .3rem .9rem;
        border-radius: 20px;
        margin-bottom: 1rem;
    }
    .result-msg {
        font-size: 1.4rem;
        font-weight: 700;
        text-align: center;
        padding: .6rem 1.4rem;
        border-radius: 12px;
        background: rgba(255,255,255,.12);
    }
    .result-hora {
        font-size: 1rem;
        color: rgba(255,255,255,.5);
        margin-top: .5rem;
    }

    /* Zona de escaneo */
    .scan-zone {
        background: #1e293b;
        border-left: 1px solid #334155;
        display: flex;
        flex-direction: column;
        padding: 1.5rem;
        gap: 1rem;
    }
    .scan-zone h5 {
        font-size:.9rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.1em;
    }
    .qr-input-wrap {
        background: #0f172a;
        border: 2px solid #334155;
        border-radius: 12px;
        padding: 1rem;
        display: flex;
        flex-direction: column;
        gap: .75rem;
    }
    #qrInput {
        background: transparent;
        border: none;
        color: #f1f5f9;
        font-size: 1.1rem;
        font-family: monospace;
        outline: none;
        width: 100%;
        text-align: center;
    }
    #qrInput::placeholder { color: #475569; font-size:.85rem; }
    .scan-instructions {
        background: #0f172a;
        border: 1px dashed #334155;
        border-radius: 10px;
        padding: 1.5rem;
        text-align: center;
        color: #64748b;
        font-size: .85rem;
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .5rem;
    }
    .scan-instructions .bi { font-size: 3rem; opacity: .3; display: block; }
    .zona-select label { font-size:.8rem; color:#94a3b8; font-weight:600; margin-bottom:.3rem; display:block; }
    .zona-select select {
        width:100%; background:#0f172a; border:1.5px solid #334155;
        color:#f1f5f9; border-radius:8px; padding:.5rem .75rem; font-size:.87rem;
    }
    .tipo-btns { display:flex; gap:.4rem; flex-wrap:wrap; }
    .tipo-btn {
        flex:1; min-width:80px;
        background:#0f172a; border:1.5px solid #334155; color:#94a3b8;
        border-radius:8px; padding:.45rem; font-size:.8rem; font-weight:600;
        cursor:pointer; text-align:center; transition:all .15s;
    }
    .tipo-btn.active { background:#1e3a6e; border-color:#2a4f96; color:#93c5fd; }

    /* Log reciente */
    .recent-log { font-size:.8rem; }
    .log-item {
        display:flex; align-items:center; gap:.5rem;
        padding:.4rem 0; border-bottom:1px solid #334155;
        color:#94a3b8;
    }
    .log-item:last-child { border-bottom:none; }
    .log-dot { width:8px;height:8px;border-radius:50%;flex-shrink:0; }

    /* Ripple animation */
    @keyframes ripple {
        0%   { transform:scale(0.8); opacity:1; }
        100% { transform:scale(1.3); opacity:0; }
    }
    .ripple { animation: ripple .6s ease-out; }

    /* Pulse on success */
    @keyframes pulse-green {
        0%,100% { box-shadow: 0 0 0 0 rgba(34,197,94,.4); }
        50%      { box-shadow: 0 0 0 20px rgba(34,197,94,0); }
    }
    .pulse-success { animation: pulse-green .8s ease 2; }
</style>
</head>
<body>

<div class="kiosco-header">
    <div class="kiosco-brand">
        <i class="bi bi-person-badge-fill me-2"></i>ZuraEdu Carnet+
        <span style="font-size:.8rem;font-weight:400;color:rgba(255,255,255,.6);margin-left:.5rem;">Modo Portería</span>
    </div>
    <div class="kiosco-clock" id="clock">00:00:00</div>
</div>

<div class="kiosco-main">

    {{-- Resultado --}}
    <div class="result-zone idle" id="resultZone">
        <div id="resultIcon" class="result-icon" style="opacity:.2;">
            <i class="bi bi-qr-code-scan"></i>
        </div>
        <div class="result-nombre" id="resultNombre" style="color:rgba(255,255,255,.15);font-size:1.5rem;">
            Esperando escaneo...
        </div>
        <div class="result-hora" id="resultHora"></div>
    </div>

    {{-- Panel derecho --}}
    <div class="scan-zone">
        <h5><i class="bi bi-qr-code me-1"></i>Control de Acceso</h5>

        {{-- QR Input (foco automático para lectores USB) --}}
        <div class="qr-input-wrap">
            <label style="font-size:.78rem;color:#64748b;text-align:center;">
                <i class="bi bi-usb-plug me-1"></i>Lector QR / entrada manual
            </label>
            <input type="text" id="qrInput" placeholder="Escanea el QR o escribe el código..." autocomplete="off">
            <button onclick="procesarQR()" style="background:#1e3a6e;border:none;color:#fff;border-radius:8px;padding:.5rem;font-size:.82rem;font-weight:700;cursor:pointer;">
                <i class="bi bi-send me-1"></i>Registrar
            </button>
        </div>

        {{-- Tipo de evento --}}
        <div>
            <label style="font-size:.78rem;color:#94a3b8;font-weight:700;margin-bottom:.4rem;display:block;">Tipo de evento</label>
            <div class="tipo-btns" id="tipoBtns">
                @foreach(['entrada'=>'Entrada','salida'=>'Salida','biblioteca'=>'Biblioteca','comedor'=>'Comedor','laboratorio'=>'Laboratorio'] as $val => $lbl)
                <div class="tipo-btn {{ $val === 'entrada' ? 'active' : '' }}" data-tipo="{{ $val }}">{{ $lbl }}</div>
                @endforeach
            </div>
        </div>

        {{-- Zona --}}
        <div class="zona-select">
            <label>Zona</label>
            <select id="zonaSelect">
                <option value="">Sin zona específica</option>
                @foreach($zonas as $zona)
                <option value="{{ $zona->id }}">{{ $zona->nombre }}</option>
                @endforeach
            </select>
        </div>

        {{-- Log reciente --}}
        <div>
            <h5 style="margin-bottom:.5rem;"><i class="bi bi-clock-history me-1"></i>Últimos accesos</h5>
            <div class="recent-log" id="recentLog">
                <div class="log-item" style="justify-content:center;color:#475569;">Sin registros aún</div>
            </div>
        </div>
    </div>
</div>

{{-- Audio feedback --}}
<audio id="sndOk"      src="data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAA..." preload="auto"></audio>
<audio id="sndError"   src="data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAA..." preload="auto"></audio>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let tipoEvento = 'entrada';
let recentItems = [];
let resetTimer  = null;

// ── Reloj ─────────────────────────────────────────────────────────────────────
function actualizarReloj() {
    const now = new Date();
    document.getElementById('clock').textContent =
        now.toLocaleTimeString('es-DO', { hour12: true });
}
setInterval(actualizarReloj, 1000);
actualizarReloj();

// ── Tipo evento --
document.querySelectorAll('.tipo-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tipo-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        tipoEvento = btn.dataset.tipo;
        document.getElementById('qrInput').focus();
    });
});

// ── Foco automático en el input ────────────────────────────────────────────────
document.addEventListener('click', function() {
    document.getElementById('qrInput').focus();
});
document.getElementById('qrInput').focus();

// ── Enter en input ─────────────────────────────────────────────────────────────
document.getElementById('qrInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') procesarQR();
});

// ── Procesar QR ────────────────────────────────────────────────────────────────
function procesarQR() {
    const raw = document.getElementById('qrInput').value.trim();
    if (!raw) return;

    // Extraer el token si es una URL completa
    let token = raw;
    try {
        const url = new URL(raw);
        const parts = url.pathname.split('/');
        token = parts[parts.length - 1];
    } catch(e) {}

    document.getElementById('qrInput').value = '';

    fetch('/admin/carnet/scan', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            qr_token:    token,
            tipo_evento: tipoEvento,
            zona_id:     document.getElementById('zonaSelect').value || null,
        }),
    })
    .then(r => r.json())
    .then(data => mostrarResultado(data))
    .catch(() => mostrarResultado({
        success: false,
        color: 'danger',
        mensaje: 'Error de conexión.',
        nombre: '—',
    }));
}

// ── Mostrar resultado ──────────────────────────────────────────────────────────
function mostrarResultado(data) {
    const zone  = document.getElementById('resultZone');
    const icon  = document.getElementById('resultIcon');
    const nom   = document.getElementById('resultNombre');
    const hora  = document.getElementById('resultHora');

    // Limpiar clases
    zone.className = 'result-zone ' + (data.color || 'danger');

    // Icono / foto
    let iconHtml = '';
    if (data.foto) {
        iconHtml = `<img src="${data.foto}" class="result-foto pulse-success" alt="">`;
    } else {
        const letra = (data.nombre || '?')[0].toUpperCase();
        iconHtml = `<div class="result-avatar ripple">${letra}</div>`;
    }
    icon.innerHTML = iconHtml;
    icon.style.opacity = '1';

    const iconoMap = {
        'success': '✓',
        'warning': '⚠',
        'danger' : '✗',
        'info'   : '↩',
    };

    nom.style.color = '#ffffff';
    nom.style.fontSize = '2.8rem';
    nom.innerHTML = `${data.nombre || '—'}<br>
        <span style="font-size:1rem;color:rgba(255,255,255,.5);">${data.grupo || ''}</span>`;

    hora.textContent = data.hora ? `📍 ${data.mensaje} · ${data.hora}` : data.mensaje;

    // Badge carnet
    if (data.numero_carnet) {
        nom.innerHTML += `<br><span class="result-carnet">${data.numero_carnet}</span>`;
    }

    // Log reciente
    const color = { success:'#22c55e', warning:'#f59e0b', danger:'#ef4444', info:'#60a5fa' };
    recentItems.unshift({
        nombre: data.nombre || '—',
        estado: data.estado || data.mensaje,
        hora:   data.hora || new Date().toLocaleTimeString('es-DO'),
        color:  color[data.color] || '#64748b',
    });
    if (recentItems.length > 6) recentItems.pop();
    renderLog();

    // Auto-reset
    clearTimeout(resetTimer);
    resetTimer = setTimeout(resetView, 5000);
    document.getElementById('qrInput').focus();
}

function resetView() {
    const zone = document.getElementById('resultZone');
    zone.className = 'result-zone idle';
    document.getElementById('resultIcon').innerHTML = '<i class="bi bi-qr-code-scan"></i>';
    document.getElementById('resultIcon').style.opacity = '.2';
    document.getElementById('resultNombre').style.color = 'rgba(255,255,255,.15)';
    document.getElementById('resultNombre').style.fontSize = '1.5rem';
    document.getElementById('resultNombre').textContent = 'Esperando escaneo...';
    document.getElementById('resultHora').textContent = '';
    document.getElementById('qrInput').focus();
}

function renderLog() {
    const container = document.getElementById('recentLog');
    if (!recentItems.length) return;
    container.innerHTML = recentItems.map(i => `
        <div class="log-item">
            <div class="log-dot" style="background:${i.color};"></div>
            <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${i.nombre}</span>
            <span style="font-size:.75rem;color:#475569;flex-shrink:0;">${i.hora}</span>
        </div>
    `).join('');
}
</script>
</body>
</html>
