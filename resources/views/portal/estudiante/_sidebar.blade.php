{{--
    Sidebar Portal Estudiante
    activeKey: dashboard | boletin | horario | asistencia | mi-riesgo | observaciones |
               comunicados | encuestas | classroom | tareas | eventos |
               mis-documentos | mensajes | mis-prestamos | mis-puntos |
               solicitudes | historial | constancia
--}}
@php $ak = $activeKey ?? 'dashboard'; @endphp

{{-- ── PRINCIPAL ── --}}
<div class="prt-sidebar-section">Mi Espacio</div>

<a href="{{ route('portal.estudiante.dashboard') }}"
   class="prt-sidebar-link {{ $ak === 'dashboard' ? 'active' : '' }}">
    <i class="bi bi-house-fill"></i>Inicio
</a>
<a href="{{ route('portal.estudiante.boletin') }}"
   class="prt-sidebar-link {{ $ak === 'boletin' ? 'active' : '' }}">
    <i class="bi bi-file-earmark-text-fill"></i>Mi Boletín
</a>
<a href="{{ route('portal.estudiante.horario') }}"
   class="prt-sidebar-link {{ $ak === 'horario' ? 'active' : '' }}">
    <i class="bi bi-calendar3"></i>Mi Horario
</a>
<a href="{{ route('portal.estudiante.asistencia') }}"
   class="prt-sidebar-link {{ $ak === 'asistencia' ? 'active' : '' }}">
    <i class="bi bi-clipboard-check"></i>Mi Asistencia
</a>
<a href="{{ route('portal.estudiante.mi-riesgo') }}"
   class="prt-sidebar-link {{ $ak === 'mi-riesgo' ? 'active' : '' }}">
    <i class="bi bi-shield-fill-check" style="{{ $ak === 'mi-riesgo' ? '' : 'color:#f59e0b;' }}"></i>Mi Situación
</a>
<a href="{{ route('portal.estudiante.observaciones') }}"
   class="prt-sidebar-link {{ $ak === 'observaciones' ? 'active' : '' }}">
    <i class="bi bi-chat-square-text"></i>Observaciones
</a>

{{-- ── AULA VIRTUAL ── --}}
<div class="prt-sidebar-section mt-2">Aula Virtual</div>

<a href="{{ route('portal.estudiante.classroom.index') }}"
   class="prt-sidebar-link {{ $ak === 'classroom' ? 'active' : '' }}">
    <i class="bi bi-easel2-fill"></i>Mi Classroom
</a>
<a href="{{ route('portal.estudiante.tareas') }}"
   class="prt-sidebar-link {{ $ak === 'tareas' ? 'active' : '' }}">
    <i class="bi bi-check2-square"></i>Mis Tareas
</a>
<a href="{{ route('portal.estudiante.plan-evaluacion') }}"
   class="prt-sidebar-link {{ $ak === 'plan-evaluacion' ? 'active' : '' }}">
    <i class="bi bi-bar-chart-steps"></i>Plan de Evaluación
</a>
<a href="{{ route('portal.estudiante.mis-recursos') }}"
   class="prt-sidebar-link {{ $ak === 'mis-recursos' ? 'active' : '' }}">
    <i class="bi bi-folder2-open"></i>Mis Recursos
</a>
<a href="{{ route('portal.estudiante.mis-rubricas') }}"
   class="prt-sidebar-link {{ $ak === 'rubricas' ? 'active' : '' }}">
    <i class="bi bi-grid-3x3-gap-fill"></i>Mis Rúbricas
</a>
<a href="{{ route('portal.estudiante.tutor-ia') }}"
   class="prt-sidebar-link {{ $ak === 'tutor-ia' ? 'active' : '' }}"
   style="{{ $ak === 'tutor-ia' ? '' : 'color:#0ea5e9;' }}">
    <i class="bi bi-robot"></i>Tutor IA
    <span style="background:linear-gradient(135deg,#0ea5e9,#6366f1);color:#fff;border-radius:99px;font-size:.58rem;padding:.1rem .38rem;font-weight:800;margin-left:auto;letter-spacing:.3px;">IA</span>
