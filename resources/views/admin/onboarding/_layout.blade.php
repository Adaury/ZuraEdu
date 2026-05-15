<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Configuración inicial — ZuraEdu</title>
<link href="/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --blue:   #1d4ed8;
    --blue-d: #1e3a8a;
    --green:  #10b981;
    --g50:    #f8fafc;
    --g100:   #f1f5f9;
    --g200:   #e2e8f0;
    --g400:   #94a3b8;
    --g500:   #64748b;
    --g700:   #374151;
    --g900:   #0f172a;
}
body {
    font-family: 'Inter', -apple-system, 'Segoe UI', sans-serif;
    background: var(--g100);
    min-height: 100vh;
    display: flex; flex-direction: column;
}

/* ── Top bar ── */
.topbar {
    background: linear-gradient(135deg, var(--blue-d), var(--blue));
    padding: .9rem 2rem;
    display: flex; align-items: center; justify-content: space-between;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,.15);
}
.topbar-brand { display: flex; align-items: center; gap: .6rem; color: #fff; font-weight: 800; font-size: 1.15rem; }
.topbar-brand i { font-size: 1.3rem; }
.topbar-sub { color: rgba(255,255,255,.65); font-size: .78rem; font-weight: 400; margin-left: .25rem; }
.topbar-logout { color: rgba(255,255,255,.7); font-size: .8rem; text-decoration: none; }
.topbar-logout:hover { color: #fff; }

/* ── Main wrapper ── */
.wizard-wrap { flex: 1; display: flex; align-items: flex-start; justify-content: center; padding: 2.5rem 1rem 4rem; }
.wizard-inner { width: 100%; max-width: 680px; }

/* ── Progress bar ── */
.progress-section { margin-bottom: 2rem; }
.progress-steps {
    display: flex; align-items: center; gap: 0;
}
.step-item { display: flex; align-items: center; flex: 1; }
.step-dot {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .85rem; flex-shrink: 0;
    transition: all .2s;
}
.step-dot.done    { background: var(--green); color: #fff; }
.step-dot.active  { background: var(--blue); color: #fff; box-shadow: 0 0 0 4px rgba(29,78,216,.2); }
.step-dot.pending { background: var(--g200); color: var(--g400); }
.step-line { flex: 1; height: 2px; background: var(--g200); }
.step-line.done { background: var(--green); }
.step-labels { display: flex; margin-top: .5rem; }
.step-label { flex: 1; text-align: center; font-size: .72rem; color: var(--g500); font-weight: 500; }
.step-label.active  { color: var(--blue); font-weight: 700; }
.step-label.done    { color: var(--green); }

/* ── Card ── */
.wizard-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 4px 24px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.04);
    overflow: hidden;
}
.wizard-card-header {
    padding: 1.75rem 2rem 1.5rem;
    border-bottom: 1px solid var(--g100);
}
.wizard-card-header h2 { font-size: 1.3rem; font-weight: 800; color: var(--g900); margin-bottom: .3rem; }
.wizard-card-header p  { font-size: .88rem; color: var(--g500); line-height: 1.5; }
.wizard-card-body  { padding: 1.75rem 2rem; }
.wizard-card-footer {
    padding: 1.25rem 2rem;
    background: var(--g50);
    border-top: 1px solid var(--g100);
    display: flex; align-items: center; justify-content: space-between;
}

/* ── Form elements ── */
.form-group { margin-bottom: 1.25rem; }
.form-label { display: block; font-size: .83rem; font-weight: 600; color: var(--g700); margin-bottom: .4rem; }
.form-control {
    width: 100%; padding: .6rem .9rem;
    border: 1.5px solid var(--g200); border-radius: 10px;
    font-size: .9rem; color: var(--g900);
    background: #fff; transition: border-color .15s, box-shadow .15s;
    outline: none;
}
.form-control:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(29,78,216,.1); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

/* ── Tipo selector ── */
.tipo-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .6rem; }
.tipo-option input { display: none; }
.tipo-card {
    border: 2px solid var(--g200); border-radius: 12px;
    padding: .9rem 1rem; cursor: pointer; transition: all .15s;
    display: flex; align-items: center; gap: .6rem;
}
.tipo-card i { font-size: 1.2rem; color: var(--g400); }
.tipo-card span { font-size: .85rem; font-weight: 600; color: var(--g700); }
.tipo-option input:checked + .tipo-card { border-color: var(--blue); background: #eff6ff; }
.tipo-option input:checked + .tipo-card i { color: var(--blue); }
.tipo-option input:checked + .tipo-card span { color: var(--blue); }

/* ── Grados grid ── */
.grados-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .6rem; }
.grado-toggle input { display: none; }
.grado-card {
    border: 2px solid var(--g200); border-radius: 12px;
    padding: .75rem 1rem; cursor: pointer; transition: all .15s;
    display: flex; align-items: center; justify-content: space-between;
}
.grado-card .grado-name { font-size: .85rem; font-weight: 600; color: var(--g700); }
.grado-card .grado-badge { font-size: .7rem; padding: .2rem .5rem; border-radius: 6px; background: var(--g100); color: var(--g500); }
.grado-toggle input:checked + .grado-card { border-color: var(--green); background: #f0fdf4; }
.grado-toggle input:checked + .grado-card .grado-name { color: #166534; }
.grado-toggle input:checked + .grado-card .grado-badge { background: #dcfce7; color: #166534; }
.grado-toggle input:checked + .grado-card::after { content: '✓'; font-weight: 900; color: var(--green); font-size: .9rem; }

/* ── Buttons ── */
.btn { display: inline-flex; align-items: center; gap: .4rem; padding: .65rem 1.5rem; border-radius: 10px; font-size: .88rem; font-weight: 700; cursor: pointer; border: none; transition: all .15s; text-decoration: none; }
.btn-primary   { background: var(--blue); color: #fff; }
.btn-primary:hover { background: var(--blue-d); }
.btn-outline   { background: transparent; color: var(--g500); border: 1.5px solid var(--g200); }
.btn-outline:hover { border-color: var(--g400); color: var(--g700); }
.btn-success   { background: var(--green); color: #fff; }
.btn-success:hover { background: #059669; }
.btn-skip { background: none; border: none; color: var(--g400); font-size: .82rem; cursor: pointer; padding: .3rem; text-decoration: underline; }
.btn-skip:hover { color: var(--g500); }

/* ── Logo upload ── */
.logo-upload-area {
    border: 2px dashed var(--g200); border-radius: 12px;
    padding: 1.5rem; text-align: center; cursor: pointer;
    transition: border-color .15s; position: relative;
}
.logo-upload-area:hover { border-color: var(--blue); }
.logo-upload-area input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.logo-preview { max-height: 60px; max-width: 200px; margin: 0 auto .5rem; display: none; }

/* ── Color picker ── */
.color-picker-wrap { display: flex; align-items: center; gap: .75rem; }
.color-picker-wrap input[type=color] { width: 44px; height: 44px; border: none; background: none; cursor: pointer; padding: 0; border-radius: 8px; }
.color-hex { font-size: .85rem; color: var(--g700); font-weight: 600; font-family: monospace; }

/* ── Completion screen ── */
.completion-hero { text-align: center; padding: 2.5rem 1rem; }
.completion-icon { font-size: 4rem; margin-bottom: 1rem; }
.completion-title { font-size: 1.8rem; font-weight: 900; color: var(--g900); margin-bottom: .5rem; }
.completion-sub { color: var(--g500); font-size: .95rem; }
.summary-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; margin: 2rem 0 1.5rem; }
.summary-card { background: var(--g50); border: 1px solid var(--g200); border-radius: 14px; padding: 1rem; text-align: center; }
.summary-card .sc-value { font-size: 1.5rem; font-weight: 900; color: var(--blue); }
.summary-card .sc-label { font-size: .75rem; color: var(--g500); margin-top: .25rem; }
.quick-actions { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.qa-link { display: flex; align-items: center; gap: .75rem; padding: .9rem 1rem; background: #fff; border: 1.5px solid var(--g200); border-radius: 12px; text-decoration: none; color: var(--g700); font-size: .85rem; font-weight: 600; transition: all .15s; }
.qa-link:hover { border-color: var(--blue); color: var(--blue); background: #eff6ff; }
.qa-link i { font-size: 1.1rem; }

/* ── Alerts ── */
.alert-error { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: .9rem 1rem; margin-bottom: 1.25rem; color: #b91c1c; font-size: .85rem; }
.alert-error ul { padding-left: 1.2rem; margin: 0; }

@media (max-width: 540px) {
    .wizard-wrap { padding: 1.5rem .75rem 3rem; }
    .wizard-card-body, .wizard-card-header { padding: 1.25rem; }
    .wizard-card-footer { padding: 1rem 1.25rem; }
    .form-row { grid-template-columns: 1fr; }
    .grados-grid { grid-template-columns: 1fr; }
    .tipo-grid { grid-template-columns: 1fr 1fr; }
    .summary-cards { grid-template-columns: 1fr; }
    .quick-actions { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<div class="topbar">
    <div class="topbar-brand">
        <i class="bi bi-mortarboard-fill"></i>
        ZuraEdu <span class="topbar-sub">— Configuración inicial</span>
    </div>
    <a href="{{ route('logout') }}" class="topbar-logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
        <i class="bi bi-box-arrow-right me-1"></i>Salir
    </a>
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
</div>

<div class="wizard-wrap">
<div class="wizard-inner">

    {{-- Progress --}}
    <div class="progress-section">
        @php
            $steps = ['Tu institución', 'Año escolar', 'Grados', 'Completado'];
        @endphp
        <div class="progress-steps">
            @foreach($steps as $i => $label)
                @php
                    $n = $i + 1;
                    $state = $n < $pasoActual ? 'done' : ($n === $pasoActual ? 'active' : 'pending');
                @endphp
                <div class="step-item">
                    <div class="step-dot {{ $state }}">
                        @if($state === 'done') <i class="bi bi-check2" style="font-size:1rem;"></i>
                        @else {{ $n }}
                        @endif
                    </div>
                    @if(! $loop->last)
                        <div class="step-line {{ $n < $pasoActual ? 'done' : '' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="step-labels">
            @foreach($steps as $i => $label)
                @php
                    $n = $i + 1;
                    $state = $n < $pasoActual ? 'done' : ($n === $pasoActual ? 'active' : '');
                @endphp
                <div class="step-label {{ $state }}">{{ $label }}</div>
            @endforeach
        </div>
    </div>

    {{-- Card --}}
    <div class="wizard-card">
        @yield('wizard-content')
    </div>

</div>
</div>

@stack('scripts')
</body>
</html>
