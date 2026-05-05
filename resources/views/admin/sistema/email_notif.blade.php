@extends('layouts.admin')
@section('page-title', 'Notificaciones por Email')

@push('styles')
<style>
.notif-card { background:#fff; border-radius:14px; border:1px solid #e5e7eb; padding:1.5rem; margin-bottom:1.25rem; }
.section-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--primary); border-bottom:2px solid var(--primary); padding-bottom:.4rem; margin-bottom:1.1rem; }
.toggle-row { display:flex; align-items:flex-start; justify-content:space-between; padding:.85rem 0; border-bottom:1px solid #f3f4f6; gap:1rem; }
.toggle-row:last-child { border-bottom:none; }
.toggle-label { font-size:.87rem; font-weight:600; color:#374151; margin:0; }
.toggle-desc  { font-size:.78rem; color:#6b7280; margin-top:.2rem; }
.form-check-input { width:2.5em; height:1.35em; cursor:pointer; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-envelope-check me-2"></i>Notificaciones por Email
        </h4>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">
            Configura qué emails automáticos se envían al personal, estudiantes y representantes.
        </p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-4" style="font-size:.83rem;border-radius:10px;">
    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
</div>
@endif

<form method="POST" action="{{ route('admin.sistema.email-notif.update') }}">
@csrf

<div class="notif-card">
    <div class="section-title">Notificaciones Académicas</div>

    <div class="toggle-row">
        <div>
            <div class="toggle-label">Email al publicar calificaciones</div>
            <div class="toggle-desc">Envía un email a representantes y estudiantes cuando un docente publica sus notas.</div>
        </div>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="email_notif_calificaciones"
                   id="sw_califs" value="1" {{ ($settings['email_notif_calificaciones'] ?? '1') === '1' ? 'checked' : '' }}>
        </div>
    </div>

    <div class="toggle-row">
        <div>
            <div class="toggle-label">Email de comunicados</div>
            <div class="toggle-desc">Envía un email a los destinatarios cada vez que se publica un comunicado.</div>
        </div>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="email_notif_comunicados"
                   id="sw_com" value="1" {{ ($settings['email_notif_comunicados'] ?? '1') === '1' ? 'checked' : '' }}>
        </div>
    </div>
</div>

<div class="notif-card">
    <div class="section-title">Notificaciones de Pagos</div>
    <div class="toggle-row">
        <div>
            <div class="toggle-label">Recordatorio semanal de pagos vencidos</div>
            <div class="toggle-desc">Cada lunes se envía un email a representantes con pagos vencidos. Solo si el módulo de pagos está activo.</div>
        </div>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="email_notif_pagos"
                   id="sw_pagos" value="1" {{ ($settings['email_notif_pagos'] ?? '1') === '1' ? 'checked' : '' }}>
        </div>
    </div>
</div>

<div class="notif-card">
    <div class="section-title">Notificaciones de Usuarios</div>
    <div class="toggle-row">
        <div>
            <div class="toggle-label">Email al aprobar usuario</div>
            <div class="toggle-desc">Cuando un administrador aprueba una solicitud de acceso, el usuario recibe un email de bienvenida.</div>
        </div>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="email_notif_aprobacion"
                   id="sw_apro" value="1" {{ ($settings['email_notif_aprobacion'] ?? '1') === '1' ? 'checked' : '' }}>
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary px-4 btn-sm">
        <i class="bi bi-check-lg me-1"></i>Guardar configuración
    </button>
    <a href="{{ route('admin.sistema.index') }}" class="btn btn-outline-secondary btn-sm">Cancelar</a>
</div>

</form>
@endsection
