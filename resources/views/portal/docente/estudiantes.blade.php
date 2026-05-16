@extends('layouts.portal')
@section('page-title', 'Estudiantes — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'estudiantes'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.asistencia', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-calendar-check"></i>Asistencia
    </a>
    <a href="{{ route('portal.docente.estudiantes', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-people-fill"></i>Estudiantes
    </a>
    <a href="{{ route('portal.docente.boletines', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-file-earmark-text"></i>Boletines
    </a>
@endsection

@section('content')
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.dashboard') }}" class="btn-back"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div>
        <h1 style="font-size:1rem;font-weight:800;margin:0;">Estudiantes — {{ $asignacion->asignatura?->nombre }}</h1>
        <div class="dm-text-muted" style="font-size:.75rem;color:#64748b;">{{ $asignacion->grupo?->nombre_completo ?? '—' }} · {{ $matriculas->count() }} estudiante(s)</div>
    </div>
    <div style="margin-left:auto;display:flex;gap:.5rem;">
        <a href="{{ route('portal.docente.asistencia', $asignacion) }}"
           style="background:#dcfce7;color:#15803d;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-calendar-check"></i>Asistencia
        </a>
        <a href="{{ route('portal.docente.observaciones', $asignacion) }}"
           style="background:#fffbeb;color:#92400e;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-chat-square-text"></i>Observaciones
        </a>
        @if($asignacion->grupo_id)
        <a href="{{ route('admin.grupos.lista-pdf', $asignacion->grupo_id) }}" target="_blank"
           style="background:#1e3a6e;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-file-earmark-text"></i>Lista PDF
        </a>
        @endif
        <a href="{{ route('portal.docente.estudiantes.pdf', $asignacion) }}" target="_blank"
           style="background:#dc2626;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-file-earmark-pdf"></i>Nómina PDF
        </a>
        <a href="{{ route('portal.docente.estudiantes.excel', $asignacion) }}"
           style="background:#16a34a;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-file-earmark-excel"></i>Excel
        </a>
    </div>
</div>

<div class="prt-card">
    <div class="prt-card-header">
        <i class="bi bi-people-fill" style="color:#1d4ed8;font-size:1rem;"></i>
        <h3>Lista de Estudiantes</h3>
        <span style="margin-left:auto;font-size:.75rem;color:#64748b;background:#f1f5f9;border-radius:6px;padding:.2rem .6rem;" id="contador-visible">
            {{ $matriculas->count() }} registrado(s)
        </span>
    </div>

    {{-- Buscador y filtros --}}
    <div style="padding:.75rem 1rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;">
        <div style="flex:1;min-width:180px;position:relative;">
            <i class="bi bi-search" style="position:absolute;left:.6rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.82rem;"></i>
            <input type="text" id="buscador-est"
                   placeholder="Buscar por nombre, apellido o matrícula…"
                   oninput="filtrarEstudiantes()"
                   style="width:100%;padding:.38rem .5rem .38rem 1.85rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.8rem;outline:none;background:#fff;">
        </div>
        <select id="filtro-nota" onchange="filtrarEstudiantes()"
                style="padding:.38rem .65rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.78rem;background:#fff;outline:none;color:#374151;">
            <option value="">Todas las notas</option>
            <option value="aprobado">Aprobados (≥70)</option>
            <option value="reprobado">Reprobados (&lt;70)</option>
            <option value="sin_nota">Sin nota</option>
            <option value="A">A (90-100)</option>
            <option value="B">B (75-89)</option>
            <option value="C">C (70-74)</option>
        </select>
        <select id="filtro-asist" onchange="filtrarEstudiantes()"
                style="padding:.38rem .65rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.78rem;background:#fff;outline:none;color:#374151;">
            <option value="">Toda asistencia</option>
            <option value="alta">Alta (≥80%)</option>
            <option value="media">Media (60-79%)</option>
            <option value="baja">Baja (&lt;60%)</option>
            <option value="sin_asist">Sin registro</option>
        </select>
        <button onclick="limpiarFiltros()"
                style="padding:.38rem .7rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.75rem;background:#fff;color:#64748b;cursor:pointer;">
            <i class="bi bi-x-circle me-1"></i>Limpiar
        </button>
    </div>

    {{-- Leyenda --}}
    <div class="dm-toolbar" style="padding:.5rem 1rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;gap:1rem;flex-wrap:wrap;align-items:center;">
        <span class="dm-text-muted" style="font-size:.72rem;color:#64748b;font-weight:600;">Leyenda:</span>
        <span class="nota-badge nota-a" style="font-size:.68rem;">A (90-100)</span>
        <span class="nota-badge nota-b" style="font-size:.68rem;">B (75-89)</span>
        <span class="nota-badge nota-c" style="font-size:.68rem;">C (70-74)</span>
        <span class="nota-badge nota-f" style="font-size:.68rem;">F (&lt;70)</span>
    </div>

    <div id="lista-estudiantes" style="padding:0;">
        <div id="sin-resultados" style="display:none;padding:2rem;text-align:center;color:#9ca3af;font-size:.83rem;">
            <i class="bi bi-search" style="font-size:1.5rem;display:block;margin-bottom:.4rem;"></i>
            No se encontraron estudiantes con esos criterios.
        </div>
        @forelse($matriculas as $m)
        @php
            $nota   = $m->_nota;
            $letra  = $m->_letra;
            $asist  = $m->_asist;
            $notaClass = $nota === null ? '' : ($nota >= 90 ? 'nota-a' : ($nota >= 75 ? 'nota-b' : ($nota >= 70 ? 'nota-c' : 'nota-f')));
            $asistColor = $asist === null ? '#9ca3af' : ($asist >= 80 ? '#15803d' : ($asist >= 60 ? '#d97706' : '#dc2626'));
        @endphp
        <div class="dm-list-item est-item"
             data-nombre="{{ strtolower($m->estudiante?->nombre_completo ?? '') }}"
             data-matricula="{{ strtolower($m->matricula ?? '') }}"
             data-nota="{{ $m->_nota ?? '' }}"
             data-asist="{{ $m->_asist ?? '' }}"
             style="padding:.85rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:1rem;">
            <div class="dm-avatar" style="width:38px;height:38px;border-radius:50%;background:#eff6ff;color:#1d4ed8;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.85rem;flex-shrink:0;">
                {{ strtoupper(substr($m->estudiante->nombres ?? 'E', 0, 1)) }}
            </div>
            <div style="flex:1;min-width:0;">
                <div class="dm-text-primary" style="font-size:.86rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ $m->estudiante?->nombre_completo ?? '—' }}
                </div>
                <div class="dm-text-muted" style="font-size:.72rem;color:#64748b;">
                    @if($asist !== null)
                        <span style="color:{{ $asistColor }};">
                            <i class="bi bi-calendar-check me-1"></i>{{ $asist }}% asistencia
                        </span>
                    @else
                        <span style="color:#9ca3af;"><i class="bi bi-dash me-1"></i>Sin asistencia</span>
                    @endif
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:.6rem;flex-shrink:0;">
                @if($nota !== null)
                    <div style="text-align:center;">
                        <span class="nota-badge {{ $notaClass }}" style="font-size:.8rem;padding:.3rem .5rem;">
                            {{ $nota }}
                        </span>
                        @if($letra)
                            <div style="font-size:.65rem;color:#9ca3af;margin-top:.1rem;">{{ $letra }}</div>
                        @endif
                    </div>
                @else
                    <span style="font-size:.75rem;color:#9ca3af;background:#f1f5f9;border-radius:6px;padding:.25rem .5rem;">Sin nota</span>
                @endif
                <a href="{{ route('portal.docente.observaciones', $asignacion) }}?estudiante={{ $m->estudiante_id }}"
                   style="background:#fffbeb;color:#92400e;border-radius:7px;padding:.3rem .55rem;font-size:.7rem;font-weight:600;text-decoration:none;"
                   title="Ver observaciones">
                    <i class="bi bi-chat-square-text"></i>
                </a>
                <a href="{{ route('portal.docente.estudiantes.ficha', [$asignacion, $m]) }}"
                   style="background:#eff6ff;color:#1d4ed8;border-radius:7px;padding:.3rem .55rem;font-size:.7rem;font-weight:600;text-decoration:none;"
                   title="Ver ficha completa">
                    <i class="bi bi-person-vcard-fill"></i>
                </a>
            </div>
        </div>
        @empty
        <div style="padding:2rem;text-align:center;color:#9ca3af;">
            <i class="bi bi-people" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
            No hay estudiantes matriculados en este grupo.
        </div>
        @endforelse
    </div>


    @if($matriculas->count() > 0)

    {{-- Resumen estadístico --}}
    @php
        $conNota    = $matriculas->whereNotNull('_nota');
        $promedioGrupo = $conNota->count() ? round($conNota->avg('_nota'), 1) : null;
        $aprobados  = $conNota->where('_nota', '>=', 60)->count();
        $reprobados = $conNota->where('_nota', '<', 60)->count();
    @endphp
    <div class="dm-toolbar" style="padding:.85rem 1rem;background:#f8fafc;border-top:1px solid #e2e8f0;display:flex;gap:1.5rem;flex-wrap:wrap;align-items:center;">
        <span class="dm-text-muted" style="font-size:.75rem;color:#64748b;font-weight:600;">Resumen del grupo:</span>
        @if($promedioGrupo !== null)
        <span class="dm-text-primary" style="font-size:.78rem;">
            Promedio: <strong>{{ $promedioGrupo }}</strong>
        </span>
        <span style="font-size:.78rem;color:#15803d;">
            <i class="bi bi-check-circle me-1"></i>Aprobados: <strong>{{ $aprobados }}</strong>
        </span>
        <span style="font-size:.78rem;color:#dc2626;">
            <i class="bi bi-x-circle me-1"></i>Reprobados: <strong>{{ $reprobados }}</strong>
        </span>
        @else
        <span style="font-size:.78rem;color:#9ca3af;">Sin calificaciones registradas aún.</span>
        @endif
    </div>
    @endif
</div>

@push('scripts')
<script>
function filtrarEstudiantes() {
    const q     = (document.getElementById('buscador-est').value || '').toLowerCase().trim();
    const fnota = document.getElementById('filtro-nota').value;
    const fasist = document.getElementById('filtro-asist').value;

    const items = document.querySelectorAll('.est-item');
    let visible = 0;

    items.forEach(item => {
        const nombre  = item.dataset.nombre || '';
        const mat     = item.dataset.matricula || '';
        const nota    = item.dataset.nota !== '' ? parseFloat(item.dataset.nota) : null;
        const asist   = item.dataset.asist !== '' ? parseFloat(item.dataset.asist) : null;

        // Text filter
        const matchText = !q || nombre.includes(q) || mat.includes(q);

        // Note filter
        let matchNota = true;
        if (fnota === 'aprobado')  matchNota = nota !== null && nota >= 70;
        else if (fnota === 'reprobado') matchNota = nota !== null && nota < 70;
        else if (fnota === 'sin_nota')  matchNota = nota === null;
        else if (fnota === 'A')   matchNota = nota !== null && nota >= 90;
        else if (fnota === 'B')   matchNota = nota !== null && nota >= 75 && nota < 90;
        else if (fnota === 'C')   matchNota = nota !== null && nota >= 70 && nota < 75;

        // Attendance filter
        let matchAsist = true;
        if (fasist === 'alta')      matchAsist = asist !== null && asist >= 80;
        else if (fasist === 'media') matchAsist = asist !== null && asist >= 60 && asist < 80;
        else if (fasist === 'baja')  matchAsist = asist !== null && asist < 60;
        else if (fasist === 'sin_asist') matchAsist = asist === null;

        const show = matchText && matchNota && matchAsist;
        item.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('contador-visible').textContent = visible + ' registrado(s)';
    document.getElementById('sin-resultados').style.display = visible === 0 ? '' : 'none';
}

function limpiarFiltros() {
    document.getElementById('buscador-est').value = '';
    document.getElementById('filtro-nota').value = '';
    document.getElementById('filtro-asist').value = '';
    filtrarEstudiantes();
}
</script>
@endpush
@endsection
