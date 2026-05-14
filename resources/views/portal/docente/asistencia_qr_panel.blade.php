@extends('layouts.portal')
@section('page-title', 'Asistencia QR')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'asistencia'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.asistencia', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-calendar-check"></i>Asistencia
    </a>
    <a href="{{ route('portal.docente.asistencia.qr.panel', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-qr-code"></i>QR
    </a>
@endsection

@section('content')
<style>
.qr-panel { background:#0f172a; border-radius:20px; padding:1.75rem; color:#fff; margin-bottom:1.5rem; }
.qr-asig  { font-size:1.1rem; font-weight:900; margin-bottom:.25rem; }
.qr-grupo { font-size:.8rem; color:#94a3b8; }
.qr-box   { background:#fff; border-radius:16px; padding:1.25rem; display:flex; align-items:center; justify-content:center; margin:1.25rem 0; }
#qrcode   { display:inline-block; }
.countdown { font-size:2.5rem; font-weight:900; text-align:center; font-variant-numeric:tabular-nums; }
.countdown.warn  { color:#f59e0b; }
.countdown.ended { color:#ef4444; }
.live-card  { background:#fff; border-radius:16px; box-shadow:0 2px 12px rgba(15,23,42,.08); overflow:hidden; }
.live-header{ padding:.9rem 1.25rem; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; }
.live-title { font-size:.85rem; font-weight:700; color:#0f172a; }
.live-count { font-size:1.4rem; font-weight:900; color:#2563eb; }
.live-item  { padding:.6rem 1.25rem; border-bottom:1px solid #f8fafc; display:flex; align-items:center; gap:.75rem; font-size:.85rem; animation:slideIn .25s ease; }
@keyframes slideIn { from{opacity:0;transform:translateY(-6px);} to{opacity:1;transform:translateY(0);} }
.live-hora  { font-size:.72rem; color:#94a3b8; margin-left:auto; white-space:nowrap; }
.live-icon  { width:28px; height:28px; border-radius:50%; background:#d1fae5; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.setup-card { background:#fff; border-radius:16px; box-shadow:0 2px 12px rgba(15,23,42,.08); padding:1.75rem 2rem; }
</style>

<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.asistencia', $asignacion) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver a asistencia
    </a>
    <div>
        <div style="font-size:1rem;font-weight:800;color:#0f172a;">Asistencia con QR</div>
        <div style="font-size:.75rem;color:#64748b;">{{ $asignacion->asignatura?->nombre }} · {{ $asignacion->grupo?->nombre_completo }}</div>
    </div>
</div>

@if($qrToken)
{{-- ── PANEL ACTIVO ── --}}
<div class="qr-panel">
    <div class="qr-asig"><i class="bi bi-qr-code me-2"></i>{{ $asignacion->asignatura?->nombre }}</div>
    <div class="qr-grupo">{{ $asignacion->grupo?->nombre_completo }} · {{ now()->format('d/m/Y') }}</div>

    <div class="qr-box">
        <div id="qrcode"></div>
    </div>

    <div id="countdown" class="countdown">--:--</div>
    <div style="text-align:center;font-size:.72rem;color:#64748b;margin-top:.3rem;">tiempo restante</div>
</div>

{{-- Instrucción para estudiantes --}}
<div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:12px;padding:.9rem 1.1rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.75rem;">
    <i class="bi bi-phone" style="font-size:1.4rem;color:#2563eb;"></i>
    <div>
        <div style="font-size:.82rem;font-weight:700;color:#1e40af;">Indica a tus estudiantes:</div>
        <div style="font-size:.78rem;color:#1e40af;">Escaneen el código QR con la cámara del teléfono o visiten
            <strong style="font-family:monospace;word-break:break-all;">{{ url('/asistencia/qr/' . $qrToken->token) }}</strong>
        </div>
    </div>
</div>

{{-- Lista en vivo --}}
<div class="live-card mb-4">
    <div class="live-header">
        <div>
            <div class="live-title"><i class="bi bi-people-fill me-1 text-primary"></i>Registros en tiempo real</div>
            <div style="font-size:.72rem;color:#94a3b8;">Actualiza cada 5 segundos</div>
        </div>
        <div style="text-align:right;">
            <div class="live-count" id="liveCount">0</div>
            <div style="font-size:.7rem;color:#94a3b8;">de {{ $totalEstudiantes }}</div>
        </div>
    </div>
    <div id="liveList" style="max-height:320px;overflow-y:auto;">
        <div style="text-align:center;padding:2rem;color:#94a3b8;font-size:.85rem;">
            <i class="bi bi-hourglass-split" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>
            Esperando registros...
        </div>
    </div>
</div>

{{-- Botones de cierre --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
    <form method="POST" action="{{ route('portal.docente.asistencia.qr.cerrar', $qrToken) }}">
        @csrf
        <input type="hidden" name="marcar_ausentes" value="1">
        <button type="submit" onclick="return confirm('¿Finalizar sesión QR y marcar ausentes a los no registrados?')"
                style="width:100%;padding:.65rem;border-radius:10px;background:linear-gradient(135deg,#dc2626,#b91c1c);color:#fff;font-size:.85rem;font-weight:700;border:none;cursor:pointer;">
            <i class="bi bi-check-all"></i> Finalizar y marcar ausentes
        </button>
    </form>
    <form method="POST" action="{{ route('portal.docente.asistencia.qr.cerrar', $qrToken) }}">
        @csrf
        <input type="hidden" name="marcar_ausentes" value="0">
        <button type="submit"
                style="width:100%;padding:.65rem;border-radius:10px;background:#f1f5f9;color:#374151;font-size:.85rem;font-weight:600;border:1.5px solid #e2e8f0;cursor:pointer;">
            <i class="bi bi-x-circle"></i> Cerrar sin marcar
        </button>
    </form>
</div>

{{-- QR + polling JS --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const QR_URL     = @json(url('/asistencia/qr/' . $qrToken->token));
const ESTADO_URL = @json(route('portal.docente.asistencia.qr.estado', $qrToken));
const EXPIRES_AT = @json($qrToken->expires_at->toIso8601String());

// Generar QR
new QRCode(document.getElementById('qrcode'), {
    text: QR_URL,
    width: 240, height: 240,
    colorDark: '#0f172a', colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.H
});

// Countdown
function updateCountdown() {
    const diff = Math.max(0, Math.floor((new Date(EXPIRES_AT) - Date.now()) / 1000));
    const min  = String(Math.floor(diff / 60)).padStart(2, '0');
    const sec  = String(diff % 60).padStart(2, '0');
    const el   = document.getElementById('countdown');
    el.textContent = `${min}:${sec}`;
    el.className   = 'countdown' + (diff <= 60 ? ' warn' : '') + (diff === 0 ? ' ended' : '');
}
setInterval(updateCountdown, 1000);
updateCountdown();

// ── Realtime: Echo DOM event (instantáneo cuando el estudiante escanea) ──────
const ASIGNACION_ID = {{ $qrToken->asignacion_id }};
window.addEventListener('qr:escaneado', function(e) {
    const data = e.detail;
    if (data.asignacion_id !== ASIGNACION_ID) return; // ignorar otros QRs

    // Actualizar contador
    document.getElementById('liveCount').textContent = data.total_presentes;
    prevCount = data.total_presentes;

    // Añadir al tope de la lista con animación
    const list = document.getElementById('liveList');
    const empty = list.querySelector('[data-empty]');
    if (empty) empty.remove();

    const item = document.createElement('div');
    item.className = 'live-item';
    item.style.cssText = 'animation:qrItemEnter .35s cubic-bezier(.34,1.56,.64,1) both;';
    item.innerHTML = `<div class="live-icon"><i class="bi bi-check2" style="color:#16a34a;font-size:.9rem;"></i></div>
        <span style="font-weight:700;color:#0f172a;">${data.nombre}</span>
        <span class="live-hora">${data.hora}</span>`;
    list.insertBefore(item, list.firstChild);
});

// ── Polling de respaldo (cada 8s, solo si Echo no está conectado) ────────────
let prevCount = -1;
async function pollEstado() {
    try {
        const res  = await fetch(ESTADO_URL);
        const data = await res.json();
        document.getElementById('liveCount').textContent = data.registrados;

        if (data.registrados !== prevCount) {
            prevCount = data.registrados;
            const list = document.getElementById('liveList');
            if (data.lista.length === 0) {
                list.innerHTML = '<div style="text-align:center;padding:2rem;color:#94a3b8;font-size:.85rem;" data-empty><i class="bi bi-hourglass-split" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>Esperando registros...</div>';
            } else {
                list.innerHTML = data.lista.map(e =>
                    `<div class="live-item">
                        <div class="live-icon"><i class="bi bi-check2" style="color:#16a34a;font-size:.9rem;"></i></div>
                        <span style="font-weight:600;color:#0f172a;">${e.nombre}</span>
                        <span class="live-hora">${e.hora}</span>
                    </div>`
                ).join('');
            }
        }

        if (! data.valido) {
            clearInterval(pollInterval);
            document.getElementById('countdown').textContent = '00:00';
        }
    } catch(e) {}
}

// Carga inicial siempre — luego el polling solo si Echo no conecta
pollEstado();
let pollInterval;
setTimeout(function() {
    if (window.Echo?.connector?.pusher?.connection?.state !== 'connected') {
        pollInterval = setInterval(pollEstado, 5000);
    } else {
        // Con Echo, solo recontar cada 30s como verificación
        pollInterval = setInterval(pollEstado, 30000);
    }
}, 3000);
</script>

@else
{{-- ── CONFIGURAR NUEVA SESIÓN ── --}}
<div class="setup-card">
    <div style="text-align:center;margin-bottom:1.75rem;">
        <div style="width:72px;height:72px;border-radius:18px;background:linear-gradient(135deg,#1e3a8a,#3b82f6);display:flex;align-items:center;justify-content:center;margin:0 auto .9rem;">
            <i class="bi bi-qr-code" style="font-size:1.8rem;color:#fff;"></i>
        </div>
        <h2 style="font-size:1.1rem;font-weight:800;color:#0f172a;margin-bottom:.35rem;">Iniciar sesión de asistencia QR</h2>
        <p style="font-size:.85rem;color:#64748b;">
            Los estudiantes escanearán el código QR con su teléfono para registrar su asistencia automáticamente.
        </p>
    </div>

    <form method="POST" action="{{ route('portal.docente.asistencia.qr.crear', $asignacion) }}">
        @csrf
        <div style="margin-bottom:1.25rem;">
            <label style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#374151;display:block;margin-bottom:.6rem;">
                Duración del código QR
            </label>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.5rem;">
                @foreach([5 => '5 min', 10 => '10 min', 15 => '15 min', 30 => '30 min'] as $min => $label)
                <label style="cursor:pointer;">
                    <input type="radio" name="duracion" value="{{ $min }}" {{ $min === 15 ? 'checked' : '' }} style="display:none;" class="dur-radio">
                    <div class="dur-card" style="border:2px solid #e2e8f0;border-radius:10px;padding:.6rem;text-align:center;font-size:.82rem;font-weight:600;color:#374151;transition:.15s;">
                        <i class="bi bi-clock" style="display:block;font-size:1.1rem;margin-bottom:.25rem;"></i>
                        {{ $label }}
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        <div style="background:#fefce8;border:1px solid #fef08a;border-radius:10px;padding:.85rem 1rem;margin-bottom:1.5rem;font-size:.82rem;color:#78350f;display:flex;gap:.5rem;">
            <i class="bi bi-info-circle-fill" style="flex-shrink:0;margin-top:.1rem;"></i>
            <span>El QR expira automáticamente. Solo los estudiantes matriculados en este grupo podrán registrar asistencia. Cada estudiante solo puede registrarse una vez.</span>
        </div>

        <button type="submit"
                style="width:100%;padding:.75rem;border-radius:12px;background:linear-gradient(135deg,#1e3a8a,#2563eb);color:#fff;font-size:.95rem;font-weight:700;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;">
            <i class="bi bi-qr-code"></i> Generar código QR
        </button>
    </form>
</div>

<style>
.dur-card:hover { border-color:#3b82f6; background:#eff6ff; }
.dur-card.selected { border-color:#2563eb!important; background:#eff6ff; color:#1e40af; }
</style>
<script>
document.querySelectorAll('.dur-radio').forEach(r => {
    r.addEventListener('change', function() {
        document.querySelectorAll('.dur-card').forEach(c => c.classList.remove('selected'));
        this.closest('label').querySelector('.dur-card').classList.add('selected');
    });
    if (r.checked) r.closest('label').querySelector('.dur-card').classList.add('selected');
});
</script>
@endif

@push('realtime-data')
<script>window._SGE_ROL = 'docente';</script>
@endpush

@push('styles')
<style>
@keyframes qrItemEnter {
    from { opacity:0; transform:translateY(-10px) scale(.95); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
</style>
@endpush

@endsection
