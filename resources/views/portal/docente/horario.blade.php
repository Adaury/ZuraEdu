@extends('layouts.portal')
@section('page-title', 'Mi Horario — ' . ($docente->nombre_completo ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    <div class="prt-sidebar-section">Mi Portal</div>
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-sidebar-link">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.horario') }}" class="prt-sidebar-link active">
        <i class="bi bi-calendar-week"></i>Mi Horario
    </a>
    <a href="{{ route('portal.docente.mensajes.index') }}" class="prt-sidebar-link">
        <i class="bi bi-envelope-fill"></i>Mensajes
    </a>
    @if(auth()->user()->hasAnyRole(['Administrador','Director','Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo']))
    <div class="prt-sidebar-section mt-2">Dirección</div>
    <a href="{{ route('admin.ejecutivo.index') }}" class="prt-sidebar-link {{ request()->routeIs('admin.ejecutivo*') ? 'active' : '' }}">
        <i class="bi bi-bar-chart-line-fill" style="color:#f59e0b;"></i>Dashboard Ejecutivo
    </a>
    <a href="{{ route('admin.rubricas.index') }}" class="prt-sidebar-link {{ request()->routeIs('admin.rubricas*') ? 'active' : '' }}">
        <i class="bi bi-grid-3x3-gap-fill"></i>Rúbricas
    </a>
    @endif
    <div class="prt-sidebar-section mt-2">Cuenta</div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="prt-sidebar-link w-100 border-0" style="cursor:pointer;text-align:left;">
            <i class="bi bi-box-arrow-right" style="color:#ef4444;"></i>Cerrar sesión
        </button>
    </form>
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.horario') }}" class="prt-nav-item active">
        <i class="bi bi-calendar3"></i>Horario
    </a>
    <a href="{{ route('portal.docente.notificaciones') }}" class="prt-nav-item">
        <i class="bi bi-bell-fill"></i>Notif.
    </a>
@endsection

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.5rem;">
    <h2 style="font-size:1rem;font-weight:800;margin:0;">
        <i class="bi bi-calendar-week me-2" style="color:#6366f1;"></i>Mi Horario Semanal
    </h2>
    <div style="display:flex;gap:.5rem;align-items:center;">
        @if($horarioActivo && !empty($gridHorario))
        <a href="{{ route('portal.docente.horario.pdf') }}" target="_blank"
           style="display:inline-flex;align-items:center;gap:.3rem;background:#1e3a6e;color:#fff;border-radius:7px;padding:.28rem .75rem;font-size:.72rem;font-weight:600;text-decoration:none;">
            <i class="bi bi-file-earmark-pdf-fill"></i> PDF
        </a>
        <a href="{{ route('portal.docente.horario.excel') }}"
           style="display:inline-flex;align-items:center;gap:.3rem;background:#15803d;color:#fff;border-radius:7px;padding:.28rem .75rem;font-size:.72rem;font-weight:600;text-decoration:none;">
            <i class="bi bi-file-earmark-excel-fill"></i> Excel
        </a>
        @endif
    </div>
</div>

<div class="prt-card">
    <div class="prt-card-body" style="padding:.5rem;">
        @if($horarioActivo && !empty($gridHorario))
        <div class="table-responsive">
        <table class="sch-table">
            <thead>
                <tr>
                    <th style="width:60px;">Hora</th>
                    @foreach($diasConfig as $dia)
                        <th>{{ ucfirst($dia === 'miercoles' ? 'Miércoles' : ucfirst($dia)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php
                    $palette  = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#f97316'];
                    $ci       = 0;
                    $colorMap = [];
                @endphp
                @foreach($franjasHorario as $franja)
                    @if($franja->es_recreo)
                    <tr class="sch-recreo">
                        <td colspan="{{ count($diasConfig) + 1 }}">
                            <i class="bi bi-cup-hot me-1"></i>Recreo
                            {{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }} –
                            {{ \Carbon\Carbon::parse($franja->hora_fin)->format('H:i') }}
                        </td>
                    </tr>
                    @else
                    <tr>
                        <td class="franja-col">
                            {{ $franja->nombre ?? 'F'.$franja->numero }}<br>
                            <span style="font-size:.6rem;color:#9ca3af;">
                                {{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }}
                            </span>
                        </td>
                        @foreach($diasConfig as $dia)
                        <td>
                            @if(isset($gridHorario[$franja->id][$dia]))
                                @php
                                    $d   = $gridHorario[$franja->id][$dia];
                                    $aId = $d->asignacion?->asignatura_id ?? 0;
                                    if (!isset($colorMap[$aId])) { $colorMap[$aId] = $palette[$ci % count($palette)]; $ci++; }
                                    $grp = $d->asignacion?->grupo;
                                @endphp
                                <div class="sch-cell" style="background:{{ $colorMap[$aId] }};">
                                    {{ \Illuminate\Support\Str::limit($d->asignacion?->asignatura?->nombre ?? '—', 16) }}
                                    @if($grp)
                                    <div style="font-size:.6rem;opacity:.85;">{{ $grp->nombre_completo ?? '' }}</div>
                                    @endif
                                    @if($d->aula)
                                    <div style="font-size:.6rem;opacity:.75;">{{ $d->aula->nombre }}</div>
                                    @endif
                                </div>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        </div>

        @elseif($asignaciones->isNotEmpty())
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:.6rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#1e40af;display:flex;align-items:center;gap:.5rem;">
            <i class="bi bi-info-circle-fill"></i>
            El horario detallado aún no ha sido publicado. Esta es tu carga académica asignada:
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.65rem;">
            @foreach($asignaciones as $asig)
            <div style="border-radius:10px;overflow:hidden;border:1px solid #e2e8f0;background:#fff;">
                <div style="background:#1e3a6e;padding:.5rem .75rem;">
                    <span style="color:#fff;font-size:.82rem;font-weight:700;">{{ $asig->asignatura?->nombre ?? '—' }}</span>
                </div>
                <div style="padding:.5rem .75rem;font-size:.78rem;color:#374151;">
                    <div><i class="bi bi-people-fill me-1" style="color:#6366f1;"></i>{{ $asig->grupo?->nombre_completo ?? '—' }}</div>
                </div>
            </div>
            @endforeach
        </div>

        @else
        <div style="text-align:center;padding:3rem;color:var(--prt-muted);">
            <i class="bi bi-calendar3-week" style="font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.4;"></i>
            El horario no está disponible aún.
        </div>
        @endif
    </div>
</div>

@endsection
