@extends('layouts.admin')
@section('page-title', 'Notificaciones y Alertas')

@push('styles')
<style>
.notif-card   { background:#fff; border-radius:14px; border:1px solid #e5e7eb; padding:1.4rem 1.6rem; margin-bottom:1.1rem; }
.sec-title    { font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:var(--primary); border-bottom:2px solid var(--primary); padding-bottom:.35rem; margin-bottom:1.1rem; display:flex; align-items:center; gap:.4rem; }
.toggle-row   { display:flex; align-items:flex-start; justify-content:space-between; padding:.8rem 0; border-bottom:1px solid #f3f4f6; gap:1rem; }
.toggle-row:last-child { border-bottom:none; padding-bottom:0; }
.toggle-label { font-size:.87rem; font-weight:600; color:#374151; margin:0; }
.toggle-desc  { font-size:.78rem; color:#6b7280; margin-top:.2rem; }
.form-check-input { width:2.5em; height:1.35em; cursor:pointer; flex-shrink:0; }
.threshold-row { display:flex; align-items:center; gap:.75rem; padding:.65rem 0; border-bottom:1px solid #f3f4f6; }
.threshold-row:last-child { border-bottom:none; }
.threshold-row label { font-size:.85rem; font-weight:600; color:#374151; flex:1; margin:0; }
.threshold-row input  { width:90px; text-align:center; }
.threshold-desc { font-size:.75rem; color:#9ca3af; margin-left:.25rem; }

/* Trigger buttons */
.trigger-card { background:#f8fafc; border:1px solid #e5e7eb; border-radius:12px; padding:1rem 1.2rem; display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:.6rem; }
.trigger-card:last-child { margin-bottom:0; }
.trigger-info h6 { font-size:.84rem; font-weight:700; color:#374151; margin:0 0 .15rem; }
.trigger-info p  { font-size:.75rem; color:#6b7280; margin:0; }
.trigger-badge { font-size:.65rem; font-weight:700; padding:.2rem .5rem; border-radius:6px; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-bell-fill me-2"></i>Notificaciones y Alertas
        </h4>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">
            Configura los emails automáticos, umbrales de alerta y ejecución manual.
        </p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-4" style="font-size:.83rem;border-radius:10px;">
    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="alert alert-danger py-2 mb-4" style="font-size:.83rem;border-radius:10px;">
    <i class="bi bi-x-circle me-1"></i>{{ session('error') }}
</div>
@endif

{{-- ══ Sección 1: Configuración de emails ══ --}}
<form method="POST" action="{{ route('admin.sistema.email-notif.update') }}">
@csrf

<div class="row g-3">
<div class="col-lg-7">

{{-- Emails académicos --}}
<div class="notif-card">
    <div class="sec-title"><i class="bi bi-mortarboard-fill"></i>Académico</div>

    <div class="toggle-row">
        <div>
            <div class="toggle-label">Email al publicar calificaciones</div>
            <div class="toggle-desc">Envía un aviso a representantes y estudiantes cuando el docente publica sus notas.</div>
        </div>
        <input class="form-check-input" type="checkbox" name="email_notif_calificaciones"
               value="1" {{ ($settings['email_notif_calificaciones'] ?? '1') === '1' ? 'checked' : '' }}>
    </div>

    <div class="toggle-row">
        <div>
            <div class="toggle-label">Email de alerta académica al representante</div>
            <div class="toggle-desc">Cuando se detecta una calificación bajo el umbral, se envía email al representante del estudiante.</div>
        </div>
        <input class="form-check-input" type="checkbox" name="email_notif_alertas_academicas"
               value="1" {{ ($settings['email_notif_alertas_academicas'] ?? '1') === '1' ? 'checked' : '' }}>
    </div>

    <div class="toggle-row">
        <div>
            <div class="toggle-label">Email por ausencias repetidas</div>
            <div class="toggle-desc">Cuando el estudiante acumula N ausencias en la ventana de días, se notifica al representante.</div>
        </div>
        <input class="form-check-input" type="checkbox" name="email_notif_ausencias"
               value="1" {{ ($settings['email_notif_ausencias'] ?? '1') === '1' ? 'checked' : '' }}>
    </div>

    <div class="toggle-row">
        <div>
            <div class="toggle-label">Email de comunicados</div>
            <div class="toggle-desc">Notifica a los destinatarios cuando se publica un comunicado.</div>
        </div>
        <input class="form-check-input" type="checkbox" name="email_notif_comunicados"
               value="1" {{ ($settings['email_notif_comunicados'] ?? '1') === '1' ? 'checked' : '' }}>
    </div>
</div>

{{-- Emails de pagos y usuarios --}}
<div class="notif-card">
    <div class="sec-title"><i class="bi bi-credit-card-fill"></i>Pagos y Usuarios</div>

    <div class="toggle-row">
        <div>
            <div class="toggle-label">Recordatorio semanal de pagos vencidos</div>
            <div class="toggle-desc">Cada lunes, representantes con pagos vencidos reciben un recordatorio. (Requiere módulo de pagos activo.)</div>
        </div>
        <input class="form-check-input" type="checkbox" name="email_notif_pagos"
               value="1" {{ ($settings['email_notif_pagos'] ?? '1') === '1' ? 'checked' : '' }}>
    </div>

    <div class="toggle-row">
        <div>
            <div class="toggle-label">Email al aprobar usuario</div>
            <div class="toggle-desc">El usuario recibe un email de bienvenida cuando se aprueba su solicitud de acceso.</div>
        </div>
        <input class="form-check-input" type="checkbox" name="email_notif_aprobacion"
               value="1" {{ ($settings['email_notif_aprobacion'] ?? '1') === '1' ? 'checked' : '' }}>
    </div>
</div>

</div>{{-- col-lg-7 --}}

<div class="col-lg-5">

{{-- Umbrales --}}
<div class="notif-card">
    <div class="sec-title"><i class="bi bi-sliders"></i>Umbrales de Alerta</div>

    <div class="threshold-row">
        <label for="th_nota">Nota mínima para alerta</label>
        <div class="d-flex align-items-center gap-1">
            <input type="number" class="form-control form-control-sm" id="th_nota"
                   name="alerta_nota_minima" min="0" max="100" step="1"
                   value="{{ $settings['alerta_nota_minima'] ?? '60' }}">
            <span class="threshold-desc">pts</span>
        </div>
    </div>

    <div class="threshold-row">
        <label for="th_asist">Asistencia mínima</label>
        <div class="d-flex align-items-center gap-1">
            <input type="number" class="form-control form-control-sm" id="th_asist"
                   name="alerta_asistencia_minima" min="0" max="100" step="1"
                   value="{{ $settings['alerta_asistencia_minima'] ?? '75' }}">
            <span class="threshold-desc">%</span>
        </div>
    </div>

    <div class="threshold-row">
        <label for="th_aus">Ausencias para activar alerta</label>
        <div class="d-flex align-items-center gap-1">
            <input type="number" class="form-control form-control-sm" id="th_aus"
                   name="alerta_ausencias_consecutivas" min="1" max="30" step="1"
                   value="{{ $settings['alerta_ausencias_consecutivas'] ?? '3' }}">
            <span class="threshold-desc">faltas</span>
        </div>
    </div>

    <div class="threshold-row">
        <label for="th_ventana">Ventana de días (ausencias)</label>
        <div class="d-flex align-items-center gap-1">
            <input type="number" class="form-control form-control-sm" id="th_ventana"
                   name="alerta_ausencias_dias_ventana" min="1" max="60" step="1"
                   value="{{ $settings['alerta_ausencias_dias_ventana'] ?? '14' }}">
            <span class="threshold-desc">días</span>
        </div>
    </div>
</div>

{{-- Horario de alertas (info) --}}
<div class="notif-card">
    <div class="sec-title"><i class="bi bi-clock-fill"></i>Programación Automática</div>
    <div style="font-size:.8rem;color:#6b7280;line-height:1.8;">
        <div class="d-flex justify-content-between"><span>Alertas de ausencias</span><strong>05:30 diario</strong></div>
        <div class="d-flex justify-content-between"><span>Alertas de rendimiento</span><strong>06:00 diario</strong></div>
        <div class="d-flex justify-content-between"><span>Alertas académicas</span><strong>06:30 diario</strong></div>
        <div class="d-flex justify-content-between"><span>Alertas entrega notas</span><strong>07:00 diario</strong></div>
        <div class="d-flex justify-content-between"><span>Recordatorio pagos</span><strong>Lunes 08:00</strong></div>
    </div>
</div>

</div>{{-- col-lg-5 --}}
</div>

<div class="d-flex gap-2 mt-1 mb-4">
    <button type="submit" class="btn btn-primary btn-sm px-4">
        <i class="bi bi-check-lg me-1"></i>Guardar configuración
    </button>
    <a href="{{ route('admin.sistema.index') }}" class="btn btn-outline-secondary btn-sm">Cancelar</a>
</div>

</form>

{{-- ══ Sección 2: Ejecución manual ══ --}}
<div class="notif-card mt-2">
    <div class="sec-title"><i class="bi bi-play-circle-fill"></i>Ejecución Manual de Alertas</div>
    <p style="font-size:.8rem;color:#6b7280;margin-bottom:1rem;">
        Ejecuta los procesos de alertas inmediatamente, sin esperar la programación automática.
        Útil para pruebas o cuando necesitas notificar de urgencia.
    </p>

    <div class="trigger-card">
        <div class="trigger-info">
            <h6><i class="bi bi-calendar-x text-danger me-1"></i>Alertas de Ausencias</h6>
            <p>Detecta estudiantes con {{ $settings['alerta_ausencias_consecutivas'] ?? '3' }}+ ausencias en los últimos {{ $settings['alerta_ausencias_dias_ventana'] ?? '14' }} días y notifica a coordinación.</p>
        </div>
        <form method="POST" action="{{ route('admin.alertas.generarAusencias') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-danger">
                <i class="bi bi-play-fill me-1"></i>Ejecutar
            </button>
        </form>
    </div>

    <div class="trigger-card">
        <div class="trigger-info">
            <h6><i class="bi bi-bar-chart-fill text-warning me-1"></i>Alertas de Rendimiento</h6>
            <p>Busca estudiantes con nota final menor a {{ $settings['alerta_nota_minima'] ?? '60' }} pts en calificaciones publicadas.</p>
        </div>
        <form method="POST" action="{{ route('admin.alertas.generarRendimiento') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-warning text-dark">
                <i class="bi bi-play-fill me-1"></i>Ejecutar
            </button>
        </form>
    </div>

    <div class="trigger-card">
        <div class="trigger-info">
            <h6><i class="bi bi-mortarboard-fill text-primary me-1"></i>Alertas Académicas (AcademicAlertService)</h6>
            <p>Evalúa baja asistencia (&lt; {{ $settings['alerta_asistencia_minima'] ?? '75' }}%) y baja nota por asignación. Envía email al representante si está habilitado.</p>
        </div>
        <form method="POST" action="{{ route('admin.alertas.generarAcademicas') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-play-fill me-1"></i>Ejecutar
            </button>
        </form>
    </div>

    <div class="trigger-card">
        <div class="trigger-info">
            <h6><i class="bi bi-bell-fill text-info me-1"></i>Alertas de Entrega de Notas</h6>
            <p>Notifica a docentes sobre eventos de entrega de notas próximos (≤ 3 días).</p>
        </div>
        <form method="POST" action="{{ route('admin.alertas.generarEntregaNotas') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-info text-white">
                <i class="bi bi-play-fill me-1"></i>Ejecutar
            </button>
        </form>
    </div>

    <div class="trigger-card">
        <div class="trigger-info">
            <h6><i class="bi bi-credit-card-fill text-success me-1"></i>Recordatorio de Pagos Vencidos</h6>
            <p>Envía email/notificación interna a representantes con pagos vencidos en el año escolar actual.</p>
        </div>
        <form method="POST" action="{{ route('admin.alertas.recordatorioPagos') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-success">
                <i class="bi bi-play-fill me-1"></i>Ejecutar
            </button>
        </form>
    </div>
</div>

@endsection
