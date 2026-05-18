{{--
    Sidebar Portal Padre/Representante
    activeKey: dashboard | hijo | boletin | horario | asistencia | observaciones |
               classroom | planificaciones | documentos | logros | proyectos |
               cafeteria | transporte | comunicados | encuestas | mensajes
--}}
@php $ak = $activeKey ?? 'dashboard'; @endphp

{{-- ── PANEL PRINCIPAL ── --}}
<div class="prt-sidebar-section">Mi Panel</div>

<a href="{{ route('portal.padre.dashboard') }}"
   class="prt-sidebar-link {{ $ak === 'dashboard' ? 'active' : '' }}">
    <i class="bi bi-house-fill"></i>Inicio
</a>

{{-- ── SECCIÓN DEL HIJO (condicional) ── --}}
@if(isset($estudiante))
<div class="prt-sidebar-section mt-2" style="display:flex;align-items:center;gap:.4rem;">
    <i class="bi bi-person-circle" style="font-size:.75rem;"></i>
    {{ $estudiante->nombres }}
</div>

<a href="{{ route('portal.padre.hijo', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'hijo' ? 'active' : '' }}">
    <i class="bi bi-grid-1x2-fill"></i>Resumen
</a>
<a href="{{ route('portal.padre.hijo.boletin', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'boletin' ? 'active' : '' }}">
    <i class="bi bi-file-earmark-text-fill"></i>Boletín
</a>
<a href="{{ route('portal.padre.hijo.horario', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'horario' ? 'active' : '' }}">
    <i class="bi bi-calendar3"></i>Horario
</a>
<a href="{{ route('portal.padre.hijo.asistencia', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'asistencia' ? 'active' : '' }}">
    <i class="bi bi-clipboard-check"></i>Asistencia
</a>
<a href="{{ route('portal.padre.hijo.observaciones', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'observaciones' ? 'active' : '' }}">
    <i class="bi bi-chat-square-text"></i>Observaciones
</a>
<a href="{{ route('portal.padre.hijo.classroom.index', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'classroom' ? 'active' : '' }}">
    <i class="bi bi-easel2-fill"></i>Classroom
</a>
<a href="{{ route('portal.padre.hijo.planificaciones', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'planificaciones' ? 'active' : '' }}">
    <i class="bi bi-journal-bookmark-fill"></i>Planificaciones
</a>
<a href="{{ route('portal.padre.hijo.plan-evaluacion', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'plan-evaluacion' ? 'active' : '' }}">
    <i class="bi bi-bar-chart-steps"></i>Plan de Evaluación
</a>
<a href="{{ route('portal.padre.hijo.tareas', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'tareas' ? 'active' : '' }}">
    <i class="bi bi-check2-square"></i>Tareas
</a>
<a href="{{ route('portal.padre.hijo.rubricas', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'rubricas' ? 'active' : '' }}">
    <i class="bi bi-grid-3x3-gap-fill"></i>Rúbricas
</a>
<a href="{{ route('portal.padre.hijo.conducta', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'conducta' ? 'active' : '' }}">
    <i class="bi bi-stars"></i>Conducta
</a>
<a href="{{ route('portal.padre.hijo.documentos', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'documentos' ? 'active' : '' }}">
    <i class="bi bi-folder2-open"></i>Documentos
</a>
<a href="{{ route('portal.padre.hijo.logros', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'logros' ? 'active' : '' }}">
    <i class="bi bi-trophy-fill"></i>Reconocimientos
</a>
<a href="{{ route('portal.padre.hijo.proyectos', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'proyectos' ? 'active' : '' }}">
    <i class="bi bi-lightbulb-fill"></i>Proyectos
</a>
@php
try { $moduleCafeteria = \App\Helpers\Setting::get('cafeteria','0');  } catch(\Exception $e){ $moduleCafeteria = '0'; }
try { $moduleTransport = \App\Helpers\Setting::get('transporte','0'); } catch(\Exception $e){ $moduleTransport = '0'; }
@endphp
<a href="{{ route('portal.padre.hijo.estado-cuenta', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'estado-cuenta' ? 'active' : '' }}">
    <i class="bi bi-receipt"></i>Estado de Cuenta
    @php
    try {
        $pagosPad = \App\Models\Pago::whereHas('matricula', fn($m) =>
            $m->where('estudiante_id', $estudiante->id)->where('estado','activa')
        )->whereIn('estado',['pendiente','vencido'])->count();
    } catch(\Exception $e){ $pagosPad = 0; }
    @endphp
    @if($pagosPad > 0)
    <span style="background:#dc2626;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $pagosPad }}</span>
    @endif
</a>
@if($moduleCafeteria)
<a href="{{ route('portal.padre.hijo.cafeteria', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'cafeteria' ? 'active' : '' }}">
    <i class="bi bi-cup-hot-fill"></i>Cafetería
