@extends('layouts.portal')
@section('page-title', 'Mis Solicitudes')
@section('portal-name', 'Portal del Representante')

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'solicitudes'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.padre.dashboard') }}" class="prt-nav-item"><i class="bi bi-house-fill"></i>Inicio</a>
    <a href="{{ route('portal.padre.solicitudes.index') }}" class="prt-nav-item active"><i class="bi bi-send-fill"></i>Solicitudes</a>
    <a href="{{ route('portal.padre.comunicados') }}" class="prt-nav-item"><i class="bi bi-megaphone"></i>Noticias</a>
    <a href="{{ route('portal.padre.notificaciones') }}" class="prt-nav-item"><i class="bi bi-bell"></i>Notif.</a>
@endsection

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap;">
    <div>
        <h1 style="font-size:1.1rem;font-weight:800;margin:0;color:#1e3a6e;"><i class="bi bi-send-fill me-2"></i>Mis Solicitudes</h1>
        <p style="font-size:.78rem;color:#64748b;margin:.2rem 0 0;">Justificaciones, citas y pedidos al centro educativo</p>
    </div>
    <a href="{{ route('portal.padre.solicitudes.create') }}"
       style="background:#1e3a6e;color:#fff;border-radius:10px;padding:.55rem 1.1rem;font-size:.82rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;white-space:nowrap;">
        <i class="bi bi-plus-lg"></i>Nueva Solicitud
    </a>
</div>

@if(session('success'))
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.83rem;color:#15803d;">
    <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
</div>
@endif

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:1.25rem;">
    <div class="prt-card" style="text-align:center;padding:.9rem;">
        <div style="font-size:1.5rem;font-weight:900;color:#d97706;">{{ $pendientes }}</div>
        <div style="font-size:.68rem;font-weight:600;text-transform:uppercase;color:#9ca3af;letter-spacing:.04em;">Pendientes</div>
    </div>
    <div class="prt-card" style="text-align:center;padding:.9rem;">
        <div style="font-size:1.5rem;font-weight:900;color:#1e3a6e;">{{ $solicitudes->total() }}</div>
        <div style="font-size:.68rem;font-weight:600;text-transform:uppercase;color:#9ca3af;letter-spacing:.04em;">Total</div>
    </div>
    <div class="prt-card" style="text-align:center;padding:.9rem;">
        <div style="font-size:1.5rem;font-weight:900;color:#16a34a;">{{ $solicitudes->where('estado','aprobada')->count() }}</div>
        <div style="font-size:.68rem;font-weight:600;text-transform:uppercase;color:#9ca3af;letter-spacing:.04em;">Aprobadas</div>
    </div>
</div>

@forelse($solicitudes as $sol)
@php $ec = $sol->estado_config; @endphp
<a href="{{ route('portal.padre.solicitudes.show', $sol) }}" style="display:block;text-decoration:none;margin-bottom:.7rem;">
    <div class="prt-card" style="padding:1rem 1.1rem;display:flex;align-items:flex-start;gap:.9rem;transition:box-shadow .15s;" onmouseover="this.style.boxShadow='0 4px 14px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow=''">
        <div style="width:40px;height:40px;border-radius:10px;background:{{ $ec['bg'] }};border:1px solid {{ $ec['color'] }}22;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-{{ $sol->tipo === 'justificacion_ausencia' ? 'calendar-x' : ($sol->tipo === 'cita_docente' ? 'person-lines-fill' : ($sol->tipo === 'solicitar_documento' ? 'file-earmark-text' : 'envelope-paper')) }}" style="font-size:1rem;color:{{ $ec['color'] }};"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <div style="font-size:.88rem;font-weight:700;color:#1e293b;margin-bottom:.15rem;">{{ $sol->asunto }}</div>
            <div style="font-size:.74rem;color:#64748b;display:flex;gap:.75rem;flex-wrap:wrap;">
                <span><i class="bi bi-tag me-1"></i>{{ $sol->tipo_label }}</span>
                @if($sol->estudiante)
                <span><i class="bi bi-person me-1"></i>{{ $sol->estudiante->nombre_completo }}</span>
                @endif
                <span><i class="bi bi-clock me-1"></i>{{ $sol->created_at->diffForHumans() }}</span>
            </div>
        </div>
        <span style="background:{{ $ec['bg'] }};color:{{ $ec['color'] }};border:1px solid {{ $ec['color'] }}33;border-radius:99px;font-size:.65rem;font-weight:700;padding:.2rem .6rem;white-space:nowrap;flex-shrink:0;">
            {{ $ec['label'] }}
        </span>
    </div>
</a>
@empty
<div class="prt-card" style="text-align:center;padding:2.5rem 1rem;">
    <i class="bi bi-send" style="font-size:2.5rem;color:#cbd5e1;display:block;margin-bottom:.75rem;"></i>
    <div style="font-weight:700;color:#475569;margin-bottom:.4rem;">Sin solicitudes aún</div>
    <div style="font-size:.8rem;color:#94a3b8;margin-bottom:1rem;">Puedes enviar justificaciones de ausencia, solicitar citas y más.</div>
    <a href="{{ route('portal.padre.solicitudes.create') }}"
       style="background:#1e3a6e;color:#fff;border-radius:8px;padding:.5rem 1.2rem;font-size:.82rem;font-weight:700;text-decoration:none;">
        Crear primera solicitud
    </a>
</div>
@endforelse

{{ $solicitudes->links() }}

@endsection
