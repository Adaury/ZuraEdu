@extends('layouts.admin')
@section('page-title', 'Dashboard')

@section('content')

{{-- ── BANNER MODO DEMO ─────────────────────────────────────────────────── --}}
@if(session('demo_mode') && session('demo_admin'))
<div id="demo-banner" style="background:linear-gradient(135deg,#7c3aed,#a855f7);border:none;border-radius:16px;padding:1rem 1.5rem;color:#fff;display:flex;align-items:center;gap:1rem;box-shadow:0 8px 24px rgba(124,58,237,.35);margin-bottom:1rem;">
    <div style="font-size:2rem;flex-shrink:0;">🎮</div>
    <div style="flex:1;">
        <div style="font-weight:800;font-size:1rem;margin-bottom:.15rem;">Modo Demo — ZuraEdu</div>
        <div style="font-size:.82rem;opacity:.9;">
            Estás explorando el sistema con datos ficticios. Las modificaciones <strong>no se guardan</strong>.
            <a href="{{ route('onboarding') }}" style="color:#fde68a;font-weight:700;margin-left:.5rem;">→ Crear mi escuela GRATIS</a>
        </div>
    </div>
    <button onclick="document.getElementById('demo-banner').remove()" style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:8px;padding:.3rem .75rem;cursor:pointer;font-size:.85rem;flex-shrink:0;">Cerrar</button>
</div>
@endif

{{-- ── BANNER CONFIGURACIÓN PENDIENTE ──────────────────────────────────── --}}
@if(! ($currentTenant->onboarding_completado ?? true))
<div class="mb-4" style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);border-radius:16px;padding:1.25rem 1.5rem;color:#fff;display:flex;align-items:center;gap:1.25rem;box-shadow:0 8px 24px rgba(29,78,216,.3);">
    <div style="font-size:2rem;flex-shrink:0;">⚙️</div>
    <div style="flex:1;">
        <div style="font-size:1rem;font-weight:800;margin-bottom:.2rem;">Completa la configuración inicial</div>
        <div style="font-size:.83rem;opacity:.9;line-height:1.5;">
            Solo faltan 3 pasos para dejar tu institución lista: institución, año escolar y grados.
        </div>
    </div>
    <a href="{{ route('admin.onboarding.show', max(1, $currentTenant->onboarding_paso ?? 1)) }}"
       style="background:rgba(255,255,255,.2);color:#fff;border:1.5px solid rgba(255,255,255,.4);border-radius:10px;padding:.55rem 1.1rem;text-decoration:none;font-size:.85rem;font-weight:700;flex-shrink:0;white-space:nowrap;">
        Continuar configuración →
    </a>
</div>
@endif

{{-- ── CHECKLIST POST-ONBOARDING ────────────────────────────────────────── --}}
@if(! empty($setupChecklist) && ! $setupChecklist['todo_listo'])
    @include('admin.dashboard._setup_checklist', ['checklist' => $setupChecklist])
@endif

{{-- ══════════════════════════════════════════════
     BANNER BIENVENIDA ESTILO ZURA
     ══════════════════════════════════════════════ --}}
@php
    $hora = now()->hour;
    $saludo = $hora < 12 ? 'Buenos días' : ($hora < 18 ? 'Buenas tardes' : 'Buenas noches');
    $nombreUsuario = Auth::user()->name ?? 'Usuario';
    $primerNombre  = explode(' ', $nombreUsuario)[0];