</a>
@endif
@if($moduleTransport)
<a href="{{ route('portal.padre.hijo.transporte', $estudiante) }}"
   class="prt-sidebar-link {{ $ak === 'transporte' ? 'active' : '' }}">
    <i class="bi bi-bus-front-fill"></i>Transporte
</a>
@endif
@endif

{{-- ── SOLICITUDES ── --}}
<div class="prt-sidebar-section mt-2">Gestiones</div>
<a href="{{ route('portal.padre.solicitudes.index') }}"
   class="prt-sidebar-link {{ $ak === 'solicitudes' ? 'active' : '' }}">
    <i class="bi bi-send-fill"></i>Mis Solicitudes
    @php try { $solPend = \App\Models\SolicitudRepresentante::whereHas('representante', fn($r) => $r->where('user_id', auth()->id()))->where('estado','pendiente')->count(); } catch(\Exception $e){ $solPend=0; } @endphp
    @if($solPend > 0)
    <span style="background:#d97706;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $solPend }}</span>
    @endif
</a>

{{-- ── COMUNICACIÓN ── --}}
<div class="prt-sidebar-section mt-2">Comunicación</div>

<a href="{{ route('portal.padre.calendario') }}"
   class="prt-sidebar-link {{ $ak === 'calendario' ? 'active' : '' }}">
    <i class="bi bi-calendar3"></i>Calendario Escolar
</a>
<a href="{{ route('portal.padre.notificaciones') }}"
   class="prt-sidebar-link {{ $ak === 'notificaciones' ? 'active' : '' }}">
    <i class="bi bi-bell-fill"></i>Notificaciones
    @php try { $notifPad = \App\Models\Notificacion::where('user_id', auth()->id())->where('leida', false)->count(); } catch(\Exception $e){ $notifPad=0; } @endphp
    @if($notifPad > 0)
    <span style="background:#6366f1;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $notifPad }}</span>
    @endif
</a>
<a href="{{ route('portal.padre.comunicados') }}"
   class="prt-sidebar-link {{ $ak === 'comunicados' ? 'active' : '' }}">
    <i class="bi bi-megaphone-fill"></i>Noticias
</a>
<a href="{{ route('portal.padre.encuestas') }}"
   class="prt-sidebar-link {{ $ak === 'encuestas' ? 'active' : '' }}">
    <i class="bi bi-clipboard-check-fill"></i>Encuestas
</a>
<a href="{{ route('portal.padre.mensajes.index') }}"
   class="prt-sidebar-link {{ $ak === 'mensajes' ? 'active' : '' }}">
    <i class="bi bi-envelope-fill"></i>Mensajes
    @php try { $__uid = auth()->id(); $msgPad = \Illuminate\Support\Facades\Cache::remember("user_{$__uid}_msg_unread", 60, fn() => \App\Models\MensajeDestinatario::where('destinatario_id',$__uid)->whereNull('leido_at')->where('eliminado',false)->count()); } catch(\Exception $e){ $msgPad=0; } @endphp
    @if($msgPad > 0)
    <span style="background:#dc2626;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $msgPad }}</span>
    @endif
</a>
@if(auth()->user()->hasAnyRole(['Docente','Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo','Director','Administrador']))
<a href="{{ route('portal.docente.comint.index') }}"
   class="prt-sidebar-link {{ $ak === 'comint' ? 'active' : '' }}">
    <i class="bi bi-envelope-paper-fill"></i>Comunicados Internos
    @php
    try {
        $__uidCiPad = auth()->id();
        $comintUnreadPad = \Illuminate\Support\Facades\Cache::remember('t'.(tenant_id()??0).'_user_'.$__uidCiPad.'_comint_unread', 120, function () use ($__uidCiPad) {
            $user = auth()->user();
            if (!$user) return 0;
            $tipos = ['todos'];
            if ($user->hasRole('Docente')) $tipos[] = 'docentes';
            if ($user->hasAnyRole(['Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo','Director','Administrador'])) {
                $tipos[] = 'coordinadores'; $tipos[] = 'docentes';
            }
            return \App\Models\Comunicado::internos()->publicados()
                ->whereIn('tipo_destinatarios', $tipos)
                ->whereDoesntHave('lecturas', fn($q) => $q->where('user_id', $__uidCiPad))
                ->count();
        });
    } catch(\Exception $e){ $comintUnreadPad = 0; }
    @endphp
    @if($comintUnreadPad > 0)
    <span class="comint-badge-sb" style="background:#ef4444;color:#fff;border-radius:99px;font-size:.6rem;padding:.1rem .38rem;font-weight:700;margin-left:auto;">{{ $comintUnreadPad }}</span>
    @endif
</a>
@endif

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