</a>
<a href="{{ route('portal.estudiante.evaluaciones.index') }}"
   class="prt-sidebar-link {{ $ak === 'evaluaciones' ? 'active' : '' }}"
   style="{{ $ak === 'evaluaciones' ? '' : 'color:#6366f1;' }}">
    <i class="bi bi-patch-question-fill"></i>Evaluaciones Online
    @php
    try {
        $__euid = auth()->id();
        $evaDisp = \Illuminate\Support\Facades\Cache::remember("user_{$__euid}_eva_disp", 120, function () {
            $user = auth()->user();
            $est  = $user->estudiante ?? null;
            if (!$est) return 0;
            $sy   = \App\Models\SchoolYear::actual();
            $mat  = \App\Models\Matricula::where('estudiante_id', $est->id)->where('estado','activa')
                ->when($sy, fn($q) => $q->where('school_year_id',$sy->id))->latest()->first();
            if (!$mat) return 0;
            $asgIds = \App\Models\Asignacion::where('grupo_id', $mat->grupo_id)->pluck('id');
            return \App\Models\EvaQuiz::whereIn('asignacion_id', $asgIds)->where('publicado', true)
                ->whereDoesntHave('intentos', fn($q) => $q->where('matricula_id',$mat->id)->where('estado','finalizado'))
                ->count();
        });
    } catch(\Exception $e){ $evaDisp = 0; }
    @endphp
    @if($evaDisp > 0)
    <span style="background:#6366f1;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $evaDisp }}</span>
    @endif
</a>

{{-- ── VIDA ESCOLAR ── --}}
<div class="prt-sidebar-section mt-2">Vida Escolar</div>

<a href="{{ route('portal.estudiante.calendario') }}"
   class="prt-sidebar-link {{ $ak === 'calendario' ? 'active' : '' }}">
    <i class="bi bi-calendar3"></i>Calendario Escolar
</a>
<a href="{{ route('portal.estudiante.comunicados') }}"
   class="prt-sidebar-link {{ $ak === 'comunicados' ? 'active' : '' }}">
    <i class="bi bi-megaphone-fill"></i>Noticias
</a>
<a href="{{ route('portal.estudiante.eventos') }}"
   class="prt-sidebar-link {{ $ak === 'eventos' ? 'active' : '' }}">
    <i class="bi bi-calendar-event-fill"></i>Eventos
</a>
<a href="{{ route('portal.estudiante.proyectos') }}"
   class="prt-sidebar-link {{ $ak === 'proyectos' ? 'active' : '' }}">
    <i class="bi bi-lightbulb-fill"></i>Proyectos
</a>
@php
try { $reconocimientosModule = !app()->bound('tenant') || (app()->bound('tenant') && app('tenant')?->can('reconocimientos')); } catch(\Exception $e){ $reconocimientosModule = true; }
$reconocimientosCount = 0;
try {
    $__est = auth()->user()->estudiante ?? null;
    if ($__est) $reconocimientosCount = \App\Models\Reconocimiento::where('estudiante_id', $__est->id)->count();
} catch(\Exception $e) {}
@endphp
@if($reconocimientosModule && $reconocimientosCount > 0)
<a href="{{ route('portal.estudiante.mis-reconocimientos') }}"
   class="prt-sidebar-link {{ $ak === 'reconocimientos' ? 'active' : '' }}">
    <i class="bi bi-trophy-fill" style="{{ $ak === 'reconocimientos' ? '' : 'color:#d97706;' }}"></i>Mis Reconocimientos
    <span style="background:#d97706;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $reconocimientosCount }}</span>
</a>
@endif
<a href="{{ route('portal.estudiante.encuestas') }}"
   class="prt-sidebar-link {{ $ak === 'encuestas' ? 'active' : '' }}">
    <i class="bi bi-clipboard-check-fill"></i>Encuestas
</a>

{{-- ── BIBLIOTECA ── --}}
<div class="prt-sidebar-section mt-2">Biblioteca</div>

<a href="{{ route('portal.estudiante.mis-prestamos') }}"
   class="prt-sidebar-link {{ $ak === 'mis-prestamos' ? 'active' : '' }}">
    <i class="bi bi-book-half"></i>Mis Préstamos
    @php
    try {
        $__eu = auth()->id();
        $prestActivos = \Illuminate\Support\Facades\Cache::remember("user_{$__eu}_prest_activos", 120, function () {
            $est = auth()->user()->estudiante ?? null;
            return $est ? \App\Models\PrestamoBiblioteca::where('estudiante_id', $est->id)->whereIn('estado',['activo','vencido'])->count() : 0;
        });
    } catch(\Exception $e){ $prestActivos = 0; }
    @endphp
    @if($prestActivos > 0)
    <span style="background:#3b82f6;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $prestActivos }}</span>
    @endif
</a>