@endphp
<div class="mb-4" style="background:#3B82F6;border-radius:16px;padding:28px 32px;position:relative;overflow:hidden;box-shadow:0 4px 24px rgba(59,130,246,.35);">
    {{-- círculos decorativos --}}
    <div style="position:absolute;top:-40px;right:-40px;width:180px;height:180px;background:rgba(255,255,255,.08);border-radius:50%;"></div>
    <div style="position:absolute;bottom:-50px;right:120px;width:130px;height:130px;background:rgba(255,255,255,.06);border-radius:50%;"></div>
    <div style="position:absolute;top:50%;right:32px;transform:translateY(-50%);opacity:.08;">
        <i class="bi bi-mortarboard-fill" style="font-size:6rem;color:#fff;"></i>
    </div>

    <div style="position:relative;z-index:1;">
        <div style="font-size:1.75rem;font-weight:800;color:#fff;letter-spacing:-.02em;line-height:1.2;">
            Bienvenido {{ $primerNombre }}
        </div>
        <div style="font-size:.9rem;color:rgba(255,255,255,.8);margin-top:6px;">
            {{ $saludo }} &mdash;
            @php
                $roles = ['Administrador'=>'Administrador del Sistema','Director'=>'Director','Coordinador'=>'Coordinador Académico','Docente'=>'Docente'];
                $rolActual = collect($roles)->first(fn($v,$k) => Auth::user()->hasRole($k)) ?? 'Usuario';
            @endphp
            {{ $rolActual }}
            @if($schoolYear)
            &nbsp;&bull;&nbsp; Año Escolar <strong>{{ $schoolYear->nombre }}</strong>
            <span style="background:rgba(255,255,255,.2);color:#fff;font-size:.7rem;border-radius:20px;padding:.2rem .65rem;margin-left:6px;font-weight:700;">ACTIVO</span>
            @endif
        </div>
        <div style="font-size:.78rem;color:rgba(255,255,255,.65);margin-top:8px;">
            <i class="bi bi-calendar3 me-1"></i>{{ now()->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
            &nbsp;&bull;&nbsp;
            <i class="bi bi-clock me-1"></i><span id="zura-clock">{{ now()->format('H:i') }}</span>
        </div>
    </div>
</div>

{{-- Bienvenida: solo para roles admin (Docentes usan su propio panel) --}}
@unless($isDocente)
@endunless {{-- /isDocente --}}

{{-- ── Stats y módulos admin (ocultos para Docentes) ──────────── --}}
@unless($isDocente)
{{-- ── Header de estadísticas con botón actualizar ─────────── --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#64748b;">RESUMEN DEL SISTEMA</div>
        <div id="stats-updated-at" style="font-size:.73rem;color:#94a3b8;">
            Actualizado: {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>
    <button id="btnRefreshStats" class="btn btn-sm d-flex align-items-center gap-2"
            style="background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;color:#374151;font-size:.8rem;font-weight:600;box-shadow:0 2px 8px rgba(0,0,0,.04);">
        <i class="bi bi-arrow-clockwise" id="refreshIcon"></i> Actualizar
    </button>
</div>

{{-- Tarjetas de estadísticas --}}
<div class="row g-4 mb-4" id="statsRow">
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.estudiantes.index') }}" class="text-decoration-none d-block">
            <div class="stat-card" style="--c:#0d6efd;cursor:pointer;">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-num" id="stat-estudiantes">{{ $totalEstudiantes }}</div>
                    <div class="stat-label">Estudiantes Activos</div>
                </div>
                <div style="position:absolute;bottom:14px;right:16px;font-size:.7rem;color:rgba(255,255,255,.55);font-weight:600;letter-spacing:.04em;">
                    VER TODOS <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.docentes.index') }}" class="text-decoration-none d-block">
            <div class="stat-card" style="--c:#198754;cursor:pointer;">
                <div class="stat-icon"><i class="bi bi-person-badge-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-num" id="stat-docentes">{{ $totalDocentes }}</div>
                    <div class="stat-label">Docentes Activos</div>
                </div>
                <div style="position:absolute;bottom:14px;right:16px;font-size:.7rem;color:rgba(255,255,255,.55);font-weight:600;letter-spacing:.04em;">
                    VER TODOS <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.grupos.index') }}" class="text-decoration-none d-block">
            <div class="stat-card" style="--c:#fd7e14;cursor:pointer;">
                <div class="stat-icon"><i class="bi bi-grid-3x3-gap-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-num" id="stat-grupos">{{ $totalGrupos }}</div>
                    <div class="stat-label">Grupos / Cursos</div>
                </div>
                <div style="position:absolute;bottom:14px;right:16px;font-size:.7rem;color:rgba(255,255,255,.55);font-weight:600;letter-spacing:.04em;">
                    VER TODOS <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.asignaturas.index') }}" class="text-decoration-none d-block">
            <div class="stat-card" style="--c:#6f42c1;cursor:pointer;">
                <div class="stat-icon"><i class="bi bi-book-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-num" id="stat-asignaturas">{{ $totalAsignaturas }}</div>
                    <div class="stat-label">Asignaturas</div>
                </div>
                <div style="position:absolute;bottom:14px;right:16px;font-size:.7rem;color:rgba(255,255,255,.55);font-weight:600;letter-spacing:.04em;">
                    VER TODOS <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>
    </div>
</div>

{{-- Stats adicionales: matrículas + planificaciones y observaciones --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
        <a href="{{ route('admin.matriculas.index') }}" class="text-decoration-none d-block">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #0d6efd !important;border-radius:14px;">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div style="width:40px;height:40px;background:#0d6efd18;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-person-check-fill" style="color:#0d6efd;font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div class="fw-bold" id="stat-matriculas" style="font-size:1.2rem;color:#0d6efd;">{{ $matriculasActivas ?? 0 }}</div>
                        <div class="text-muted small">Matrículas Activas</div>
                    </div>
                    <div class="ms-auto">
                        <span id="stat-matriculas-pulse" style="display:none;width:8px;height:8px;background:#22c55e;border-radius:50%;animation:pulse 1.5s infinite;" title="Actualizado en tiempo real"></span>
                    </div>
                </div>
            </div>
        </a>
    </div>
@if(!empty($statsExtra))
    @if(($statsExtra['planificaciones'] ?? 0) > 0 || ($statsExtra['planes_clase'] ?? 0) > 0)
    <div class="col-sm-6 col-xl-4">
        <a href="{{ route('admin.planificacion.index') }}" class="text-decoration-none d-block">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #7c3aed !important;border-radius:14px;">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div style="width:40px;height:40px;background:#7c3aed18;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-journal-text" style="color:#7c3aed;font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:1.2rem;color:#7c3aed;">{{ $statsExtra['planificaciones'] }}</div>
                        <div class="text-muted small">Planificaciones Técnicas</div>
                    </div>
                    @if(($statsExtra['planes_clase'] ?? 0) > 0)
                    <div class="ms-auto text-end">
                        <div class="fw-bold" style="font-size:1rem;color:#0891b2;">{{ $statsExtra['planes_clase'] }}</div>
                        <div class="text-muted" style="font-size:.72rem;">Planes de Clase</div>
                    </div>
                    @endif
                </div>
            </div>
        </a>
    </div>
    @endif
    @if(($statsExtra['observaciones'] ?? 0) > 0)
    <div class="col-sm-6 col-xl-4">
        <a href="{{ route('admin.observaciones.index') }}" class="text-decoration-none d-block">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #f59e0b !important;border-radius:14px;">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div style="width:40px;height:40px;background:#f59e0b18;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-chat-square-text" style="color:#f59e0b;font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:1.2rem;color:#f59e0b;">{{ $statsExtra['observaciones'] }}</div>
                        <div class="text-muted small">Observaciones Docentes</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endif
@endif
</div>

@endunless {{-- /isDocente --}}

{{-- ── Widget: ZuraClass ───────────────────────────────────────────── --}}
@if(!empty($zuraClassData))
@if($isDocente && !empty($zuraClassData['clases']) && $zuraClassData['clases']->isNotEmpty())
{{-- Vista docente: mis clases + entregas pendientes --}}
<div class="card border-0 mb-4" style="border-radius:20px;box-shadow:0 4px 24px rgba(0,0,0,.06);overflow:hidden;">
    <div class="card-header border-0 py-3 px-4 d-flex align-items-center gap-2"
         style="background:linear-gradient(135deg,#3730a3,#4f46e5);color:#fff;">
        <i class="bi bi-easel2-fill" style="font-size:1.1rem;"></i>
        <span style="font-weight:700;">ZuraClass — Mis Aulas Virtuales</span>
        @if($zuraClassData['entregasPendientes'] > 0)
        <span class="ms-2" style="background:rgba(255,255,255,.2);border-radius:99px;padding:.15rem .6rem;font-size:.72rem;font-weight:700;">
            <i class="bi bi-inbox me-1"></i>{{ $zuraClassData['entregasPendientes'] }} por calificar
        </span>
        @endif
        <a href="{{ route('portal.docente.classroom.index') }}" class="ms-auto"
           style="font-size:.75rem;color:rgba(255,255,255,.9);text-decoration:none;display:flex;align-items:center;gap:.3rem;">
            Ver todas <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    <div class="card-body py-3 px-4">
        <div class="row g-3">
            @foreach($zuraClassData['clases'] as $clase)
            @php $color = $clase->portada_color ?? '#4f46e5'; @endphp
            <div class="col-md-6">
                <a href="{{ route('portal.docente.classroom.show', $clase) }}" class="text-decoration-none">
                    <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E5E7EB;transition:.15s;" onmouseover="this.style.borderColor='{{ $color }}'" onmouseout="this.style.borderColor='#E5E7EB'">
                        <div style="width:44px;height:44px;background:{{ $color }};border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-easel2-fill" style="color:#fff;font-size:1.1rem;"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold text-dark" style="font-size:.88rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $clase->nombre }}</div>
                            <div class="text-muted" style="font-size:.75rem;">{{ $clase->asignacion?->asignatura?->nombre }} · {{ $clase->asignacion?->grupo?->nombre }}</div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <div style="font-size:.75rem;color:#6b7280;">{{ $clase->materiales->count() }} materiales</div>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
        @if($zuraClassData['entregasPendientes'] > 0)
        <div class="mt-3 pt-2 border-top">
            <a href="{{ route('portal.docente.classroom.index') }}"
               style="display:inline-flex;align-items:center;gap:.4rem;background:#eef2ff;color:#4338ca;border-radius:8px;padding:.35rem .8rem;font-size:.78rem;font-weight:700;text-decoration:none;">
                <i class="bi bi-inbox-fill"></i>
                {{ $zuraClassData['entregasPendientes'] }} entrega(s) esperando calificación
            </a>
        </div>
        @endif
    </div>
</div>

@elseif(!$isDocente && !empty($zuraClassData['totalClasesActivas']))
{{-- Vista admin: resumen global --}}
<div class="card border-0 mb-4" style="border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.06);overflow:hidden;">
    <div class="card-body d-flex align-items-center gap-4 py-3 px-4" style="background:linear-gradient(135deg,#eef2ff,#e0e7ff);">
        <div style="width:48px;height:48px;background:#4f46e5;border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-easel2-fill" style="color:#fff;font-size:1.25rem;"></i>
        </div>
        <div class="flex-grow-1">
            <div style="font-weight:800;font-size:.95rem;color:#3730a3;">ZuraClass — Aulas Virtuales</div>
            <div style="font-size:.8rem;color:#4f46e5;margin-top:2px;">
                <strong>{{ $zuraClassData['totalClasesActivas'] }}</strong> aulas activas
                @if($zuraClassData['totalEntregasPend'] > 0)
                &nbsp;·&nbsp; <strong style="color:#dc2626;">{{ $zuraClassData['totalEntregasPend'] }}</strong> entregas pendientes de calificación
                @else
                &nbsp;·&nbsp; Todas las entregas calificadas
                @endif
            </div>
        </div>
        <a href="{{ route('admin.classroom.index') }}"
           style="background:#4f46e5;color:#fff;border-radius:10px;padding:.4rem 1rem;font-size:.8rem;font-weight:700;text-decoration:none;white-space:nowrap;">
            <i class="bi bi-arrow-right me-1"></i>Ver aulas
        </a>
    </div>
</div>
@endif
@endif

{{-- ── Widget: Pagos y Colegiaturas ───────────────────────────────── --}}
@if(!empty($statsPagos))
<div class="card border-0 mb-4" style="border-radius:20px;box-shadow:0 4px 24px rgba(0,0,0,.06);overflow:hidden;">
    <div class="card-header border-0 py-3 px-4 d-flex align-items-center gap-2"
         style="background:linear-gradient(135deg,#0f766e,#14b8a6);color:#fff;">
        <i class="bi bi-cash-coin" style="font-size:1.1rem;"></i>
        <span style="font-weight:700;">Pagos y Colegiaturas</span>
        @if($statsPagos['deudores'] > 0)
        <span class="ms-2" style="background:rgba(255,255,255,.2);border-radius:99px;padding:.15rem .6rem;font-size:.72rem;font-weight:700;">
            <i class="bi bi-exclamation-triangle me-1"></i>{{ $statsPagos['deudores'] }} deudor(es)
        </span>
        @endif
        <a href="{{ route('admin.pagos.index') }}" class="ms-auto"
           style="font-size:.75rem;color:rgba(255,255,255,.9);text-decoration:none;display:flex;align-items:center;gap:.3rem;">
            Ver todo <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    <div class="card-body py-3 px-4">
        <div class="row g-3">
            <div class="col-4 text-center">
                <div style="font-size:1.3rem;font-weight:900;color:#065f46;">RD$ {{ number_format($statsPagos['cobrado'],0) }}</div>
                <div class="text-muted small">Cobrado</div>
            </div>
            <div class="col-4 text-center">
                <div style="font-size:1.3rem;font-weight:900;color:#92400e;">RD$ {{ number_format($statsPagos['pendiente'],0) }}</div>
                <div class="text-muted small">Pendiente</div>
            </div>
            <div class="col-4 text-center">
                <div style="font-size:1.3rem;font-weight:900;color:{{ $statsPagos['vencido'] > 0 ? '#991b1b' : '#6b7280' }};">
                    RD$ {{ number_format($statsPagos['vencido'],0) }}
                </div>
                <div class="text-muted small">Vencido</div>
            </div>
        </div>
        @if($statsPagos['deudores'] > 0)
        <div class="mt-3 pt-2 border-top">
            <a href="{{ route('admin.pagos.index', ['estado' => 'vencido']) }}"
               style="display:inline-flex;align-items:center;gap:.4rem;background:#fee2e2;color:#991b1b;border-radius:8px;padding:.35rem .8rem;font-size:.78rem;font-weight:700;text-decoration:none;">
                <i class="bi bi-exclamation-circle-fill"></i>
                Ver los {{ $statsPagos['deudores'] }} estudiante(s) con pagos vencidos
            </a>
        </div>
        @endif
    </div>
</div>
@endif

{{-- ── Widget: Horario publicado ──────────────────────────────────── --}}
@if($horarioActivo)
<div class="card border-0 mb-4" style="border-radius:20px;box-shadow:0 4px 24px rgba(0,0,0,.06);overflow:hidden;">
    <div class="card-body d-flex align-items-center gap-3 py-3 px-4" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);">
        <div style="width:44px;height:44px;background:#1d4ed8;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-calendar-week-fill" style="color:#fff;font-size:1.1rem;"></i>
        </div>
        <div class="flex-grow-1">
            <div style="font-weight:700;font-size:.92rem;color:#1e3a8a;">
                Horario publicado: {{ $horarioActivo->nombre }}
            </div>
            <div style="font-size:.77rem;color:#1d4ed8;margin-top:2px;">
                <i class="bi bi-check-circle-fill me-1"></i>Disponible para toda la comunidad educativa
            </div>
        </div>
        <a href="{{ route('admin.horarios.show', $horarioActivo) }}"
           class="btn btn-sm"
           style="background:#1d4ed8;color:#fff;border-radius:10px;font-size:.8rem;font-weight:600;white-space:nowrap;">
            <i class="bi bi-eye me-1"></i>Ver horario
        </a>
    </div>
</div>
@endif

{{-- ── Panel Docente (banner único — sin doble color) ──────────── --}}
@if($docentePanel)
@php extract($docentePanel); @endphp
<div class="card border-0 mb-4" style="border-radius:20px;box-shadow:0 4px 24px rgba(0,0,0,.06);overflow:hidden;">
    {{-- Banner docente — un solo gradiente, incluye año escolar --}}
    <div style="background:linear-gradient(140deg,#0a0f2e 0%,#1e3a8a 55%,#1d4ed8 100%);padding:1.4rem 1.75rem;display:flex;align-items:center;gap:1.1rem;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-20px;right:-20px;width:110px;height:110px;background:rgba(255,255,255,.07);border-radius:50%;"></div>
        <div style="position:absolute;bottom:-25px;right:55px;width:75px;height:75px;background:rgba(255,255,255,.05);border-radius:50%;"></div>
        <div style="width:52px;height:52px;background:rgba(255,255,255,.18);border-radius:50%;border:2px solid rgba(255,255,255,.28);display:flex;align-items:center;justify-content:center;font-size:1.25rem;font-weight:900;color:#fff;flex-shrink:0;position:relative;z-index:1;">
            {{ strtoupper(substr($docente->nombres ?? 'D', 0, 1)) }}
        </div>
        <div style="position:relative;z-index:1;flex:1;">
            <div style="font-size:1rem;font-weight:800;color:#fff;">
                {{ $docente->apellidos }}, {{ $docente->nombres }}
                <span class="ms-2 badge" style="background:rgba(255,255,255,.18);color:#fff;font-size:.68rem;border-radius:12px;padding:.2rem .6rem;font-weight:600;">Docente</span>
            </div>
            @if($schoolYear)
            <div style="font-size:.79rem;color:rgba(255,255,255,.72);margin-top:3px;">
                <i class="bi bi-calendar3 me-1"></i>{{ $schoolYear->nombre }}
                &nbsp;·&nbsp;
                <i class="bi bi-mortarboard me-1"></i>{{ $docente->especialidad ?? $docente->area ?? 'Área no definida' }}
            </div>
            @endif
        </div>
        <div class="d-none d-md-flex gap-2" style="position:relative;z-index:1;">
            <a href="{{ route('admin.horarios.mi-horario') }}"
               style="background:rgba(255,255,255,.14);color:#fff;border:1px solid rgba(255,255,255,.25);border-radius:9px;padding:.35rem .85rem;font-size:.78rem;font-weight:600;text-decoration:none;">
                <i class="bi bi-calendar-week me-1"></i>Mi Horario
            </a>
            <a href="{{ route('admin.perfiles.miPerfil') }}"
               style="background:rgba(255,255,255,.14);color:#fff;border:1px solid rgba(255,255,255,.25);border-radius:9px;padding:.35rem .85rem;font-size:.78rem;font-weight:600;text-decoration:none;">
                <i class="bi bi-person-circle me-1"></i>Mi Perfil
            </a>
        </div>
    </div>
    <div class="card-body">

        {{-- Stats docente --}}
        <div class="row g-3 mb-3">
            <div class="col-sm-4">
                <div class="p-3 rounded-3 d-flex align-items-center gap-3" style="background:#dbeafe;">
                    <div style="width:40px;height:40px;background:#1d4ed8;border-radius:10px;display:flex;align-items:center;justify-content:center;"><i class="bi bi-journal-check text-white"></i></div>
                    <div>
                        <div style="font-size:1.4rem;font-weight:800;color:#1e3a8a;">{{ $asignacionesDocente->count() }}</div>
                        <div style="font-size:.77rem;color:#1e3a8a;">Mis asignaciones</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="p-3 rounded-3 d-flex align-items-center gap-3" style="background:{{ $notasPendientes > 0 ? '#fee2e2' : '#dcfce7' }};">
                    <div style="width:40px;height:40px;background:{{ $notasPendientes > 0 ? '#dc2626' : '#16a34a' }};border-radius:10px;display:flex;align-items:center;justify-content:center;"><i class="bi bi-exclamation-circle text-white"></i></div>
                    <div>
                        <div style="font-size:1.4rem;font-weight:800;color:{{ $notasPendientes > 0 ? '#dc2626' : '#16a34a' }};">{{ $notasPendientes }}</div>
                        <div style="font-size:.77rem;color:{{ $notasPendientes > 0 ? '#dc2626' : '#16a34a' }};">Notas sin publicar</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="p-3 rounded-3 d-flex align-items-center gap-3" style="background:{{ $periodosCerrando->count() > 0 ? '#fffbeb' : '#f0fdf4' }};">
                    <div style="width:40px;height:40px;background:{{ $periodosCerrando->count() > 0 ? '#f59e0b' : '#6b7280' }};border-radius:10px;display:flex;align-items:center;justify-content:center;"><i class="bi bi-calendar-x text-white"></i></div>
                    <div>
                        <div style="font-size:1.4rem;font-weight:800;color:{{ $periodosCerrando->count() > 0 ? '#d97706' : '#6b7280' }};">{{ $periodosCerrando->count() }}</div>
                        <div style="font-size:.77rem;color:{{ $periodosCerrando->count() > 0 ? '#d97706' : '#6b7280' }};">Período(s) cerrando pronto</div>
                    </div>
                </div>
            </div>
        </div>

        @if($periodosCerrando->count() > 0)
        <div class="alert alert-warning py-2 px-3 mb-3" style="border-radius:8px;font-size:.82rem;">
            <i class="bi bi-clock-history me-1"></i>
            <strong>Cierre próximo:</strong>
            @foreach($periodosCerrando as $p)
                {{ $p->nombre }} — vence el {{ \Carbon\Carbon::parse($p->fecha_fin)->format('d/m/Y') }}@if(!$loop->last), @endif
            @endforeach
        </div>
        @endif

        {{-- Mis asignaciones --}}
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
                <thead>
                    <tr style="background:#f8faff;">
                        <th class="ps-2">Asignatura</th>
                        <th>Grupo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($asignacionesDocente as $a)
                <tr>
                    <td class="ps-2 fw-semibold">{{ $a->asignatura?->nombre }}</td>
                    <td>{{ $a->grupo?->nombre_completo }}</td>
                    <td>
                        <a href="{{ route('admin.asistencia.registrar', $a->id) }}" class="btn btn-xs btn-outline-teal me-1" style="font-size:.72rem;padding:.18rem .5rem;border-radius:5px;border-color:#0f766e;color:#0f766e;">
                            <i class="bi bi-calendar-check"></i> Asistencia
                        </a>
                        <a href="{{ route('admin.calificaciones.planilla-academica', ['asignacion_id'=>$a->id]) }}" class="btn btn-xs btn-outline-primary" style="font-size:.72rem;padding:.18rem .5rem;border-radius:5px;">
                            <i class="bi bi-journal-check"></i> Notas
                        </a>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mi horario semanal --}}
        @if($horarioDocente->isNotEmpty())
        @php
            $dias = ['lunes'=>'Lun','martes'=>'Mar','miercoles'=>'Mié','jueves'=>'Jue','viernes'=>'Vie'];
            $gridD = [];
            foreach ($horarioDocente as $hd) { $gridD[$hd->franja_id][$hd->dia] = $hd; }
        @endphp
        <hr style="border-color:#f0f4f8;margin:1rem 0;">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div style="font-weight:700;font-size:.82rem;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;">
                <i class="bi bi-calendar-week me-1"></i>Mi Horario Semanal
            </div>
            <a href="{{ route('admin.horarios.index') }}" style="font-size:.76rem;color:#1d4ed8;text-decoration:none;">Ver completo</a>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:.76rem;border-collapse:separate;border-spacing:0 2px;">
                <thead>
                    <tr>
                        <th style="background:#f8faff;font-size:.7rem;color:#6b7280;padding:.3rem .5rem;border-radius:6px 0 0 6px;">Franja</th>
                        @foreach($dias as $dKey => $dLabel)
                        <th class="text-center" style="background:#f8faff;font-size:.7rem;color:#6b7280;padding:.3rem .4rem;">{{ $dLabel }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @foreach($franjasHorario as $franja)
                @if(!$franja->es_recreo)
                <tr>
                    <td style="font-size:.7rem;color:#64748b;white-space:nowrap;padding:.25rem .5rem;">
                        {{ $franja->hora_inicio }} – {{ $franja->hora_fin }}
                    </td>
                    @foreach($dias as $dKey => $dLabel)
                    @php $cel = $gridD[$franja->id][$dKey] ?? null; @endphp
                    <td class="text-center" style="padding:.2rem .3rem;">
                        @if($cel)
                        <div style="background:#dbeafe;border-radius:6px;padding:.2rem .35rem;font-size:.68rem;line-height:1.3;">
                            <div style="font-weight:700;color:#1e3a8a;">{{ $cel->asignacion?->asignatura?->nombre }}</div>
                            <div style="color:#1d4ed8;">{{ $cel->asignacion?->grupo?->nombre_corto }}</div>
                        </div>
                        @else
                        <span style="color:#e2e8f0;">—</span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endif
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

    </div>
</div>
@elseif($isDocente)
{{-- Docente sin perfil o sin año escolar activo --}}
<div class="alert d-flex align-items-center gap-3 mb-4" style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:16px;padding:1.1rem 1.4rem;">
    <div style="width:42px;height:42px;background:#1d4ed8;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="bi bi-person-circle" style="color:#fff;font-size:1.1rem;"></i>
    </div>
    <div>
        <div style="font-weight:700;font-size:.92rem;color:#1e3a8a;">Completa tu perfil docente</div>
        <div style="font-size:.81rem;color:#1d4ed8;margin-top:2px;">
            Para ver tus asignaciones y horario, primero configura tu perfil con tus materias y grupos.
        </div>
        <a href="{{ route('portal.docente.setup') }}" class="btn btn-sm mt-2"
           style="background:#1d4ed8;color:#fff;border-radius:8px;font-size:.79rem;font-weight:600;">
            <i class="bi bi-pencil-square me-1"></i>Configurar mi perfil
        </a>
    </div>
</div>
@endif

{{-- Acciones rápidas --}}
<div class="card border-0 mb-4" style="border-radius:20px;box-shadow:0 4px 24px rgba(0,0,0,.06);">
    <div class="card-header border-bottom d-flex align-items-center gap-2 py-3" style="background:#fff;border-radius:20px 20px 0 0;border-color:#f0f4f8;">
        <div style="width:30px;height:30px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:8px;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-lightning-fill" style="color:#fff;font-size:.85rem;"></i>
        </div>
        <strong style="font-size:.95rem;">Acciones Rápidas</strong>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @if($docentePanel)
            {{-- Acciones para Docente --}}
            <div class="col-6 col-md-3">
                <a href="{{ route('admin.asistencia.index') }}" class="quick-action">
                    <i class="bi bi-calendar-check-fill"></i>
                    <span>Tomar Asistencia</span>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('admin.calificaciones.index') }}" class="quick-action">
                    <i class="bi bi-journal-check"></i>
                    <span>Calificaciones</span>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('admin.planificacion.index') }}" class="quick-action">
                    <i class="bi bi-journal-text"></i>
                    <span>Planificaciones</span>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('admin.horarios.mi-horario') }}" class="quick-action">
                    <i class="bi bi-calendar-week-fill"></i>
                    <span>Mi Horario</span>
                </a>
            </div>
            @else
            {{-- Acciones para Admin / Director / Coordinador --}}
            <div class="col-6 col-md-2">
                <a href="{{ route('admin.estudiantes.create') }}" class="quick-action">
                    <i class="bi bi-person-plus-fill"></i>
                    <span>Nuevo Estudiante</span>
                </a>
            </div>
            <div class="col-6 col-md-2">
                <a href="{{ route('admin.asistencia.index') }}" class="quick-action">
                    <i class="bi bi-calendar-check-fill"></i>
                    <span>Asistencia</span>
                </a>
            </div>
            <div class="col-6 col-md-2">
                <a href="{{ route('admin.calificaciones.index') }}" class="quick-action">
                    <i class="bi bi-journal-check"></i>
                    <span>Calificaciones</span>
                </a>
            </div>
            <div class="col-6 col-md-2">
                <a href="{{ route('admin.boletines.index') }}" class="quick-action">
                    <i class="bi bi-file-earmark-text-fill"></i>
                    <span>Boletines</span>
                </a>
            </div>
            <div class="col-6 col-md-2">
                <a href="{{ route('admin.planificacion.index') }}" class="quick-action">
                    <i class="bi bi-journal-text"></i>
                    <span>Planificaciones</span>
                </a>
            </div>
            <div class="col-6 col-md-2">
                <a href="{{ route('admin.observaciones.index') }}" class="quick-action">
                    <i class="bi bi-chat-square-text"></i>
                    <span>Observaciones</span>
                </a>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ══ CENTRO DE IMPORTACIÓN DE PLANILLAS (solo admin) ═══════════ --}}
