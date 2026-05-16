@extends('layouts.portal')
@section('page-title', 'Planificación Anual — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'planif-anual', 'asignacion' => $asignacion])
@endsection

@section('bottom-nav')
<a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item"><i class="bi bi-house-fill"></i>Inicio</a>
<a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item"><i class="bi bi-journal-check"></i>Notas</a>
<a href="{{ route('portal.docente.planif-anual.index', $asignacion) }}" class="prt-nav-item active"><i class="bi bi-map-fill"></i>Plan Anual</a>
@endsection

@section('content')

<div style="display:flex;align-items:center;gap:.7rem;margin-bottom:1.2rem;flex-wrap:wrap;">
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-map-fill" style="color:#0ea5e9;margin-right:.35rem;"></i>
            Planificación Anual — {{ $asignacion->asignatura?->nombre ?? '—' }}
        </h1>
        <div style="font-size:.72rem;color:#64748b;margin-top:.1rem;">
            {{ $asignacion->grupo?->grado?->nombre }} {{ $asignacion->grupo?->seccion?->nombre }}
            @if($schoolYear) &nbsp;·&nbsp; {{ $schoolYear->nombre }} @endif
        </div>
    </div>
    <button onclick="document.getElementById('modal-nuevo').style.display='flex'"
        style="background:#0ea5e9;color:#fff;border:none;border-radius:9px;padding:.48rem 1rem;font-size:.78rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:.35rem;">
        <i class="bi bi-plus-lg"></i>Nuevo Plan
    </button>
</div>

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;border-radius:10px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.83rem;color:#15803d;display:flex;align-items:center;gap:.5rem;">
    <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
</div>
@endif

@if($planes->isEmpty())
<div class="prt-card" style="text-align:center;padding:3rem;color:#94a3b8;">
    <i class="bi bi-map" style="font-size:2.5rem;display:block;margin-bottom:.75rem;color:#bae6fd;"></i>
    <p style="margin:0 0 .5rem;font-size:.9rem;font-weight:600;color:#475569;">Sin planes anuales</p>
    <p style="margin:0;font-size:.8rem;">Crea tu primer plan de unidades curriculares para esta asignatura.</p>
</div>
@else
<div style="display:flex;flex-direction:column;gap:.75rem;">
    @foreach($planes as $plan)
    <div class="prt-card" style="padding:1rem 1.1rem;display:flex;align-items:center;gap:.9rem;flex-wrap:wrap;">
        <div style="width:46px;height:46px;border-radius:10px;background:#e0f2fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-map-fill" style="font-size:1.2rem;color:#0284c7;"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <div style="font-weight:700;font-size:.9rem;color:#1e293b;">{{ $plan->titulo }}</div>
            <div style="font-size:.72rem;color:#64748b;margin-top:.1rem;">
                {{ $plan->unidades->count() }} unidade(s)
                @if($plan->descripcion)
                    &nbsp;·&nbsp; {{ Str::limit($plan->descripcion, 60) }}
                @endif
                &nbsp;·&nbsp; {{ $plan->created_at->format('d/m/Y') }}
            </div>
        </div>
        <div style="display:flex;gap:.45rem;flex-shrink:0;">
            <a href="{{ route('portal.docente.planif-anual.show', [$asignacion, $plan]) }}"
               style="background:#0ea5e9;color:#fff;border-radius:7px;padding:.35rem .75rem;font-size:.75rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;">
                <i class="bi bi-pencil-fill"></i>Editar
            </a>
            <a href="{{ route('portal.docente.planif-anual.pdf', [$asignacion, $plan]) }}"
               style="background:#0f172a;color:#fff;border-radius:7px;padding:.35rem .75rem;font-size:.75rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;">
                <i class="bi bi-file-earmark-pdf-fill"></i>PDF
            </a>
            <form method="POST" action="{{ route('portal.docente.planif-anual.destroy', [$asignacion, $plan]) }}"
                  onsubmit="return confirm('¿Eliminar este plan?')">
                @csrf @method('DELETE')
                <button type="submit"
                    style="background:#fee2e2;color:#dc2626;border:none;border-radius:7px;padding:.35rem .6rem;font-size:.75rem;font-weight:700;cursor:pointer;">
                    <i class="bi bi-trash3-fill"></i>
                </button>
            </form>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Modal nuevo plan --}}
<div id="modal-nuevo" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:14px;padding:1.5rem;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="font-size:.95rem;font-weight:800;margin-bottom:1rem;color:#1e293b;">
            <i class="bi bi-map-fill" style="color:#0ea5e9;margin-right:.4rem;"></i>Nuevo Plan Anual
        </div>
        <form method="POST" action="{{ route('portal.docente.planif-anual.store', $asignacion) }}">
            @csrf
            <div style="margin-bottom:.75rem;">
                <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;color:#475569;">Título del Plan *</label>
                <input type="text" name="titulo" required maxlength="200"
                    placeholder="Ej. Plan Anual 2026 — {{ $asignacion->asignatura?->nombre }}"
                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.85rem;font-family:inherit;">
            </div>
            <div style="margin-bottom:1.1rem;">
                <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;color:#475569;">Descripción (opcional)</label>
                <textarea name="descripcion" rows="2" placeholder="Nota o descripción..."
                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.82rem;font-family:inherit;resize:vertical;"></textarea>
            </div>
            <div style="display:flex;gap:.6rem;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('modal-nuevo').style.display='none'"
                    style="background:#f1f5f9;color:#475569;border:none;border-radius:8px;padding:.48rem 1rem;font-size:.8rem;font-weight:600;cursor:pointer;">
                    Cancelar
                </button>
                <button type="submit"
                    style="background:#0ea5e9;color:#fff;border:none;border-radius:8px;padding:.48rem 1.1rem;font-size:.8rem;font-weight:700;cursor:pointer;">
                    Crear Plan
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
