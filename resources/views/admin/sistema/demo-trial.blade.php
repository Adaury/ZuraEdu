@extends('layouts.admin')
@section('page-title', 'Demo & Período de Prueba')

@push('styles')
<style>
    .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
    .page-header h1 { font-size:1.45rem; font-weight:800; color:var(--primary); margin:0; }
    .card-panel { background:#fff; border-radius:14px; border:1px solid #e5e7eb; padding:1.5rem; margin-bottom:1.5rem; }
    .section-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--primary); border-bottom:2px solid var(--primary); padding-bottom:.4rem; margin-bottom:1.1rem; }

    /* Toggle switch */
    .big-toggle { display:flex; align-items:center; gap:1rem; }
    .big-toggle .form-check-input { width:3rem; height:1.6rem; cursor:pointer; }
    .big-toggle-label { font-size:1rem; font-weight:700; color:#111827; }
    .big-toggle-sub { font-size:.8rem; color:#6b7280; margin-top:.15rem; }

    /* Demo user card */
    .demo-user-card { border:1.5px solid #e5e7eb; border-radius:10px; padding:1rem 1.25rem; display:flex; align-items:center; gap:1rem; }
    .demo-user-avatar { width:42px; height:42px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:1rem; color:#fff; flex-shrink:0; }
    .demo-user-name { font-size:.88rem; font-weight:700; color:#111827; }
    .demo-user-email { font-size:.75rem; color:#6b7280; }
    .badge-ok { background:#dcfce7; color:#166534; border-radius:6px; padding:.15rem .55rem; font-size:.7rem; font-weight:700; }
    .badge-missing { background:#fee2e2; color:#991b1b; border-radius:6px; padding:.15rem .55rem; font-size:.7rem; font-weight:700; }

    /* Trial progress */
    .trial-bar-wrap { background:#f1f5f9; border-radius:99px; height:12px; overflow:hidden; margin:.5rem 0; }
    .trial-bar { height:100%; border-radius:99px; transition:width .5s; }
    [data-theme="dark"] .card-panel { background: #1e293b; border-color: #334155; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1><i class="bi bi-play-circle me-2"></i>Demo & Período de Prueba</h1>
        <p class="text-muted small mb-0">Gestiona el modo demo y el período de prueba del sistema</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 mb-3">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 mb-3">
        <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ── MODO DEMO ─────────────────────────────────────────────────────── --}}
<div class="card-panel">
    <div class="section-title"><i class="bi bi-play-circle-fill me-1"></i>Modo Demo Público</div>

    {{-- Toggle ON/OFF --}}
    <form method="POST" action="{{ route('admin.sistema.demo.toggle') }}" class="mb-4">
        @csrf
        <div class="big-toggle mb-3">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" name="demo_activo" id="demoToggle"
                    {{ ($settings['demo_activo'] ?? '0') === '1' ? 'checked' : '' }}
                    onchange="this.form.submit()">
            </div>
            <div>
                <div class="big-toggle-label">
                    @if(($settings['demo_activo'] ?? '0') === '1')
                        <span class="text-success"><i class="bi bi-circle-fill me-1" style="font-size:.55rem;"></i>Demo activado</span>
                    @else
                        <span class="text-danger"><i class="bi bi-circle-fill me-1" style="font-size:.55rem;"></i>Demo desactivado</span>
                    @endif
                </div>
                <div class="big-toggle-sub">Cuando está activo, los visitantes pueden acceder con perfiles de prueba desde la página de inicio.</div>
            </div>
        </div>
    </form>

    {{-- Perfiles disponibles --}}
    <div class="section-title" style="font-size:.68rem;"><i class="bi bi-people me-1"></i>Perfiles demo disponibles</div>
    <div class="row g-3 mb-3">
        @php
        $perfiles = [
            ['rol'=>'docente',    'email'=>'docente@demo.com',    'color'=>'#1d4ed8', 'bg'=>'#dbeafe', 'icon'=>'bi-person-badge-fill', 'label'=>'Docente'],
            ['rol'=>'estudiante', 'email'=>'estudiante@demo.com', 'color'=>'#059669', 'bg'=>'#dcfce7', 'icon'=>'bi-mortarboard-fill',  'label'=>'Estudiante'],
            ['rol'=>'padre',      'email'=>'padre@demo.com',      'color'=>'#b45309', 'bg'=>'#fef3c7', 'icon'=>'bi-people-fill',       'label'=>'Representante'],
        ];
        @endphp
        @foreach($perfiles as $p)
        <div class="col-md-4">
            <div class="demo-user-card">
                <div class="demo-user-avatar" style="background:{{ $p['color'] }};">
                    <i class="bi {{ $p['icon'] }}"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="demo-user-name">{{ $p['label'] }}</div>
                    <div class="demo-user-email">{{ $p['email'] }}</div>
                    <div class="mt-1">
                        @if($usuariosDemo[$p['rol']])
                            <span class="badge-ok"><i class="bi bi-check-circle-fill me-1"></i>Listo</span>
                        @else
                            <span class="badge-missing"><i class="bi bi-exclamation-circle-fill me-1"></i>Falta crear</span>
                        @endif
                    </div>
                </div>
                @if(($settings['demo_activo'] ?? '0') === '1' && $usuariosDemo[$p['rol']])
                <a href="{{ route('demo.login', $p['rol']) }}" target="_blank"
                   class="btn btn-sm btn-outline-secondary" title="Probar acceso">
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Crear usuarios demo --}}
    @if(!$usuariosDemo['docente'] || !$usuariosDemo['estudiante'] || !$usuariosDemo['padre'])
    <form method="POST" action="{{ route('admin.sistema.demo.crear') }}">
        @csrf
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-1"></i>Crear usuarios demo (contraseña: 123456)
        </button>
    </form>
    @else
    <form method="POST" action="{{ route('admin.sistema.demo.crear') }}">
        @csrf
        <button type="submit" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-clockwise me-1"></i>Recrear / restablecer usuarios demo
        </button>
    </form>
    @endif

    <div class="mt-3 p-3 rounded-3" style="background:#f8fafc;border:1px dashed #cbd5e1;font-size:.8rem;color:#64748b;">
        <i class="bi bi-info-circle me-1"></i>
        El perfil <strong>Administrador</strong> no tiene acceso demo por seguridad.
        La contraseña de todos los perfiles demo es <code style="background:#e2e8f0;border-radius:4px;padding:.1rem .35rem;">123456</code>.
        Los cambios críticos están bloqueados automáticamente en modo demo.
    </div>
</div>

{{-- ── PERÍODO DE PRUEBA ────────────────────────────────────────────── --}}
<div class="card-panel">
    <div class="section-title"><i class="bi bi-hourglass-split me-1"></i>Período de Prueba</div>

    {{-- Estado actual --}}
    @if($trialActivo && $trialInicio)
        @php $pct = $trialDiasRestantes > 0 ? round(($trialDiasRestantes / $trialDias) * 100) : 0; @endphp
        <div class="p-3 rounded-3 mb-4 {{ $trialExpirado ? 'bg-danger bg-opacity-10 border border-danger' : 'bg-warning bg-opacity-10 border border-warning' }}">
            @if($trialExpirado)
                <div class="fw-700 text-danger mb-1"><i class="bi bi-x-octagon-fill me-1"></i>Período de prueba EXPIRADO</div>
                <div class="text-muted small">Expiró el {{ $trialExpira->format('d/m/Y') }}</div>
            @else
                <div class="fw-700 mb-1" style="color:#92400e;">
                    <i class="bi bi-hourglass-split me-1"></i>En período de prueba —
                    <span style="font-size:1.2rem;">{{ $trialDiasRestantes }}</span> días restantes
                </div>
                <div class="trial-bar-wrap">
                    <div class="trial-bar" style="width:{{ $pct }}%;background:{{ $pct > 50 ? '#10b981' : ($pct > 20 ? '#f59e0b' : '#ef4444') }};"></div>
                </div>
                <div class="text-muted small">
                    Inicio: {{ \Carbon\Carbon::parse($trialInicio)->format('d/m/Y') }} —
                    Expira: {{ $trialExpira->format('d/m/Y') }}
                </div>
            @endif
            <form method="POST" action="{{ route('admin.sistema.trial.desactivar') }}" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-stop-fill me-1"></i>Desactivar período de prueba
                </button>
            </form>
        </div>
    @else
        <div class="alert alert-secondary border-0 rounded-3 mb-4" style="font-size:.85rem;">
            <i class="bi bi-hourglass me-1"></i>Período de prueba <strong>inactivo</strong>.
            Actívalo para mostrar una barra de cuenta regresiva en el sistema.
        </div>
    @endif

    {{-- Formulario configuración --}}
    <form method="POST" action="{{ route('admin.sistema.trial.save') }}">
        @csrf
        <div class="row g-3">
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="trial_activo" id="trialActivo" value="1"
                        {{ $trialActivo ? 'checked' : '' }}>
                    <label class="form-check-label fw-600" for="trialActivo">Activar período de prueba</label>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-600" style="font-size:.83rem;">Fecha de inicio</label>
                <input type="date" name="trial_inicio" class="form-control"
                    value="{{ $trialInicio ?? now()->format('Y-m-d') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-600" style="font-size:.83rem;">Duración (días)</label>
                <input type="number" name="trial_dias" class="form-control"
                    value="{{ $trialDias }}" min="1" max="365">
                <small class="text-muted">Ej: 30 días de prueba</small>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-600" style="font-size:.83rem;">Mensaje informativo</label>
                <input type="text" name="trial_mensaje" class="form-control"
                    value="{{ $settings['trial_mensaje'] ?? '' }}"
                    placeholder="Estás usando una versión de prueba...">
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i>Guardar configuración de prueba
            </button>
        </div>
    </form>
</div>
@endsection