@unless($isDocente)
<div class="card border-0 mb-4" style="border-radius:20px;box-shadow:0 4px 24px rgba(0,0,0,.06);">
    <div class="card-header border-bottom d-flex align-items-center justify-content-between py-3 px-4"
         style="background:#fff;border-radius:20px 20px 0 0;border-color:#f0f4f8;">
        <div class="d-flex align-items-center gap-2">
            <div style="width:32px;height:32px;background:linear-gradient(135deg,#0891b2,#0e7490);border-radius:9px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-file-earmark-arrow-up-fill" style="color:#fff;font-size:.88rem;"></i>
            </div>
            <div>
                <strong style="font-size:.95rem;">Centro de Importación de Planillas</strong>
                <div style="font-size:.72rem;color:#64748b;margin-top:1px;">Descarga la plantilla, llénala y vuelve a cargarla al sistema</div>
            </div>
        </div>
        {{-- Tabs --}}
        <div class="d-none d-md-flex gap-1" id="importTabBtns">
            <button onclick="switchImportTab('estudiantes', this)" class="import-tab-btn active">Estudiantes</button>
            <button onclick="switchImportTab('docentes', this)"    class="import-tab-btn">Docentes</button>
            <button onclick="switchImportTab('asistencia', this)"  class="import-tab-btn">Asistencia</button>
            <button onclick="switchImportTab('notas', this)"       class="import-tab-btn">Notas</button>
        </div>
    </div>

    {{-- Mobile tab selector --}}
    <div class="d-md-none px-4 pt-3">
        <select class="form-select form-select-sm" style="border-radius:8px;" onchange="switchImportTab(this.value, null)">
            <option value="estudiantes">Estudiantes</option>
            <option value="docentes">Docentes</option>
            <option value="asistencia">Asistencia</option>
            <option value="notas">Notas</option>
        </select>
    </div>

    <div class="card-body px-4 pb-4">

        {{-- ── Tab: Estudiantes ──────────────────────────────────────── --}}
        <div class="import-tab-pane active" id="importTab-estudiantes">
            <div class="row g-3 align-items-stretch">
                <div class="col-md-4">
                    <div class="import-step-card" style="--sc:#0d6efd;">
                        <div class="import-step-num">1</div>
                        <div class="import-step-icon"><i class="bi bi-download"></i></div>
                        <div class="import-step-title">Descargar Plantilla</div>
                        <div class="import-step-desc">Obtén la plantilla oficial con el formato correcto para estudiantes.</div>
                        <div class="d-flex gap-2 mt-auto pt-3">
                            <a href="{{ route('admin.estudiantes.plantilla.descargar', ['formato'=>'xlsx']) }}"
                               class="btn btn-sm flex-fill" style="background:#0d6efd;color:#fff;border-radius:8px;font-size:.78rem;font-weight:600;">
                                <i class="bi bi-file-earmark-excel me-1"></i>Excel
                            </a>
                            <a href="{{ route('admin.estudiantes.plantilla.descargar', ['formato'=>'csv']) }}"
                               class="btn btn-sm flex-fill btn-outline-secondary" style="border-radius:8px;font-size:.78rem;font-weight:600;">
                                <i class="bi bi-filetype-csv me-1"></i>CSV
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="import-step-card" style="--sc:#f59e0b;">
                        <div class="import-step-num">2</div>
                        <div class="import-step-icon"><i class="bi bi-pencil-square"></i></div>
                        <div class="import-step-title">Llenar los datos</div>
                        <div class="import-step-desc">Solo <strong>Nombres</strong> y <strong>Apellidos</strong> son obligatorios. Los demás campos son opcionales.</div>
                        <div class="import-fields mt-auto pt-3">
                            <span class="field-tag required">Nombres *</span>
                            <span class="field-tag required">Apellidos *</span>
                            <span class="field-tag">F. Nacimiento</span>
                            <span class="field-tag">Sexo</span>
                            <span class="field-tag">Cédula</span>
                            <span class="field-tag">Teléfono</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="import-step-card" style="--sc:#16a34a;">
                        <div class="import-step-num">3</div>
                        <div class="import-step-icon"><i class="bi bi-cloud-upload"></i></div>
                        <div class="import-step-title">Subir al sistema</div>
                        <div class="import-step-desc">Ve a la página de importación, carga tu archivo y revisa el reporte de errores.</div>
                        <a href="{{ route('admin.estudiantes.import') }}"
                           class="btn btn-sm w-100 mt-auto" style="background:#16a34a;color:#fff;border-radius:8px;font-size:.82rem;font-weight:600;margin-top:auto;">
                            <i class="bi bi-cloud-upload me-1"></i>Ir a importar estudiantes
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Tab: Docentes ──────────────────────────────────────────── --}}
        <div class="import-tab-pane" id="importTab-docentes">
            <div class="row g-3 align-items-stretch">
                <div class="col-md-4">
                    <div class="import-step-card" style="--sc:#198754;">
                        <div class="import-step-num">1</div>
                        <div class="import-step-icon"><i class="bi bi-download"></i></div>
                        <div class="import-step-title">Descargar Plantilla</div>
                        <div class="import-step-desc">Plantilla para registrar múltiples docentes de forma masiva.</div>
                        <div class="d-flex gap-2 mt-auto pt-3">
                            <a href="{{ route('admin.docentes.import') }}"
                               class="btn btn-sm flex-fill" style="background:#198754;color:#fff;border-radius:8px;font-size:.78rem;font-weight:600;">
                                <i class="bi bi-file-earmark-excel me-1"></i>Ir a plantilla
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="import-step-card" style="--sc:#f59e0b;">
                        <div class="import-step-num">2</div>
                        <div class="import-step-icon"><i class="bi bi-pencil-square"></i></div>
                        <div class="import-step-title">Llenar los datos</div>
                        <div class="import-step-desc">Solo <strong>Nombres</strong> y <strong>Apellidos</strong> son obligatorios. Los demás campos son opcionales.</div>
                        <div class="import-fields mt-auto pt-3">
                            <span class="field-tag required">Nombres *</span>
                            <span class="field-tag required">Apellidos *</span>
                            <span class="field-tag">Cédula</span>
                            <span class="field-tag">Área</span>
                            <span class="field-tag">Email</span>
                            <span class="field-tag">Especialidad</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="import-step-card" style="--sc:#16a34a;">
                        <div class="import-step-num">3</div>
                        <div class="import-step-icon"><i class="bi bi-cloud-upload"></i></div>
                        <div class="import-step-title">Subir al sistema</div>
                        <div class="import-step-desc">Carga el archivo y el sistema registrará los docentes automáticamente.</div>
                        <a href="{{ route('admin.docentes.import') }}"
                           class="btn btn-sm w-100 mt-auto" style="background:#16a34a;color:#fff;border-radius:8px;font-size:.82rem;font-weight:600;margin-top:auto;">
                            <i class="bi bi-cloud-upload me-1"></i>Ir a importar docentes
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Tab: Asistencia ────────────────────────────────────────── --}}
        <div class="import-tab-pane" id="importTab-asistencia">
            <div class="row g-3 align-items-stretch">
                <div class="col-md-4">
                    <div class="import-step-card" style="--sc:#e83e8c;">
                        <div class="import-step-num">1</div>
                        <div class="import-step-icon"><i class="bi bi-download"></i></div>
                        <div class="import-step-title">Descargar Plantilla</div>
                        <div class="import-step-desc">La plantilla se genera con los estudiantes del grupo ya pre-cargados.</div>
                        <a href="{{ route('admin.asistencia.import') }}"
                           class="btn btn-sm w-100 mt-auto pt-3" style="background:#e83e8c;color:#fff;border-radius:8px;font-size:.78rem;font-weight:600;">
                            <i class="bi bi-file-earmark-excel me-1"></i>Seleccionar grupo y descargar
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="import-step-card" style="--sc:#f59e0b;">
                        <div class="import-step-num">2</div>
                        <div class="import-step-icon"><i class="bi bi-pencil-square"></i></div>
                        <div class="import-step-title">Registrar estados</div>
                        <div class="import-step-desc">Marca el estado de asistencia para cada estudiante en la fecha indicada.</div>
                        <div class="import-fields mt-auto pt-3">
                            <span class="field-tag required">Fecha *</span>
                            <span class="field-tag required" style="background:#dcfce7;color:#15803d;">presente</span>
                            <span class="field-tag required" style="background:#fee2e2;color:#991b1b;">ausente</span>
                            <span class="field-tag" style="background:#fef3c7;color:#92400e;">tardanza</span>
                            <span class="field-tag" style="background:#dbeafe;color:#1d4ed8;">excusa</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="import-step-card" style="--sc:#16a34a;">
                        <div class="import-step-num">3</div>
                        <div class="import-step-icon"><i class="bi bi-cloud-upload"></i></div>
                        <div class="import-step-title">Subir al sistema</div>
                        <div class="import-step-desc">Selecciona la asignación, sube el archivo y confirma el registro masivo.</div>
                        <a href="{{ route('admin.asistencia.import') }}"
                           class="btn btn-sm w-100 mt-auto" style="background:#16a34a;color:#fff;border-radius:8px;font-size:.82rem;font-weight:600;">
                            <i class="bi bi-cloud-upload me-1"></i>Ir a importar asistencia
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Tab: Notas ─────────────────────────────────────────────── --}}
        <div class="import-tab-pane" id="importTab-notas">
            <div class="row g-3 align-items-stretch">
                <div class="col-md-4">
                    <div class="import-step-card" style="--sc:#6f42c1;">
                        <div class="import-step-num">1</div>
                        <div class="import-step-icon"><i class="bi bi-download"></i></div>
                        <div class="import-step-title">Descargar Plantilla</div>
                        <div class="import-step-desc">La plantilla varía según el área: Académica (competencias P1–P4) o Técnica (RA ponderados).</div>
                        <a href="{{ route('admin.calificaciones.import') }}"
                           class="btn btn-sm w-100 mt-auto pt-3" style="background:#6f42c1;color:#fff;border-radius:8px;font-size:.78rem;font-weight:600;">
                            <i class="bi bi-file-earmark-excel me-1"></i>Seleccionar área y descargar
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="import-step-card" style="--sc:#f59e0b;">
                        <div class="import-step-num">2</div>
                        <div class="import-step-icon"><i class="bi bi-pencil-square"></i></div>
                        <div class="import-step-title">Ingresar las notas</div>
                        <div class="import-step-desc">Puedes ingresar notas directamente en el sistema o preparar el archivo con las calificaciones.</div>
                        <div class="import-fields mt-auto pt-3">
                            <span class="field-tag required">N° Matrícula *</span>
                            <span class="field-tag required">Nota Final *</span>
                            <span class="field-tag">Período</span>
                            <span class="field-tag">Observaciones</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="import-step-card" style="--sc:#16a34a;">
                        <div class="import-step-num">3</div>
                        <div class="import-step-icon"><i class="bi bi-cloud-upload"></i></div>
                        <div class="import-step-title">Subir al sistema</div>
                        <div class="import-step-desc">Selecciona asignación y período, sube tu archivo y revisa los resultados.</div>
                        <a href="{{ route('admin.calificaciones.import') }}"
                           class="btn btn-sm w-100 mt-auto" style="background:#16a34a;color:#fff;border-radius:8px;font-size:.82rem;font-weight:600;">
                            <i class="bi bi-cloud-upload me-1"></i>Ir a importar notas
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Nota informativa --}}
        <div class="mt-3 px-1" style="font-size:.76rem;color:#64748b;display:flex;align-items:center;gap:.5rem;">
            <i class="bi bi-info-circle-fill" style="color:#1d4ed8;flex-shrink:0;"></i>
            Todos los archivos importados generan un reporte de errores por fila. Los registros válidos se guardan aunque haya errores en otras filas.
        </div>
    </div>
