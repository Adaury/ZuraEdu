@extends('layouts.admin')
@section('title', 'Centro de Integraciones')

@push('styles')
<style>
.integration-hero {
    background: linear-gradient(135deg, #1e3a6e 0%, #3b5bdb 50%, #7c3aed 100%);
    border-radius: 20px;
    padding: 2.5rem 2rem;
    position: relative;
    overflow: hidden;
    margin-bottom: 2rem;
}
.integration-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Ccircle cx='30' cy='30' r='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}
.integration-hero .hero-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(60px);
    opacity: .25;
}
.integration-hero .orb-1 { width: 300px; height: 300px; background: #60a5fa; top: -100px; right: -80px; }
.integration-hero .orb-2 { width: 200px; height: 200px; background: #a78bfa; bottom: -80px; left: 10%; }

/* Integration cards */
.int-card {
    border: none;
    border-radius: 18px;
    overflow: hidden;
    transition: transform .3s cubic-bezier(.4,0,.2,1), box-shadow .3s ease;
    position: relative;
    background: #fff;
    box-shadow: 0 4px 20px rgba(0,0,0,.07);
}
.int-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 40px rgba(0,0,0,.13);
}
.int-card .card-accent {
    height: 6px;
    width: 100%;
}
.int-card .card-body { padding: 1.75rem; }

/* Icon circle */
.int-icon {
    width: 60px; height: 60px;
    border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem;
    flex-shrink: 0;
    margin-bottom: 1rem;
}

/* Status badges */
.badge-activo  { background: linear-gradient(135deg,#22c55e,#16a34a); color:#fff; }
.badge-config  { background: linear-gradient(135deg,#f59e0b,#d97706); color:#fff; }
.badge-prox    { background: linear-gradient(135deg,#94a3b8,#64748b); color:#fff; }
.badge-disp    { background: linear-gradient(135deg,#3b82f6,#2563eb); color:#fff; }

.int-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: 50px;
    font-size: .75rem; font-weight: 600; letter-spacing: .4px;
}
.int-badge i { font-size: .7rem; }

/* Action button */
.int-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 20px; border-radius: 10px;
    font-size: .85rem; font-weight: 600;
    text-decoration: none;
    transition: all .2s ease;
    border: none; cursor: pointer;
}
.int-btn:hover { transform: translateX(3px); }
.int-btn-disabled { opacity: .5; cursor: not-allowed; pointer-events: none; }

/* Stats row */
.stat-pill {
    background: rgba(59,91,219,.07);
    border: 1px solid rgba(59,91,219,.12);
    border-radius: 50px;
    padding: 6px 16px;
    font-size: .78rem;
    display: inline-flex; align-items: center; gap: 6px;
    color: #3b5bdb; font-weight: 600;
}

/* Dark mode */
[data-bs-theme="dark"] .int-card {
    background: rgba(30,30,46,.85);
    box-shadow: 0 4px 20px rgba(0,0,0,.3);
}
[data-bs-theme="dark"] .stat-pill {
    background: rgba(99,102,241,.15);
    border-color: rgba(99,102,241,.25);
    color: #818cf8;
}

/* Slide-up entrance */
.p-slide-up   { animation: premiumSlideUp .55s cubic-bezier(.4,0,.2,1) both; }
.p-delay-1    { animation-delay: .07s; }
.p-delay-2    { animation-delay: .14s; }
.p-delay-3    { animation-delay: .21s; }
.p-delay-4    { animation-delay: .28s; }
@keyframes premiumSlideUp {
    from { opacity:0; transform:translateY(22px); }
    to   { opacity:1; transform:translateY(0); }
}
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

    {{-- Hero Header --}}
    <div class="integration-hero p-slide-up">
        <div class="hero-orb orb-1"></div>
        <div class="hero-orb orb-2"></div>
        <div class="position-relative">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div style="width:48px;height:48px;background:rgba(255,255,255,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px)">
                    <i class="bi bi-plugin fs-4 text-white"></i>
                </div>
                <div>
                    <h1 class="h3 mb-0 text-white fw-bold">Centro de Integraciones</h1>
                    <p class="text-white-50 mb-0 small">Conecta ZuraEdu SGE con sistemas externos</p>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap mt-3">
                <span class="stat-pill" style="background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.2);color:#fff">
                    <i class="bi bi-check-circle-fill" style="color:#4ade80"></i> 2 activas
                </span>
                <span class="stat-pill" style="background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.2);color:#fff">
                    <i class="bi bi-clock" style="color:#fbbf24"></i> 1 próximamente
                </span>
                <span class="stat-pill" style="background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.2);color:#fff">
                    <i class="bi bi-grid-3x3-gap" style="color:#93c5fd"></i> 4 integraciones
                </span>
            </div>
        </div>
    </div>

    {{-- Cards Grid --}}
    <div class="row g-4">

        {{-- SIGERD MINERD --}}
        <div class="col-md-6 p-slide-up p-delay-1">
            <div class="int-card h-100">
                <div class="card-accent" style="background:linear-gradient(90deg,#1e3a6e,#3b5bdb)"></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="int-icon" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe)">
                            <i class="bi bi-building" style="color:#1e3a6e"></i>
                        </div>
                        <span class="int-badge badge-activo">
                            <i class="bi bi-circle-fill"></i> Activo
                        </span>
                    </div>
                    <h5 class="fw-bold mb-1">SIGERD</h5>
                    <p class="text-muted small mb-1">Sistema MINERD — República Dominicana</p>
                    <p class="text-muted mb-4" style="font-size:.88rem">
                        Exportaciones oficiales al sistema SIGERD del Ministerio de Educación. Nómina, calificaciones, docentes y asistencia.
                    </p>
                    <a href="{{ route('admin.sigerd.index') }}" class="int-btn" style="background:linear-gradient(135deg,#1e3a6e,#3b5bdb);color:#fff">
                        Abrir SIGERD <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Google Classroom --}}
        <div class="col-md-6 p-slide-up p-delay-2">
            <div class="int-card h-100">
                <div class="card-accent" style="background:linear-gradient(90deg,#ea4335,#fbbc04,#34a853,#4285f4)"></div>
                <div class="card-body">
                    @php $classroomActive = class_exists('App\Models\ClassroomConfig') && \App\Models\ClassroomConfig::count() > 0; @endphp
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="int-icon" style="background:linear-gradient(135deg,#fef9c3,#fde68a)">
                            <i class="bi bi-google" style="color:#ea4335"></i>
                        </div>
                        <span class="int-badge {{ $classroomActive ? 'badge-config' : 'badge-disp' }}">
                            <i class="bi bi-circle-fill"></i> {{ $classroomActive ? 'Configurado' : 'Disponible' }}
                        </span>
                    </div>
                    <h5 class="fw-bold mb-1">Google Classroom</h5>
                    <p class="text-muted small mb-1">ZuraClass Integration</p>
                    <p class="text-muted mb-4" style="font-size:.88rem">
                        Sincroniza clases, tareas y calificaciones con Google Classroom. Gestiona estudiantes y docentes automáticamente.
                    </p>
                    @if(\Illuminate\Support\Facades\Route::has('admin.classroom.index'))
                    <a href="{{ route('admin.classroom.index') }}" class="int-btn" style="background:linear-gradient(135deg,#ea4335,#4285f4);color:#fff">
                        Configurar <i class="bi bi-arrow-right"></i>
                    </a>
                    @else
                    <a href="#" class="int-btn" style="background:linear-gradient(135deg,#ea4335,#4285f4);color:#fff">
                        Abrir <i class="bi bi-arrow-right"></i>
                    </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Office 365 --}}
        <div class="col-md-6 p-slide-up p-delay-3">
            <div class="int-card h-100">
                <div class="card-accent" style="background:linear-gradient(90deg,#0078d4,#00bcf2)"></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="int-icon" style="background:linear-gradient(135deg,#dbeafe,#bae6fd)">
                            <i class="bi bi-microsoft" style="color:#0078d4"></i>
                        </div>
                        <span class="int-badge badge-prox">
                            <i class="bi bi-hourglass-split"></i> Próximamente
                        </span>
                    </div>
                    <h5 class="fw-bold mb-1">Office 365</h5>
                    <p class="text-muted small mb-1">Microsoft Education</p>
                    <p class="text-muted mb-4" style="font-size:.88rem">
                        Integración con Microsoft Teams, OneDrive y aplicaciones educativas de Office 365.
                    </p>
                    <button class="int-btn int-btn-disabled" style="background:#e2e8f0;color:#94a3b8">
                        Próximamente <i class="bi bi-lock"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- WhatsApp --}}
        <div class="col-md-6 p-slide-up p-delay-4">
            <div class="int-card h-100">
                <div class="card-accent" style="background:linear-gradient(90deg,#25D366,#128C7E)"></div>
                <div class="card-body">
                    @php $waActive = \App\Helpers\Setting::moduleEnabled('whatsapp'); @endphp
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="int-icon" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0)">
                            <i class="bi bi-whatsapp" style="color:#25D366"></i>
                        </div>
                        <span class="int-badge {{ $waActive ? 'badge-activo' : 'badge-disp' }}">
                            <i class="bi bi-circle-fill"></i> {{ $waActive ? 'Activo' : 'Disponible' }}
                        </span>
                    </div>
                    <h5 class="fw-bold mb-1">WhatsApp Business</h5>
                    <p class="text-muted small mb-1">Notificaciones y alertas</p>
                    <p class="text-muted mb-4" style="font-size:.88rem">
                        Envía notificaciones de notas, ausencias, pagos y avisos a representantes vía WhatsApp.
                    </p>
                    <a href="{{ route('admin.sistema.whatsapp') }}" class="int-btn" style="background:linear-gradient(135deg,#25D366,#128C7E);color:#fff">
                        Configurar <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
