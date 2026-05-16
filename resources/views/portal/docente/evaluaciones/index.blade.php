@extends('layouts.portal')
@section('page-title', 'Evaluaciones Online')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'evaluaciones', 'asignacion' => $asignacion])
@endsection

@section('bottom-nav')
<a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item"><i class="bi bi-house-fill"></i>Inicio</a>
<a href="{{ route('portal.docente.evaluaciones.index', $asignacion) }}" class="prt-nav-item active"><i class="bi bi-patch-question-fill"></i>Evaluaciones</a>
<a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item"><i class="bi bi-journal-check"></i>Notas</a>
@endsection

@push('styles')
<style>
.quiz-card {
    background:#fff;
    border:1.5px solid #e2e8f0;
    border-radius:12px;
    padding:1rem 1.1rem;
    margin-bottom:.7rem;
    border-left:4px solid #6366f1;
    transition:box-shadow .15s;
}
.quiz-card:hover { box-shadow:0 3px 14px rgba(99,102,241,.12); }
.badge-pub {
    display:inline-flex;align-items:center;gap:.3rem;
    padding:.2rem .65rem;border-radius:99px;
    font-size:.68rem;font-weight:700;color:#fff;
}
.modal-overlay {
    display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);
    z-index:1000;align-items:center;justify-content:center;
}
.modal-overlay.active { display:flex; }
.modal-box {
    background:#fff;border-radius:14px;padding:1.5rem;
    width:100%;max-width:500px;max-height:90vh;overflow-y:auto;
    box-shadow:0 20px 60px rgba(0,0,0,.2);
}
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;margin-bottom:1.25rem;">
    <div>
        <h2 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-patch-question-fill me-2" style="color:#6366f1;"></i>Evaluaciones Online
        </h2>
        <p style="font-size:.75rem;color:#64748b;margin:.2rem 0 0;">
            {{ $asignacion->asignatura?->nombre }} — {{ $asignacion->grupo?->nombre_completo }}
        </p>
    </div>
    <button onclick="document.getElementById('modalCrear').classList.add('active')"
        style="background:#6366f1;color:#fff;border:none;border-radius:8px;padding:.5rem 1rem;font-size:.8rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-plus-lg"></i>Nueva Evaluación
    </button>
</div>

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:.6rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#166534;">
    <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:.6rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#991b1b;">
    <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ session('error') }}
</div>
@endif