</div>
@endunless {{-- /isDocente --}}

{{-- Módulos disponibles (solo admin) --}}
@unless($isDocente)
<div class="card border-0" style="border-radius:20px;box-shadow:0 4px 24px rgba(0,0,0,.06);">
    <div class="card-header border-bottom d-flex align-items-center gap-2 py-3" style="background:#fff;border-radius:20px 20px 0 0;border-color:#f0f4f8;">
        <div style="width:30px;height:30px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-radius:8px;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-grid-fill" style="color:#fff;font-size:.85rem;"></i>
        </div>
        <strong style="font-size:.95rem;">Módulos del Sistema</strong>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @php
            $modulos = [
                ['icon'=>'bi-people-fill',       'color'=>'#0d6efd','title'=>'Estudiantes',   'desc'=>'Registro y gestión de alumnos',       'href'=>route('admin.estudiantes.index')],
                ['icon'=>'bi-person-badge-fill',  'color'=>'#198754','title'=>'Docentes',      'desc'=>'Registro de profesores y asignaciones','href'=>route('admin.docentes.index')],
                ['icon'=>'bi-grid-3x3-gap',       'color'=>'#fd7e14','title'=>'Grupos',        'desc'=>'Cursos y secciones por año escolar',   'href'=>route('admin.grupos.index')],
                ['icon'=>'bi-card-list',          'color'=>'#20c997','title'=>'Matrículas',    'desc'=>'Inscripción de estudiantes',           'href'=>route('admin.matriculas.index')],
                ['icon'=>'bi-journal-check',      'color'=>'#6f42c1','title'=>'Calificaciones','desc'=>'Registro de notas por período',        'href'=>route('admin.calificaciones.index')],
                ['icon'=>'bi-calendar-check-fill','color'=>'#e83e8c','title'=>'Asistencia',    'desc'=>'Control diario por materia',           'href'=>route('admin.asistencia.index')],
                ['icon'=>'bi-diagram-3',          'color'=>'#17a2b8','title'=>'Asignaciones',  'desc'=>'Docentes asignados a grupos/materias', 'href'=>route('admin.asignaciones.index')],
                ['icon'=>'bi-file-earmark-text',  'color'=>'#dc3545','title'=>'Boletines',     'desc'=>'Generación e impresión de boletines',  'href'=>route('admin.boletines.index')],
                ['icon'=>'bi-mortarboard',        'color'=>'#6c757d','title'=>'Año Escolar',   'desc'=>'Gestión de años y períodos',           'href'=>route('admin.school-years.index')],
                ['icon'=>'bi-calendar-week-fill', 'color'=>'#0369a1','title'=>'Horarios',      'desc'=>'Generación automática de horarios',    'href'=>route('admin.horarios.index')],
            ];
            @endphp
            @foreach($modulos as $m)
            <div class="col-sm-6 col-lg-4">
                <a href="{{ $m['href'] }}" class="modulo-card" style="--mc:{{ $m['color'] }}">
                    <div class="modulo-icon"><i class="bi {{ $m['icon'] }}"></i></div>
                    <div>
                        <div class="modulo-title">{{ $m['title'] }}</div>
                        <div class="modulo-desc">{{ $m['desc'] }}</div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</div>
{{-- ── Alertas Académicas Activas ──────────────────────────────────────── --}}
@unless($isDocente)
@if(!empty($alertasAcad) && $alertasAcad->isNotEmpty())
<div class="card border-0 mb-4" style="border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.06);overflow:hidden;">
    <div class="card-header border-0 py-3 px-4 d-flex align-items-center gap-2"
         style="background:linear-gradient(135deg,#92400e,#d97706);color:#fff;">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:1rem;"></i>
        <span style="font-weight:700;">Alertas Académicas Activas</span>
        <span style="background:rgba(255,255,255,.2);border-radius:99px;padding:.15rem .6rem;font-size:.72rem;font-weight:700;">
            {{ $alertasAcad->count() }}
        </span>
        <a href="{{ route('admin.alertas.index') }}" class="ms-auto"
           style="font-size:.75rem;color:rgba(255,255,255,.9);text-decoration:none;">
            Ver todas <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    <div class="card-body p-0">
        @foreach($alertasAcad as $alerta)
        <div class="d-flex align-items-start gap-3 px-4 py-2 border-bottom"
             style="background:{{ $loop->even ? '#fffbeb' : '#fff' }};">
            <i class="bi bi-{{ $alerta->tipo === 'asistencia' ? 'calendar-x' : 'graph-down-arrow' }}"
               style="color:#d97706;font-size:1rem;margin-top:2px;flex-shrink:0;"></i>
            <div style="flex:1;min-width:0;">
                <div style="font-size:.83rem;font-weight:700;color:#1e293b;">{{ $alerta->titulo }}</div>
                <div style="font-size:.76rem;color:#6b7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $alerta->mensaje }}</div>
            </div>
            <div style="font-size:.72rem;color:#9ca3af;white-space:nowrap;flex-shrink:0;">
                {{ $alerta->created_at->diffForHumans() }}
            </div>
            <form method="POST" action="{{ route('admin.alertas.leer', $alerta) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-sm py-0 px-1" style="font-size:.75rem;color:#9ca3af;" title="Marcar leída">
                    <i class="bi bi-check-lg"></i>
                </button>
            </form>
        </div>
        @endforeach
    </div>
