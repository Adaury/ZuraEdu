@extends('layouts.admin')
@section('page-title', 'Configuración del Login')

@push('styles')
<style>
    .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
    .page-header h1 { font-size:1.45rem; font-weight:800; color:var(--primary); margin:0; }
    .card-panel { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:1.5rem; margin-bottom:1.5rem; }
    .section-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--primary); border-bottom:2px solid var(--primary); padding-bottom:.4rem; margin-bottom:1.1rem; }
    .form-label-custom { font-size:.83rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
    .form-control-custom { border:1.5px solid #d1d5db; border-radius:8px; padding:.5rem .75rem; font-size:.875rem; width:100%; transition:border-color .15s; }
    .form-control-custom:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(30,64,175,.1); }

    /* Login preview */
    .login-preview {
        border-radius:14px; overflow:hidden;
        box-shadow:0 20px 50px rgba(0,0,0,.25);
        display:flex; height:320px; max-width:700px; margin:0 auto 1.5rem;
    }
    .lp-left {
        flex:1;
        padding:2rem;
        display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center;
        position:relative; overflow:hidden;
        transition:background .3s;
    }
    .lp-left-bg { position:absolute; inset:0; transition:background .3s; }
    .lp-badge { width:56px; height:56px; border-radius:16px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.5rem; font-weight:900; margin-bottom:1rem; position:relative; z-index:1; }
    .lp-title { font-size:1.25rem; font-weight:800; color:#fff; margin-bottom:.5rem; position:relative; z-index:1; }
    .lp-sub { font-size:.78rem; color:rgba(255,255,255,.65); position:relative; z-index:1; }
    .lp-right { width:260px; background:#fff; padding:2rem; display:flex; flex-direction:column; justify-content:center; gap:.75rem; }
    .lp-right h4 { font-size:.95rem; font-weight:800; color:#111; margin:0; }
    .lp-input { border:1.5px solid #e5e7eb; border-radius:8px; padding:.45rem .7rem; font-size:.78rem; color:#374151; }
    .lp-btn { border-radius:8px; padding:.5rem; font-size:.8rem; font-weight:700; color:#fff; text-align:center; }

    /* Color picker row */
    .color-row { display:flex; align-items:center; gap:.75rem; }
    .color-row input[type=color] { width:48px; height:36px; border:2px solid #e5e7eb; border-radius:8px; cursor:pointer; padding:2px; }
    .color-row label { font-size:.83rem; font-weight:600; color:#374151; flex:1; }
    [data-theme="dark"] .card-panel { background: #1e293b; border-color: #334155; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1><i class="bi bi-palette me-2"></i>Configuración del Login</h1>
        <p class="text-muted small mb-0">Personaliza el aspecto y textos de la página de inicio de sesión</p>
    </div>
    <a href="{{ route('login') }}" target="_blank" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-box-arrow-up-right me-1"></i>Ver login
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Vista previa --}}
<div class="card-panel">
    <div class="section-title"><i class="bi bi-eye me-1"></i>Vista previa</div>
    <div class="login-preview" id="loginPreview">
        <div class="lp-left">
            <div class="lp-left-bg" id="prevBg" style="background:linear-gradient(140deg,{{ $settings['login_color_bg1'] ?? '#0a0f2e' }} 0%,{{ $settings['login_color_bg2'] ?? '#1e3a8a' }} 55%,{{ $settings['login_color_bg3'] ?? '#1d4ed8' }} 100%);"></div>
            <div class="lp-badge" id="prevBadge" style="background:{{ $settings['login_color_acc'] ?? '#10b981' }};">S</div>
            <div class="lp-title" id="prevTitle">{{ $settings['login_titulo'] ?? config('app.name', 'SGE') }}</div>
            <div class="lp-sub" id="prevSub">{{ $settings['login_subtitulo'] ?? 'Sistema de Gestión Escolar' }}</div>
        </div>
        <div class="lp-right">
            <div style="text-align:center;margin-bottom:.5rem;">
                <h4>Iniciar sesión</h4>
            </div>
            <div class="lp-input">usuario@escuela.edu</div>
            <div class="lp-input">••••••••</div>
            <div class="lp-btn" id="prevBtn" style="background:{{ $settings['login_color_bg2'] ?? '#1e3a8a' }};">Ingresar</div>
            @if(($settings['login_allow_reg'] ?? '0') === '1')
            <div style="text-align:center;font-size:.72rem;color:#6b7280;">¿No tienes cuenta? <span style="color:{{ $settings['login_color_acc'] ?? '#10b981' }};">Registrarse</span></div>
            @endif
        </div>
    </div>
</div>

<form method="POST" action="{{ route('admin.sistema.login-config.update') }}">
    @csrf

    {{-- Textos --}}
    <div class="card-panel">
        <div class="section-title"><i class="bi bi-type me-1"></i>Textos</div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label-custom">Título (nombre del sistema)</label>
                <input type="text" name="login_titulo" class="form-control-custom"
                    value="{{ $settings['login_titulo'] ?? config('app.name') }}"
                    oninput="document.getElementById('prevTitle').textContent=this.value">
                <small class="text-muted">Aparece en grande sobre el panel izquierdo</small>
            </div>
            <div class="col-md-6">
                <label class="form-label-custom">Subtítulo</label>
                <input type="text" name="login_subtitulo" class="form-control-custom"
                    value="{{ $settings['login_subtitulo'] ?? 'Sistema de Gestión Escolar' }}"
                    oninput="document.getElementById('prevSub').textContent=this.value">
            </div>
        </div>
    </div>

    {{-- Colores --}}
    <div class="card-panel">
        <div class="section-title"><i class="bi bi-palette2 me-1"></i>Colores del panel izquierdo</div>
        <p class="text-muted small mb-3">El fondo usa un degradado de tres colores (oscuro → medio → claro).</p>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label-custom">Color inicio (oscuro)</label>
                <div class="color-row">
                    <input type="color" name="login_color_bg1" id="c1"
                        value="{{ $settings['login_color_bg1'] ?? '#0a0f2e' }}"
                        oninput="updatePreview()">
                    <span class="form-control-custom" style="flex:1;padding:.3rem .6rem;font-size:.78rem;" id="c1val">{{ $settings['login_color_bg1'] ?? '#0a0f2e' }}</span>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label-custom">Color medio</label>
                <div class="color-row">
                    <input type="color" name="login_color_bg2" id="c2"
                        value="{{ $settings['login_color_bg2'] ?? '#1e3a8a' }}"
                        oninput="updatePreview()">
                    <span class="form-control-custom" style="flex:1;padding:.3rem .6rem;font-size:.78rem;" id="c2val">{{ $settings['login_color_bg2'] ?? '#1e3a8a' }}</span>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label-custom">Color final (claro)</label>
                <div class="color-row">
                    <input type="color" name="login_color_bg3" id="c3"
                        value="{{ $settings['login_color_bg3'] ?? '#1d4ed8' }}"
                        oninput="updatePreview()">
                    <span class="form-control-custom" style="flex:1;padding:.3rem .6rem;font-size:.78rem;" id="c3val">{{ $settings['login_color_bg3'] ?? '#1d4ed8' }}</span>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label-custom">Color acento (badge / botón)</label>
                <div class="color-row">
                    <input type="color" name="login_color_acc" id="c4"
                        value="{{ $settings['login_color_acc'] ?? '#10b981' }}"
                        oninput="updatePreview()">
                    <span class="form-control-custom" style="flex:1;padding:.3rem .6rem;font-size:.78rem;" id="c4val">{{ $settings['login_color_acc'] ?? '#10b981' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Registro --}}
    <div class="card-panel">
        <div class="section-title"><i class="bi bi-person-plus me-1"></i>Registro público</div>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="login_allow_reg" id="allowReg" value="1"
                {{ ($settings['login_allow_reg'] ?? '0') === '1' ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="allowReg">
                Mostrar enlace "¿No tienes cuenta? Registrarse" en el login
            </label>
        </div>
        <small class="text-muted d-block mt-1">Si está desactivado, los usuarios solo pueden iniciar sesión con cuentas creadas por el administrador.</small>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <button type="reset" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar
        </button>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save me-1"></i>Guardar cambios
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
function updatePreview() {
    const c1 = document.getElementById('c1').value;
    const c2 = document.getElementById('c2').value;
    const c3 = document.getElementById('c3').value;
    const c4 = document.getElementById('c4').value;
    document.getElementById('c1val').textContent = c1;
    document.getElementById('c2val').textContent = c2;
    document.getElementById('c3val').textContent = c3;
    document.getElementById('c4val').textContent = c4;
    document.getElementById('prevBg').style.background = `linear-gradient(140deg,${c1} 0%,${c2} 55%,${c3} 100%)`;
    document.getElementById('prevBadge').style.background = c4;
    document.getElementById('prevBtn').style.background = c2;
}
</script>
@endpush
