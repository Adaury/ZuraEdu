@extends('layouts.portal')
@section('page-title', 'Mis Rúbricas')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'rubricas'])
@endsection

@section('bottom-nav')
<a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item"><i class="bi bi-house-fill"></i>Inicio</a>
<a href="{{ route('portal.docente.rubricas.index') }}" class="prt-nav-item active"><i class="bi bi-table"></i>Rúbricas</a>
@endsection

@push('styles')
<style>
.rub-card {
    background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;
    padding:1rem 1.1rem;margin-bottom:.7rem;
    border-left:4px solid #ec4899;transition:box-shadow .15s;
}
.rub-card:hover { box-shadow:0 3px 14px rgba(236,72,153,.1); }
.modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center; }
.modal-overlay.active { display:flex; }
.modal-box { background:#fff;border-radius:14px;padding:1.5rem;width:100%;max-width:460px;box-shadow:0 20px 60px rgba(0,0,0,.2); }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;margin-bottom:1.25rem;">
    <div>
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-table me-2" style="color:#ec4899;"></i>Rúbricas de Evaluación
        </h1>
        <p style="font-size:.75rem;color:#64748b;margin:.2rem 0 0;">{{ $rubricas->count() }} rúbrica{{ $rubricas->count() !== 1 ? 's' : '' }} creadas</p>
    </div>
    <button onclick="document.getElementById('modalCrear').classList.add('active')"
        style="background:#ec4899;color:#fff;border:none;border-radius:8px;padding:.5rem 1rem;font-size:.8rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-plus-lg"></i>Nueva Rúbrica
    </button>
</div>

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:.6rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#166534;">
    <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
</div>
@endif

@forelse($rubricas as $r)
<div class="rub-card">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;flex-wrap:wrap;">
        <div style="flex:1;min-width:0;">
            <div style="font-weight:800;font-size:.9rem;margin-bottom:.2rem;">{{ $r->titulo }}</div>
            <div style="display:flex;gap:.6rem;flex-wrap:wrap;font-size:.72rem;color:#64748b;">
                @if($r->asignatura)
                    <span><i class="bi bi-book me-1"></i>{{ $r->asignatura->nombre }}</span>
                @endif
                <span><i class="bi bi-list-ul me-1"></i>{{ count($r->criterios) }} criterios</span>
                <span><i class="bi bi-grid me-1"></i>{{ count($r->niveles) }} niveles</span>
                <span><i class="bi bi-star me-1"></i>{{ $r->puntaje_max }} pts</span>
                @if($r->aplicaciones_count > 0)
                    <span style="color:#ec4899;"><i class="bi bi-check2-all me-1"></i>{{ $r->aplicaciones_count }} aplicaciones</span>
                @endif
            </div>
            @if($r->descripcion)
                <p style="font-size:.78rem;color:#475569;margin:.35rem 0 0;">{{ Str::limit($r->descripcion, 100) }}</p>
            @endif
        </div>
        <div style="display:flex;gap:.35rem;flex-shrink:0;flex-wrap:wrap;">
            <a href="{{ route('portal.docente.rubricas.show', $r) }}"
               style="background:#fce7f3;color:#be185d;border:none;border-radius:7px;padding:.32rem .65rem;font-size:.78rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;">
                <i class="bi bi-pencil-fill"></i>Editar
            </a>
            <a href="{{ route('portal.docente.rubricas.aplicar', $r) }}"
               style="background:#ec4899;color:#fff;border:none;border-radius:7px;padding:.32rem .65rem;font-size:.78rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;">
                <i class="bi bi-play-fill"></i>Aplicar
            </a>
            @if($r->aplicaciones_count > 0)
            <a href="{{ route('portal.docente.rubricas.resultados', $r) }}"
               style="background:#0ea5e9;color:#fff;border:none;border-radius:7px;padding:.32rem .65rem;font-size:.78rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;">
                <i class="bi bi-bar-chart-fill"></i>Resultados
            </a>
            @endif
            <form method="POST" action="{{ route('portal.docente.rubricas.destroy', $r) }}"
                  onsubmit="return confirm('¿Eliminar esta rúbrica?')" style="margin:0;">
                @csrf @method('DELETE')
                <button type="submit"
                    style="background:#fee2e2;color:#ef4444;border:none;border-radius:7px;padding:.32rem .55rem;font-size:.78rem;cursor:pointer;">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    </div>
</div>
@empty
<div class="prt-card" style="text-align:center;padding:2.5rem;color:#94a3b8;">
    <i class="bi bi-table" style="font-size:2.5rem;display:block;margin-bottom:.6rem;"></i>
    <p style="margin:0;font-size:.88rem;">No tienes rúbricas todavía. ¡Crea la primera!</p>
</div>
@endforelse

{{-- Modal crear --}}
<div id="modalCrear" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('active')">
    <div class="modal-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h3 style="margin:0;font-size:.95rem;font-weight:800;"><i class="bi bi-table me-2" style="color:#ec4899;"></i>Nueva Rúbrica</h3>
            <button onclick="this.closest('.modal-overlay').classList.remove('active')" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#64748b;">&times;</button>
        </div>
        <form method="POST" action="{{ route('portal.docente.rubricas.store') }}">
            @csrf
            <div style="margin-bottom:.8rem;">
                <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Título *</label>
                <input name="titulo" required maxlength="200" placeholder="Ej: Rúbrica Proyecto de Ciencias"
                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.85rem;">
            </div>
            <div style="margin-bottom:.8rem;">
                <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Descripción</label>
                <textarea name="descripcion" rows="2" placeholder="Para qué se usará esta rúbrica..."
                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.85rem;resize:vertical;"></textarea>
            </div>
            <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:.8rem;">
                <button type="button" onclick="this.closest('.modal-overlay').classList.remove('active')"
                    style="background:#f1f5f9;color:#475569;border:none;border-radius:8px;padding:.55rem 1rem;font-size:.82rem;font-weight:600;cursor:pointer;">
                    Cancelar
                </button>
                <button type="submit"
                    style="background:#ec4899;color:#fff;border:none;border-radius:8px;padding:.55rem 1.2rem;font-size:.82rem;font-weight:700;cursor:pointer;">
                    <i class="bi bi-plus-lg me-1"></i>Crear y personalizar
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
