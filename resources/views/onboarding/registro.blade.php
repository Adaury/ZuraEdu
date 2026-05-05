<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear mi escuela — ZuraEdu</title>
    <link href="/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
        --blue:   #1d4ed8;
        --blue-d: #1e3a8a;
        --blue-l: #3b82f6;
        --green:  #10b981;
        --g50:    #f8fafc;
        --g100:   #f1f5f9;
        --g200:   #e2e8f0;
        --g400:   #94a3b8;
        --g500:   #64748b;
        --g700:   #374151;
        --g900:   #0f172a;
    }
    html { scroll-behavior: smooth; }
    body {
        font-family: 'Inter', -apple-system, 'Segoe UI', sans-serif;
        background: linear-gradient(135deg, #060b20 0%, #0e1f5e 50%, #1d4ed8 100%);
        min-height: 100vh;
        display: flex; align-items: center; justify-content: center;
        padding: 2rem 1rem;
    }
    .wrap {
        width: 100%; max-width: 520px;
    }
    /* Logo bar */
    .logo-bar {
        text-align: center; margin-bottom: 2rem;
    }
    .logo-icon {
        width: 52px; height: 52px; border-radius: 16px;
        background: rgba(255,255,255,.12);
        border: 1.5px solid rgba(255,255,255,.2);
        display: inline-flex; align-items: center; justify-content: center;
        color: #fff; font-size: 1.5rem; margin-bottom: .75rem;
        backdrop-filter: blur(10px);
    }
    .logo-name { font-size: 1.5rem; font-weight: 900; color: #fff; letter-spacing: -.03em; }
    .logo-sub { font-size: .8rem; color: rgba(255,255,255,.5); margin-top: .25rem; }

    /* Card */
    .card {
        background: #fff; border-radius: 24px;
        padding: 2.5rem 2.25rem;
        box-shadow: 0 32px 80px rgba(0,0,0,.35), 0 0 0 1px rgba(255,255,255,.05);
    }
    .card-title { font-size: 1.45rem; font-weight: 900; color: var(--g900); letter-spacing: -.025em; margin-bottom: .4rem; }
    .card-sub { font-size: .86rem; color: var(--g500); margin-bottom: 2rem; line-height: 1.5; }

    /* Steps indicator */
    .steps {
        display: flex; gap: .5rem; margin-bottom: 2rem; align-items: center;
    }
    .step {
        height: 4px; border-radius: 99px; flex: 1;
        background: var(--g200); transition: background .3s;
    }
    .step.done { background: var(--green); }
    .step.active { background: var(--blue); }

    /* Form */
    .form-group { margin-bottom: 1.15rem; }
    label {
        display: block; font-size: .8rem; font-weight: 700;
        color: var(--g700); margin-bottom: .4rem;
    }
    label .req { color: #ef4444; }
    input, select {
        width: 100%; padding: .72rem 1rem;
        border: 1.5px solid var(--g200);
        border-radius: 10px;
        font-size: .9rem; color: var(--g900);
        background: var(--g50);
        transition: border-color .15s, box-shadow .15s;
        outline: none; font-family: inherit;
    }
    input:focus, select:focus {
        border-color: var(--blue-l);
        box-shadow: 0 0 0 3px rgba(59,130,246,.12);
        background: #fff;
    }
    input.error { border-color: #ef4444; }
    .err-msg {
        font-size: .74rem; color: #ef4444; margin-top: .3rem;
        display: flex; align-items: center; gap: .3rem;
    }

    /* Subdomain preview */
    .subdomain-wrap {
        position: relative;
    }
    .subdomain-preview {
        margin-top: .45rem; padding: .5rem .85rem;
        background: #eff6ff; border-radius: 8px;
        font-size: .78rem; color: #1d4ed8;
        display: flex; align-items: center; gap: .4rem;
        border: 1px solid #bfdbfe; min-height: 36px;
    }
    .subdomain-preview i { flex-shrink: 0; }
    .subdomain-preview .domain-highlight { font-weight: 700; }

    /* Two columns */
    .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: .85rem; }

    /* Password strength */
    .pwd-strength { margin-top: .45rem; }
    .pwd-bars { display: flex; gap: 3px; }
    .pwd-bar { height: 3px; flex: 1; border-radius: 99px; background: var(--g200); transition: background .25s; }
    .pwd-label { font-size: .72rem; color: var(--g400); margin-top: .3rem; }

    /* Plan badge */
    .plan-badge {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .35rem .9rem; border-radius: 99px;
        background: #eff6ff; color: #1d4ed8;
        border: 1px solid #bfdbfe;
        font-size: .75rem; font-weight: 700;
        margin-bottom: 1.75rem;
    }

    /* Submit button */
    .btn-submit {
        width: 100%; padding: .85rem;
        background: linear-gradient(135deg, #1e3a8a, #2563eb);
        color: #fff; border: none; border-radius: 12px;
        font-size: 1rem; font-weight: 700; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: .5rem;
        transition: all .2s; font-family: inherit;
        box-shadow: 0 4px 16px rgba(30,64,175,.35);
        margin-top: 1.5rem;
    }
    .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(30,64,175,.45); }
    .btn-submit:active { transform: translateY(0); }
    .btn-submit .spinner {
        width: 18px; height: 18px; border: 2.5px solid rgba(255,255,255,.3);
        border-top-color: #fff; border-radius: 50%;
        animation: spin .7s linear infinite; display: none;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Terms */
    .terms {
        text-align: center; font-size: .73rem; color: var(--g400);
        margin-top: 1rem; line-height: 1.6;
    }
    .terms a { color: var(--blue); text-decoration: none; font-weight: 600; }

    /* Bottom link */
    .bottom-link {
        text-align: center; margin-top: 1.5rem;
        font-size: .82rem; color: rgba(255,255,255,.55);
    }
    .bottom-link a { color: #fff; font-weight: 700; text-decoration: none; }

    /* Alert errors */
    .alert-error {
        background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px;
        padding: .85rem 1rem; margin-bottom: 1.25rem;
        font-size: .82rem; color: #dc2626;
    }
    .alert-error ul { margin: 0; padding-left: 1.2rem; }

    /* Benefits row */
    .benefits-row {
        display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1.75rem;
    }
    .benefit-chip {
        display: flex; align-items: center; gap: .3rem;
        padding: .28rem .7rem; border-radius: 99px;
        background: #f0fdf4; color: #059669;
        border: 1px solid #bbf7d0;
        font-size: .7rem; font-weight: 600;
    }

    @media (max-width: 480px) {
        .card { padding: 1.75rem 1.25rem; }
        .row2 { grid-template-columns: 1fr; }
    }
    </style>
</head>
<body>
<div class="wrap">

    {{-- Logo --}}
    <div class="logo-bar">
        <a href="/" style="text-decoration:none;">
            <div><div class="logo-icon"><i class="bi bi-mortarboard-fill"></i></div></div>
            <div class="logo-name">ZuraEdu</div>
            <div class="logo-sub">Plataforma educativa inteligente</div>
        </a>
    </div>

    <div class="card">
        {{-- Plan badge --}}
        @if($plan === 'pro')
        <div class="plan-badge" style="background:#f5f3ff;color:#7c3aed;border-color:#ddd6fe;">
            <i class="bi bi-star-fill" style="font-size:.7rem;"></i>Plan Pro — 30 días gratis
        </div>
        @elseif($plan === 'premium')
        <div class="plan-badge" style="background:#0f172a;color:#fbbf24;border-color:#fbbf24;">
            <i class="bi bi-gem" style="font-size:.7rem;"></i>Plan Premium — 30 días gratis
        </div>
        @else
        <div class="plan-badge">
            <i class="bi bi-gift" style="font-size:.7rem;"></i>Plan Gratuito — Sin tarjeta
        </div>
        @endif

        <h1 class="card-title">Crea tu institución</h1>
        <p class="card-sub">Completa el formulario y tu sistema estará listo en segundos.</p>

        {{-- Steps --}}
        <div class="steps">
            <div class="step done"></div>
            <div class="step active"></div>
            <div class="step"></div>
        </div>

        {{-- Benefits --}}
        <div class="benefits-row">
            <div class="benefit-chip"><i class="bi bi-check-circle-fill"></i>Sin tarjeta de crédito</div>
            <div class="benefit-chip"><i class="bi bi-check-circle-fill"></i>30 días gratis</div>
            <div class="benefit-chip"><i class="bi bi-check-circle-fill"></i>Setup automático</div>
        </div>

        {{-- Errores globales --}}
        @if($errors->any())
        <div class="alert-error">
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" action="{{ route('onboarding.store') }}" id="regForm">
            @csrf

            <div class="form-group">
                <label>Nombre de la institución <span class="req">*</span></label>
                <input type="text" name="nombre_institucion" id="nombre_inst"
                       value="{{ old('nombre_institucion') }}"
                       placeholder="Ej: Colegio San José, ITEPC, Escuela Básica..."
                       class="{{ $errors->has('nombre_institucion') ? 'error' : '' }}"
                       autocomplete="organization" required>
                @error('nombre_institucion')<p class="err-msg"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>@enderror
                <div class="subdomain-preview" id="subPreview">
                    <i class="bi bi-globe2"></i>
                    <span>Tu URL: <span class="domain-highlight" id="subText">tuescuela</span>.zuraedu.com</span>
                </div>
            </div>

            <div class="form-group">
                <label>Nombre del administrador <span class="req">*</span></label>
                <input type="text" name="nombre_admin"
                       value="{{ old('nombre_admin') }}"
                       placeholder="Tu nombre completo"
                       class="{{ $errors->has('nombre_admin') ? 'error' : '' }}"
                       autocomplete="name" required>
                @error('nombre_admin')<p class="err-msg"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label>Correo electrónico <span class="req">*</span></label>
                <input type="email" name="email"
                       value="{{ old('email') }}"
                       placeholder="director@miescuela.edu"
                       class="{{ $errors->has('email') ? 'error' : '' }}"
                       autocomplete="email" required>
                @error('email')<p class="err-msg"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>@enderror
            </div>

            <div class="row2">
                <div class="form-group" style="margin-bottom:0;">
                    <label>Contraseña <span class="req">*</span></label>
                    <input type="password" name="password" id="pwdInput"
                           placeholder="Mínimo 8 caracteres"
                           class="{{ $errors->has('password') ? 'error' : '' }}"
                           autocomplete="new-password" required>
                    <div class="pwd-strength">
                        <div class="pwd-bars">
                            <div class="pwd-bar" id="b1"></div>
                            <div class="pwd-bar" id="b2"></div>
                            <div class="pwd-bar" id="b3"></div>
                            <div class="pwd-bar" id="b4"></div>
                        </div>
                        <div class="pwd-label" id="pwdLabel">Ingresa una contraseña</div>
                    </div>
                    @error('password')<p class="err-msg"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>@enderror
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label>Confirmar contraseña <span class="req">*</span></label>
                    <input type="password" name="password_confirmation"
                           placeholder="Repite la contraseña"
                           autocomplete="new-password" required>
                </div>
            </div>

            <div class="form-group" style="margin-top:1.15rem;">
                <label>Tipo de institución <span class="req">*</span></label>
                <select name="tipo" class="{{ $errors->has('tipo') ? 'error' : '' }}">
                    <option value="">— Selecciona —</option>
                    <option value="publico"   @selected(old('tipo')==='publico')>Institución pública</option>
                    <option value="privado"   @selected(old('tipo')==='privado')>Institución privada</option>
                    <option value="instituto" @selected(old('tipo')==='instituto')>Instituto / Academia</option>
                    <option value="tecnico"   @selected(old('tipo')==='tecnico')>Centro técnico vocacional</option>
                </select>
                @error('tipo')<p class="err-msg"><i class="bi bi-exclamation-circle"></i>{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <div class="spinner" id="spinner"></div>
                <i class="bi bi-rocket-takeoff" id="submitIco"></i>
                <span id="submitTxt">Crear mi institución ahora</span>
            </button>
        </form>

        <p class="terms">
            Al registrarte aceptas nuestros <a href="#">Términos de uso</a> y
            <a href="#">Política de privacidad</a>. Tu información está protegida.
        </p>
    </div>

    <div class="bottom-link">
        ¿Ya tienes cuenta? <a href="{{ route('login') }}">Iniciar sesión</a>
        &nbsp;·&nbsp;
        <a href="{{ route('landing') }}" style="color:rgba(255,255,255,.4);">← Volver al inicio</a>
    </div>
</div>

<script>
// Subdomain preview live
const nameInput = document.getElementById('nombre_inst');
const subText   = document.getElementById('subText');
function slug(str) {
    return str.toLowerCase()
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '')
        .substring(0, 30) || 'tuescuela';
}
nameInput.addEventListener('input', () => {
    subText.textContent = slug(nameInput.value) || 'tuescuela';
});

// Password strength
const pwdInput = document.getElementById('pwdInput');
const bars     = [document.getElementById('b1'), document.getElementById('b2'), document.getElementById('b3'), document.getElementById('b4')];
const label    = document.getElementById('pwdLabel');
const colors   = { 1: '#ef4444', 2: '#f59e0b', 3: '#3b82f6', 4: '#10b981' };
const labels   = { 0: 'Ingresa una contraseña', 1: 'Muy débil', 2: 'Débil', 3: 'Buena', 4: 'Fuerte' };

pwdInput.addEventListener('input', () => {
    const v = pwdInput.value;
    let score = 0;
    if (v.length >= 8) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    bars.forEach((b, i) => {
        b.style.background = i < score ? (colors[score] || '#e2e8f0') : '#e2e8f0';
    });
    label.textContent = labels[score] || '';
    label.style.color = colors[score] || '#94a3b8';
});

// Submit loading state
document.getElementById('regForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    const ico = document.getElementById('submitIco');
    const txt = document.getElementById('submitTxt');
    const sp  = document.getElementById('spinner');
    btn.disabled = true;
    ico.style.display = 'none';
    sp.style.display  = 'block';
    txt.textContent   = 'Creando tu institución...';
});
</script>
</body>
</html>
