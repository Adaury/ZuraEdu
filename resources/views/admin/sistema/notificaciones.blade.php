@extends('layouts.admin')
@section('page-title', 'Notificaciones In-app y Push')

@push('styles')
<style>
    .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
    .page-header h1 { font-size:1.45rem; font-weight:800; color:var(--primary); margin:0; }
    .card-panel { background:#fff; border-radius:14px; border:1px solid #e5e7eb; padding:1.5rem; margin-bottom:1.5rem; }
    .section-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--primary); border-bottom:2px solid var(--primary); padding-bottom:.4rem; margin-bottom:1.1rem; }
    .notif-toggle { display:flex; align-items:center; justify-content:space-between; padding:.75rem 1rem; border-radius:10px; background:#f8fafc; border:1px solid #e5e7eb; }
    .notif-toggle-label { font-size:.88rem; font-weight:600; }
    .notif-toggle-sub { font-size:.75rem; color:#6b7280; }

    [data-theme="dark"] .card-panel { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .notif-toggle { background: #162032; border-color: #334155; }

    .matriz { width:100%; }
    .matriz td, .matriz th { vertical-align: middle; padding:.7rem .6rem; border-bottom:1px solid #e5e7eb; }
    .matriz th { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#6b7280; text-align:center; }
    .matriz th:first-child { text-align:left; }
    .cat-nombre { display:flex; align-items:center; gap:.6rem; }
    .cat-icono { width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; flex-shrink:0; color:#fff; font-size:.9rem; }
    .cat-tipos { font-size:.72rem; color:#9ca3af; margin-top:.15rem; }
    [data-theme="dark"] .matriz td, [data-theme="dark"] .matriz th { border-color:#334155; }
    [data-theme="dark"] .cat-tipos { color:#64748b; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1><i class="bi bi-bell-fill me-2"></i>Notificaciones In-app y Push</h1>
        <p class="text-muted small mb-0">Elige qué categorías de notificación llegan a la campanita del sistema y al celular de cada usuario.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.sistema.whatsapp') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-whatsapp me-1"></i>WhatsApp</a>
        <a href="{{ route('admin.sistema.email-notif') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-envelope-check me-1"></i>Email</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 mb-3">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card-panel">
    <p class="small text-muted mb-0">
        <strong>In-app</strong> es la campanita y el historial de notificaciones del sistema — queda registrado para siempre y solo la institución puede apagarlo por categoría (el usuario final no puede borrarlo, es evidencia de que se avisó).
        <strong>Push</strong> es la alerta del celular — cada usuario puede además silenciarla por sí mismo desde <a href="{{ route('perfil.show') }}#notificaciones">su perfil</a>.
    </p>
</div>

<form method="POST" action="{{ route('admin.sistema.notificaciones.update') }}">
    @csrf

    <div class="card-panel">
        <div class="section-title"><i class="bi bi-grid-3x3-gap-fill me-1"></i>Matriz por categoría</div>

        <div class="table-responsive">
            <table class="matriz">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th style="width:120px;">In-app</th>
                        <th style="width:120px;">Push</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categorias as $clave => $meta)
                        @php($tipos = array_keys(array_filter(\App\Models\Notificacion::TIPO_CATEGORIA, fn($c) => $c === $clave)))
                        <tr>
                            <td>
                                <div class="cat-nombre">
                                    <div class="cat-icono" style="background:{{ $meta['color'] }};">
                                        <i class="bi {{ $meta['icono'] }}"></i>
                                    </div>
                                    <div>
                                        <div class="notif-toggle-label">{{ $meta['label'] }}</div>
                                        <div class="notif-toggle-sub">{{ $meta['desc'] }}</div>
                                        <div class="cat-tipos">{{ implode(', ', $tipos) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                @if(!empty($meta['inapp_bloqueado']))
                                    <span class="badge text-bg-secondary" title="Esta categoría incluye avisos críticos de cuenta (aprobación de acceso, mensajes directos, tickets) y no puede apagarse.">
                                        <i class="bi bi-lock-fill"></i> Siempre activo
                                    </span>
                                @else
                                    <input type="checkbox" class="form-check-input" name="inapp_{{ $clave }}" value="1"
                                           {{ ($settings["notif_inapp_{$clave}"] ?? '1') !== '0' ? 'checked' : '' }}
                                           style="width:2.5rem;height:1.4rem;">
                                @endif
                            </td>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input" name="push_{{ $clave }}" value="1"
                                       {{ ($settings["notif_push_{$clave}"] ?? '1') !== '0' ? 'checked' : '' }}
                                       style="width:2.5rem;height:1.4rem;">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save me-1"></i>Guardar configuración
    </button>
    <a href="{{ route('admin.sistema.index') }}" class="btn btn-outline-secondary">Cancelar</a>
</form>
@endsection
