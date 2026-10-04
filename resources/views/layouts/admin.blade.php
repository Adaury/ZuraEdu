<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php $__sysName = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name')); @endphp
    <title>@yield('page-title', 'Dashboard') — {{ $__sysName }}</title>

    {{-- Aplicar tema antes de renderizar para evitar FOUC --}}
    <script>(function(){var t=localStorage.getItem('sge-theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>

    {{-- Aplicar preferencia de sidebar colapsado antes de renderizar (evita FOUC) --}}
    <script>(function(){if(localStorage.getItem('sidebarCollapsed')==='1'){document.documentElement.classList.add('sidebar-collapsed');}})();</script>

    {{-- Dynamic favicon — tenant-scoped cache --}}
    @php $faviconPath = \App\Helpers\Setting::get('system_favicon'); @endphp
    @if($faviconPath)
    <link rel="icon" href="{{ asset('storage/' . $faviconPath) }}" type="image/x-icon">
    @else
    @include('partials.marca.head')
    @endif

    <!-- Progress bar de navegación -->
    <style>
        #nprogress-bar {
            position: fixed; top: 0; left: 0; right: 0;
            height: 3px; z-index: 99999;
            background: var(--primary, #2563eb);
            box-shadow: 0 0 10px rgba(37,99,235,.7);
            transform: scaleX(0); transform-origin: left;
            transition: transform .1s linear;
            pointer-events: none;
        }
    </style>

    <!-- Bootstrap 5 — local -->
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Bootstrap Icons — local -->
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">

    <!-- DataTables 2 + Scroller JS — CSS propio (sin CDN CSS para evitar conflictos Bootstrap) -->
    <link rel="stylesheet" href="{{ asset('css/admin-base.css') }}?v={{ @filemtime(public_path('css/admin-base.css')) }}">

    <!-- Inter font — sistema (sin dependencia de Google) -->
    <style>
        :root { font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
    </style>

    <!-- Tailwind CSS compilado (sin preflight, para convivir con Bootstrap) —
         antes cargaba el script CDN https://cdn.tailwindcss.com, no apto
         para producción (recompila el CSS en el navegador en cada carga de
         página). Modo oscuro por data-theme="dark", ver resources/css/admin.css. -->
    @vite('resources/css/admin.css')
    <!-- x-cloak: ocultar elementos Alpine hasta que inicialice -->
    <style>[x-cloak] { display: none !important; }</style>

    @stack('styles')

    <link rel="stylesheet" href="{{ asset('css/admin-layout.css') }}?v={{ @filemtime(public_path('css/admin-layout.css')) }}">

    {{-- PWA --}}
    <link rel="manifest" href="/pwa/manifest.json" crossorigin="use-credentials">
    <meta name="theme-color" content="{{ $currentTenant->color_primario ?? '#1d4ed8' }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $currentTenant->nombre_institucion ?? config('app.name') }}">
    <link rel="apple-touch-icon" href="/pwa/icon/192?tid={{ $currentTenant->id ?? 0 }}">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .catch(() => {});
            });
        }
    </script>
</head>
@php
$bodyRoleClass = '';
if (auth()->check()) {
    $r = auth()->user();
    if      ($r->hasRole('Director'))                                                                          $bodyRoleClass = 'role-director';
    elseif  ($r->hasAnyRole(['Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo'])) $bodyRoleClass = 'role-coordinador';
    elseif  ($r->hasRole('Secretaria Docente'))                                                                $bodyRoleClass = 'role-docente-guia';
    elseif  ($r->hasRole('Docente'))                                                                           $bodyRoleClass = 'role-docente';
    elseif  ($r->hasRole('Secretaría'))                                                                        $bodyRoleClass = 'role-secretaria';
    elseif  ($r->hasAnyRole(['Registrador Académico', 'Encargado de Registro Académico']))                      $bodyRoleClass = 'role-registro';
    elseif  ($r->hasAnyRole(['Personal Administrativo','Cajero']))                                             $bodyRoleClass = 'role-cajero';
    elseif  ($r->hasRole('Representante'))                                                                     $bodyRoleClass = 'role-representante';
    elseif  ($r->hasRole('Estudiante'))                                                                        $bodyRoleClass = 'role-estudiante';
    elseif  ($r->hasRole('Encargado de Área'))                                                                 $bodyRoleClass = 'role-encargado';
}
@endphp
<body class="{{ $bodyRoleClass }}">

    <div id="nprogress-bar"></div>

    {{-- ── Banner Modo Demo ──────────────────────────────────────────── --}}
    @if(session('demo_mode'))
    <div id="demo-banner" style="background:linear-gradient(90deg,#92400e,#b45309);color:#fff;text-align:center;padding:.55rem 1rem;font-size:.8rem;font-weight:600;position:sticky;top:0;z-index:9999;display:flex;align-items:center;justify-content:center;gap:.75rem;flex-wrap:wrap;box-shadow:0 2px 8px rgba(0,0,0,.25);">
        <span style="display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-shield-exclamation" style="font-size:.95rem;"></i>
            <strong>MODO DEMO</strong> — Estás explorando el sistema con datos de ejemplo. Los cambios críticos están bloqueados.
        </span>
        @if($errors->has('demo_mode'))
        <span style="background:rgba(0,0,0,.25);border-radius:6px;padding:.2rem .6rem;font-size:.75rem;">
            🔒 {{ $errors->first('demo_mode') }}
        </span>
        @endif
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit" style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.35);color:#fff;border-radius:6px;padding:.25rem .75rem;font-size:.73rem;font-weight:700;cursor:pointer;">
                <i class="bi bi-box-arrow-right me-1"></i>Salir del demo
            </button>
        </form>
    </div>
    @endif

    <a href="#main-content" class="skip-link">Saltar al contenido principal</a>

    <!-- ════════════════════════════════════════════════
         SIDEBAR
    ════════════════════════════════════════════════ -->
    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Menú principal">

        <!-- Logo -->
        <a href="{{ route('admin.dashboard') }}" class="sidebar-logo">
            @if(!empty($systemSettings['system_logo']))
                <img src="{{ Storage::url($systemSettings['system_logo']) }}"
                     alt="{{ $systemSettings['system_abbr'] }}"
                     style="width:38px;height:38px;border-radius:8px;object-fit:contain;background:#fff;padding:2px;">
            @else
                {{-- El colegio aún no subió su logo: se muestra la insignia de ZuraEdu en vez de las siglas «SGE» --}}
                <img src="{{ asset('brand/zuraedu-icono.svg') }}" alt="ZuraEdu" style="width:38px;height:38px;border-radius:9px;flex-shrink:0;">
            @endif
            <div class="logo-text">
                <div class="system-name">{{ $systemSettings['system_name'] ?? 'Zura' }}</div>
                <div class="system-sub">{{ $systemSettings['system_sub'] ?? 'Gestión Escolar' }}</div>
            </div>
        </a>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            @php ob_start(); @endphp   {{-- el menú se filtra al final: ver App\Support\MenuFiltro --}}

            @php
                $u = Auth::user();
                // ── Roles base ────────────────────────────────────────────
                $isAdmin        = $u->hasRole('Administrador');
                $isDir          = $u->hasRole('Director');
                $isCoord        = $u->hasAnyRole(['Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo']);
                $isDocente      = $u->hasAnyRole(['Docente','Docente Académico','Docente Técnico','Docente Guía']);
                $isSecre        = $u->hasAnyRole(['Secretaría','Secretaria Docente','Secretaria']);
                $isPersonalAdm  = $u->hasRole('Personal Administrativo');
                $isSuperAdmin   = $u->hasRole('super_admin');
                $isRegistro     = $u->hasAnyRole(['Registrador Académico', 'Encargado de Registro Académico']);
                $isCaja         = $u->hasRole('Caja / Finanzas');
                $isBiblioteca   = $u->hasRole('Biblioteca');
                $isRecepcion    = $u->hasRole('Recepción');
                // ── Flags "solo ese rol" — impide que admins caigan en sidebars focalizados
                $isSecreOnly    = $isSecre    && !$isAdmin && !$isDir;
                $isCoordOnly    = $isCoord    && !$isAdmin && !$isDir;
                // ── Permisos compuestos (compatibilidad) ──────────────────
                $canSupervisar  = $isAdmin || $isDir || $isPersonalAdm;
                $canConfig      = $isAdmin || $isSuperAdmin;
                $canAcad        = $isAdmin || $isDir || $isCoord || $isSecre || $isPersonalAdm || $isRegistro;
                $canCalif       = $isAdmin || $isDir || $isCoord || $isDocente;
                $docenteArea    = null;
                if ($isDocente) {
                    try {
                        $docenteArea = \Illuminate\Support\Facades\Cache::remember(
                            'docente_area_' . $u->id, 300,
                            fn() => \App\Models\Docente::where('user_id', $u->id)->value('area')
                        );
                    } catch (\Exception $e) {}
                }
                $showSegTecnica = $isAdmin || $isDir || $isCoord || $isSecre || $isPersonalAdm
                    || ($isDocente && in_array($docenteArea, ['tecnica','ambas']));
            @endphp

            {{-- ══ SIDEBAR REGISTRADOR ACADÉMICO ══ --}}
            @if($isRegistro)
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.registro-academico.dashboard') }}"
                       class="{{ request()->routeIs('admin.registro-academico*') ? 'active' : '' }}">
                        <i class="bi bi-journal-bookmark-fill"></i>Mi Escritorio
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Registro de Estudiantes</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.estudiantes.wizard') }}"
                       class="{{ request()->routeIs('admin.estudiantes.wizard') ? 'active' : '' }}">
                        <i class="bi bi-magic"></i>Wizard de Registro
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.estudiantes.index') }}"
                       class="{{ request()->routeIs('admin.estudiantes.index') ? 'active' : '' }}">
                        <i class="bi bi-people-fill"></i>Estudiantes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.estudiantes.import') }}"
                       class="{{ request()->routeIs('admin.estudiantes.import*') ? 'active' : '' }}">
                        <i class="bi bi-upload"></i>Importar
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pre-matriculas.index') }}"
                       class="{{ request()->routeIs('admin.pre-matriculas*') ? 'active' : '' }}">
                        <i class="bi bi-inbox"></i>Pre-matrículas
                        @php
                            try { $__preCount = \App\Models\PreMatricula::pendientes()->count(); } catch(\Exception $e){ $__preCount = 0; }
                        @endphp
                        @if($__preCount > 0)
                        <span class="badge rounded-pill text-bg-warning ms-auto" style="font-size:.6rem;padding:.18rem .45rem;">{{ $__preCount }}</span>
                        @endif
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Matrículas y Grupos</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.matriculas.index') }}"
                       class="{{ request()->routeIs('admin.matriculas*') ? 'active' : '' }}">
                        <i class="bi bi-card-list"></i>Matrículas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.inscripciones.index') }}"
                       class="{{ request()->routeIs('admin.inscripciones*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-check"></i>Inscripciones
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.grupos.index') }}"
                       class="{{ request()->routeIs('admin.grupos*') ? 'active' : '' }}">
                        <i class="bi bi-diagram-3"></i>Grupos
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Documentos y Registros</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.boletines.index') }}"
                       class="{{ request()->routeIs('admin.boletines*') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-text"></i>Boletines
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.registro.index') }}"
                       class="{{ request()->routeIs('admin.registro*') ? 'active' : '' }}">
                        <i class="bi bi-table"></i>Registro MINERD
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.competencias.index') }}"
                       class="{{ request()->routeIs('admin.competencias*') ? 'active' : '' }}">
                        <i class="bi bi-diagram-3"></i>Competencias / IL
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.calificaciones.index') }}"
                       class="{{ request()->routeIs('admin.calificaciones*') ? 'active' : '' }}">
                        <i class="bi bi-journal-check"></i>Calificaciones
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.asistencia.index') }}"
                       class="{{ request()->routeIs('admin.asistencia*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-check"></i>Asistencia
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Reportes</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.estudiantes.lista-excel') }}">
                        <i class="bi bi-file-earmark-excel"></i>Excel Estudiantes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.estudiantes.lista-pdf') }}">
                        <i class="bi bi-file-earmark-pdf"></i>PDF Estudiantes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pre-matriculas.lista-pdf') }}">
                        <i class="bi bi-file-earmark-pdf"></i>PDF Pre-matrículas
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Exportación SIGERD</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.sigerd.index') }}"
                       class="{{ request()->routeIs('admin.sigerd.index') ? 'active' : '' }}"
                       style="{{ request()->routeIs('admin.sigerd*') ? '' : 'color:#a78bfa;' }}">
                        <i class="bi bi-cloud-upload"></i>Exportar a SIGERD
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sigerd.historial') }}"
                       class="{{ request()->routeIs('admin.sigerd.historial') ? 'active' : '' }}">
                        <i class="bi bi-clock-history"></i>Historial Exportaciones
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Comunicación</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.comunicaciones.index') }}"
                       class="{{ request()->routeIs('admin.comunicaciones*') ? 'active' : '' }}">
                        <i class="bi bi-envelope-fill"></i>Mensajes
                        @php try { $__uid = auth()->id(); $__msgR = \Illuminate\Support\Facades\Cache::remember("user_{$__uid}_msg_unread", 60, fn() => \App\Models\MensajeDestinatario::where('destinatario_id',$__uid)->whereNull('leido_at')->where('eliminado',false)->count()); } catch(\Exception $e){ $__msgR=0; } @endphp
                        @if($__msgR > 0)
                        <span class="badge rounded-pill text-bg-primary ms-auto" style="font-size:.62rem;padding:.2rem .5rem;">{{ $__msgR }}</span>
                        @endif
                    </a>
                </li>
            </ul>
            {{-- ══ SIDEBAR SECRETARÍA ══ --}}
            @elseif($isSecreOnly)
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>Mi Escritorio
                    </a>
                </li>
            </ul>
            <div class="nav-section-title">Registro de Estudiantes</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.estudiantes.index') }}" class="{{ request()->routeIs('admin.estudiantes*') ? 'active' : '' }}">
                        <i class="bi bi-people-fill"></i>Estudiantes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pre-matriculas.index') }}" class="{{ request()->routeIs('admin.pre-matriculas*') ? 'active' : '' }}">
                        <i class="bi bi-inbox"></i>Pre-matrículas
                        @php try { $__preC = \App\Models\PreMatricula::pendientes()->count(); } catch(\Exception $e){ $__preC=0; } @endphp
                        @if($__preC > 0)<span class="badge rounded-pill text-bg-warning ms-auto" style="font-size:.6rem;padding:.18rem .45rem;">{{ $__preC }}</span>@endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.matriculas.index') }}" class="{{ request()->routeIs('admin.matriculas*') ? 'active' : '' }}">
                        <i class="bi bi-card-list"></i>Matrículas
                    </a>
                </li>
                {{-- «Inscripciones» ya está en su propio grupo para estos roles: no se repite aquí --}}
                @unless($isAdmin || $isDir || $isSecre)
                <li class="nav-item">
                    <a href="{{ route('admin.inscripciones.index') }}" class="{{ request()->routeIs('admin.inscripciones*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-check"></i>Inscripciones
                    </a>
                </li>
                @endunless
            </ul>
            <div class="nav-section-title">Documentos</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.boletines.index') }}" class="{{ request()->routeIs('admin.boletines*') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-text"></i>Boletines
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.asistencia.index') }}" class="{{ request()->routeIs('admin.asistencia*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-check"></i>Asistencia
                    </a>
                </li>
            </ul>
            <div class="nav-section-title">Comunicación</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.comunicaciones.index') }}" class="{{ request()->routeIs('admin.comunicaciones*') ? 'active' : '' }}">
                        <i class="bi bi-envelope-fill"></i>Mensajes
                        @php try { $__uid=auth()->id(); $__msgS=\Illuminate\Support\Facades\Cache::remember("user_{$__uid}_msg_unread",60,fn()=>\App\Models\MensajeDestinatario::where('destinatario_id',$__uid)->whereNull('leido_at')->where('eliminado',false)->count()); } catch(\Exception $e){ $__msgS=0; } @endphp
                        @if($__msgS > 0)<span class="badge rounded-pill text-bg-primary ms-auto" style="font-size:.62rem;padding:.2rem .5rem;">{{ $__msgS }}</span>@endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.comunicados.mis') }}" class="{{ request()->routeIs('admin.comunicados.mis') ? 'active' : '' }}">
                        <i class="bi bi-megaphone"></i>Comunicados
                    </a>
                </li>
            </ul>

            {{-- ══ SIDEBAR COORDINADOR ══ --}}
            @elseif($isCoordOnly)
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.kpis.index') }}" class="{{ request()->routeIs('admin.kpis*') ? 'active' : '' }}">
                        <i class="bi bi-graph-up-arrow"></i>KPIs
                    </a>
                </li>
            </ul>
            <div class="nav-section-title">Gestión Académica</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.estudiantes.index') }}" class="{{ request()->routeIs('admin.estudiantes.index') ? 'active' : '' }}">
                        <i class="bi bi-people-fill"></i>Estudiantes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.grupos.index') }}" class="{{ request()->routeIs('admin.grupos*') ? 'active' : '' }}">
                        <i class="bi bi-diagram-3"></i>Grupos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.docentes.index') }}" class="{{ request()->routeIs('admin.docentes*') ? 'active' : '' }}">
                        <i class="bi bi-person-workspace"></i>Docentes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.asignaciones.index') }}" class="{{ request()->routeIs('admin.asignaciones*') ? 'active' : '' }}">
                        <i class="bi bi-link-45deg"></i>Asignaciones
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.periodos.index') }}" class="{{ request()->routeIs('admin.periodos*') ? 'active' : '' }}">
                        <i class="bi bi-calendar3"></i>Períodos
                    </a>
                </li>
            </ul>
            <div class="nav-section-title">Calificaciones</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.calificaciones.index') }}" class="{{ request()->routeIs('admin.calificaciones*') ? 'active' : '' }}">
                        <i class="bi bi-journal-check"></i>Calificaciones
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.boletines.index') }}" class="{{ request()->routeIs('admin.boletines*') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-text"></i>Boletines
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.asistencia.index') }}" class="{{ request()->routeIs('admin.asistencia*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-check"></i>Asistencia
                    </a>
                </li>
            </ul>
            <div class="nav-section-title">Supervisión</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.observaciones.index') }}" class="{{ request()->routeIs('admin.observaciones*') ? 'active' : '' }}">
                        <i class="bi bi-eye"></i>Observaciones
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.registro.index') }}" class="{{ request()->routeIs('admin.registro*') ? 'active' : '' }}">
                        <i class="bi bi-table"></i>Registro MINERD
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.rendimiento.dashboard') }}" class="{{ request()->routeIs('admin.rendimiento*') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-fill"></i>Rendimiento
                    </a>
                </li>
            </ul>
            <div class="nav-section-title">Planificación</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.planes-clase.index') }}" class="{{ request()->routeIs('admin.planes-clase*') ? 'active' : '' }}">
                        <i class="bi bi-journal-text"></i>Planes de Clase
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.instrumentos.index') }}" class="{{ request()->routeIs('admin.instrumentos*') ? 'active' : '' }}">
                        <i class="bi bi-tools"></i>Instrumentos
                    </a>
                </li>
            </ul>
            <div class="nav-section-title">Comunicación</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.comunicaciones.index') }}" class="{{ request()->routeIs('admin.comunicaciones*') ? 'active' : '' }}">
                        <i class="bi bi-envelope-fill"></i>Mensajes
                        @php try { $__uid=auth()->id(); $__msgC=\Illuminate\Support\Facades\Cache::remember("user_{$__uid}_msg_unread",60,fn()=>\App\Models\MensajeDestinatario::where('destinatario_id',$__uid)->whereNull('leido_at')->where('eliminado',false)->count()); } catch(\Exception $e){ $__msgC=0; } @endphp
                        @if($__msgC > 0)<span class="badge rounded-pill text-bg-primary ms-auto" style="font-size:.62rem;padding:.2rem .5rem;">{{ $__msgC }}</span>@endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.comunicados.mis') }}" class="{{ request()->routeIs('admin.comunicados.mis') ? 'active' : '' }}">
                        <i class="bi bi-megaphone"></i>Comunicados
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.calendario.index') }}" class="{{ request()->routeIs('admin.calendario*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-event"></i>Calendario
                    </a>
                </li>
            </ul>

            {{-- ══ SIDEBAR CAJA / FINANZAS ══ --}}
            @elseif($isCaja)
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>Mi Escritorio
                    </a>
                </li>
            </ul>
            <div class="nav-section-title">Pagos y Colegiaturas</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.pagos.dashboard') }}" class="{{ request()->routeIs('admin.pagos.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-cash-coin"></i>Dashboard Financiero
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pagos.index') }}" class="{{ request()->routeIs('admin.pagos.index') ? 'active' : '' }}">
                        <i class="bi bi-receipt"></i>Gestión de Pagos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pagos.deudores') }}" class="{{ request()->routeIs('admin.pagos.deudores') ? 'active' : '' }}">
                        <i class="bi bi-exclamation-triangle-fill"></i>Deudores
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.becas.index') }}" class="{{ request()->routeIs('admin.becas*') ? 'active' : '' }}">
                        <i class="bi bi-award"></i>Becas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pagos.conceptos') }}" class="{{ request()->routeIs('admin.pagos.conceptos') ? 'active' : '' }}">
                        <i class="bi bi-tags"></i>Conceptos de Pago
                    </a>
                </li>
            </ul>
            <div class="nav-section-title">Reportes</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.pagos.resumen-mensual-pdf') }}">
                        <i class="bi bi-file-earmark-pdf"></i>Resumen Mensual PDF
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pagos.lista-pdf') }}">
                        <i class="bi bi-file-earmark-pdf"></i>Lista de Pagos PDF
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pagos.lista-excel') }}">
                        <i class="bi bi-file-earmark-excel"></i>Lista de Pagos Excel
                    </a>
                </li>
            </ul>
            <div class="nav-section-title">Comunicación</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.comunicaciones.index') }}" class="{{ request()->routeIs('admin.comunicaciones*') ? 'active' : '' }}">
                        <i class="bi bi-envelope-fill"></i>Mensajes
                    </a>
                </li>
            </ul>

            {{-- ══ SIDEBAR BIBLIOTECA ══ --}}
            @elseif($isBiblioteca)
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>Mi Escritorio
                    </a>
                </li>
            </ul>
            <div class="nav-section-title">Biblioteca</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.biblioteca.prestamos.index') }}" class="{{ request()->routeIs('admin.biblioteca.prestamos*') ? 'active' : '' }}">
                        <i class="bi bi-book"></i>Préstamos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.recursos.index') }}" class="{{ request()->routeIs('admin.recursos*') ? 'active' : '' }}">
                        <i class="bi bi-archive"></i>Inventario / Recursos
                    </a>
                </li>
            </ul>
            <div class="nav-section-title">Comunicación</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.comunicaciones.index') }}" class="{{ request()->routeIs('admin.comunicaciones*') ? 'active' : '' }}">
                        <i class="bi bi-envelope-fill"></i>Mensajes
                    </a>
                </li>
            </ul>

            {{-- ══ SIDEBAR RECEPCIÓN ══ --}}
            @elseif($isRecepcion)
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>Mi Escritorio
                    </a>
                </li>
            </ul>
            <div class="nav-section-title">Estudiantes</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.estudiantes.index') }}" class="{{ request()->routeIs('admin.estudiantes*') ? 'active' : '' }}">
                        <i class="bi bi-people-fill"></i>Estudiantes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pre-matriculas.index') }}" class="{{ request()->routeIs('admin.pre-matriculas*') ? 'active' : '' }}">
                        <i class="bi bi-inbox"></i>Pre-matrículas
                        @php try { $__preR = \App\Models\PreMatricula::pendientes()->count(); } catch(\Exception $e){ $__preR=0; } @endphp
                        @if($__preR > 0)<span class="badge rounded-pill text-bg-warning ms-auto" style="font-size:.6rem;padding:.18rem .45rem;">{{ $__preR }}</span>@endif
                    </a>
                </li>
            </ul>
            <div class="nav-section-title">Comunicación</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.comunicaciones.index') }}" class="{{ request()->routeIs('admin.comunicaciones*') ? 'active' : '' }}">
                        <i class="bi bi-envelope-fill"></i>Mensajes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.comunicados.index') }}" class="{{ request()->routeIs('admin.comunicados*') ? 'active' : '' }}">
                        <i class="bi bi-megaphone"></i>Comunicados
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.calendario.index') }}" class="{{ request()->routeIs('admin.calendario*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-event"></i>Eventos y Calendario
                    </a>
                </li>
            </ul>

            {{-- ══ SIDEBAR ADMIN COMPLETO (Administrador, Director, Personal Adm) ══ --}}
            @else
            {{-- Dashboard --}}
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>Dashboard
                    </a>
                </li>
                @if($isAdmin || $isDir)
                <li class="nav-item">
                    <a href="{{ route('admin.kpis.index') }}" class="{{ request()->routeIs('admin.kpis*') ? 'active' : '' }}">
                        <i class="bi bi-graph-up-arrow"></i>KPIs Director
                    </a>
                </li>
                @endif
            </ul>

            {{-- ══ GESTIÓN ACADÉMICA ══ --}}
            @if($canAcad || $isDocente)
            <div class="nav-section-title">Gestión Académica</div>
            <ul class="list-unstyled mb-0">
                @if($canAcad)
                <li class="nav-item">
                    <a href="{{ route('admin.estudiantes.index') }}" class="{{ request()->routeIs('admin.estudiantes.index') ? 'active' : '' }}">
                        <i class="bi bi-people-fill"></i>Estudiantes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.inscripciones.index') }}" class="{{ request()->routeIs('admin.inscripciones*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-check"></i>Inscripciones
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.matriculas.index') }}" class="{{ request()->routeIs('admin.matriculas*') ? 'active' : '' }}">
                        <i class="bi bi-card-list"></i>Matrículas
                    </a>
                </li>
                @endif
                @if($canCalif || $isDocente)
                <li class="nav-item">
                    <a href="{{ route('admin.asistencia.index') }}" class="{{ request()->routeIs('admin.asistencia*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-check"></i>Asistencia
                    </a>
                </li>
                @if(!$isDocente)
                @php $horarioActive = request()->routeIs('admin.horarios*'); @endphp
                <li class="nav-item">
                    <button class="nav-link-btn w-100 text-start d-flex align-items-center justify-content-between"
                            type="button" data-sidebar-toggle="subHorarios"
                            aria-expanded="{{ $horarioActive ? 'true' : 'false' }}">
                        <span class="d-flex align-items-center gap-2"><i class="bi bi-calendar-week"></i>Horarios</span>
                        <i class="bi bi-chevron-down" style="font-size:.65rem;transition:transform .2s;{{ $horarioActive ? 'transform:rotate(180deg)' : '' }}"></i>
                    </button>
                    <div class="sidebar-submenu {{ $horarioActive ? 'sidebar-submenu-open' : '' }}" id="subHorarios">
                        <ul class="list-unstyled ps-3 mb-0" style="border-left:2px solid rgba(255,255,255,.12);margin-left:1.25rem;margin-top:.25rem;">
                            <li><a href="{{ route('admin.horarios.index') }}" class="{{ request()->routeIs('admin.horarios.index') || request()->routeIs('admin.horarios.show') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-calendar3"></i>Horarios</a></li>
                            <li><a href="{{ route('admin.horarios.vista-maestra') }}" class="{{ request()->routeIs('admin.horarios.vista-maestra') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-grid-3x3-gap-fill"></i>Vista Maestra</a></li>
                            <li><a href="{{ route('admin.horarios.suplencias') }}" class="{{ request()->routeIs('admin.horarios.suplencias*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-person-fill-exclamation"></i>Suplencias</a></li>
                            <li><a href="{{ route('admin.horarios.disponibilidad') }}" class="{{ request()->routeIs('admin.horarios.disponibilidad') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-person-check"></i>Disponibilidad</a></li>
                        </ul>
                    </div>
                </li>
                @else
                <li class="nav-item">
                    <a href="{{ route('admin.horarios.mi-horario') }}" class="{{ request()->routeIs('admin.horarios.mi-horario') ? 'active' : '' }}">
                        <i class="bi bi-calendar-week-fill"></i>Mi Horario
                    </a>
                </li>
                @endif
                <li class="nav-item">
                    <a href="{{ route('admin.calificaciones.index') }}" class="{{ request()->routeIs('admin.calificaciones.index') || request()->routeIs('admin.calificaciones.planilla*') ? 'active' : '' }}">
                        <i class="bi bi-journal-check"></i>Registro de Notas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.boletines.index') }}" class="{{ request()->routeIs('admin.boletines.index') || request()->routeIs('admin.boletines.ver') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-text"></i>Boletines
                    </a>
                </li>
                @if(!$isDocente && !$canConfig)   {{-- quien tiene Configuración ya la ve allí --}}
                <li class="nav-item">
                    <a href="{{ route('admin.boletines.config') }}" class="{{ request()->routeIs('admin.boletines.config*') ? 'active' : '' }}" style="padding-left:2.1rem;font-size:.82rem;opacity:.85;">
                        <i class="bi bi-gear" style="font-size:.8rem;"></i>Config. Boletín
                    </a>
                </li>
                @endif
                <li class="nav-item">
                    <a href="{{ route('admin.carnet.index') }}" class="{{ request()->routeIs('admin.carnet*') ? 'active' : '' }}">
                        <i class="bi bi-person-badge-fill"></i>Carnet+
                    </a>
                </li>
                @endif
                @if($showSegTecnica)
                <li class="nav-item">
                    <a href="{{ route('admin.planificacion.dashboard') }}" class="{{ request()->routeIs('admin.planificacion*') ? 'active' : '' }}">
                        <i class="bi bi-journal-text"></i>Planificaciones Técnicas
                    </a>
                </li>
                @endif
            </ul>
            @endif

            {{-- ══ GESTIÓN INSTITUCIONAL ══ --}}
            @if($isAdmin || $isDir || $isCoord)
            <div class="nav-section-title">Gestión Institucional</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.docentes.index') }}" class="{{ request()->routeIs('admin.docentes*') ? 'active' : '' }}">
                        <i class="bi bi-person-badge"></i>Docentes
                    </a>
                </li>
                @if($canAcad)
                <li class="nav-item">
                    <a href="{{ route('admin.academico.index') }}" class="{{ request()->routeIs('admin.academico*') ? 'active' : '' }}">
                        <i class="bi bi-calendar3-event"></i>Cursos del año
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.grupos.index') }}" class="{{ request()->routeIs('admin.grupos*') ? 'active' : '' }}">
                        <i class="bi bi-grid-3x3-gap"></i>Grupos / Cursos
                    </a>
                </li>
                @endif
                @if($isAdmin || $isCoord)
                <li class="nav-item">
                    <a href="{{ route('admin.indicadores.index') }}" class="{{ request()->routeIs('admin.indicadores*') ? 'active' : '' }}">
                        <i class="bi bi-check2-square"></i>Indicadores de Logro
                    </a>
                </li>
                @endif
                <li class="nav-item">
                    <a href="{{ route('admin.calificaciones.resumen') }}" class="{{ request()->routeIs('admin.calificaciones.resumen') ? 'active' : '' }}">
                        <i class="bi bi-table"></i>Resumen de Notas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.calificaciones.ranking') }}" class="{{ request()->routeIs('admin.calificaciones.ranking') ? 'active' : '' }}">
                        <i class="bi bi-trophy"></i>Ranking Académico
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.observaciones.index') }}" class="{{ request()->routeIs('admin.observaciones*') ? 'active' : '' }}">
                        <i class="bi bi-chat-square-text"></i>Observaciones
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.disciplina.dashboard') }}" class="{{ request()->routeIs('admin.disciplina*') ? 'active' : '' }}">
                        <i class="bi bi-shield-exclamation"></i>Disciplina
                    </a>
                </li>
                @php $otrasActive = request()->routeIs('admin.reconocimientos*') || request()->routeIs('admin.gamificacion*') || request()->routeIs('admin.proyectos*') || request()->routeIs('admin.salud*') || request()->routeIs('admin.tutorias*') || request()->routeIs('admin.seguimiento-social*') || request()->routeIs('admin.reuniones*') || request()->routeIs('admin.evaluaciones-docentes*'); @endphp
                <li class="nav-item">
                    <button class="nav-link-btn w-100 text-start d-flex align-items-center justify-content-between"
                            type="button" data-sidebar-toggle="subOtrasFunciones"
                            aria-expanded="{{ $otrasActive ? 'true' : 'false' }}">
                        <span class="d-flex align-items-center gap-2"><i class="bi bi-three-dots"></i>Más funciones</span>
                        <i class="bi bi-chevron-down" style="font-size:.65rem;transition:transform .2s;{{ $otrasActive ? 'transform:rotate(180deg)' : '' }}"></i>
                    </button>
                    <div class="sidebar-submenu {{ $otrasActive ? 'sidebar-submenu-open' : '' }}" id="subOtrasFunciones">
                        <ul class="list-unstyled ps-3 mb-0" style="border-left:2px solid rgba(255,255,255,.12);margin-left:1.25rem;margin-top:.25rem;">
                            <li><a href="{{ route('admin.tutorias.index') }}" class="{{ request()->routeIs('admin.tutorias*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-person-hearts"></i>Tutorías</a></li>
                            <li><a href="{{ route('admin.seguimiento-social.dashboard') }}" class="{{ request()->routeIs('admin.seguimiento-social*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-people"></i>Seguimiento Social</a></li>
                            <li><a href="{{ route('admin.salud.dashboard') }}" class="{{ request()->routeIs('admin.salud*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-heart-pulse"></i>Salud Escolar</a></li>
                            <li><a href="{{ route('admin.evaluaciones-docentes.dashboard') }}" class="{{ request()->routeIs('admin.evaluaciones-docentes*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-clipboard2-check"></i>Eval. Docentes</a></li>
                            <li><a href="{{ route('admin.reuniones.dashboard') }}" class="{{ request()->routeIs('admin.reuniones*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-journal-text"></i>Actas Reuniones</a></li>
                            <li><a href="{{ route('admin.proyectos.dashboard') }}" class="{{ request()->routeIs('admin.proyectos*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-lightbulb"></i>Proyectos</a></li>
                            <li><a href="{{ route('admin.reconocimientos.dashboard') }}" class="{{ request()->routeIs('admin.reconocimientos*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-trophy"></i>Reconocimientos</a></li>
                            <li><a href="{{ route('admin.gamificacion.index') }}" class="{{ request()->routeIs('admin.gamificacion*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-controller"></i>Gamificación</a></li>
                        </ul>
                    </div>
                </li>
            </ul>
            @endif

            {{-- ══ MI ESPACIO (solo Docente) ══ --}}
            @if($isDocente)
            <div class="nav-section-title">Mi Espacio</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.horarios.mi-horario') }}" class="{{ request()->routeIs('admin.horarios.mi-horario') ? 'active' : '' }}">
                        <i class="bi bi-calendar-week-fill"></i>Mi Horario
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.perfiles.miPerfil') }}" class="{{ request()->routeIs('admin.perfiles.miPerfil') ? 'active' : '' }}">
                        <i class="bi bi-person-circle"></i>Mi Perfil
                    </a>
                </li>
            </ul>
            @endif

            {{-- ══ RENDIMIENTO INSTITUCIONAL ══ --}}
            @if($isAdmin || $isDir || $isCoord)
            <div class="nav-section-title">Rendimiento</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.rendimiento.dashboard') }}" class="{{ request()->routeIs('admin.rendimiento.dashboard') && !request('ciclo') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-line"></i>Dashboard General
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.rendimiento.semaforo') }}" class="{{ request()->routeIs('admin.rendimiento.semaforo') ? 'active' : '' }}">
                        <i class="bi bi-circle-fill" style="color:#22c55e;font-size:.6rem;"></i>&nbsp;Semáforo
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.rendimiento.porArea') }}" class="{{ request()->routeIs('admin.rendimiento.porArea') ? 'active' : '' }}">
                        <i class="bi bi-graph-up-arrow"></i>Por Área
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.rendimiento.recuperaciones') }}" class="{{ request()->routeIs('admin.rendimiento.recuperaciones') ? 'active' : '' }}">
                        <i class="bi bi-exclamation-triangle-fill" style="color:#ef4444;font-size:.75rem;"></i>&nbsp;Recuperaciones
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.rendimiento.rezagados') }}" class="{{ request()->routeIs('admin.rendimiento.rezagados') ? 'active' : '' }}">
                        <i class="bi bi-person-x-fill" style="color:#d97706;font-size:.75rem;"></i>&nbsp;Rezagados
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.rendimiento.comparativo') }}" class="{{ request()->routeIs('admin.rendimiento.comparativo') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-steps"></i>Comparativo Períodos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.rendimiento.rankingAsignaturas') }}" class="{{ request()->routeIs('admin.rendimiento.rankingAsignaturas') ? 'active' : '' }}">
                        <i class="bi bi-trophy"></i>Ranking Asignaturas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.rendimiento.tendencia') }}" class="{{ request()->routeIs('admin.rendimiento.tendencia') ? 'active' : '' }}">
                        <i class="bi bi-graph-up-arrow"></i>Tendencia por Grupo
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.riesgo.index') }}" class="{{ request()->routeIs('admin.riesgo*') ? 'active' : '' }}">
                        <i class="bi bi-shield-exclamation" style="color:#ef4444;font-size:.75rem;"></i>Risk Score
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.malla.matriz') }}" class="{{ request()->routeIs('admin.malla.matriz') ? 'active' : '' }}">
                        <i class="bi bi-grid-3x3"></i>Matriz Curricular
                    </a>
                </li>
            </ul>
            @endif

            {{-- ══ PLANIFICACIÓN DOCENTE ══ --}}
            @if($isAdmin || $isDir || $isCoord || $isDocente)
            <div class="nav-section-title">Planificación Docente</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.planes-clase.index') }}" class="{{ request()->routeIs('admin.planes-clase*') ? 'active' : '' }}">
                        <i class="bi bi-journal-text"></i>Planes de Clase
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.instrumentos.index') }}" class="{{ request()->routeIs('admin.instrumentos*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-check"></i>Instrumentos de Evaluación
                    </a>
                </li>
                @if($isAdmin || $isDir || $isCoord)
                <li class="nav-item">
                    <a href="{{ route('admin.classroom.index') }}" class="{{ request()->routeIs('admin.classroom*') ? 'active' : '' }}">
                        <i class="bi bi-easel2-fill"></i>Classroom Virtual
                    </a>
                </li>
                @endif
            </ul>
            @endif

            {{-- ══ SUPERVISIÓN ══ --}}
            @if($canSupervisar || $isDir || $isCoord)
            <div class="nav-section-title">Supervisión</div>
            <ul class="list-unstyled mb-0">
                @if($isAdmin || $isDir || $isCoord)
                <li class="nav-item">
                    <a href="{{ route('admin.ejecutivo.index') }}" class="{{ request()->routeIs('admin.ejecutivo*') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-line-fill" style="color:#f59e0b;"></i>Dashboard Ejecutivo
                    </a>
                    @if(request()->routeIs('admin.ejecutivo*'))
                    <ul class="list-unstyled ms-3 mt-1 mb-1" style="font-size:.76rem;">
                        <li>
                            <a href="{{ route('admin.ejecutivo.pdf', request()->query()) }}" target="_blank"
                               style="color:#94a3b8;display:flex;align-items:center;gap:.4rem;padding:.25rem .5rem;border-radius:6px;text-decoration:none;"
                               onmouseover="this.style.color='#e2e8f0'" onmouseout="this.style.color='#94a3b8'">
                                <i class="bi bi-file-earmark-pdf" style="color:#f87171;"></i>Exportar PDF
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.ejecutivo.excel', request()->query()) }}"
                               style="color:#94a3b8;display:flex;align-items:center;gap:.4rem;padding:.25rem .5rem;border-radius:6px;text-decoration:none;"
                               onmouseover="this.style.color='#e2e8f0'" onmouseout="this.style.color='#94a3b8'">
                                <i class="bi bi-file-earmark-excel" style="color:#4ade80;"></i>Exportar Excel
                            </a>
                        </li>
                    </ul>
                    @endif
                </li>
                @endif
                <li class="nav-item">
                    <a href="{{ route('admin.reportes.index') }}" class="{{ request()->routeIs('admin.reportes*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard2-data"></i>Reportes Institucionales
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.registro.index') }}" class="{{ request()->routeIs('admin.registro*') ? 'active' : '' }}">
                        <i class="bi bi-journal-bookmark-fill"></i>Registro Académico
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.rubricas.index') }}" class="{{ request()->routeIs('admin.rubricas*') ? 'active' : '' }}">
                        <i class="bi bi-grid-3x3-gap-fill"></i>Rúbricas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.competencias.index') }}" class="{{ request()->routeIs('admin.competencias*') ? 'active' : '' }}">
                        <i class="bi bi-diagram-3"></i>Competencias / IL
                    </a>
                </li>
                @if($isAdmin || $isDir)
                <li class="nav-item">
                    <a href="{{ route('admin.cierre-ano.index') }}" class="{{ request()->routeIs('admin.cierre-ano*') ? 'active' : '' }}">
                        <i class="bi bi-lock-fill"></i>Cierre de Año
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.exportacion-masiva.index') }}" class="{{ request()->routeIs('admin.exportacion-masiva*') ? 'active' : '' }}">
                        <i class="bi bi-file-zip"></i>Exportación Masiva
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.importaciones.index') }}" class="{{ request()->routeIs('admin.importaciones*') ? 'active' : '' }}">
                        <i class="bi bi-cloud-upload"></i>Importaciones
                    </a>
                </li>
                @endif
            </ul>
            @endif

            {{-- ══ CALENDARIO Y ALERTAS ══ --}}
            @if($isAdmin || $isDir || $isCoord || $isDocente)
            <div class="nav-section-title">Calendario</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.calendario.index') }}" class="{{ request()->routeIs('admin.calendario*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-event"></i>Calendario Académico
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.alertas.index') }}" class="{{ request()->routeIs('admin.alertas.index') ? 'active' : '' }}" style="justify-content:space-between;">
                        <span class="d-flex align-items-center gap-2">
                            <i class="bi bi-bell"></i>Notificaciones
                        </span>
                        @if(!empty($alertasNoLeidas) && $alertasNoLeidas > 0)
                            <span class="badge rounded-pill text-bg-danger" style="font-size:.62rem;padding:.2rem .5rem;">{{ $alertasNoLeidas }}</span>
                        @endif
                    </a>
                </li>
            </ul>
        @endif

            {{-- ══ COMUNICADOS Y MENSAJES ══ --}}
            <div class="nav-section-title">Comunicados y Mensajes</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.comunicaciones.index') }}" class="{{ request()->routeIs('admin.comunicaciones*') ? 'active' : '' }}">
                        <i class="bi bi-envelope-fill"></i>Mensajes Internos
                        @php try { $__uid = auth()->id(); $msgNoLeidos = \Illuminate\Support\Facades\Cache::remember("user_{$__uid}_msg_unread", 60, fn() => \App\Models\MensajeDestinatario::where('destinatario_id',$__uid)->whereNull('leido_at')->where('eliminado',false)->count()); } catch(\Exception $e){ $msgNoLeidos=0; } @endphp
                        @if($msgNoLeidos > 0)
                        <span class="badge rounded-pill text-bg-primary ms-auto" style="font-size:.62rem;padding:.2rem .5rem;">{{ $msgNoLeidos }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.comunicados.mis') }}" class="{{ request()->routeIs('admin.comunicados.mis') ? 'active' : '' }}">
                        <i class="bi bi-megaphone"></i>Mis Comunicados
                    </a>
                </li>
                @if($isAdmin || $isDir || $isCoord)
                <li class="nav-item">
                    <a href="{{ route('admin.comunicados.dashboard') }}" class="{{ request()->routeIs('admin.comunicados*') ? 'active' : '' }}">
                        <i class="bi bi-megaphone-fill"></i>Gestionar Comunicados
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.avisos-emergencia.index') }}" class="{{ request()->routeIs('admin.avisos-emergencia*') ? 'active' : '' }}">
                        <i class="bi bi-exclamation-octagon-fill" style="color:#ef4444;"></i>Avisos Emergencia
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.encuestas.dashboard') }}" class="{{ request()->routeIs('admin.encuestas*') ? 'active' : '' }}">
                        <i class="bi bi-patch-question"></i>Encuestas
                    </a>
                </li>
                @endif
            </ul>

            {{-- ══ PAGOS Y COLEGIATURAS ══ --}}
            @php
                $moduloPagos    = \App\Models\ConfigInstitucional::moduloActivo('pagos');
                $countVencidos  = $moduloPagos ? \App\Models\Pago::vencidos()->count() : 0;
            @endphp
            @if($moduloPagos && ($isAdmin || $isDir))
            <div class="nav-section-title">Pagos y Colegiaturas</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.pagos.dashboard') }}" class="{{ request()->routeIs('admin.pagos.dashboard') || request()->routeIs('admin.pagos.index') ? 'active' : '' }}">
                        <i class="bi bi-cash-coin"></i>Gestión de Pagos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pagos.deudores') }}" class="{{ request()->routeIs('admin.pagos.deudores') ? 'active' : '' }}" style="display:flex;align-items:center;justify-content:space-between;">
                        <span><i class="bi bi-exclamation-circle"></i>Deudores</span>
                        @if($countVencidos > 0)
                        <span style="background:#dc2626;color:#fff;border-radius:99px;font-size:.62rem;font-weight:700;min-width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;padding:0 5px;flex-shrink:0;">{{ $countVencidos > 99 ? '99+' : $countVencidos }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.becas.dashboard') }}" class="{{ request()->routeIs('admin.becas*') ? 'active' : '' }}">
                        <i class="bi bi-award"></i>Becas y Descuentos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pagos.conceptos') }}" class="{{ request()->routeIs('admin.pagos.conceptos') ? 'active' : '' }}">
                        <i class="bi bi-tags"></i>Conceptos de Pago
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pagos.config') }}" class="{{ request()->routeIs('admin.pagos.config') ? 'active' : '' }}">
                        <i class="bi bi-gear"></i>Config. Pagos
                    </a>
                </li>
                @can('solo-administrador')
                <li class="nav-item">
                    <a href="{{ route('admin.odoo.index') }}" class="{{ request()->routeIs('admin.odoo.*') ? 'active' : '' }}">
                        <i class="bi bi-plug"></i>Integración Odoo
                    </a>
                </li>
                @endcan
            </ul>
            @endif

            {{-- ══ INSCRIPCIONES ══ --}}
            @if($isAdmin || $isDir || $isSecre)
            @php $pmPendientes = \App\Models\PreMatricula::where('estado','pendiente')->count(); @endphp
            <div class="nav-section-title">Inscripciones</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.inscripciones.index') }}" class="{{ request()->routeIs('admin.inscripciones*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-check"></i>Inscripciones
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pre-matriculas.index') }}"
                       class="{{ request()->routeIs('admin.pre-matriculas*') ? 'active' : '' }}"
                       style="display:flex;align-items:center;justify-content:space-between;">
                        <span><i class="bi bi-person-lines-fill"></i>Pre-matrículas</span>
                        @if($pmPendientes > 0)
                        <span style="background:#f59e0b;color:#fff;font-size:.65rem;font-weight:800;padding:.1rem .45rem;border-radius:20px;line-height:1.5;flex-shrink:0;margin-left:.4rem;">{{ $pmPendientes }}</span>
                        @endif
                    </a>
                </li>
            </ul>
            @endif

            {{-- ══ SOLICITUDES DEL PERSONAL ══ --}}
            @if($isAdmin || $isDir || $isCoord)
            <div class="nav-section-title">Solicitudes</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.solicitudes.index') }}" class="{{ request()->routeIs('admin.solicitudes.index') ? 'active' : '' }}"
                       style="display:flex;align-items:center;justify-content:space-between;">
                        <span><i class="bi bi-people-fill"></i>Representantes</span>
                        @php
                        try {
                            $__tid = tenant_id();
                            $solRepPend = \Illuminate\Support\Facades\Cache::remember("t{$__tid}_sol_rep_pend", 60,
                                fn() => \App\Models\SolicitudRepresentante::where('estado','pendiente')->count()
                            );
                        } catch(\Exception $e){ $solRepPend=0; }
                        @endphp
                        @if($solRepPend > 0)
                        <span style="background:#d97706;color:#fff;font-size:.65rem;font-weight:800;padding:.1rem .45rem;border-radius:20px;line-height:1.5;">{{ $solRepPend }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.solicitudes-est.index') }}" class="{{ request()->routeIs('admin.solicitudes-est*') ? 'active' : '' }}"
                       style="display:flex;align-items:center;justify-content:space-between;">
                        <span><i class="bi bi-mortarboard-fill"></i>Solicitudes de estudiantes</span>
                        @php
                        try {
                            $solEstPend = \Illuminate\Support\Facades\Cache::remember("t{$__tid}_sol_est_pend", 60,
                                fn() => \App\Models\SolicitudEstudiante::where('estado','pendiente')->count()
                            );
                        } catch(\Exception $e){ $solEstPend=0; }
                        @endphp
                        @if($solEstPend > 0)
                        <span style="background:#d97706;color:#fff;font-size:.65rem;font-weight:800;padding:.1rem .45rem;border-radius:20px;line-height:1.5;">{{ $solEstPend }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.solicitudes-docente.index') }}" class="{{ request()->routeIs('admin.solicitudes-docente*') ? 'active' : '' }}"
                       style="display:flex;align-items:center;justify-content:space-between;">
                        <span><i class="bi bi-person-badge-fill"></i>Solicitudes de docentes</span>
                        @php
                        try {
                            $solDocPend = \Illuminate\Support\Facades\Cache::remember("t{$__tid}_sol_doc_pend", 60,
                                fn() => \App\Models\SolicitudDocente::where('estado','pendiente')->count()
                            );
                        } catch(\Exception $e){ $solDocPend=0; }
                        @endphp
                        @if($solDocPend > 0)
                        <span style="background:#d97706;color:#fff;font-size:.65rem;font-weight:800;padding:.1rem .45rem;border-radius:20px;line-height:1.5;">{{ $solDocPend }}</span>
                        @endif
                    </a>
                </li>
            </ul>
            @endif

            {{-- ══ SERVICIOS INSTITUCIONALES ══ --}}
            @if($isAdmin || $isDir || $isSecre || $isCoord)
            <div class="nav-section-title">Servicios Institucionales</div>
            <ul class="list-unstyled mb-0">
                @if($isAdmin || $isDir || $isSecre)
                <li class="nav-item">
                    <a href="{{ route('admin.cafeteria.dashboard') }}" class="{{ request()->routeIs('admin.cafeteria*') ? 'active' : '' }}">
                        <i class="bi bi-shop"></i>Cafetería
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.equipos.dashboard') }}" class="{{ request()->routeIs('admin.equipos.dashboard') || request()->routeIs('admin.equipos.index') || request()->routeIs('admin.equipos.create') || request()->routeIs('admin.equipos.edit') ? 'active' : '' }}">
                        <i class="bi bi-laptop"></i>Equipos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.equipos.prestamos.index') }}" class="{{ request()->routeIs('admin.equipos.prestamos*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-left-right"></i>Préstamos de Equipos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.biblioteca.dashboard') }}" class="{{ request()->routeIs('admin.biblioteca.dashboard') || request()->routeIs('admin.biblioteca.index') || request()->routeIs('admin.biblioteca.libros*') ? 'active' : '' }}">
                        <i class="bi bi-book-half"></i>Biblioteca
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.biblioteca.prestamos.index') }}" class="{{ request()->routeIs('admin.biblioteca.prestamos*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-left-right"></i>Préstamos de Libros
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.inventario.index') }}" class="{{ request()->routeIs('admin.inventario*') ? 'active' : '' }}">
                        <i class="bi bi-archive"></i>Inventario Escolar
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.recursos.index') }}" class="{{ request()->routeIs('admin.recursos*') && !request()->routeIs('admin.recursos.disponibilidad') ? 'active' : '' }}">
                        <i class="bi bi-building"></i>Recursos y Aulas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.recursos.disponibilidad') }}" class="{{ request()->routeIs('admin.recursos.disponibilidad') ? 'active' : '' }}">
                        <i class="bi bi-calendar2-check"></i>Disponibilidad Aulas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.transporte.dashboard') }}" class="{{ request()->routeIs('admin.transporte*') ? 'active' : '' }}">
                        <i class="bi bi-bus-front"></i>Transporte Escolar
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.galeria.dashboard') }}" class="{{ request()->routeIs('admin.galeria*') ? 'active' : '' }}">
                        <i class="bi bi-images"></i>Galería
                    </a>
                </li>
                @endif
                <li class="nav-item">
                    <a href="{{ route('admin.eventos.dashboard') }}" class="{{ request()->routeIs('admin.eventos*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-event-fill"></i>Eventos
                    </a>
                </li>
                @if($isAdmin || $isDir)
                <li class="nav-item">
                    <a href="{{ route('admin.nomina.dashboard') }}" class="{{ request()->routeIs('admin.nomina*') ? 'active' : '' }}">
                        <i class="bi bi-cash-stack"></i>Nómina de Empleados
                    </a>
                </li>
                @endif
            </ul>
            @endif

            {{-- ══ SOPORTE ══ --}}
            @if($isAdmin || $isDir)
            <div class="nav-section-title">Soporte</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.soporte.chat') }}" class="{{ request()->routeIs('admin.soporte.chat*') ? 'active' : '' }}"
                       id="sidebar-soporte-chat">
                        <i class="bi bi-headset"></i>Chat de Soporte
                        <span id="sidebar-support-badge" style="display:none;background:#ef4444;color:#fff;border-radius:99px;font-size:.6rem;font-weight:700;min-width:17px;height:17px;padding:0 4px;margin-left:auto;align-items:center;justify-content:center;"></span>
                    </a>
                </li>
            </ul>
            @endif

            {{-- ══ CONFIGURACIÓN ══ --}}
            @if($canConfig || $isDir)
            <div class="nav-section-title">Configuración</div>
            <ul class="list-unstyled mb-0">
                @if($isAdmin || $isDir || $isSuperAdmin)
                <li class="nav-item">
                    <a href="{{ route('admin.asignaturas.index') }}" class="{{ request()->routeIs('admin.asignaturas*') ? 'active' : '' }}">
                        <i class="bi bi-book"></i>Asignaturas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.asignaciones.index') }}" class="{{ request()->routeIs('admin.asignaciones*') ? 'active' : '' }}">
                        <i class="bi bi-diagram-3"></i>Asignaciones
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.familias.index') }}" class="{{ request()->routeIs('admin.familias*') ? 'active' : '' }}">
                        <i class="bi bi-collection"></i>Familias Profesionales
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.bachillerato-tecnico.index') }}" class="{{ request()->routeIs('admin.bachillerato-tecnico*') ? 'active' : '' }}">
                        <i class="bi bi-mortarboard-fill"></i>Bachillerato Técnico
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.especialidades.index') }}" class="{{ request()->routeIs('admin.especialidades*') ? 'active' : '' }}">
                        <i class="bi bi-tools"></i>Especialidades Técnicas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.periodos.index') }}" class="{{ request()->routeIs('admin.periodos*') ? 'active' : '' }}">
                        <i class="bi bi-calendar3"></i>Períodos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.school-years.index') }}" class="{{ request()->routeIs('admin.school-years*') ? 'active' : '' }}">
                        <i class="bi bi-mortarboard"></i>Años escolares
                    </a>
                </li>
                @endif
                @if($isAdmin)
                <li class="nav-item">
                    <a href="{{ route('admin.config.calificacion') }}" class="{{ request()->routeIs('admin.config.calificacion*') ? 'active' : '' }}">
                        <i class="bi bi-sliders"></i>Config. Notas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.boletines.config') }}" class="{{ request()->routeIs('admin.boletines.config*') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-medical"></i>Config. Boletín
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.config.ra') }}" class="{{ request()->routeIs('admin.config.ra*') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-steps"></i>Config. RA
                    </a>
                </li>
                @endif
            </ul>
            @endif

            {{-- ══ PÁGINA DE INICIO ══ --}}
            @if($isAdmin || $isDir || $isCoord)
            <div class="nav-section-title">Página de Inicio</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.sistema.landing') }}" class="{{ request()->routeIs('admin.sistema.landing') ? 'active' : '' }}">
                        <i class="bi bi-display"></i>Editor Landing
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.secciones.index') }}" class="{{ request()->routeIs('admin.secciones*') ? 'active' : '' }}">
                        <i class="bi bi-grid-1x2"></i>Secciones del Sitio
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.homepage.edit') }}" class="{{ request()->routeIs('admin.homepage*') ? 'active' : '' }}">
                        <i class="bi bi-layout-text-window-reverse"></i>Branding / Institución
                    </a>
                </li>
                @if($isAdmin)
                <li class="nav-item">
                    <a href="{{ route('admin.sistema.login-config') }}" class="{{ request()->routeIs('admin.sistema.login-config') ? 'active' : '' }}">
                        <i class="bi bi-palette"></i>Config. Login
                    </a>
                </li>
                @endif
            </ul>
            @endif

                        {{-- INTEGRACIONES --}}
            @if($isAdmin)
            <div class="nav-section-title">Integraciones</div>
            <ul class="list-unstyled mb-0">
                <li>
                    <a href="{{ route("admin.integraciones.index") }}" class="{{ request()->routeIs("admin.integraciones*", "admin.sigerd*") ? "active" : "" }}">
                        <i class="bi bi-plug-fill"></i>Integraciones
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.asistente.index') }}" class="{{ request()->routeIs('admin.asistente*') ? 'active' : '' }}">
                        <i class="bi bi-stars"></i>ZuraAI
                    </a>
                </li>
            </ul>
            @endif

            {{-- ══ SISTEMA ══ --}}
            @if($isAdmin)
            <div class="nav-section-title">Sistema</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.usuarios.index') }}" class="{{ request()->routeIs('admin.usuarios.index') || request()->routeIs('admin.usuarios.create') || request()->routeIs('admin.usuarios.edit') ? 'active' : '' }}">
                        <i class="bi bi-people"></i>Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.usuarios.pendientes') }}" class="{{ request()->routeIs('admin.usuarios.pendientes') ? 'active' : '' }}" style="display:flex;align-items:center;justify-content:space-between;">
                        <span class="d-flex align-items-center gap-2"><i class="bi bi-person-check"></i>Accesos Pendientes</span>
                        @if(!empty($usuariosPendientes) && $usuariosPendientes > 0)
                            <span id="admin-pending-badge" class="badge rounded-pill text-bg-warning" style="font-size:.62rem;padding:.2rem .5rem;">{{ $usuariosPendientes }}</span>
                        @else
                            <span id="admin-pending-badge" class="badge rounded-pill text-bg-warning" style="font-size:.62rem;padding:.2rem .5rem;display:none;">0</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sistema.index') }}" class="{{ request()->routeIs('admin.sistema.index') ? 'active' : '' }}">
                        <i class="bi bi-gear"></i>Configuración
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sistema.whatsapp') }}" class="{{ request()->routeIs('admin.sistema.whatsapp') ? 'active' : '' }}">
                        <i class="bi bi-whatsapp"></i>WhatsApp
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sistema.email-notif') }}" class="{{ request()->routeIs('admin.sistema.email-notif') ? 'active' : '' }}">
                        <i class="bi bi-envelope-check"></i>Email / Notif.
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sistema.notificaciones') }}" class="{{ request()->routeIs('admin.sistema.notificaciones') ? 'active' : '' }}">
                        <i class="bi bi-bell-fill"></i>Notificaciones In-app
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.plantillas.index') }}" class="{{ request()->routeIs('admin.plantillas*') ? 'active' : '' }}">
                        <i class="bi bi-chat-square-text"></i>Plantillas de Mensajes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sistema.actividad') }}" class="{{ request()->routeIs('admin.sistema.actividad') ? 'active' : '' }}">
                        <i class="bi bi-shield-check"></i>Log de Actividad
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sistema.estadisticas') }}" class="{{ request()->routeIs('admin.sistema.estadisticas') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>Estadísticas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sistema.demo-trial') }}" class="{{ request()->routeIs('admin.sistema.demo-trial') ? 'active' : '' }}">
                        <i class="bi bi-play-circle"></i>Demo & Prueba
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.billing.index') }}" class="{{ request()->routeIs('admin.billing*') ? 'active' : '' }}">
                        <i class="bi bi-credit-card"></i>Facturación
                    </a>
                </li>
            </ul>
            @endif
            @endif {{-- end @else isRegistro --}}

            {{-- ══ SUPER ADMIN — solo visible para super_admin ══ --}}
            @if(Auth::user()->hasRole('super_admin'))
            <div class="nav-section-title" style="color:#a78bfa;">ZuraEdu Platform</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('superadmin.tenants.index') }}" class="{{ request()->routeIs('superadmin*') ? 'active' : '' }}" style="{{ request()->routeIs('superadmin*') ? '' : 'color:#c4b5fd;' }}">
                        <i class="bi bi-building-fill-gear"></i>Panel de Instituciones
                    </a>
                </li>
            </ul>
            @endif

            {{-- ══ CENTRO DE ADMINISTRACIÓN (hub, roadmap: "tipo Moodle") ══ --}}
            <div class="nav-section-title">Administración</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.centro-administracion') }}" class="{{ request()->routeIs('admin.centro-administracion') ? 'active' : '' }}">
                        <i class="bi bi-grid-3x3-gap"></i>Centro de Administración
                    </a>
                </li>
            </ul>

            {{-- ══ SOPORTE ══ --}}
            <div class="nav-section-title">Soporte</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.soporte.dashboard') }}" class="{{ request()->routeIs('admin.soporte*') ? 'active' : '' }}">
                        <i class="bi bi-headset"></i>Tickets de Soporte
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.ayuda') }}" class="{{ request()->routeIs('admin.ayuda') ? 'active' : '' }}">
                        <i class="bi bi-question-circle"></i>Centro de Ayuda
                    </a>
                </li>
            </ul>

            @php echo \App\Support\MenuFiltro::filtrar(ob_get_clean()); @endphp
        </nav>

        <!-- User Footer -->
        <div class="sidebar-user">
            <div class="user-avatar">
                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
            </div>
            <div class="user-info">
                <div class="user-name">{{ Auth::user()->name ?? 'Usuario' }}</div>
                <div class="user-role">{{ Auth::user()->getRoleNames()->first() ?? 'Usuario' }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-logout" title="Cerrar sesión">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>

        {{-- Plataforma: ZuraEdu respalda al colegio (el logo del colegio sigue arriba) --}}
        <a href="{{ url('/') }}" class="sidebar-marca" title="ZuraEdu — {{ \App\Support\Marca::lema() }}" style="display:flex;align-items:center;justify-content:center;padding:8px 12px 12px;opacity:.7;">
            <x-marca.logo variante="blanco" :alto="20" />
        </a>
        <style>.sidebar-collapsed .sidebar-marca { display:none !important; }</style>

    </aside>

    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" aria-label="Cerrar menú"></div>

    <!-- ════════════════════════════════════════════════
         TOPBAR
    ════════════════════════════════════════════════ -->
    <header class="topbar" role="banner">
        <button class="topbar-hamburger" id="hamburgerBtn" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false">
            <i class="bi bi-list"></i>
        </button>

        <button class="dark-toggle" id="sidebarCollapseBtn" title="Ocultar/mostrar menú" aria-label="Ocultar/mostrar menú" aria-controls="sidebar" aria-expanded="true">
            <i class="bi bi-layout-sidebar-inset" id="sidebarCollapseIcon"></i>
        </button>

        <div class="topbar-title d-none d-md-block">
            <i class="bi bi-chevron-right me-1" style="font-size:.7rem;opacity:.5;"></i>
            @yield('page-title', 'Dashboard')
        </div>

        <!-- Global Search -->
        <div class="topbar-search">
            <i class="bi bi-search gs-icon"></i>
            <input type="text" id="globalSearchInput"
                   placeholder="Buscar estudiantes, docentes, grupos…"
                   autocomplete="off" aria-label="Búsqueda global">
            <div id="gsDropdown"></div>
        </div>

        <!-- School Year Badge -->
        @isset($schoolYear)
            <span class="schoolyear-badge d-none d-lg-inline">
                <i class="bi bi-calendar2-check me-1"></i>
                {{ $schoolYear->nombre ?? $schoolYear->name ?? 'Año Escolar' }}
            </span>
        @endisset

        <!-- Campanita alertas -->
        @php $alertasTopbar = $alertasNoLeidas ?? 0; @endphp
        <div style="position:relative;">
            <button id="adminBell" title="Alertas del sistema"
                    style="background:none;border:none;color:#6b7280;font-size:1.15rem;cursor:pointer;padding:.35rem .5rem;border-radius:8px;position:relative;transition:color .15s;"
                    onclick="window.location.href='{{ route('admin.alertas.index') }}'">
                <i class="bi bi-bell"></i>
                <span id="adminBellBadge"
                      style="position:absolute;top:2px;right:2px;background:#ef4444;color:#fff;border-radius:99px;font-size:.55rem;font-weight:700;min-width:14px;height:14px;display:{{ $alertasTopbar > 0 ? 'flex' : 'none' }};align-items:center;justify-content:center;padding:0 3px;line-height:1;">
                    {{ $alertasTopbar > 9 ? '9+' : $alertasTopbar }}
                </span>
            </button>
        </div>

        {{-- Accesos rápidos del rol (icono + panel) --}}
        @include('partials.accesos-rapidos-boton', ['variante' => 'claro'])

        <!-- Dark mode toggle -->
        <button class="dark-toggle" id="darkToggleBtn" title="Modo oscuro / claro">
            <i class="bi bi-moon-stars-fill" id="darkToggleIcon"></i>
        </button>

        <!-- User dropdown -->
        <div class="topbar-user dropdown">
            <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    data-bs-strategy="fixed" data-bs-offset="0,4" aria-expanded="false">
                @if(Auth::user()->photo_url)
                    <img src="{{ Auth::user()->photo_url }}" alt="Foto"
                         style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid #e5e7eb;">
                @else
                <div class="topbar-avatar">
                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                </div>
                @endif
                <span class="d-none d-md-inline">{{ Auth::user()->name ?? 'Usuario' }}</span>
                <i class="bi bi-chevron-down" style="font-size:.7rem;color:rgba(255,255,255,.55);"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="min-width:180px;">
                <li>
                    <span class="dropdown-item-text text-muted" style="font-size:.75rem;">
                        {{ Auth::user()->email ?? '' }}
                    </span>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a href="{{ route('perfil.show') }}" class="dropdown-item">
                        <i class="bi bi-person-circle me-2 text-primary"></i>Mi Perfil
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </header>

    <!-- ════════════════════════════════════════════════
         MAIN CONTENT
    ════════════════════════════════════════════════ -->
    <main class="main-content" id="main-content" role="main">

        {{-- ── Barra de Período de Prueba ──────────────────────────── --}}
        @php
            try {
                $trialSetting = collect(\App\Helpers\Setting::all())->only(['trial_activo','trial_inicio','trial_dias','trial_mensaje']);
                $showTrial = ($trialSetting['trial_activo'] ?? '0') === '1'
                    && !empty($trialSetting['trial_inicio']);
                if ($showTrial) {
                    $tInicio  = \Carbon\Carbon::parse($trialSetting['trial_inicio']);
                    $tDias    = (int)($trialSetting['trial_dias'] ?? 30);
                    $tExpira  = $tInicio->copy()->addDays($tDias);
                    $tRestantes = max(0, (int) now()->diffInDays($tExpira, false));
                    $tExpirado  = now()->gt($tExpira);
                    $tPct = $tDias > 0 ? max(2, round(($tRestantes / $tDias) * 100)) : 0;
                    $tMensaje = $trialSetting['trial_mensaje'] ?? 'Estás usando una versión de prueba del sistema.';
                }
            } catch(\Exception $e) { $showTrial = false; }
        @endphp
        @if(!empty($showTrial))
        <div style="background:{{ $tExpirado ? '#fef2f2' : '#fffbeb' }};border-bottom:2px solid {{ $tExpirado ? '#fca5a5' : '#fcd34d' }};padding:.6rem 1.25rem;display:flex;align-items:center;gap:.85rem;flex-wrap:wrap;margin:-1.5rem -1.5rem 1.5rem;">
            <div style="flex-shrink:0;">
                <i class="bi bi-{{ $tExpirado ? 'x-octagon-fill text-danger' : 'hourglass-split' }}" style="font-size:1.1rem;color:{{ $tExpirado ? '#dc2626' : '#b45309' }};"></i>
            </div>
            <div style="flex:1;min-width:200px;">
                <div style="font-size:.8rem;font-weight:700;color:{{ $tExpirado ? '#991b1b' : '#92400e' }};">
                    @if($tExpirado)
                        Período de prueba expirado — Contacta al administrador del sistema
                    @else
                        {{ $tMensaje }} — <strong>{{ $tRestantes }} días restantes</strong>
                    @endif
                </div>
                @if(!$tExpirado)
                <div style="background:#e5e7eb;border-radius:99px;height:5px;margin-top:4px;overflow:hidden;max-width:220px;">
                    <div style="height:100%;border-radius:99px;width:{{ $tPct }}%;background:{{ $tPct > 50 ? '#10b981' : ($tPct > 20 ? '#f59e0b' : '#ef4444') }};"></div>
                </div>
                @endif
            </div>
            <div style="font-size:.73rem;color:#6b7280;white-space:nowrap;">
                Expira: {{ $tExpira->format('d/m/Y') }}
            </div>
            @can('admin.sistema.demo-trial')
            <a href="{{ route('admin.sistema.demo-trial') }}" style="font-size:.73rem;color:var(--primary);font-weight:600;text-decoration:none;white-space:nowrap;">
                Gestionar →
            </a>
            @endcan
        </div>
        @endif

        {{-- ── Banner SuperAdmin: modo panel de institución ──────── --}}
        @if(Auth::check() && Auth::user()->hasRole('super_admin') && session('sa_tenant_id'))
        <div style="background:linear-gradient(90deg,#4f46e5,#7c3aed);color:#fff;padding:.6rem 1.25rem;display:flex;align-items:center;gap:.85rem;flex-wrap:wrap;margin:-1.5rem -1.5rem 1.5rem;border-bottom:2px solid #6366f1;">
            <i class="bi bi-shield-fill-check" style="font-size:1.1rem;flex-shrink:0;"></i>
            <div style="flex:1;min-width:200px;font-size:.83rem;font-weight:600;">
                <span style="opacity:.8;">SuperAdmin · Administrando:</span>
                <strong class="ms-1">{{ session('sa_tenant_nombre') }}</strong>
            </div>
            <div class="d-flex align-items-center gap-2" style="flex-shrink:0;">
                <a href="{{ route('superadmin.tenants.show', session('sa_tenant_id')) }}"
                   style="font-size:.75rem;font-weight:700;color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.4);padding:.25rem .7rem;border-radius:6px;background:rgba(255,255,255,.1);">
                    <i class="bi bi-building me-1"></i>Ficha
                </a>
                <a href="{{ route('admin.homepage.edit') }}"
                   style="font-size:.75rem;font-weight:700;color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.4);padding:.25rem .7rem;border-radius:6px;background:rgba(255,255,255,.1);">
                    <i class="bi bi-palette-fill me-1"></i>Homepage
                </a>
                <form method="POST" action="{{ route('superadmin.tenants.exit-panel') }}" class="d-inline">
                    @csrf
                    <button type="submit"
                        style="font-size:.75rem;font-weight:700;color:#4f46e5;background:#fff;border:none;padding:.25rem .7rem;border-radius:6px;cursor:pointer;">
                        <i class="bi bi-box-arrow-left me-1"></i>Salir al panel ZuraEdu
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- ── Banner de vencimiento de suscripción ──────────────── --}}
        @php
            $showSuscBanner = false;
            try {
                if (
                    app()->bound('tenant') &&
                    ! auth()->user()->hasRole('super_admin') &&
                    isset($currentTenant) && $currentTenant?->fecha_vencimiento
                ) {
                    $diasVence = (int) now()->diffInDays($currentTenant->fecha_vencimiento, false);
                    $showSuscBanner = $diasVence <= 14;
                }
            } catch (\Exception $e) {}
        @endphp
        @if($showSuscBanner)
        @php
            if ($diasVence <= 0)       { $bColor = '#fef2f2'; $bBorder = '#fca5a5'; $bText = '#991b1b'; $bIcon = 'x-octagon-fill'; }
            elseif ($diasVence <= 3)   { $bColor = '#fef2f2'; $bBorder = '#fca5a5'; $bText = '#991b1b'; $bIcon = 'exclamation-octagon-fill'; }
            elseif ($diasVence <= 7)   { $bColor = '#fffbeb'; $bBorder = '#fcd34d'; $bText = '#92400e'; $bIcon = 'exclamation-triangle-fill'; }
            else                       { $bColor = '#eff6ff'; $bBorder = '#93c5fd'; $bText = '#1e40af'; $bIcon = 'info-circle-fill'; }
        @endphp
        <div style="background:{{ $bColor }};border-bottom:2px solid {{ $bBorder }};padding:.6rem 1.25rem;display:flex;align-items:center;gap:.85rem;flex-wrap:wrap;margin:-1.5rem -1.5rem 1.5rem;">
            <i class="bi bi-{{ $bIcon }}" style="font-size:1.1rem;color:{{ $bText }};flex-shrink:0;"></i>
            <div style="flex:1;min-width:200px;font-size:.82rem;font-weight:600;color:{{ $bText }};">
                @if($diasVence <= 0)
                    ¡Tu suscripción <strong>ha vencido hoy</strong>! El acceso puede suspenderse en cualquier momento.
                @elseif($diasVence === 1)
                    Tu suscripción vence <strong>mañana</strong>. Renueva para no perder el acceso.
                @else
                    Tu suscripción vence en <strong>{{ $diasVence }} días</strong> ({{ $currentTenant->fecha_vencimiento->format('d/m/Y') }}).
                @endif
            </div>
            <div class="d-flex align-items-center gap-2" style="flex-shrink:0;">
                <a href="mailto:soporte@zuraedu.com"
                   style="font-size:.75rem;font-weight:700;color:{{ $bText }};text-decoration:none;border:1px solid {{ $bBorder }};padding:.25rem .7rem;border-radius:6px;background:rgba(255,255,255,.6);">
                    <i class="bi bi-envelope me-1"></i>Renovar
                </a>
                <button type="button" onclick="this.closest('div[style]').remove()"
                    style="background:none;border:none;color:{{ $bText }};opacity:.6;cursor:pointer;font-size:1rem;padding:0 .2rem;">
                    ✕
                </button>
            </div>
        </div>
        @endif

        {{-- ── Flash de feature deshabilitada ────────────────────── --}}
        @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2 mb-3 rounded-3" role="alert" style="font-size:.88rem;">
            <i class="bi bi-shield-exclamation fs-5 flex-shrink-0"></i>
            <span>{{ session('warning') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @yield('content')

        {{-- ── FOOTER ─────────────────────────────────────────────── --}}
        <div style="margin-top:3rem;border-top:1px solid #e5e7eb;">
            <x-marca.pie />
        </div>
    </main>

    <!-- Bootstrap 5 JS — local -->
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Alpine.js — local -->
    <script defer src="{{ asset('vendor/alpinejs/alpine.min.js') }}"></script>

    {{-- dark mode ya aplicado en <head> para evitar FOUC --}}

    <script>
        // ── Sidebar toggle (mobile) ────────────────────
        const sidebar         = document.getElementById('sidebar');
        const overlay         = document.getElementById('sidebarOverlay');
        const hamburgerBtn    = document.getElementById('hamburgerBtn');

        function openSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
            document.body.style.overflow = '';
        }

        hamburgerBtn.addEventListener('click', () => {
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });

        overlay.addEventListener('click', closeSidebar);

        // Close sidebar on window resize above breakpoint
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 992) closeSidebar();
        });

        // ── Sidebar scroll persistence ─────────────────
        // Keeps scroll position when navigating between pages.
        // Only resets to active item if the user hasn't manually scrolled.
        (function() {
            const nav = document.querySelector('.sidebar-nav');
            if (!nav) return;
            const KEY = 'sge-sidebar-scroll';

            // Restore saved position immediately (before paint)
            // Always scroll active item into center on every load
            const active = nav.querySelector('.nav-item a.active');
            if (active) {
                const offset = active.getBoundingClientRect().top
                             - nav.getBoundingClientRect().top
                             + nav.scrollTop
                             - nav.clientHeight / 3;
                nav.scrollTop = Math.max(0, offset);
            } else if (sessionStorage.getItem(KEY) !== null) {
                nav.scrollTop = parseInt(sessionStorage.getItem(KEY), 10);
            }

            // Save scroll position on every link click (before unload)
            nav.addEventListener('click', e => {
                const link = e.target.closest('a[href]');
                if (link) {
                    sessionStorage.setItem(KEY, nav.scrollTop);
                    // Al entrar a un ítem del menú, colapsar el sidebar
                    // completo para dar más espacio a la página destino
                    // (en desktop — en móvil ya se cierra solo al navegar).
                    // Se vuelve a mostrar con el botón del topbar.
                    localStorage.setItem('sidebarCollapsed', '1');
                }
            });

            // Also save when browser navigates away (back/forward)
            window.addEventListener('pagehide', () => {
                sessionStorage.setItem(KEY, nav.scrollTop);
            });
        })();

        // ── Secciones del menú colapsables (auto-aplicado a cada
        // .nav-section-title + <ul> siguiente, sin tocar el HTML de cada
        // sección — reutiliza el mismo patrón .sidebar-submenu de abajo) ──
        (function () {
            // Icono por sección, por texto — evita tocar los 39 bloques
            // Blade uno por uno. "bi-collection" es el genérico de respaldo
            // para cualquier título nuevo que no esté en este mapa.
            const ICONOS_SECCION = {
                'Registro de Estudiantes':   'bi-person-vcard',
                'Matrículas y Grupos':       'bi-diagram-3',
                'Documentos y Registros':    'bi-folder2-open',
                'Reportes':                  'bi-bar-chart-line',
                'Exportación SIGERD':        'bi-cloud-arrow-up',
                'Comunicación':              'bi-chat-dots',
                'Documentos':                'bi-file-earmark-text',
                'Gestión Académica':         'bi-mortarboard',
                'Calificaciones':            'bi-journal-check',
                'Supervisión':               'bi-eye',
                'Planificación':             'bi-calendar2-week',
                'Pagos y Colegiaturas':      'bi-cash-coin',
                'Biblioteca':                'bi-book',
                'Estudiantes':               'bi-people',
                'Gestión Institucional':     'bi-building',
                'Mi Espacio':                'bi-house-door',
                'Rendimiento':               'bi-graph-up-arrow',
                'Planificación Docente':     'bi-clipboard-data',
                'Calendario':                'bi-calendar3',
                'Comunicados y Mensajes':    'bi-megaphone',
                'Inscripciones':             'bi-pencil-square',
                'Solicitudes':               'bi-inbox',
                'Servicios Institucionales': 'bi-gear-wide-connected',
                'Soporte':                   'bi-life-preserver',
                'Configuración':             'bi-sliders',
                'Página de Inicio':          'bi-globe',
                'Integraciones':             'bi-plug',
                'Sistema':                   'bi-hdd-stack',
                'ZuraEdu Platform':          'bi-stars',
            };

            const titles = document.querySelectorAll('.sidebar-nav .nav-section-title');
            titles.forEach((title, idx) => {
                const list = title.nextElementSibling;
                if (!list || list.tagName !== 'UL') return;

                // Por nombre y no por posición: el filtro por rol cambia las posiciones y el estado abierto/cerrado se mezclaba entre secciones
                const key = 'sidebarSection:' + title.textContent.trim();
                const hasActive = !!list.querySelector('a.active, a[aria-current="page"]');
                const stored = localStorage.getItem(key);
                const open = stored !== null ? stored === '1' : hasActive;

                const labelText = title.textContent.trim();
                const iconClass = ICONOS_SECCION[labelText] || 'bi-collection';
                const label = document.createElement('span');
                label.className = 'nav-section-label';
                label.innerHTML = '<i class="bi ' + iconClass + '"></i>' + labelText;
                title.textContent = '';
                title.appendChild(label);

                title.classList.add('nav-section-toggle');
                title.setAttribute('role', 'button');
                title.setAttribute('tabindex', '0');
                title.setAttribute('aria-expanded', open ? 'true' : 'false');

                const chevron = document.createElement('i');
                chevron.className = 'bi bi-chevron-down nav-section-chevron';
                title.appendChild(chevron);

                list.classList.add('sidebar-submenu');
                if (open) {
                    list.classList.add('sidebar-submenu-open');
                    chevron.style.transform = 'rotate(180deg)';
                }

                const toggle = () => {
                    const nowOpen = list.classList.toggle('sidebar-submenu-open');
                    title.setAttribute('aria-expanded', nowOpen ? 'true' : 'false');
                    chevron.style.transform = nowOpen ? 'rotate(180deg)' : '';
                    localStorage.setItem(key, nowOpen ? '1' : '0');
                };

                title.addEventListener('click', toggle);
                title.addEventListener('keydown', e => {
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
                });
            });
        })();

        // ── Toggle de sidebar completo (ocultar/mostrar en desktop) ───
        (function () {
            const btn  = document.getElementById('sidebarCollapseBtn');
            const icon = document.getElementById('sidebarCollapseIcon');
            if (!btn) return;

            function aplicar(colapsado) {
                document.documentElement.classList.toggle('sidebar-collapsed', colapsado);
                btn.setAttribute('aria-expanded', colapsado ? 'false' : 'true');
                if (icon) icon.className = colapsado ? 'bi bi-layout-sidebar' : 'bi bi-layout-sidebar-inset';
            }

            aplicar(document.documentElement.classList.contains('sidebar-collapsed'));

            btn.addEventListener('click', () => {
                const colapsado = !document.documentElement.classList.contains('sidebar-collapsed');
                aplicar(colapsado);
                localStorage.setItem('sidebarCollapsed', colapsado ? '1' : '0');
            });
        })();

        // ── Sidebar submenus (custom toggle, no Bootstrap Collapse) ───
        document.querySelectorAll('[data-sidebar-toggle]').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.dataset.sidebarToggle;
                const target   = document.getElementById(targetId);
                if (!target) return;
                const isOpen = target.classList.toggle('sidebar-submenu-open');
                btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                const chevron = btn.querySelector('.bi-chevron-down');
                if (chevron) chevron.style.transform = isOpen ? 'rotate(180deg)' : 'rotate(0deg)';
                if (isOpen) {
                    const nav = document.querySelector('.sidebar-nav');
                    if (nav) {
                        setTimeout(() => {
                            const btnBottom = btn.getBoundingClientRect().bottom;
                            const navBottom = nav.getBoundingClientRect().bottom;
                            if (btnBottom > navBottom - 60) {
                                nav.scrollBy({ top: target.scrollHeight + 24, behavior: 'smooth' });
                            }
                        }, 360);
                    }
                }
            });
        });

        // ── Dark mode toggle ──────────────────────────
        const darkBtn  = document.getElementById('darkToggleBtn');
        const darkIcon = document.getElementById('darkToggleIcon');
        function applyTheme(t) {
            document.documentElement.setAttribute('data-theme', t);
            if (darkIcon) {
                darkIcon.className = t === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
            }
        }
        applyTheme(localStorage.getItem('sge-theme') || 'light');
        if (darkBtn) {
            darkBtn.addEventListener('click', () => {
                const current = document.documentElement.getAttribute('data-theme');
                const next    = current === 'dark' ? 'light' : 'dark';
                localStorage.setItem('sge-theme', next);
                applyTheme(next);
            });
        }

        // ── Global Search ─────────────────────────────
        (function() {
            const input    = document.getElementById('globalSearchInput');
            const dropdown = document.getElementById('gsDropdown');
            if (!input || !dropdown) return;

            const ROUTE = '{{ route("admin.search") }}';
            let timer, activeIdx = -1, items = [];

            function render(results) {
                if (!results.length) {
                    dropdown.innerHTML = '<div class="gs-empty"><i class="bi bi-search me-1"></i>Sin resultados</div>';
                    dropdown.classList.add('open');
                    return;
                }
                const groups = {};
                results.forEach(r => { if (!groups[r.grupo]) groups[r.grupo] = []; groups[r.grupo].push(r); });
                let html = '';
                Object.keys(groups).forEach(g => {
                    html += `<div class="gs-group-header">${g}</div>`;
                    groups[g].forEach((r, i) => {
                        html += `<a href="${r.url}" class="gs-item" data-idx="${items.length}">
                            <div class="gs-item-icon" style="background:${r.color}"><i class="bi ${r.icon}"></i></div>
                            <div><div class="gs-item-label">${r.label}</div><div class="gs-item-sub">${r.sub}</div></div>
                        </a>`;
                        items.push(r);
                    });
                });
                dropdown.innerHTML = html;
                dropdown.classList.add('open');
                activeIdx = -1;
            }

            function close() { dropdown.classList.remove('open'); activeIdx = -1; items = []; }

            input.addEventListener('input', () => {
                clearTimeout(timer);
                const q = input.value.trim();
                items = [];
                if (q.length < 2) { close(); return; }
                timer = setTimeout(() => {
                    fetch(`${ROUTE}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => r.json()).then(d => render(d.results || []))
                        .catch(() => {});
                }, 280);
            });

            input.addEventListener('keydown', e => {
                const els = dropdown.querySelectorAll('.gs-item');
                if (e.key === 'ArrowDown') {
                    activeIdx = Math.min(activeIdx + 1, els.length - 1);
                    els.forEach((el, i) => el.classList.toggle('active', i === activeIdx));
                    e.preventDefault();
                } else if (e.key === 'ArrowUp') {
                    activeIdx = Math.max(activeIdx - 1, -1);
                    els.forEach((el, i) => el.classList.toggle('active', i === activeIdx));
                    e.preventDefault();
                } else if (e.key === 'Enter' && activeIdx >= 0 && els[activeIdx]) {
                    els[activeIdx].click();
                } else if (e.key === 'Escape') {
                    close(); input.blur();
                }
            });

            document.addEventListener('click', e => {
                if (!input.contains(e.target) && !dropdown.contains(e.target)) close();
            });
        })();
    </script>

    <script src="{{ asset('js/admin-layout-1.js') }}?v={{ @filemtime(public_path('js/admin-layout-1.js')) }}"></script>

    @stack('scripts')

    <div id="sge-toast-container" aria-live="polite" aria-atomic="false"></div>

    {{-- ── Chat interno del tenant ────────────────────────────────────────── --}}
    @auth
    <div id="tenant-chat-widget" style="position:fixed;bottom:1.5rem;right:5rem;z-index:9990;display:flex;flex-direction:column;align-items:flex-end;gap:.5rem;">
        {{-- Panel de chat --}}
        <div id="tenant-chat-panel"
             style="width:340px;max-height:480px;background:#fff;border-radius:18px;box-shadow:0 8px 32px rgba(0,0,0,.16);display:none;flex-direction:column;overflow:hidden;border:1px solid #e2e8f0;">
            {{-- Header --}}
            <div style="background:linear-gradient(135deg,#1e3a6e,#3B82F6);padding:.85rem 1rem;display:flex;align-items:center;gap:.6rem;">
                <div style="width:32px;height:32px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-people-fill" style="color:#fff;font-size:.9rem;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;color:#fff;font-size:.88rem;line-height:1.2;">Chat del Personal</div>
                    <div id="chat-online-count" style="font-size:.72rem;color:rgba(255,255,255,.7);">conectando...</div>
                </div>
                <button onclick="clearTenantChat()" style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:7px;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-size:.78rem;cursor:pointer;opacity:.8;flex-shrink:0;" title="Limpiar chat">
                    <i class="bi bi-trash3"></i>
                </button>
                <button onclick="toggleTenantChat()" style="background:none;border:none;color:#fff;opacity:.7;cursor:pointer;font-size:1.1rem;padding:.2rem;" title="Cerrar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            {{-- Mensajes --}}
            <div id="tenant-chat-messages"
                 style="flex:1;overflow-y:auto;padding:.75rem;display:flex;flex-direction:column;gap:.5rem;background:#f8fafc;min-height:200px;max-height:320px;">
            </div>
            {{-- Input --}}
            <div style="padding:.6rem .75rem;border-top:1px solid #e2e8f0;background:#fff;">
                <form id="tenant-chat-form" style="display:flex;gap:.5rem;align-items:center;">
                    @csrf
                    <input id="tenant-chat-input" type="text" placeholder="Escribe un mensaje..."
                           autocomplete="off" maxlength="2000"
                           style="flex:1;border:1px solid #e2e8f0;border-radius:10px;padding:.45rem .75rem;font-size:.83rem;outline:none;">
                    <button type="submit" style="background:#3B82F6;border:none;color:#fff;border-radius:10px;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;">
                        <i class="bi bi-send-fill" style="font-size:.8rem;"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- Botón flotante --}}
        <button id="tenant-chat-btn" onclick="toggleTenantChat()"
                style="width:52px;height:52px;background:linear-gradient(135deg,#1e3a6e,#3B82F6);border:none;border-radius:50%;color:#fff;box-shadow:0 4px 16px rgba(59,130,246,.5);cursor:pointer;display:flex;align-items:center;justify-content:center;position:relative;transition:transform .2s;"
                onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'"
                title="Chat del Personal">
            <i class="bi bi-chat-dots-fill" style="font-size:1.2rem;"></i>
            <span id="tenant-chat-badge" data-count="0"
                  style="display:none;position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;font-size:.6rem;font-weight:700;min-width:18px;height:18px;border-radius:99px;align-items:center;justify-content:center;border:2px solid #fff;"></span>
        </button>
    </div>

    <script>
    const _CHAT_ME_ID   = {{ auth()->id() }};
    const _CHAT_ME_NAME = '{{ Str::limit(auth()->user()->name ?? '', 20) }}';
    const _CHAT_URL     = '{{ route('admin.tenant-chat.index') }}';
    const _CHAT_CSRF    = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let   _chatLoaded   = false;

    function toggleTenantChat() {
        const panel = document.getElementById('tenant-chat-panel');
        const isOpen = panel.style.display !== 'none';

        if (!isOpen) {
            // Abrir siempre limpio
            const box = document.getElementById('tenant-chat-messages');
            if (box) box.innerHTML = '';
            _chatLoaded = true;
            panel.style.display = 'flex';
            panel.style.flexDirection = 'column';
            const badge = document.getElementById('tenant-chat-badge');
            if (badge) { badge.style.display = 'none'; badge.dataset.count = '0'; }
            setTimeout(() => document.getElementById('tenant-chat-input')?.focus(), 100);
        } else {
            panel.style.display = 'none';
        }
    }

    function clearTenantChat() {
        if (!confirm('¿Eliminar todos los mensajes del chat?')) return;
        fetch('{{ route('admin.tenant-chat.clear') }}', {
            method:  'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _CHAT_CSRF },
        })
        .then(r => r.json())
        .then(() => {
            const box = document.getElementById('tenant-chat-messages');
            if (box) box.innerHTML = '<div class="text-center text-muted small py-3">Chat limpiado.</div>';
            _chatLoaded = true;
        })
        .catch(() => {});
    }

    function loadChatHistory() {
        fetch(_CHAT_URL, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(msgs => {
                _chatLoaded = true;
                const box = document.getElementById('tenant-chat-messages');
                const loader = document.getElementById('chat-loading-msg');
                if (loader) loader.remove();
                msgs.forEach(m => appendChatBubble(m, false));
                scrollChatBottom();
            })
            .catch(() => { _chatLoaded = true; });
    }

    function appendChatBubble(data, scroll = true) {
        const box   = document.getElementById('tenant-chat-messages');
        if (!box) return;
        const isMio = data.user_id === _CHAT_ME_ID;
        const div   = document.createElement('div');
        div.style.cssText = `display:flex;flex-direction:column;align-items:${isMio ? 'flex-end' : 'flex-start'};gap:2px;`;
        div.innerHTML = `
            ${!isMio ? `<span style="font-size:.68rem;color:#64748b;font-weight:600;">${escHtml(data.user_name)}</span>` : ''}
            <div style="max-width:78%;background:${isMio ? '#3B82F6' : '#fff'};color:${isMio ? '#fff' : '#1e293b'};
                        border-radius:${isMio ? '14px 14px 4px 14px' : '14px 14px 14px 4px'};
                        padding:.45rem .75rem;font-size:.83rem;line-height:1.4;
                        box-shadow:0 1px 3px rgba(0,0,0,.08);">
                ${escHtml(data.mensaje)}
            </div>
            <span style="font-size:.63rem;color:#94a3b8;">${data.hora || data.tiempo}</span>`;
        box.appendChild(div);
        if (scroll) scrollChatBottom();
    }

    function scrollChatBottom() {
        const box = document.getElementById('tenant-chat-messages');
        if (box) box.scrollTop = box.scrollHeight;
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(str ?? ''));
        return d.innerHTML;
    }

    // Enviar mensaje
    document.getElementById('tenant-chat-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const input = document.getElementById('tenant-chat-input');
        const msg   = input.value.trim();
        if (!msg) return;
        input.value = '';

        fetch(_CHAT_URL, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': _CHAT_CSRF },
            body:    JSON.stringify({ mensaje: msg }),
        })
        .then(r => r.json())
        .then(data => appendChatBubble(data, true))
        .catch(() => {});
    });

    // Escuchar mensajes entrantes vía Echo
    window.addEventListener('tenant:chat-message', function(e) {
        const panel = document.getElementById('tenant-chat-panel');
        if (panel && panel.style.display !== 'none' && _chatLoaded) {
            if (e.detail.user_id !== _CHAT_ME_ID) {
                appendChatBubble(e.detail, true);
            }
        }
    });
    </script>
    @endauth

    <script src="{{ asset('js/admin-layout-2.js') }}?v={{ @filemtime(public_path('js/admin-layout-2.js')) }}"></script>

{{-- ════════════════════════════════════════════════════════════════
     CHATBOX IA — Gemini
════════════════════════════════════════════════════════════════ --}}
<link rel="stylesheet" href="{{ asset('css/admin-extra.css') }}?v={{ @filemtime(public_path('css/admin-extra.css')) }}">

{{-- Floating button --}}
<button id="chat-fab" title="Asistente IA" onclick="toggleChat()">
    <i class="bi bi-stars"></i>
    <span class="badge-dot"></span>
</button>

{{-- Chat window --}}
<div id="chat-window">
    <div id="chat-header">
        <div class="chat-avatar"><i class="bi bi-stars"></i></div>
        <div>
            <div class="chat-title">Zura — {{ $systemSettings['system_name'] ?? config('app.name') }}</div>
            <div class="chat-status">Powered by Google Gemini</div>
        </div>
        <div style="display:flex;align-items:center;gap:.35rem;margin-left:auto;">
            <button onclick="clearChat()" title="Limpiar conversación"
                    style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:7px;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-size:.78rem;cursor:pointer;opacity:.8;">
                <i class="bi bi-trash3"></i>
            </button>
            <button id="chat-close" onclick="toggleChat()" title="Cerrar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <div id="chat-messages"></div>

    {{-- Sugerencias rápidas --}}
    <div id="chat-suggestions" style="padding:.55rem .75rem;border-top:1px solid #e5e7eb;background:#f8faff;display:flex;gap:.4rem;flex-wrap:wrap;">
        <button class="chat-sug" onclick="useSuggestion(this)">¿Cómo registro asistencia?</button>
        <button class="chat-sug" onclick="useSuggestion(this)">¿Cómo genero un boletín?</button>
        <button class="chat-sug" onclick="useSuggestion(this)">¿Cómo matriculo un estudiante?</button>
        <button class="chat-sug" onclick="useSuggestion(this)">¿Cómo asigno un docente?</button>
        <button class="chat-sug" onclick="useSuggestion(this)">¿Cómo agrego calificaciones?</button>
        <button class="chat-sug" onclick="useSuggestion(this)">¿Cómo exporto un reporte?</button>
    </div>

    <div id="chat-input-area">
        <textarea id="chat-input"
                  rows="1"
                  placeholder="Escribe tu consulta..."
                  onkeydown="handleChatKey(event)"
                  oninput="autoResize(this)"></textarea>
        <button id="chat-send" onclick="sendChat()" title="Enviar">
            <i class="bi bi-send-fill"></i>
        </button>
    </div>
</div>

<script>
(function() {
    const ROUTE_CHAT = "{{ route('admin.chat.send') }}";
    const CSRF       = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    let chatOpen    = false;
    let isTyping    = false;
    let chatHistory = [];

    const WELCOME_MSG = '¡Hola! Soy <strong>Zura</strong>, el asistente de <strong>{{ $systemSettings['system_name'] ?? config('app.name') }}</strong>. Puedo ayudarte con el sistema: asistencia, calificaciones, matrículas, boletines y más. ¿En qué te ayudo?';

    function resetChat() {
        chatHistory = [];
        isTyping    = false;
        document.getElementById('chat-messages').innerHTML = '<div class="chat-msg bot">' + WELCOME_MSG + '</div>';
        document.getElementById('chat-suggestions').style.display = 'flex';
        document.getElementById('chat-send').disabled = false;
        const inp = document.getElementById('chat-input');
        if (inp) { inp.value = ''; inp.style.height = 'auto'; }
    }

    window.toggleChat = function() {
        chatOpen = !chatOpen;
        document.getElementById('chat-window').classList.toggle('open', chatOpen);
        if (chatOpen) {
            resetChat();
            setTimeout(() => document.getElementById('chat-input').focus(), 250);
        }
    };

    window.autoResize = function(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 100) + 'px';
    };

    window.handleChatKey = function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendChat();
        }
    };

    function renderMarkdown(text) {
        // Escapar HTML para evitar XSS, luego renderizar markdown básico
        const safe = text
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        return safe
            .replace(/^#{1,3}\s+(.+)$/gm, '<strong>$1</strong>')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/`([^`\n]+)`/g, '<code style="background:#f1f5f9;padding:.1em .35em;border-radius:4px;font-size:.82em;font-family:monospace;">$1</code>')
            .replace(/^[-•*]\s+(.+)$/gm, '• $1')
            .replace(/\n/g, '<br>');
    }

    function appendMsg(text, role) {
        const msgs = document.getElementById('chat-messages');
        const div  = document.createElement('div');
        div.className = 'chat-msg ' + role;
        if (role === 'bot') {
            div.innerHTML = renderMarkdown(text);
        } else {
            div.textContent = text;
        }
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
        return div;
    }

    function showTyping() {
        const msgs = document.getElementById('chat-messages');
        const div  = document.createElement('div');
        div.className = 'chat-msg typing';
        div.id = 'chat-typing';
        div.innerHTML = '<div class="typing-dots"><span></span><span></span><span></span></div>';
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
    }

    function removeTyping() {
        const t = document.getElementById('chat-typing');
        if (t) t.remove();
    }

    window.sendChat = async function() {
        if (isTyping) return;

        const input = document.getElementById('chat-input');
        const text  = input.value.trim();
        if (!text) return;

        appendMsg(text, 'user');
        // Snapshot del historial ANTES de agregar el mensaje actual
        // para no enviarlo duplicado al backend (history + message)
        const historySend = chatHistory.slice(-10);

        input.value = '';
        input.style.height = 'auto';
        document.getElementById('chat-send').disabled = true;
        isTyping = true;

        showTyping();

        try {
            const res = await fetch(ROUTE_CHAT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message: text, history: historySend }),
            });

            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }

            const data = await res.json();
            removeTyping();

            const reply = data.reply ?? 'Sin respuesta.';
            appendMsg(reply, 'bot');

            // Agregar al historial DESPUÉS de recibir respuesta
            chatHistory.push({ role: 'user', text });
            chatHistory.push({ role: 'model', text: reply });

            if (chatHistory.length > 20) chatHistory = chatHistory.slice(-20);

        } catch (err) {
            removeTyping();
            appendMsg('No se pudo obtener respuesta. Por favor intenta de nuevo.', 'bot');
        } finally {
            document.getElementById('chat-send').disabled = false;
            isTyping = false;
        }
    };

    window.clearChat = function() {
        resetChat();
    };

    window.useSuggestion = function(btn) {
        const text = btn.textContent.trim();
        document.getElementById('chat-suggestions').style.display = 'none';
        document.getElementById('chat-input').value = text;
        sendChat();
    };

    // Ocultar sugerencias cuando el usuario empieza a escribir
    document.getElementById('chat-input').addEventListener('input', function() {
        if (this.value.trim().length > 0) {
            document.getElementById('chat-suggestions').style.display = 'none';
        }
    });
})();
</script>

<!-- DataTables 2 + Scroller JS (requiere jQuery pese al build "sin jQuery" del CDN) -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/scroller/2.4.3/js/dataTables.scroller.min.js"></script>
<script src="{{ asset('js/admin-layout-3.js') }}?v={{ @filemtime(public_path('js/admin-layout-3.js')) }}"></script>

{{-- Polling alertas admin cada 60 segundos --}}
<script>
(function() {
    const CONTEO_URL = "{{ route('admin.alertas.conteo') }}";
    let lastCount    = {{ $alertasTopbar ?? 0 }};

    async function pollAlertas() {
        try {
            const res  = await fetch(CONTEO_URL, { headers: { 'Accept': 'application/json' } });
            if (! res.ok) return;
            const { total } = await res.json();
            const badge = document.getElementById('adminBellBadge');
            const bell  = document.getElementById('adminBell');
            if (! badge) return;

            if (total > 0) {
                badge.textContent = total > 9 ? '9+' : total;
                badge.style.display = 'flex';
                if (total > lastCount) {
                    bell.style.color = '#ef4444';
                    setTimeout(() => { bell.style.color = ''; }, 3000);
                }
            } else {
                badge.style.display = 'none';
            }
            lastCount = total;
        } catch (_) {}
    }

    setInterval(pollAlertas, 60000);
})();
</script>

{{-- ── ZuraEdu Realtime — Echo + Reverb ──────────────────────────────────── --}}
@auth
<script>
window._REVERB_KEY    = '{{ config("broadcasting.connections.reverb.key") }}';
window._REVERB_HOST   = '{{ config("broadcasting.connections.reverb.options.host") }}';
window._REVERB_PORT   = {{ config("broadcasting.connections.reverb.options.port", 8080) }};
window._REVERB_SCHEME = '{{ config("broadcasting.connections.reverb.options.scheme", "http") }}';
window._SGE_USER_ID   = {{ auth()->id() }};
window._SGE_ROL       = '{{ auth()->user()->roles->first()?->name ?? "" }}';
window._SGE_TENANT_ID = {{ tenant_id() ?? 'null' }};
window._SGE_GRUPO_IDS = [];
window._SGE_CLASE_IDS = [];
window._SGE_DEBUG     = {{ config('app.debug') ? 'true' : 'false' }};
</script>
@stack('realtime-data')
@vite('resources/js/echo.js')
<script>
// Actualizar UI global cuando llega DashboardActualizado
window.addEventListener('sge:dashboard-updated', function(e) {
    const data = e.detail;

    if (data.tipo === 'usuario_aprobado') {
        const badge = document.getElementById('admin-pending-badge');
        if (badge) {
            const n = data.datos?.usuarios_pendientes ?? 0;
            badge.textContent   = n;
            badge.style.display = n > 0 ? '' : 'none';
        }
    }

    if (data.tipo === 'nueva_matricula') {
        // Pulsa el botón Actualizar en el dashboard si estamos en esa página
        const btn = document.getElementById('btnRefreshStats');
        if (btn && !btn.disabled) btn.click();
    }
});
</script>
@endauth

@include('partials.pwa-install-prompt')

@auth
@php
    $__zuraAdmin = auth()->user()->hasAnyRole(['Administrador','Director','Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo','SuperAdmin']);
@endphp
@if(false) {{-- widget removido; ZuraAI disponible en /admin/asistente --}}
<link rel="stylesheet" href="{{ asset('css/admin-extra-2.css') }}?v={{ @filemtime(public_path('css/admin-extra-2.css')) }}">

<button id="zura-fab" title="ZuraAI — Asistente Institucional"><i class="bi bi-stars"></i></button>

<div id="zura-panel">
    <div id="zura-header">
        <div class="zura-avatar"><i class="bi bi-stars"></i></div>
        <div class="zura-info"><strong>ZuraAI</strong><small>Asistente Institucional</small></div>
        <button id="zura-close" title="Cerrar">&times;</button>
    </div>
    <div id="zura-msgs"></div>
    <div id="zura-chips">
        <button class="zchip">Interpretar rendimiento académico</button>
        <button class="zchip">Redactar circular oficial</button>
        <button class="zchip">Analizar estadísticas del período</button>
        <button class="zchip">Preparar informe SIGERD/MINERD</button>
    </div>
    <div id="zura-footer">
        <textarea id="zura-input" rows="1" placeholder="Escribe tu consulta institucional…"></textarea>
        <button id="zura-send"><i class="bi bi-send-fill"></i></button>
    </div>
</div>

<script>
(function(){
    const fab    = document.getElementById('zura-fab');
    const panel  = document.getElementById('zura-panel');
    const close  = document.getElementById('zura-close');
    const msgs   = document.getElementById('zura-msgs');
    const input  = document.getElementById('zura-input');
    const send   = document.getElementById('zura-send');
    const chips  = document.querySelectorAll('.zchip');
    const CHAT_URL = '{{ route("admin.asistente.chat") }}';
    const CSRF     = '{{ csrf_token() }}';
    let history    = [];
    let streaming  = false;

    const welcome = 'Hola, soy <strong>ZuraAI</strong>. Estoy aquí para asistirte en la gestión institucional: reportes, estadísticas, documentos oficiales y más. ¿En qué te ayudo hoy?';
    addMsg('bot', welcome);

    fab.addEventListener('click', () => { panel.classList.toggle('open'); if(panel.classList.contains('open')) input.focus(); });
    close.addEventListener('click', () => panel.classList.remove('open'));

    chips.forEach(c => c.addEventListener('click', () => { if(streaming) return; input.value = c.textContent.trim(); input.focus(); sendMsg(); }));

    input.addEventListener('keydown', e => { if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); sendMsg(); }});
    input.addEventListener('input', () => { input.style.height='auto'; input.style.height=Math.min(input.scrollHeight,120)+'px'; });
    send.addEventListener('click', sendMsg);

    function addMsg(role, html){
        const d = document.createElement('div');
        d.className = 'zmsg ' + (role==='bot' ? 'bot' : 'usr');
        d.innerHTML = html;
        msgs.appendChild(d);
        msgs.scrollTop = msgs.scrollHeight;
        return d;
    }

    function renderMd(t){
        return t
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/```[\w]*\n?([\s\S]*?)```/g,'<pre><code>$1</code></pre>')
            .replace(/`([^`]+)`/g,'<code>$1</code>')
            .replace(/^### (.+)$/gm,'<h3>$1</h3>')
            .replace(/^## (.+)$/gm,'<h2>$1</h2>')
            .replace(/^# (.+)$/gm,'<h1>$1</h1>')
            .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
            .replace(/^\* (.+)$/gm,'<li>$1</li>')
            .replace(/^- (.+)$/gm,'<li>$1</li>')
            .replace(/^\d+\. (.+)$/gm,'<li>$1</li>')
            .replace(/(<li>[\s\S]*?<\/li>)/g,'<ul>$1</ul>')
            .replace(/\n/g,'<br>');
    }

    async function sendMsg(){
        const text = input.value.trim();
        if(!text || streaming) return;
        input.value = ''; input.style.height = 'auto';
        addMsg('usr', text.replace(/</g,'&lt;'));
        streaming = true; send.disabled = true;

        const typing = addMsg('bot','<div class="ztyping"><span></span><span></span><span></span></div>');

        try {
            const res = await fetch(CHAT_URL, {
                method:'POST',
                headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'text/event-stream'},
                body: JSON.stringify({message: text, history})
            });
            if(!res.ok){ typing.innerHTML='<em>Error al conectar con ZuraAI.</em>'; return; }

            const reader = res.body.getReader();
            const dec    = new TextDecoder();
            let buf = '', out = '';
            typing.innerHTML = '';

            while(true){
                const {done, value} = await reader.read();
                if(done) break;
                buf += dec.decode(value, {stream:true});
                const lines = buf.split('\n');
                buf = lines.pop();
                for(const line of lines){
                    if(!line.startsWith('data:')) continue;
                    try {
                        const ev = JSON.parse(line.slice(5).trim());
                        if(ev.type==='content_block_delta' && ev.delta?.type==='text_delta'){
                            out += ev.delta.text;
                            typing.innerHTML = renderMd(out);
                            msgs.scrollTop = msgs.scrollHeight;
                        }
                        if(ev.type==='message_stop') break;
                    } catch(e){}
                }
            }
            if(out) history.push({role:'user',content:text},{role:'assistant',content:out});
            if(history.length > 20) history = history.slice(-20);
        } catch(e){
            typing.innerHTML = '<em>Error de conexión. Intenta de nuevo.</em>';
        } finally {
            streaming = false; send.disabled = false; input.focus();
        }
    }
})();
</script>
@endif
@endauth
</body>
</html>