</div>
@endif
@endunless

{{-- ── Transporte ───────────────────────────────────────────────────────── --}}
@unless($isDocente)
@if(!empty($transporteStats) && $transporteStats['total_rutas'] > 0)
@php
    $tPct = $transporteStats['total_cap'] > 0
        ? round(($transporteStats['total_pasaj'] / $transporteStats['total_cap']) * 100)
        : 0;
    $tColor = $tPct >= 90 ? '#dc2626' : ($tPct >= 70 ? '#d97706' : '#059669');
    $tBar   = $tPct >= 90 ? '#dc2626' : ($tPct >= 70 ? '#f59e0b' : '#10b981');
@endphp
<div class="card border-0 mb-4" style="border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.06);overflow:hidden;">
    <div class="card-body py-3 px-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:#eff6ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-bus-front-fill" style="color:#1d4ed8;font-size:1.2rem;"></i>
                </div>
                <div>
                    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;">Transporte Escolar</div>
                    <div style="font-size:.92rem;font-weight:800;color:#1e293b;">
                        {{ $transporteStats['total_rutas'] }} ruta{{ $transporteStats['total_rutas'] != 1 ? 's' : '' }} activa{{ $transporteStats['total_rutas'] != 1 ? 's' : '' }}
                        <span style="font-size:.78rem;color:#64748b;font-weight:500;">—
                            {{ $transporteStats['total_pasaj'] }} / {{ $transporteStats['total_cap'] }} pasajeros ({{ $tPct }}%)
                        </span>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                @if($transporteStats['rutas_llenas'] > 0)
                <span style="background:#fef3c7;color:#92400e;border-radius:99px;padding:.25rem .75rem;font-size:.75rem;font-weight:700;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    {{ $transporteStats['rutas_llenas'] }} ruta{{ $transporteStats['rutas_llenas'] != 1 ? 's' : '' }} al {{ $tPct >= 90 ? '90' : '80' }}%+
                </span>
                @endif
                <div style="min-width:140px;">
                    <div class="d-flex justify-content-between" style="font-size:.7rem;color:#6b7280;margin-bottom:3px;">
                        <span>Ocupación global</span>
                        <span style="color:{{ $tColor }};font-weight:700;">{{ $tPct }}%</span>
                    </div>
                    <div style="background:#e5e7eb;border-radius:99px;height:6px;overflow:hidden;">
                        <div style="width:{{ min($tPct,100) }}%;background:{{ $tBar }};height:100%;border-radius:99px;transition:width .3s;"></div>
                    </div>
                </div>
                <a href="{{ route('admin.transporte.index') }}"
                   style="font-size:.78rem;color:#1d4ed8;text-decoration:none;font-weight:600;white-space:nowrap;">
                    Ver rutas <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>
