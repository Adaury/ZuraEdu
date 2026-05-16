@extends('layouts.portal')
@section('page-title', 'Comunicado — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'comunicado'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.estudiantes', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-people-fill"></i>Estudiantes
    </a>
    <a href="{{ route('portal.docente.comunicado', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-megaphone-fill"></i>Comunicado
    </a>
@endsection

@section('content')

{{-- Cabecera --}}
<div style="display:flex;align-items:flex-start;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;flex-shrink:0;margin-top:.1rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-megaphone-fill" style="color:#2563eb;"></i>
            Comunicado al Grupo
        </h1>
        <div style="font-size:.75rem;color:#64748b;margin-top:.15rem;">
            {{ $asignacion->asignatura?->nombre }} &mdash; {{ $asignacion->grupo?->nombre_completo ?? '—' }}
            @if($schoolYear) · {{ $schoolYear->nombre }} @endif
        </div>
    </div>
</div>

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;padding:.65rem 1rem;margin-bottom:1rem;font-size:.82rem;color:#15803d;display:flex;align-items:center;gap:.5rem;">
    <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
</div>
@endif

{{-- Formulario de redacción --}}
<div class="prt-card" style="margin-bottom:1rem;">
    <div class="prt-card-header">
        <i class="bi bi-pencil-square" style="color:#2563eb;"></i>
        <h3>Redactar comunicado</h3>
    </div>

    <form method="POST" action="{{ route('portal.docente.comunicado.enviar', $asignacion) }}" style="padding:1rem;">
        @csrf

        @if($errors->any())
        <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:.65rem 1rem;margin-bottom:1rem;font-size:.82rem;color:#991b1b;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            {{ $errors->first() }}
        </div>
        @endif

        {{-- Info destinatarios --}}
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:.65rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#1d4ed8;display:flex;align-items:center;gap:.5rem;">
            <i class="bi bi-people-fill"></i>
            Este comunicado se enviará a los <strong>representantes</strong> de todos los estudiantes del grupo
            <strong>{{ $asignacion->grupo?->nombre_completo }}</strong>
            y aparecerá en su portal.
        </div>

        <div style="margin-bottom:.9rem;">
            <label style="font-size:.75rem;font-weight:700;color:#374151;display:block;margin-bottom:.3rem;">
                Asunto <span style="color:#dc2626;">*</span>
            </label>
            <input type="text" name="titulo" required maxlength="200"
                   value="{{ old('titulo') }}"
                   placeholder="Ej: Prueba de la próxima semana, Recordatorio de uniforme..."
                   style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.45rem .75rem;font-size:.88rem;color:#1e293b;">
        </div>

        <div style="margin-bottom:1rem;">
            <label style="font-size:.75rem;font-weight:700;color:#374151;display:block;margin-bottom:.3rem;">
                Mensaje <span style="color:#dc2626;">*</span>
            </label>
            <textarea name="cuerpo" required maxlength="3000" rows="6"
                      placeholder="Redacte el comunicado aquí..."
                      style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.5rem .75rem;font-size:.85rem;color:#1e293b;resize:vertical;">{{ old('cuerpo') }}</textarea>
            <div style="font-size:.68rem;color:#94a3b8;margin-top:.2rem;">Máximo 3000 caracteres.</div>
        </div>

        <div style="display:flex;justify-content:flex-end;">
            <button type="submit"
                    style="background:linear-gradient(135deg,#1d4ed8,#2563eb);color:#fff;border:none;border-radius:9px;padding:.55rem 1.6rem;font-size:.85rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.5rem;">
                <i class="bi bi-send-fill"></i>Enviar comunicado
            </button>
        </div>
    </form>
</div>

{{-- Historial de comunicados enviados --}}
<div class="prt-card">
    <div class="prt-card-header">
        <i class="bi bi-clock-history" style="color:#7c3aed;"></i>
        <h3>Comunicados enviados</h3>
        <span style="margin-left:auto;font-size:.72rem;color:#64748b;">últimos {{ $comunicados->count() }}</span>
    </div>

    @if($comunicados->isEmpty())
    <div style="padding:2rem;text-align:center;color:#94a3b8;font-size:.85rem;">
        <i class="bi bi-megaphone" style="font-size:1.8rem;display:block;margin-bottom:.6rem;color:#cbd5e1;"></i>
        Aún no has enviado comunicados a este grupo.
    </div>
    @else
    <div>
        @foreach($comunicados as $com)
        <div style="padding:.8rem 1rem;border-bottom:1px solid #f1f5f9;" x-data="{ open: false }">
            <div style="display:flex;align-items:flex-start;gap:.6rem;">
                <div style="width:34px;height:34px;border-radius:50%;background:#eff6ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:.1rem;">
                    <i class="bi bi-megaphone-fill" style="color:#2563eb;font-size:.8rem;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:.83rem;font-weight:700;color:#1e293b;">{{ $com->titulo }}</div>
                    <div style="font-size:.7rem;color:#64748b;margin-top:.1rem;">
                        <i class="bi bi-calendar3 me-1"></i>{{ $com->created_at->format('d/m/Y H:i') }}
                    </div>
                    <div style="font-size:.78rem;color:#374151;margin-top:.4rem;line-height:1.45;display:none;" id="cuerpo-{{ $com->id }}">
                        {{ $com->cuerpo }}
                    </div>
                </div>
                <button type="button"
                        onclick="toggleCuerpo({{ $com->id }}, this)"
                        style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:7px;padding:.25rem .6rem;font-size:.7rem;color:#64748b;cursor:pointer;flex-shrink:0;white-space:nowrap;">
                    Ver
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
function toggleCuerpo(id, btn) {
    const el = document.getElementById('cuerpo-' + id);
    if (!el) return;
    const showing = el.style.display !== 'none';
    el.style.display = showing ? 'none' : 'block';
    btn.textContent = showing ? 'Ver' : 'Ocultar';
}
</script>
@endpush