{{-- ── GAMIFICACIÓN ── --}}
@php
try {
    $moduleGamif = !app()->bound('tenant') || auth()->user()?->hasRole('super_admin') || (app()->bound('tenant') && app('tenant')?->can('gamificacion'));
} catch(\Exception $e) { $moduleGamif = false; }
@endphp
@if($moduleGamif)
<div class="prt-sidebar-section mt-2">Logros</div>
<a href="{{ route('portal.estudiante.logros') }}"
   class="prt-sidebar-link {{ $ak === 'logros' ? 'active' : '' }}">
    <i class="bi bi-trophy-fill"></i>Mis Logros
</a>
<a href="{{ route('portal.estudiante.mis-puntos') }}"
   class="prt-sidebar-link {{ $ak === 'mis-puntos' ? 'active' : '' }}">
    <i class="bi bi-controller"></i>Mis Puntos
</a>
@endif

{{-- ── PAGOS / CAFETERÍA / TRANSPORTE ── --}}
@php
try { $modulePayments  = \App\Helpers\Setting::get('module_payments','0');  } catch(\Exception $e){ $modulePayments  = '0'; }
try { $moduleCafeteria = \App\Helpers\Setting::get('cafeteria','0');         } catch(\Exception $e){ $moduleCafeteria = '0'; }
try { $moduleTransport = \App\Helpers\Setting::get('transporte','0');        } catch(\Exception $e){ $moduleTransport = '0'; }
@endphp
@if($modulePayments || $moduleCafeteria || $moduleTransport)
<div class="prt-sidebar-section mt-2">Servicios</div>
@if($modulePayments)
<a href="{{ route('portal.estudiante.mis-pagos') }}"
   class="prt-sidebar-link {{ $ak === 'mis-pagos' ? 'active' : '' }}">
    <i class="bi bi-cash-coin"></i>Mis Pagos
    @php
    try {
        $__eu2 = auth()->id();
        $pagosPendientes = \Illuminate\Support\Facades\Cache::remember("user_{$__eu2}_pagos_pend", 300, function () {
            $est2 = auth()->user()->estudiante ?? null;
            if (! $est2) return 0;
            $mat2 = $est2->matriculas()->where('estado','activa')->latest()->value('id');
            return $mat2 ? \App\Models\Pago::where('matricula_id', $mat2)->whereIn('estado',['pendiente','vencido'])->count() : 0;
        });
    } catch(\Exception $e){ $pagosPendientes = 0; }
    @endphp
    @if($pagosPendientes > 0)
    <span style="background:#dc2626;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $pagosPendientes }}</span>
    @endif
</a>
@endif
@if($moduleCafeteria)
<a href="{{ route('portal.estudiante.mi-saldo-cafeteria') }}"
   class="prt-sidebar-link {{ $ak === 'cafeteria' ? 'active' : '' }}">
    <i class="bi bi-cup-hot-fill"></i>Mi Cafetería
</a>
@endif
@if($moduleTransport)
<a href="{{ route('portal.estudiante.mi-ruta-transporte') }}"
   class="prt-sidebar-link {{ $ak === 'transporte' ? 'active' : '' }}">
    <i class="bi bi-bus-front-fill"></i>Mi Transporte
</a>
@endif
@endif

{{-- ── GESTIONES ── --}}
<div class="prt-sidebar-section mt-2">Gestiones</div>
@if(auth()->user()->hasAnyRole(['Docente','Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo','Director','Administrador']))
<a href="{{ route('portal.docente.comint.index') }}"
   class="prt-sidebar-link {{ $ak === 'comint' ? 'active' : '' }}">
    <i class="bi bi-envelope-paper-fill"></i>Comunicados Internos
    @php
    try {
        $__uidCiEst = auth()->id();
        $comintUnreadEst = \Illuminate\Support\Facades\Cache::remember('t'.(tenant_id()??0).'_user_'.$__uidCiEst.'_comint_unread', 120, function () use ($__uidCiEst) {
            $user = auth()->user();
            if (!$user) return 0;
            $tipos = ['todos'];
            if ($user->hasRole('Docente')) $tipos[] = 'docentes';
            if ($user->hasAnyRole(['Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo','Director','Administrador'])) {
                $tipos[] = 'coordinadores'; $tipos[] = 'docentes';
            }
            return \App\Models\Comunicado::internos()->publicados()
                ->whereIn('tipo_destinatarios', $tipos)
                ->whereDoesntHave('lecturas', fn($q) => $q->where('user_id', $__uidCiEst))
                ->count();
        });
    } catch(\Exception $e){ $comintUnreadEst = 0; }
    @endphp
    @if($comintUnreadEst > 0)
    <span class="comint-badge-sb" style="background:#ef4444;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $comintUnreadEst }}</span>
    @endif
