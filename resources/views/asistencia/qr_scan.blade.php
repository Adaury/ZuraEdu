<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Registrar Asistencia</title>
<link href="/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: #f1f5f9;
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
}
.card {
    background: #fff;
    border-radius: 24px;
    box-shadow: 0 8px 40px rgba(15,23,42,.12);
    padding: 2rem 1.75rem;
    width: 100%;
    max-width: 420px;
    text-align: center;
}
.class-icon {
    width: 80px; height: 80px; border-radius: 20px;
    background: linear-gradient(135deg, #1e3a8a, #3b82f6);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1.25rem;
}
.class-icon i { font-size: 2rem; color: #fff; }
.class-name { font-size: 1.25rem; font-weight: 900; color: #0f172a; margin-bottom: .3rem; }
.class-info { font-size: .85rem; color: #64748b; margin-bottom: .5rem; }
.student-badge {
    display: inline-flex; align-items: center; gap: .4rem;
    background: #eff6ff; border: 1.5px solid #bfdbfe;
    border-radius: 99px; padding: .35rem .85rem;
    font-size: .8rem; font-weight: 600; color: #1e40af;
    margin-bottom: 1.5rem;
}
.timer-box {
    background: #f8fafc; border-radius: 14px;
    padding: .85rem; margin-bottom: 1.5rem;
    display: flex; align-items: center; justify-content: center; gap: .5rem;
}
.timer-num {
    font-size: 1.6rem; font-weight: 900; font-variant-numeric: tabular-nums;
    color: #0f172a; letter-spacing: .05em;
}
.timer-num.warn  { color: #d97706; }
.timer-num.ended { color: #dc2626; }
.timer-label { font-size: .72rem; color: #94a3b8; }

.btn-confirm {
    width: 100%; padding: .9rem;
    border-radius: 14px;
    background: linear-gradient(135deg, #16a34a, #15803d);
    color: #fff; font-size: 1rem; font-weight: 800;
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: .5rem;
    transition: opacity .15s;
}
.btn-confirm:hover { opacity: .9; }
.btn-confirm:disabled { opacity: .5; cursor: not-allowed; }

.success-box {
    background: linear-gradient(135deg, #065f46, #059669);
    border-radius: 20px; padding: 2.25rem 1.75rem; text-align: center;
}
.success-icon {
    width: 80px; height: 80px; border-radius: 50%;
    background: rgba(255,255,255,.2);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1.1rem;
}
.success-icon i { font-size: 2.2rem; color: #fff; }
.success-title { font-size: 1.4rem; font-weight: 900; color: #fff; margin-bottom: .4rem; }
.success-sub   { font-size: .88rem; color: rgba(255,255,255,.75); }

.already-box {
    background: #eff6ff; border: 2px solid #bfdbfe;
    border-radius: 14px; padding: 1.25rem;
    display: flex; align-items: center; gap: .75rem; text-align: left;
}
</style>
</head>
<body>

@if(session('registrado') || $yaRegistrado)
{{-- ✅ Ya registrado --}}
<div class="success-box" style="max-width:420px;width:100%;">
    <div class="success-icon"><i class="bi bi-check-lg"></i></div>
    <div class="success-title">¡Asistencia registrada!</div>
    <div class="success-sub">
        Tu presencia en <strong>{{ $qr->asignacion?->asignatura?->nombre }}</strong> fue confirmada correctamente.
    </div>
    <div style="margin-top:1.25rem;font-size:.78rem;color:rgba(255,255,255,.6);">
        {{ now()->format('d/m/Y H:i') }}
    </div>
    @if(auth()->check() && auth()->user()->hasRole('Estudiante'))
    <a href="{{ route('portal.estudiante.asistencia') }}"
       style="display:block;margin-top:1.25rem;padding:.6rem;border-radius:10px;background:rgba(255,255,255,.2);color:#fff;font-size:.85rem;font-weight:600;text-decoration:none;">
        Ver mi asistencia
    </a>
    @endif
</div>

@else
{{-- Formulario de confirmación --}}
<div class="card">
    <div class="class-icon"><i class="bi bi-clipboard-check-fill"></i></div>
    <div class="class-name">{{ $qr->asignacion?->asignatura?->nombre ?? 'Clase' }}</div>
    <div class="class-info">{{ $qr->asignacion?->grupo?->nombre_completo ?? '' }}</div>
    <div class="class-info" style="margin-bottom:.85rem;">{{ now()->format('d/m/Y') }}</div>

    <div class="student-badge">
        <i class="bi bi-person-fill"></i>
        {{ $estudiante->nombre_completo }}
    </div>

    {{-- Countdown --}}
    <div class="timer-box">
        <i class="bi bi-clock" style="color:#94a3b8;font-size:1.1rem;"></i>
        <div>
            <div class="timer-num" id="timerNum">--:--</div>
            <div class="timer-label">tiempo restante</div>
        </div>
    </div>

    @if(session('error'))
    <div style="background:#fef2f2;border:1.5px solid #fecaca;border-radius:10px;padding:.75rem 1rem;margin-bottom:1.25rem;font-size:.82rem;color:#dc2626;display:flex;gap:.4rem;">
        <i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}
    </div>
    @endif

    <form method="POST" action="{{ route('asistencia.qr.registrar', $token) }}">
        @csrf
        <button type="submit" class="btn-confirm" id="btnConfirm">
            <i class="bi bi-check2-circle"></i> Confirmar mi asistencia
        </button>
    </form>

    <div style="font-size:.72rem;color:#94a3b8;margin-top:.85rem;line-height:1.5;">
        Solo puedes registrarte una vez. Tu asistencia quedará guardada en el sistema.
    </div>
</div>
@endif

<script>
const EXPIRES_AT = @json($qr->expires_at->toIso8601String());

function updateTimer() {
    const diff = Math.max(0, Math.floor((new Date(EXPIRES_AT) - Date.now()) / 1000));
    const min  = String(Math.floor(diff / 60)).padStart(2, '0');
    const sec  = String(diff % 60).padStart(2, '0');
    const el   = document.getElementById('timerNum');
    if (el) {
        el.textContent = `${min}:${sec}`;
        el.className = 'timer-num' + (diff <= 60 ? ' warn' : '') + (diff === 0 ? ' ended' : '');
    }
    const btn = document.getElementById('btnConfirm');
    if (btn && diff === 0) {
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-x-circle"></i> Código expirado';
    }
}
setInterval(updateTimer, 1000);
updateTimer();
</script>
</body>
</html>