{{-- KPIs --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.7rem;margin-bottom:1.2rem;">
    <div class="prt-card" style="text-align:center;padding:.8rem;">
        <div style="font-size:1.5rem;font-weight:800;color:#6366f1;">{{ $quizzes->count() }}</div>
        <div style="font-size:.7rem;color:#64748b;">Evaluaciones</div>
    </div>
    <div class="prt-card" style="text-align:center;padding:.8rem;">
        <div style="font-size:1.5rem;font-weight:800;color:#10b981;">{{ $quizzes->where('publicado', true)->count() }}</div>
        <div style="font-size:.7rem;color:#64748b;">Publicadas</div>
    </div>
    <div class="prt-card" style="text-align:center;padding:.8rem;">
        <div style="font-size:1.5rem;font-weight:800;color:#f59e0b;">{{ $totalEst }}</div>
        <div style="font-size:.7rem;color:#64748b;">Estudiantes</div>
    </div>
</div>

@forelse($quizzes as $quiz)
<div class="quiz-card">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;flex-wrap:wrap;">
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.3rem;">
                <strong style="font-size:.88rem;">{{ $quiz->titulo }}</strong>
                @if($quiz->publicado)
                    <span class="badge-pub" style="background:#10b981;"><i class="bi bi-broadcast"></i>Publicada</span>
                @else
                    <span class="badge-pub" style="background:#94a3b8;"><i class="bi bi-eye-slash"></i>Borrador</span>
                @endif
            </div>
            <div style="display:flex;gap:.8rem;flex-wrap:wrap;font-size:.72rem;color:#64748b;">
                <span><i class="bi bi-list-ul me-1"></i>{{ $quiz->preguntas_count }} preg.</span>
                <span><i class="bi bi-people me-1"></i>{{ $quiz->intentos_count }} intentos</span>
                @if($quiz->duracion_minutos)
                    <span><i class="bi bi-clock me-1"></i>{{ $quiz->duracion_minutos }} min</span>
                @endif
                @if($quiz->disponible_desde || $quiz->disponible_hasta)
                    <span><i class="bi bi-calendar-range me-1"></i>
                        {{ $quiz->disponible_desde?->format('d/m') ?? '∞' }} →
                        {{ $quiz->disponible_hasta?->format('d/m') ?? '∞' }}
                    </span>
                @endif
            </div>
        </div>
        <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
            <a href="{{ route('portal.docente.evaluaciones.show', [$asignacion, $quiz]) }}"
               style="background:#6366f1;color:#fff;border:none;border-radius:7px;padding:.35rem .75rem;font-size:.75rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;">
                <i class="bi bi-pencil-fill"></i>Editar
            </a>
            @if($quiz->intentos_count > 0)
            <a href="{{ route('portal.docente.evaluaciones.resultados', [$asignacion, $quiz]) }}"
               style="background:#0ea5e9;color:#fff;border:none;border-radius:7px;padding:.35rem .75rem;font-size:.75rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;">
                <i class="bi bi-bar-chart-fill"></i>Resultados
            </a>
            @endif
            <form method="POST" action="{{ route('portal.docente.evaluaciones.toggle-publicado', [$asignacion, $quiz]) }}" style="margin:0;">
                @csrf @method('PATCH')
                <button type="submit"
                    style="background:{{ $quiz->publicado ? '#f59e0b' : '#10b981' }};color:#fff;border:none;border-radius:7px;padding:.35rem .75rem;font-size:.75rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:.3rem;">
                    <i class="bi bi-{{ $quiz->publicado ? 'eye-slash' : 'broadcast' }}"></i>
                    {{ $quiz->publicado ? 'Despublicar' : 'Publicar' }}
                </button>
            </form>
            <form method="POST" action="{{ route('portal.docente.evaluaciones.destroy', [$asignacion, $quiz]) }}"
                  onsubmit="return confirm('¿Eliminar esta evaluación?')" style="margin:0;">
                @csrf @method('DELETE')
                <button type="submit"
                    style="background:#ef4444;color:#fff;border:none;border-radius:7px;padding:.35rem .65rem;font-size:.75rem;font-weight:600;cursor:pointer;">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    </div>
</div>
@empty
<div class="prt-card" style="text-align:center;padding:2rem;color:#94a3b8;">
    <i class="bi bi-patch-question" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
    <p style="margin:0;font-size:.85rem;">No hay evaluaciones. ¡Crea la primera!</p>
</div>
@endforelse

{{-- Modal: Crear Quiz --}}
<div id="modalCrear" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('active')">
    <div class="modal-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h3 style="margin:0;font-size:.95rem;font-weight:800;">
                <i class="bi bi-patch-question-fill me-2" style="color:#6366f1;"></i>Nueva Evaluación
            </h3>
            <button onclick="document.getElementById('modalCrear').classList.remove('active')"
                style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#64748b;">&times;</button>
        </div>
        <form method="POST" action="{{ route('portal.docente.evaluaciones.store', $asignacion) }}">
            @csrf
            <div style="margin-bottom:.8rem;">
                <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Título *</label>
                <input name="titulo" required maxlength="200" placeholder="Ej: Evaluación Parcial I"
                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.85rem;">
            </div>
            <div style="margin-bottom:.8rem;">
                <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Instrucciones</label>
                <textarea name="instrucciones" rows="2" placeholder="Indicaciones para el estudiante..."
                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.85rem;resize:vertical;"></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.7rem;margin-bottom:.8rem;">
                <div>
                    <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Duración (min)</label>
                    <input name="duracion_minutos" type="number" min="1" max="300" placeholder="Sin límite"
                        style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.85rem;">
                </div>
                <div>
                    <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Intentos máx. *</label>
                    <input name="intentos_max" type="number" min="1" max="10" value="1" required
                        style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.85rem;">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.7rem;margin-bottom:.8rem;">
                <div>
                    <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Disponible desde</label>
                    <input name="disponible_desde" type="datetime-local"
                        style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.82rem;">
                </div>
                <div>
                    <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.3rem;">Disponible hasta</label>
                    <input name="disponible_hasta" type="datetime-local"
                        style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .75rem;font-size:.82rem;">
                </div>
            </div>
            <div style="display:flex;gap:1rem;margin-bottom:1rem;">
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.78rem;font-weight:600;cursor:pointer;">
                    <input name="mostrar_resultados" type="checkbox" value="1" checked> Mostrar resultados al terminar
                </label>
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.78rem;font-weight:600;cursor:pointer;">
                    <input name="aleatorizar" type="checkbox" value="1"> Aleatorizar preguntas
                </label>
            </div>
            <div style="display:flex;gap:.5rem;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('modalCrear').classList.remove('active')"
                    style="background:#f1f5f9;color:#475569;border:none;border-radius:8px;padding:.55rem 1.1rem;font-size:.82rem;font-weight:600;cursor:pointer;">
                    Cancelar
                </button>
                <button type="submit"
                    style="background:#6366f1;color:#fff;border:none;border-radius:8px;padding:.55rem 1.2rem;font-size:.82rem;font-weight:700;cursor:pointer;">
                    <i class="bi bi-plus-lg me-1"></i>Crear
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
