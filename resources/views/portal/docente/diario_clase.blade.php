@extends('layouts.portal')
@section('page-title', 'Diario de Clase')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'diario', 'asignacion' => $asignacion])
@endsection

@section('bottom-nav')
<a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item"><i class="bi bi-house-fill"></i>Inicio</a>
<a href="{{ route('portal.docente.diario.index', $asignacion) }}" class="prt-nav-item active"><i class="bi bi-journal-text"></i>Diario</a>
<a href="{{ route('portal.docente.asistencia', $asignacion) }}" class="prt-nav-item"><i class="bi bi-calendar-check"></i>Asistencia</a>
<a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item"><i class="bi bi-journal-check"></i>Notas</a>
@endsection

@push('styles')
<style>
.entrada-card {
    background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;
    padding:1rem 1.1rem;margin-bottom:.75rem;
    border-left:4px solid #0ea5e9;position:relative;
    transition:box-shadow .15s;
}
.entrada-card:hover { box-shadow:0 3px 14px rgba(14,165,233,.1); }
.entrada-card.editando { border-left-color:#f59e0b; }
.campo-label {
    font-size:.7rem;font-weight:700;color:#94a3b8;
    text-transform:uppercase;letter-spacing:.04em;
    margin-bottom:.2rem;display:block;
}
.campo-val { font-size:.85rem;color:#334155;margin:0 0 .5rem; }
.mes-label {
    font-size:.72rem;font-weight:800;color:#0ea5e9;
    text-transform:uppercase;letter-spacing:.05em;
    margin:1.2rem 0 .5rem;display:flex;align-items:center;gap:.4rem;
}
textarea.diario-input {
    width:100%;border:1.5px solid #e2e8f0;border-radius:8px;
    padding:.5rem .7rem;font-size:.83rem;resize:vertical;
    font-family:inherit;
}
textarea.diario-input:focus { outline:none;border-color:#0ea5e9; }
.tag-incidencia {
    display:inline-block;background:#fef2f2;color:#dc2626;
    border:1px solid #fca5a5;border-radius:99px;
    padding:.12rem .5rem;font-size:.68rem;font-weight:700;
}
</style>
@endpush

@section('content')

{{-- Cabecera --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;margin-bottom:1.1rem;">
    <div>
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-journal-text me-2" style="color:#0ea5e9;"></i>Diario de Clase
        </h1>
        <p style="font-size:.75rem;color:#64748b;margin:.2rem 0 0;">
            {{ $asignacion->asignatura?->nombre }} — {{ $asignacion->grupo?->nombre_completo }}
        </p>
    </div>
    <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
        <a href="{{ route('portal.docente.diario.pdf', [$asignacion, 'anio' => $anio, 'mes' => $mes]) }}"
           target="_blank"
           style="background:#dc2626;color:#fff;border:none;border-radius:8px;padding:.42rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.35rem;">
            <i class="bi bi-file-earmark-pdf"></i>PDF
        </a>
        <button onclick="document.getElementById('formNueva').scrollIntoView({behavior:'smooth'})"
            style="background:#0ea5e9;color:#fff;border:none;border-radius:8px;padding:.42rem .9rem;font-size:.78rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.35rem;">
            <i class="bi bi-plus-lg"></i>Nueva entrada
        </button>
    </div>
</div>

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:.6rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#166534;">
    <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
</div>
@endif

{{-- KPIs --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.65rem;margin-bottom:1.2rem;">
    <div class="prt-card" style="text-align:center;padding:.8rem;">
        <div style="font-size:1.5rem;font-weight:800;color:#0ea5e9;">{{ $totalEntradas }}</div>
        <div style="font-size:.7rem;color:#64748b;">Entradas totales</div>
    </div>
    <div class="prt-card" style="text-align:center;padding:.8rem;">
        <div style="font-size:1.5rem;font-weight:800;color:#10b981;">{{ $entradas->count() }}</div>
        <div style="font-size:.7rem;color:#64748b;">Este filtro</div>
    </div>
    <div class="prt-card" style="text-align:center;padding:.8rem;">
        <div style="font-size:1.5rem;font-weight:800;color:#f59e0b;">{{ $entradas->whereNotNull('incidencias')->where('incidencias','!=','')->count() }}</div>
        <div style="font-size:.7rem;color:#64748b;">Con incidencias</div>
    </div>
</div>

{{-- Filtros --}}
<div class="prt-card" style="padding:.75rem 1rem;margin-bottom:1rem;">
    <form method="GET" style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap;">
        <div>
            <label class="campo-label">Año</label>
            <select name="anio" style="border:1.5px solid #e2e8f0;border-radius:7px;padding:.38rem .65rem;font-size:.82rem;">
                @foreach(range(now()->year, max(now()->year - 3, 2023)) as $y)
                <option value="{{ $y }}" {{ $anio == $y ? 'selected':'' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="campo-label">Mes</label>
            <select name="mes" style="border:1.5px solid #e2e8f0;border-radius:7px;padding:.38rem .65rem;font-size:.82rem;">
                <option value="">Todos</option>
                @foreach([1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'] as $n => $nombre)
                <option value="{{ $n }}" {{ $mes == $n ? 'selected':'' }}>{{ $nombre }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit"
            style="background:#0ea5e9;color:#fff;border:none;border-radius:7px;padding:.42rem .85rem;font-size:.8rem;font-weight:700;cursor:pointer;">
            <i class="bi bi-funnel me-1"></i>Filtrar
        </button>
        @if($mes)
        <a href="{{ route('portal.docente.diario.index', [$asignacion, 'anio' => $anio]) }}"
           style="background:#f1f5f9;color:#64748b;border:none;border-radius:7px;padding:.42rem .75rem;font-size:.8rem;font-weight:600;text-decoration:none;">
            <i class="bi bi-x"></i> Todo el año
        </a>
        @endif
    </form>
</div>

{{-- Timeline de entradas --}}
@php $mesActual = null; @endphp
@forelse($entradas as $entrada)
    @php $mesEntrada = $entrada->fecha->format('Y-m'); @endphp
    @if($mesEntrada !== $mesActual)
        @php $mesActual = $mesEntrada; @endphp
        <div class="mes-label">
            <i class="bi bi-calendar3"></i>
            {{ \Carbon\Carbon::parse($entrada->fecha)->translatedFormat('F Y') }}
        </div>
    @endif

    <div class="entrada-card" id="entrada-{{ $entrada->id }}">
        {{-- Vista --}}
        <div id="vista-{{ $entrada->id }}">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;flex-wrap:wrap;margin-bottom:.5rem;">
                <div>
                    <span style="font-size:.78rem;font-weight:800;color:#0ea5e9;">
                        {{ $entrada->fecha->translatedFormat('l, d \d\e F') }}
                    </span>
                    @if($entrada->asistentes !== null)
                    <span style="font-size:.7rem;color:#64748b;margin-left:.5rem;">
                        <i class="bi bi-people me-1"></i>{{ $entrada->asistentes }} asistentes
                    </span>
                    @endif
                </div>
                <div style="display:flex;gap:.3rem;">
                    <button onclick="modoEditar({{ $entrada->id }})"
                        style="background:#ede9fe;color:#7c3aed;border:none;border-radius:7px;padding:.28rem .52rem;font-size:.78rem;cursor:pointer;">
                        <i class="bi bi-pencil-fill"></i>
                    </button>
                    <button onclick="eliminarEntrada({{ $entrada->id }})"
                        style="background:#fee2e2;color:#ef4444;border:none;border-radius:7px;padding:.28rem .52rem;font-size:.78rem;cursor:pointer;">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>

            <span class="campo-label">Tema</span>
            <p class="campo-val" style="font-weight:700;">{{ $entrada->tema }}</p>

            @if($entrada->actividades)
            <span class="campo-label">Actividades</span>
            <p class="campo-val">{{ $entrada->actividades }}</p>
            @endif

            @if($entrada->observaciones)
            <span class="campo-label">Observaciones</span>
            <p class="campo-val">{{ $entrada->observaciones }}</p>
            @endif

            @if($entrada->incidencias)
            <div style="margin-top:.3rem;">
                <span class="tag-incidencia"><i class="bi bi-exclamation-triangle-fill me-1"></i>Incidencia</span>
                <p class="campo-val" style="margin-top:.3rem;color:#dc2626;">{{ $entrada->incidencias }}</p>
            </div>
            @endif
        </div>

        {{-- Formulario edición (oculto) --}}
        <div id="form-{{ $entrada->id }}" style="display:none;">
            <form method="POST" action="{{ route('portal.docente.diario.update', [$asignacion, $entrada]) }}"
                  onsubmit="submitEditar(event, {{ $entrada->id }})">
                @csrf @method('PUT')
                <div style="display:grid;grid-template-columns:1fr auto;gap:.5rem;margin-bottom:.6rem;align-items:end;">
                    <div>
                        <label class="campo-label">Tema *</label>
                        <input name="tema" value="{{ $entrada->tema }}" required maxlength="300"
                            style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.45rem .65rem;font-size:.83rem;">
                    </div>
                    <div>
                        <label class="campo-label">Asistentes</label>
                        <input name="asistentes" type="number" min="0" max="200" value="{{ $entrada->asistentes }}"
                            style="width:80px;border:1.5px solid #e2e8f0;border-radius:8px;padding:.45rem .65rem;font-size:.83rem;">
                    </div>
                </div>
                <div style="margin-bottom:.6rem;">
                    <label class="campo-label">Actividades realizadas</label>
                    <textarea name="actividades" rows="2" class="diario-input">{{ $entrada->actividades }}</textarea>
                </div>
                <div style="margin-bottom:.6rem;">
                    <label class="campo-label">Observaciones del grupo</label>
                    <textarea name="observaciones" rows="2" class="diario-input">{{ $entrada->observaciones }}</textarea>
                </div>
                <div style="margin-bottom:.75rem;">
                    <label class="campo-label">Incidencias</label>
                    <textarea name="incidencias" rows="2" class="diario-input" placeholder="Situaciones destacadas o problemas...">{{ $entrada->incidencias }}</textarea>
                </div>
                <div style="display:flex;gap:.4rem;justify-content:flex-end;">
                    <button type="button" onclick="cancelarEditar({{ $entrada->id }})"
                        style="background:#f1f5f9;color:#475569;border:none;border-radius:7px;padding:.42rem .85rem;font-size:.8rem;font-weight:600;cursor:pointer;">
                        Cancelar
                    </button>
                    <button type="submit"
                        style="background:#0ea5e9;color:#fff;border:none;border-radius:7px;padding:.42rem 1rem;font-size:.8rem;font-weight:700;cursor:pointer;">
                        <i class="bi bi-floppy me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
@empty
<div class="prt-card" style="text-align:center;padding:2.5rem;color:#94a3b8;">
    <i class="bi bi-journal-x" style="font-size:2.5rem;display:block;margin-bottom:.6rem;"></i>
    <p style="margin:0;font-size:.88rem;">No hay entradas en el diario para este período.</p>
</div>
@endforelse

{{-- Formulario nueva entrada --}}
<div id="formNueva" class="prt-card" style="padding:1.2rem;margin-top:1.2rem;border:2px solid #0ea5e9;">
    <div class="prt-card-header" style="margin-bottom:1rem;color:#0ea5e9;">
        <i class="bi bi-plus-circle-fill me-2"></i>Nueva entrada del diario
    </div>
    <form method="POST" action="{{ route('portal.docente.diario.store', $asignacion) }}">
        @csrf
        <div style="display:grid;grid-template-columns:1fr auto;gap:.6rem;margin-bottom:.75rem;align-items:end;">
            <div>
                <label class="campo-label">Fecha *</label>
                <input name="fecha" type="date" value="{{ now()->format('Y-m-d') }}" required
                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.5rem .7rem;font-size:.85rem;">
            </div>
            <div>
                <label class="campo-label">Asistentes</label>
                <input name="asistentes" type="number" min="0" max="200" placeholder="—"
                    style="width:80px;border:1.5px solid #e2e8f0;border-radius:8px;padding:.5rem .7rem;font-size:.85rem;">
            </div>
        </div>
        <div style="margin-bottom:.75rem;">
            <label class="campo-label">Tema impartido *</label>
            <input name="tema" required maxlength="300" placeholder="Ej: Fracciones equivalentes — Ejercicios prácticos"
                style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.5rem .7rem;font-size:.85rem;">
        </div>
        <div style="margin-bottom:.75rem;">
            <label class="campo-label">Actividades realizadas</label>
            <textarea name="actividades" rows="2" class="diario-input"
                placeholder="Clase expositiva, trabajo en grupos, ejercicios en pizarra..."></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.7rem;margin-bottom:.75rem;">
            <div>
                <label class="campo-label">Observaciones del grupo</label>
                <textarea name="observaciones" rows="2" class="diario-input"
                    placeholder="Participación, nivel de comprensión..."></textarea>
            </div>
            <div>
                <label class="campo-label">Incidencias (opcional)</label>
                <textarea name="incidencias" rows="2" class="diario-input"
                    placeholder="Interrupciones, conflictos, ausencias notables..."></textarea>
            </div>
        </div>
        <div style="text-align:right;">
            <button type="submit"
                style="background:#0ea5e9;color:#fff;border:none;border-radius:8px;padding:.55rem 1.4rem;font-size:.85rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;">
                <i class="bi bi-floppy"></i>Registrar entrada
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
const CSRF = '{{ csrf_token() }}';

function modoEditar(id) {
    document.getElementById('vista-' + id).style.display = 'none';
    document.getElementById('form-' + id).style.display  = '';
    document.getElementById('entrada-' + id).classList.add('editando');
}
function cancelarEditar(id) {
    document.getElementById('vista-' + id).style.display = '';
    document.getElementById('form-' + id).style.display  = 'none';
    document.getElementById('entrada-' + id).classList.remove('editando');
}
function submitEditar(e, id) {
    // Dejar que el form haga POST normal — recarga la página
}
async function eliminarEntrada(id) {
    if (!confirm('¿Eliminar esta entrada del diario?')) return;
    const url = window.location.pathname.replace(/\/diario.*/, '/diario/' + id);
    const r = await fetch(url, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    });
    const data = await r.json();
    if (data.ok) {
        document.getElementById('entrada-' + id)?.remove();
    }
}
</script>
@endpush

@endsection
