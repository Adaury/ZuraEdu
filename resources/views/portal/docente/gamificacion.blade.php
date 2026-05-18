@extends('layouts.portal')
@section('page-title', 'Gamificación — Portal Docente')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'gamificacion'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.gamificacion') }}" class="prt-nav-item active">
        <i class="bi bi-trophy-fill"></i>Gamificación
    </a>
    <a href="{{ route('portal.docente.mis-estudiantes') }}" class="prt-nav-item">
        <i class="bi bi-people-fill"></i>Estudiantes
    </a>
@endsection

@push('styles')
<style>
.rank-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.rank-table th { background:#f8fafc; color:#64748b; font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; padding:.55rem .75rem; border-bottom:2px solid #e2e8f0; }
.rank-table td { padding:.55rem .75rem; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.rank-table tr:last-child td { border-bottom:none; }
.rank-table tr:hover td { background:#f8fafc; }
.rank-pos  { font-weight:900; font-size:.95rem; width:36px; text-align:center; }
.rank-medal-1 { color:#f59e0b; }
.rank-medal-2 { color:#94a3b8; }
.rank-medal-3 { color:#d97706; }
.pts-badge  { display:inline-block; background:#eef2ff; color:#4338ca; border-radius:99px; font-size:.75rem; font-weight:800; padding:.18rem .55rem; }
.ins-badge  { display:inline-block; background:#fef9c3; color:#92400e; border-radius:99px; font-size:.72rem; font-weight:700; padding:.15rem .5rem; }
.cat-pill   { display:inline-block; border-radius:99px; font-size:.68rem; font-weight:700; padding:.12rem .48rem; }
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:9000; align-items:center; justify-content:center; }
.modal-overlay.open { display:flex; }
.modal-box { background:#fff; border-radius:16px; padding:1.5rem; width:100%; max-width:420px; box-shadow:0 8px 32px rgba(0,0,0,.18); }
.form-group { margin-bottom:.85rem; }
.form-group label { display:block; font-size:.75rem; font-weight:700; color:#374151; margin-bottom:.25rem; }
.form-group input, .form-group select, .form-group textarea { width:100%; border:1.5px solid #e2e8f0; border-radius:8px; padding:.5rem .75rem; font-size:.85rem; color:#1e293b; outline:none; transition:border-color .15s; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:#6366f1; }
</style>
@endpush

@section('content')

{{-- Cabecera --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.dashboard') }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-trophy-fill" style="color:#f59e0b;"></i> Gamificación
        </h1>
        <div style="font-size:.75rem;color:#64748b;">
            Ranking y puntos de tus grupos · {{ $schoolYear?->nombre ?? 'Sin año activo' }}
        </div>
    </div>
    @if($asignacionSel)
    <button onclick="document.getElementById('modalAsignar').classList.add('open')"
            style="background:#6366f1;color:#fff;border:none;border-radius:8px;padding:.45rem 1rem;font-size:.82rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-plus-circle-fill"></i>Asignar Puntos
    </button>
    @endif
</div>

@if($asignaciones->isEmpty())
<div class="prt-card" style="text-align:center;padding:2rem;color:#94a3b8;">
    <i class="bi bi-people" style="font-size:2rem;"></i>
    <p style="margin:.5rem 0 0;font-size:.85rem;">No tienes asignaciones activas este año.</p>
</div>
@else

{{-- Selector de grupo --}}
<div class="prt-card" style="padding:1rem;margin-bottom:1rem;">
    <form method="GET" action="{{ route('portal.docente.gamificacion') }}" style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
        <label style="font-size:.78rem;font-weight:700;color:#374151;white-space:nowrap;">
            <i class="bi bi-funnel-fill" style="color:#6366f1;"></i> Grupo / Materia:
        </label>
        <select name="asignacion_id" onchange="this.form.submit()"
                style="border:1.5px solid #e2e8f0;border-radius:8px;padding:.4rem .75rem;font-size:.82rem;color:#1e293b;flex:1;min-width:200px;">
            @foreach($asignaciones as $asig)
            <option value="{{ $asig->id }}" {{ $asignacionSel?->id == $asig->id ? 'selected' : '' }}>
                {{ $asig->grupo?->nombre_completo ?? '—' }} — {{ $asig->asignatura?->nombre ?? '—' }}
            </option>
            @endforeach
        </select>
    </form>
</div>

@if($ranking->isEmpty())
<div class="prt-card" style="text-align:center;padding:2rem;color:#94a3b8;">
    <i class="bi bi-people" style="font-size:2rem;"></i>
    <p style="margin:.5rem 0 0;font-size:.85rem;">Sin estudiantes matriculados en este grupo.</p>
</div>
@else

{{-- Stats del grupo --}}
@php
    $totalPtsGrupo  = $ranking->sum('_puntos');
    $conPuntos      = $ranking->where('_puntos', '>', 0)->count();
    $totalInsGrupo  = $ranking->sum('_insignias');
@endphp
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.65rem;margin-bottom:1rem;">
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:.85rem 1rem;text-align:center;">
        <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;">Estudiantes</div>
        <div style="font-size:1.6rem;font-weight:900;color:#6366f1;">{{ $ranking->count() }}</div>
    </div>
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:.85rem 1rem;text-align:center;">
        <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;">Pts Totales</div>
        <div style="font-size:1.6rem;font-weight:900;color:#4338ca;">{{ number_format($totalPtsGrupo) }}</div>
    </div>
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:.85rem 1rem;text-align:center;">
        <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;">Con Puntos</div>
        <div style="font-size:1.6rem;font-weight:900;color:#059669;">{{ $conPuntos }}</div>
    </div>
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:.85rem 1rem;text-align:center;">
        <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;">Insignias</div>
        <div style="font-size:1.6rem;font-weight:900;color:#d97706;">{{ $totalInsGrupo }}</div>
    </div>
</div>

{{-- Ranking completo --}}
<div class="prt-card">
    <div class="prt-card-header">
        <i class="bi bi-trophy-fill" style="color:#f59e0b;"></i>
        <h3>Ranking del Grupo — {{ $asignacionSel?->grupo?->nombre_completo }}</h3>
    </div>
    <div style="overflow-x:auto;">
        <table class="rank-table">
            <thead>
                <tr>
                    <th style="text-align:center;">#</th>
                    <th>Estudiante</th>
                    <th style="text-align:center;">Puntos</th>
                    <th style="text-align:center;">Insignias</th>
                    <th style="text-align:center;">Acción</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ranking as $i => $mat)
                @php
                    $pos = $i + 1;
                    $medalClass = $pos === 1 ? 'rank-medal-1' : ($pos === 2 ? 'rank-medal-2' : ($pos === 3 ? 'rank-medal-3' : ''));
                    $medalIcon  = $pos === 1 ? '🥇' : ($pos === 2 ? '🥈' : ($pos === 3 ? '🥉' : $pos));
                @endphp
                <tr>
                    <td class="rank-pos {{ $medalClass }}">{{ $medalIcon }}</td>
                    <td>
                        <div style="font-weight:700;color:#1e293b;">
                            {{ $mat->estudiante?->apellidos }}, {{ $mat->estudiante?->nombres }}
                        </div>
                        <div style="font-size:.7rem;color:#94a3b8;">
                            Matrícula #{{ $mat->id }}
                        </div>
                    </td>
                    <td style="text-align:center;">
                        <span class="pts-badge">{{ number_format($mat->_puntos) }} pts</span>
                    </td>
                    <td style="text-align:center;">
                        @if($mat->_insignias > 0)
                        <span class="ins-badge">⭐ {{ $mat->_insignias }}</span>
                        @else
                        <span style="color:#cbd5e1;font-size:.75rem;">—</span>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        <button onclick="abrirAsignar({{ $mat->id }}, '{{ addslashes($mat->estudiante?->nombre_completo ?? '—') }}')"
                                style="background:#eef2ff;color:#4338ca;border:none;border-radius:6px;padding:.25rem .6rem;font-size:.75rem;font-weight:700;cursor:pointer;">
                            <i class="bi bi-plus"></i> Puntos
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endif

{{-- Modal Asignar Puntos --}}
<div id="modalAsignar" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h3 style="font-size:1rem;font-weight:800;margin:0;">
                <i class="bi bi-trophy-fill" style="color:#f59e0b;"></i> Asignar Puntos
            </h3>
            <button onclick="document.getElementById('modalAsignar').classList.remove('open')"
                    style="background:none;border:none;font-size:1.2rem;color:#94a3b8;cursor:pointer;">✕</button>
        </div>

        @if(session('success'))
        <div style="background:#dcfce7;border-radius:8px;padding:.6rem .9rem;font-size:.8rem;font-weight:600;color:#15803d;margin-bottom:.75rem;">
            <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div style="background:#fee2e2;border-radius:8px;padding:.6rem .9rem;font-size:.8rem;font-weight:600;color:#dc2626;margin-bottom:.75rem;">
            <i class="bi bi-exclamation-circle-fill me-1"></i>{{ session('error') }}
        </div>
        @endif

        <form method="POST" action="{{ route('portal.docente.gamificacion.asignar') }}">
            @csrf
            <div class="form-group">
                <label>Estudiante</label>
                <select name="matricula_id" id="modalMatriculaId" required
                        style="border:1.5px solid #e2e8f0;border-radius:8px;padding:.5rem .75rem;font-size:.82rem;width:100%;">
                    <option value="">— Selecciona —</option>
                    @foreach($ranking as $mat)
                    <option value="{{ $mat->id }}">{{ $mat->estudiante?->apellidos }}, {{ $mat->estudiante?->nombres }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Concepto</label>
                <input type="text" name="concepto" placeholder="Ej: Participación destacada" required maxlength="150">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                <div class="form-group">
                    <label>Categoría</label>
                    <select name="categoria" required>
                        @foreach($categorias as $key => $info)
                        <option value="{{ $key }}">{{ $info['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Puntos</label>
                    <input type="number" name="puntos" value="10" min="1" max="500" required>
                </div>
            </div>
            <div class="form-group">
                <label>Fecha</label>
                <input type="date" name="fecha" value="{{ today()->toDateString() }}" required>
            </div>
            <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:.25rem;">
                <button type="button" onclick="document.getElementById('modalAsignar').classList.remove('open')"
                        style="background:#f1f5f9;color:#374151;border:none;border-radius:8px;padding:.5rem 1rem;font-size:.82rem;font-weight:600;cursor:pointer;">
                    Cancelar
                </button>
                <button type="submit"
                        style="background:#6366f1;color:#fff;border:none;border-radius:8px;padding:.5rem 1.2rem;font-size:.82rem;font-weight:700;cursor:pointer;">
                    <i class="bi bi-check-circle-fill me-1"></i>Guardar
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function abrirAsignar(matriculaId, nombre) {
    const sel = document.getElementById('modalMatriculaId');
    if (sel) sel.value = matriculaId;
    document.getElementById('modalAsignar').classList.add('open');
}
@if(session('success') || session('error'))
document.getElementById('modalAsignar').classList.add('open');
@endif
</script>
@endpush

@endsection