</a>
@endif

<a href="{{ route('portal.estudiante.mensajes.index') }}"
   class="prt-sidebar-link {{ $ak === 'mensajes' ? 'active' : '' }}">
    <i class="bi bi-envelope-fill"></i>Mensajes
    @php
    try {
        $__euMsg = auth()->id();
        $msgNoLeidos = \Illuminate\Support\Facades\Cache::remember("t".(tenant_id()??0)."_user_{$__euMsg}_msg_unread", 120, function () use ($__euMsg) {
            return \App\Models\MensajeDestinatario::where('destinatario_id', $__euMsg)
                ->whereNull('leido_at')->where('eliminado', false)->count();
        });
    } catch(\Exception $e){ $msgNoLeidos = 0; }
    @endphp
    @if($msgNoLeidos > 0)
    <span style="background:#2563eb;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $msgNoLeidos }}</span>
    @endif
</a>

<a href="{{ route('portal.estudiante.solicitudes.index') }}"
   class="prt-sidebar-link {{ $ak === 'solicitudes' ? 'active' : '' }}">
    <i class="bi bi-send-fill"></i>Mis Solicitudes
    @php try { $__eu3 = auth()->id(); $solEstPend = \Illuminate\Support\Facades\Cache::remember("user_{$__eu3}_sol_est_pend", 60, fn() => \App\Models\SolicitudEstudiante::where('estudiante_id', auth()->user()->estudiante?->id ?? 0)->where('estado','pendiente')->count()); } catch(\Exception $e){ $solEstPend=0; } @endphp
    @if($solEstPend > 0)
    <span style="background:#d97706;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $solEstPend }}</span>
    @endif
</a>

{{-- ── DOCUMENTOS ── --}}
<div class="prt-sidebar-section mt-2">Documentos</div>

<a href="{{ route('portal.estudiante.historial-academico') }}"
   class="prt-sidebar-link {{ $ak === 'historial' ? 'active' : '' }}">
    <i class="bi bi-clock-history"></i>Historial Académico
</a>
<a href="{{ route('portal.estudiante.constancia') }}"
   class="prt-sidebar-link {{ $ak === 'constancia' ? 'active' : '' }}">
    <i class="bi bi-file-earmark-arrow-down-fill"></i>Mi Constancia
</a>
<a href="{{ route('portal.estudiante.notificaciones') }}"
   class="prt-sidebar-link {{ $ak === 'notificaciones' ? 'active' : '' }}">
    <i class="bi bi-bell-fill"></i>Notificaciones
    @php try { $notifEst = \App\Models\Notificacion::where('user_id', auth()->id())->where('leida', false)->count(); } catch(\Exception $e){ $notifEst=0; } @endphp
    @if($notifEst > 0)
    <span style="background:#6366f1;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $notifEst }}</span>
    @endif
</a>
{{-- ── DIRECCIÓN ── --}}
@if(auth()->user()->hasAnyRole(['Administrador','Director','Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo']))
<div class="prt-sidebar-section mt-2">Dirección</div>
<a href="{{ route('admin.ejecutivo.index') }}" class="prt-sidebar-link {{ request()->routeIs('admin.ejecutivo*') ? 'active' : '' }}">
    <i class="bi bi-bar-chart-line-fill" style="color:#f59e0b;"></i>Dashboard Ejecutivo
</a>
<a href="{{ route('admin.rubricas.index') }}" class="prt-sidebar-link {{ request()->routeIs('admin.rubricas*') ? 'active' : '' }}">
    <i class="bi bi-grid-3x3-gap-fill"></i>Rúbricas
</a>
@endif

{{-- ── CUENTA ── --}}
<div class="prt-sidebar-section mt-3">Cuenta</div>
<a href="{{ route('perfil.show') }}"
   class="prt-sidebar-link {{ $ak === 'perfil' ? 'active' : '' }}">
    <i class="bi bi-person-circle"></i>Mi Perfil
</a>
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="prt-sidebar-link w-100 border-0" style="cursor:pointer;text-align:left;background:transparent;">
        <i class="bi bi-box-arrow-right" style="color:#ef4444;"></i>Cerrar sesión
    </button>
</form>