@endif
@endunless

{{-- ── Agenda próximos 7 días + Pre-matrículas ─────────────────────────── --}}
@unless($isDocente)
@if(($agendaProxima && $agendaProxima->isNotEmpty()) || $preMatriculasPendientes > 0)
<div class="row g-3 mb-4">

    {{-- Agenda --}}
    @if($agendaProxima && $agendaProxima->isNotEmpty())
    <div class="{{ $preMatriculasPendientes > 0 ? 'col-md-8' : 'col-12' }}">
        <div class="card border-0 h-100" style="border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.06);overflow:hidden;">
            <div class="card-header border-0 py-3 px-4 d-flex align-items-center gap-2"
                 style="background:linear-gradient(135deg,#0f172a,#1e3a8a);color:#fff;">
                <i class="bi bi-calendar2-week-fill" style="font-size:1rem;"></i>
                <span style="font-weight:700;font-size:.92rem;">Agenda — Próximos 7 días</span>
                <span style="background:rgba(255,255,255,.2);border-radius:99px;padding:.15rem .6rem;font-size:.72rem;font-weight:700;">
                    {{ $agendaProxima->count() }} evento{{ $agendaProxima->count() != 1 ? 's' : '' }}
                </span>
            </div>
            <div class="card-body p-0">
                @foreach($agendaProxima as $item)
                <a href="{{ $item['route'] }}" class="d-flex align-items-center gap-3 px-4 py-2 border-bottom text-decoration-none"
                   style="background:{{ $loop->even ? '#f8fafc' : '#fff' }};transition:background .15s;"
                   onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='{{ $loop->even ? '#f8fafc' : '#fff' }}'">
                    <div style="width:36px;height:36px;border-radius:10px;flex-shrink:0;display:flex;flex-direction:column;align-items:center;justify-content:center;background:{{ $item['bg'] }};">
                        <i class="bi {{ $item['icon'] }}" style="color:{{ $item['color'] }};font-size:.85rem;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.83rem;font-weight:700;color:#1e293b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $item['titulo'] }}
                        </div>
                        <div style="font-size:.73rem;color:#64748b;">
                            <span style="background:{{ $item['bg'] }};color:{{ $item['color'] }};border-radius:4px;padding:.05rem .35rem;font-weight:700;">{{ $item['sub'] }}</span>
                            @if($item['lugar']) <span class="ms-1">· {{ $item['lugar'] }}</span> @endif
                        </div>
                    </div>
                    <div style="font-size:.75rem;color:#94a3b8;white-space:nowrap;flex-shrink:0;text-align:right;">
                        @php
                            $diff = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($item['fecha'])->startOfDay(), false);
                        @endphp
                        @if($diff === 0)
                            <span style="color:#dc2626;font-weight:700;">Hoy</span>
                        @elseif($diff === 1)
                            <span style="color:#d97706;font-weight:700;">Mañana</span>
                        @else
                            En {{ $diff }} días
                        @endif
                        <div>{{ \Carbon\Carbon::parse($item['fecha'])->format('d/m') }}</div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Pre-matrículas pendientes --}}
    @if($preMatriculasPendientes > 0)
    <div class="{{ ($agendaProxima && $agendaProxima->isNotEmpty()) ? 'col-md-4' : 'col-12' }}">
        <a href="{{ route('admin.pre-matriculas.index', ['estado' => 'pendiente']) }}"
           class="card border-0 h-100 text-decoration-none"
           style="border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.06);overflow:hidden;display:block;">
            <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-4"
                 style="background:linear-gradient(135deg,#fef9c3,#fef3c7);">
                <div style="width:56px;height:56px;border-radius:16px;background:#fbbf24;display:flex;align-items:center;justify-content:center;margin-bottom:.75rem;">
                    <i class="bi bi-person-lines-fill" style="color:#fff;font-size:1.5rem;"></i>
                </div>
                <div style="font-size:2.5rem;font-weight:900;color:#92400e;line-height:1;">{{ $preMatriculasPendientes }}</div>
                <div style="font-size:.8rem;font-weight:700;color:#a16207;text-transform:uppercase;letter-spacing:.05em;margin-top:.25rem;">
                    Pre-matrículas pendientes
                </div>
                <div style="margin-top:.75rem;font-size:.75rem;color:#b45309;background:rgba(251,191,36,.3);border-radius:99px;padding:.25rem .75rem;">
                    Revisar y resolver <i class="bi bi-arrow-right ms-1"></i>
                </div>
            </div>
        </a>
    </div>
    @endif

</div>
@endif
@endunless

{{-- ── Disciplina & Salud ───────────────────────────────────────────────── --}}
@unless($isDocente)
@if(($recentDisciplina && $recentDisciplina->isNotEmpty()) || ($recentSalud && $recentSalud->isNotEmpty()))
<div class="row g-3 mb-4">

    {{-- Widget: Faltas Disciplinarias Recientes --}}
    @if($recentDisciplina && $recentDisciplina->isNotEmpty())
    <div class="{{ ($recentSalud && $recentSalud->isNotEmpty()) ? 'col-md-6' : 'col-12' }}">
        <div class="card border-0 h-100" style="border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.06);overflow:hidden;">
            <div class="card-header border-0 py-3 px-4 d-flex align-items-center gap-2"
                 style="background:linear-gradient(135deg,#6d28d9,#8b5cf6);color:#fff;">
                <i class="bi bi-shield-exclamation" style="font-size:1rem;"></i>
                <span style="font-weight:700;font-size:.92rem;">Disciplina — Recientes</span>
                @if($pendientesDisciplina > 0)
                <span style="background:rgba(255,255,255,.22);border-radius:99px;padding:.15rem .6rem;font-size:.72rem;font-weight:700;">
                    {{ $pendientesDisciplina }} pendiente{{ $pendientesDisciplina != 1 ? 's' : '' }}
                </span>
                @endif
                <a href="{{ route('admin.disciplina.index') }}" class="ms-auto"
                   style="font-size:.75rem;color:rgba(255,255,255,.9);text-decoration:none;">
                    Ver todo <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-body p-0">
                @foreach($recentDisciplina as $falta)
                @php $ti = $falta->tipo_info; @endphp
                <div class="d-flex align-items-center gap-3 px-4 py-2 border-bottom"
                     style="background:{{ $loop->even ? '#faf5ff' : '#fff' }};">
                    <div style="width:30px;height:30px;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:{{ $ti['bg'] }};">
                        <i class="bi {{ $ti['icon'] }}" style="color:{{ $ti['color'] }};font-size:.8rem;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.82rem;font-weight:700;color:#1e293b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $falta->estudiante?->nombre_completo ?? '—' }}
                        </div>
                        <div style="font-size:.73rem;color:#64748b;">
                            <span style="background:{{ $ti['bg'] }};color:{{ $ti['color'] }};border-radius:4px;padding:.05rem .35rem;font-weight:700;">{{ $ti['label'] }}</span>
                        </div>
                    </div>
                    <div style="font-size:.72rem;color:#9ca3af;white-space:nowrap;flex-shrink:0;">
                        {{ $falta->fecha->format('d/m') }}
                    </div>
                    <div style="flex-shrink:0;">
                        @if($falta->resuelto)
                            <span style="background:#d1fae5;color:#065f46;border-radius:99px;padding:.1rem .5rem;font-size:.68rem;font-weight:700;">Resuelto</span>
                        @else
                            <span style="background:#fee2e2;color:#991b1b;border-radius:99px;padding:.1rem .5rem;font-size:.68rem;font-weight:700;">Pendiente</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Widget: Incidentes Médicos Recientes --}}
    @if($recentSalud && $recentSalud->isNotEmpty())
    <div class="{{ ($recentDisciplina && $recentDisciplina->isNotEmpty()) ? 'col-md-6' : 'col-12' }}">
        <div class="card border-0 h-100" style="border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.06);overflow:hidden;">
            <div class="card-header border-0 py-3 px-4 d-flex align-items-center gap-2"
                 style="background:linear-gradient(135deg,#0369a1,#0ea5e9);color:#fff;">
                <i class="bi bi-heart-pulse-fill" style="font-size:1rem;"></i>
                <span style="font-weight:700;font-size:.92rem;">Salud — Recientes</span>
                @if($totalIncidentesMes > 0)
                <span style="background:rgba(255,255,255,.22);border-radius:99px;padding:.15rem .6rem;font-size:.72rem;font-weight:700;">
                    {{ $totalIncidentesMes }} este mes
                </span>
                @endif
                <a href="{{ route('admin.salud.incidentes') }}" class="ms-auto"
                   style="font-size:.75rem;color:rgba(255,255,255,.9);text-decoration:none;">
                    Ver todo <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-body p-0">
                @foreach($recentSalud as $inc)
                @php $ti = $inc->tipo_info; @endphp
                <div class="d-flex align-items-center gap-3 px-4 py-2 border-bottom"
                     style="background:{{ $loop->even ? '#f0f9ff' : '#fff' }};">
                    <div style="width:30px;height:30px;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:{{ $ti['bg'] }};">
                        <i class="bi {{ $ti['icon'] }}" style="color:{{ $ti['color'] }};font-size:.8rem;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.82rem;font-weight:700;color:#1e293b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $inc->estudiante?->nombre_completo ?? '—' }}
                        </div>
                        <div style="font-size:.73rem;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $inc->descripcion ? \Illuminate\Support\Str::limit($inc->descripcion, 40) : '—' }}
                        </div>
                    </div>
                    <div style="flex-shrink:0;text-align:right;">
                        <div style="font-size:.72rem;color:#9ca3af;white-space:nowrap;">{{ $inc->fecha->format('d/m') }}</div>
                        <span style="background:{{ $ti['bg'] }};color:{{ $ti['color'] }};border-radius:4px;padding:.05rem .35rem;font-size:.68rem;font-weight:700;">{{ $ti['label'] }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

</div>
@endif
@endunless

{{-- ── Gráficas de resumen ─────────────────────────────────────────────── --}}
@unless($isDocente)
@if(!empty($chartData))
<div class="row g-3 mb-4">
    {{-- Gráfica: Estudiantes por Grado --}}
    @if($chartData['porGrado']->isNotEmpty())
    <div class="col-md-7">
        <div class="card border-0 shadow-sm h-100" style="border-radius:16px;">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;">Distribución</div>
                        <div style="font-size:1rem;font-weight:800;color:#111827;">Estudiantes por Grado</div>
                    </div>
                    <i class="bi bi-bar-chart-fill" style="color:var(--primary);font-size:1.4rem;"></i>
                </div>
                <canvas id="chartGrados" height="160"></canvas>
            </div>
        </div>
    </div>
    @endif

    {{-- Gráfica: Asistencia del mes --}}
    @if($chartData['asistenciaMes']->isNotEmpty())
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100" style="border-radius:16px;">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;">Este mes</div>
                        <div style="font-size:1rem;font-weight:800;color:#111827;">Asistencia Global</div>
                    </div>
                    <i class="bi bi-pie-chart-fill" style="color:#10b981;font-size:1.4rem;"></i>
                </div>
                <canvas id="chartAsistencia" height="180"></canvas>
                <div class="d-flex justify-content-center gap-3 mt-2 flex-wrap" style="font-size:.75rem;">
                    <span><span style="display:inline-block;width:10px;height:10px;background:#10b981;border-radius:2px;margin-right:4px;"></span>Presente</span>
                    <span><span style="display:inline-block;width:10px;height:10px;background:#ef4444;border-radius:2px;margin-right:4px;"></span>Ausente</span>
                    <span><span style="display:inline-block;width:10px;height:10px;background:#f59e0b;border-radius:2px;margin-right:4px;"></span>Tardanza</span>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endif
@endunless

@endunless {{-- /isDocente --}}
@endsection

@push('styles')
<style>
/* ── Stat Cards ─────────────────────────────────────────────── */
.stat-card {
    background: linear-gradient(135deg, var(--c) 0%, color-mix(in srgb, var(--c) 75%, #000) 100%);
    border-radius: 20px;
    padding: 22px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 4px 24px color-mix(in srgb, var(--c) 35%, transparent);
    transition: transform .25s cubic-bezier(.34,1.56,.64,1), box-shadow .25s;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.stat-card::before {
    content: '';
    position: absolute;
    top: -20px; right: -20px;
    width: 90px; height: 90px;
    background: rgba(255,255,255,.12);
    border-radius: 50%;
}
.stat-card::after {
    content: '';
    position: absolute;
    bottom: -30px; right: 10px;
    width: 120px; height: 120px;
    background: rgba(255,255,255,.07);
    border-radius: 50%;
}
.stat-card:hover { transform: translateY(-6px); box-shadow: 0 12px 36px color-mix(in srgb, var(--c) 45%, transparent); }
.stat-icon {
    width: 54px; height: 54px;
    background: rgba(255,255,255,.22);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.55rem; flex-shrink: 0;
    position: relative; z-index: 1;
}
.stat-body { position: relative; z-index: 1; }
.stat-num  { font-size: 2.1rem; font-weight: 800; color: #fff; line-height: 1; }
.stat-label{ font-size: .82rem; color: rgba(255,255,255,.82); font-weight: 500; margin-top: 5px; letter-spacing: .02em; }

/* ── Quick Actions ──────────────────────────────────────────── */
.quick-action {
    display: flex; flex-direction: column; align-items: center; gap: 10px;
    padding: 20px 12px;
    background: #fff; border-radius: 16px;
    text-decoration: none; color: #1e293b;
    border: 1.5px solid #e2e8f0;
    transition: all .22s cubic-bezier(.34,1.56,.64,1);
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
.quick-action:hover {
    border-color: var(--primary);
    background: #eff6ff;
    color: var(--primary);
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(29,78,216,.15);
}
.quick-action i  { font-size: 1.7rem; color: var(--primary); }
.quick-action span { font-size: .82rem; font-weight: 600; }

/* ── Module Cards ───────────────────────────────────────────── */
.modulo-card {
    display: flex; align-items: center; gap: 14px;
    padding: 16px 18px;
    background: #fff; border-radius: 16px;
    text-decoration: none; color: #1e293b;
    border: 1.5px solid #e8edf5;
    transition: all .22s cubic-bezier(.34,1.56,.64,1);
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
.modulo-card:hover {
    border-color: var(--mc);
    background: #fff;
    color: #1e293b;
    transform: translateY(-4px);
    box-shadow: 0 8px 28px color-mix(in srgb, var(--mc) 20%, transparent);
}
.modulo-icon {
    width: 44px; height: 44px; flex-shrink: 0;
    background: color-mix(in srgb, var(--mc) 12%, white);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    color: var(--mc); font-size: 1.25rem;
}
.modulo-title { font-weight: 700; font-size: .9rem; }
.modulo-desc  { font-size: .77rem; color: #64748b; margin-top: 2px; }

/* ── Import Section ─────────────────────────────────────────── */
.import-tab-btn {
    padding: .35rem .85rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 20px;
    background: #fff;
    color: #374151;
    font-size: .78rem;
    font-weight: 600;
    cursor: pointer;
    transition: all .18s ease;
    white-space: nowrap;
}
.import-tab-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: #eff6ff;
}
.import-tab-btn.active {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff;
    box-shadow: 0 4px 12px rgba(29,78,216,.28);
}
.import-tab-pane { display: none; }
.import-tab-pane.active { display: block; }
.import-step-card {
    background: #fff;
    border-radius: 16px;
    border: 1.5px solid #e8edf5;
    border-top: 4px solid var(--sc);
    padding: 20px;
    display: flex;
    flex-direction: column;
    height: 100%;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
    transition: box-shadow .2s, transform .2s;
}
.import-step-card:hover {
    box-shadow: 0 8px 28px color-mix(in srgb, var(--sc) 18%, transparent);
    transform: translateY(-3px);
}
.import-step-num {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: var(--sc);
    color: #fff;
    font-size: .78rem;
    font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 12px;
    flex-shrink: 0;
}
.import-step-icon {
    font-size: 1.6rem;
    color: var(--sc);
    margin-bottom: 8px;
}
.import-step-title {
    font-weight: 700;
    font-size: .9rem;
    color: #1e293b;
    margin-bottom: 6px;
}
.import-step-desc {
    font-size: .79rem;
    color: #64748b;
    line-height: 1.5;
}
.import-fields {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}
.field-tag {
    display: inline-block;
    padding: .18rem .55rem;
    border-radius: 20px;
    font-size: .7rem;
    font-weight: 600;
    background: #f1f5f9;
    color: #475569;
}
.field-tag.required {
    background: #dbeafe;
    color: #1d4ed8;
}

/* ── Stagger fade-in for stat cards ────────────────────────── */
@keyframes cardFadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.stat-card {
    animation: cardFadeUp .45s cubic-bezier(.34,1.56,.64,1) both;
}
.stat-card:nth-child(1) { animation-delay: .05s; }
.stat-card:nth-child(2) { animation-delay: .12s; }
.stat-card:nth-child(3) { animation-delay: .19s; }
.stat-card:nth-child(4) { animation-delay: .26s; }

[data-theme="dark"] .quick-action { background: #1e293b; border-color: #334155; color: #e2e8f0; }
[data-theme="dark"] .quick-action:hover { background: #1e3a5f; border-color: var(--primary); color: #93c5fd; }
[data-theme="dark"] .quick-action i { color: #93c5fd; }
[data-theme="dark"] .modulo-card { background: #1e293b; border-color: #334155; color: #e2e8f0; }
[data-theme="dark"] .modulo-card:hover { background: #1e293b; color: #e2e8f0; }
[data-theme="dark"] .modulo-icon { background: #162032; }
[data-theme="dark"] .modulo-desc { color: #64748b; }
[data-theme="dark"] .import-tab-btn { background: #1e293b; border-color: #334155; color: #94a3b8; }
[data-theme="dark"] .import-tab-btn:hover { background: #162032; border-color: var(--primary); color: #93c5fd; }
[data-theme="dark"] .import-step-card { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .import-step-title { color: #e2e8f0; }
[data-theme="dark"] .import-step-desc { color: #94a3b8; }
[data-theme="dark"] .field-tag { background: #334155; color: #94a3b8; }
[data-theme="dark"] .field-tag.required { background: #0c1f3f; color: #93c5fd; }
</style>
@endpush

@push('scripts')
<script>
// ── Import tab switcher ────────────────────────────────────────
function switchImportTab(name, el) {
    document.querySelectorAll('.import-tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.import-tab-btn').forEach(b => b.classList.remove('active'));
    const pane = document.getElementById('importTab-' + name);
    if (pane) pane.classList.add('active');
    if (el) el.classList.add('active');
}

// ── Stats refresh ──────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const btn  = document.getElementById('btnRefreshStats');
    const icon = document.getElementById('refreshIcon');
    if (!btn) return;

    btn.addEventListener('click', function () {
        icon.style.transition = 'transform .6s linear';
        icon.style.transform  = 'rotate(360deg)';
        btn.disabled = true;

        fetch('{{ route('admin.dashboard.stats') }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            const map = {
                'stat-estudiantes': data.totalEstudiantes,
                'stat-docentes':    data.totalDocentes,
                'stat-grupos':      data.totalGrupos,
                'stat-asignaturas': data.totalAsignaturas,
                'stat-matriculas':  data.matriculasActivas,
            };
            Object.entries(map).forEach(([id, val]) => {
                const el = document.getElementById(id);
                if (el === null || val === undefined) return;
                el.style.transition = 'opacity .2s';
                el.style.opacity = '0';
                setTimeout(() => {
                    el.textContent = Number(val).toLocaleString('es-DO');
                    el.style.opacity = '1';
                }, 200);
            });
            const updEl = document.getElementById('stats-updated-at');
            if (updEl) updEl.textContent = 'Actualizado: ' + (data.updatedAt ?? '');
        })
        .catch(() => {})
        .finally(() => {
            setTimeout(() => {
                icon.style.transition = 'none';
                icon.style.transform  = '';
                btn.disabled = false;
            }, 650);
        });
    });
});

// ── Counter-up animation for stat numbers ─────────────────────
(function() {
    function animateCounter(el) {
        const target = parseInt(el.textContent.replace(/\D/g, ''), 10);
        if (!target || target < 2) return;
        const duration = 900;
        const step = target / (duration / 16);
        let current = 0;
        const timer = setInterval(() => {
            current = Math.min(current + step, target);
            el.textContent = Math.floor(current).toLocaleString('es-DO');
            if (current >= target) clearInterval(timer);
        }, 16);
    }
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.stat-num').forEach(el => animateCounter(el));
    });
})();

// ── Reloj en tiempo real (banner Zura) ─────────────────────────
(function () {
    const el = document.getElementById('zura-clock');
    if (!el) return;
    setInterval(() => {
        const now = new Date();
        el.textContent = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');
    }, 1000);
})();

// ── Realtime: auto-refresh stats cuando llega nueva_matricula ──
(function () {
    let refreshTimeout = null;

    function triggerStatsRefresh(tipo) {
        // Debounce: si llegan varias matrículas seguidas solo hace 1 fetch
        clearTimeout(refreshTimeout);
        refreshTimeout = setTimeout(() => {
            fetch('{{ route('admin.dashboard.stats') }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                const map = {
                    'stat-estudiantes': data.totalEstudiantes,
                    'stat-docentes':    data.totalDocentes,
                    'stat-grupos':      data.totalGrupos,
                    'stat-asignaturas': data.totalAsignaturas,
                    'stat-matriculas':  data.matriculasActivas,
                };
                Object.entries(map).forEach(([id, val]) => {
                    const el = document.getElementById(id);
                    if (el === null || val === undefined) return;
                    const prev = parseInt(el.textContent.replace(/\D/g,''), 10);
                    const next = Number(val);
                    if (prev === next) return;
                    el.style.transition = 'opacity .2s';
                    el.style.opacity = '0';
                    setTimeout(() => { el.textContent = next.toLocaleString('es-DO'); el.style.opacity = '1'; }, 200);
                });
                const updEl = document.getElementById('stats-updated-at');
                if (updEl) updEl.textContent = 'Actualizado: ' + (data.updatedAt ?? '');

                // Pulso verde en la tarjeta de matrículas
                if (tipo === 'nueva_matricula') {
                    const pulse = document.getElementById('stat-matriculas-pulse');
                    if (pulse) {
                        pulse.style.display = 'inline-block';
                        setTimeout(() => { pulse.style.display = 'none'; }, 5000);
                    }
                }
            })
            .catch(() => {});
        }, 800);
    }

    window.addEventListener('sge:dashboard-updated', function (e) {
        const tipo = e.detail?.tipo;
        if (tipo === 'nueva_matricula') {
            triggerStatsRefresh('nueva_matricula');
        }
    });
})();

</script>
<style>@keyframes pulse { 0%,100% { opacity:1; transform:scale(1); } 50% { opacity:.5; transform:scale(1.5); } }</style>
@endpush

@unless($isDocente)
@if(!empty($chartData))
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#94a3b8' : '#6b7280';
    const gridColor = isDark ? '#1e293b' : '#f1f5f9';

    @if($chartData['porGrado']->isNotEmpty())
    new Chart(document.getElementById('chartGrados'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($chartData['porGrado']->keys()) !!},
            datasets: [{
                label: 'Estudiantes',
                data: {!! json_encode($chartData['porGrado']->values()) !!},
                backgroundColor: [
                    '#3b82f6','#10b981','#f59e0b','#ef4444',
                    '#8b5cf6','#06b6d4','#ec4899','#84cc16',
                ],
                borderRadius: 6,
                borderSkipped: false,
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: textColor, font: { size: 11 } }, grid: { display: false } },
                y: { ticks: { color: textColor, font: { size: 11 } }, grid: { color: gridColor }, beginAtZero: true },
            },
        },
    });
    @endif

    @if($chartData['asistenciaMes']->isNotEmpty())
    @php
        $presente = $chartData['asistenciaMes']['presente'] ?? 0;
        $ausente  = $chartData['asistenciaMes']['ausente']  ?? 0;
        $tardanza = $chartData['asistenciaMes']['tardanza'] ?? 0;
    @endphp
    new Chart(document.getElementById('chartAsistencia'), {
        type: 'doughnut',
        data: {
            labels: ['Presente', 'Ausente', 'Tardanza'],
            datasets: [{
                data: [{{ $presente }}, {{ $ausente }}, {{ $tardanza }}],
                backgroundColor: ['#10b981','#ef4444','#f59e0b'],
                borderWidth: 2,
                borderColor: isDark ? '#0f172a' : '#fff',
            }],
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            const total = ctx.dataset.data.reduce((a,b) => a+b, 0);
                            const pct = total ? Math.round(ctx.parsed / total * 100) : 0;
                            return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`;
                        }
                    }
                }
            },
        },
    });
    @endif
})();
</script>
@endpush
@endif
@endunless
