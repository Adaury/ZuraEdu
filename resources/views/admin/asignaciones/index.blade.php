@extends('layouts.admin')

@section('page-title', 'Asignaciones Docentes')

@push('styles')
<style>
    .table-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 6px rgba(30,58,110,.05);
    }
    .table thead th {
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: #6b7280;
        padding: .75rem 1rem;
        white-space: nowrap;
    }
    .table tbody td {
        padding: .7rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f3f4f6;
        font-size: .84rem;
    }
    .table tbody tr:last-child td { border-bottom: none; }
    .table tbody tr:hover td { background: #fafbff; }
    .asignatura-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        border-radius: 8px;
        padding: .28rem .7rem;
        font-size: .76rem;
        font-weight: 700;
        letter-spacing: .01em;
        border: 1.5px solid transparent;
    }
    .asignatura-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .grupo-chip {
        background: #eef2ff;
        color: var(--primary);
        border-radius: 6px;
        padding: .15rem .5rem;
        font-size: .75rem;
        font-weight: 700;
    }
    .docente-cell {
        display: flex;
        align-items: center;
        gap: .55rem;
    }
    .docente-avatar {
        width: 30px; height: 30px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .65rem;
        font-weight: 800;
        color: #fff;
        flex-shrink: 0;
    }
    .badge-activo   { background: #d1fae5; color: #065f46; }
    .badge-inactivo { background: #f3f4f6; color: #6b7280; }
    .status-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: .3rem;
    }
    .status-dot.activo   { background: #10b981; }
    .status-dot.inactivo { background: #d1d5db; }
    .section-divider {
        background: #f8fafc;
        padding: .5rem 1rem;
        border-bottom: 1px solid #e5e7eb;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--primary);
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .section-divider .count-badge {
        background: var(--primary);
        color: #fff;
        border-radius: 20px;
        padding: .08rem .45rem;
        font-size: .65rem;
    }
    .btn-action {
        padding: .22rem .5rem;
        font-size: .75rem;
        border-radius: 6px;
        line-height: 1.4;
    }
    .empty-state {
        text-align: center;
        padding: 3.5rem 2rem;
        color: #9ca3af;
    }
    .empty-state i { font-size: 2.5rem; display: block; margin-bottom: .75rem; color: #d1d5db; }

    [data-theme="dark"] .table-card { background: #1e293b; border-color: #334155; }
</style>
@endpush

@section('content')

{{-- Page Header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h4 fw-bold mb-0" style="color:var(--primary);">
            <i class="bi bi-diagram-3 me-2"></i>Asignaciones Docentes
        </h1>
        <p class="text-muted mb-0 mt-1" style="font-size:.82rem;">
            @if($schoolYear)
                Año escolar: <strong>{{ $schoolYear->nombre }}</strong> &mdash;
            @endif
            {{ $asignaciones->count() }} asignación{{ $asignaciones->count() !== 1 ? 'es' : '' }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.asignaciones.lista-pdf') }}" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.asignaciones.lista-excel') }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('admin.asignaciones.create') }}" class="btn btn-sm fw-semibold"
           style="background:var(--primary);color:#fff;border-radius:8px;padding:.45rem 1rem;">
            <i class="bi bi-plus-lg me-1"></i>Nueva Asignación
        </a>
    </div>
</div>

{{-- Session alerts --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert" style="border-radius:10px;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert" style="border-radius:10px;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Filtro por ciclo --}}
<div class="d-flex gap-2 mb-3">
    <button type="button" class="btn btn-sm fw-semibold active" id="tab-todos"
            onclick="filtrarCiclo('todos')"
            style="border-radius:8px;border:1.5px solid #e5e7eb;background:#f8fafc;color:#374151;font-size:.78rem;">
        Todos
    </button>
    <button type="button" class="btn btn-sm fw-semibold" id="tab-primer"
            onclick="filtrarCiclo('primer')"
            style="border-radius:8px;border:1.5px solid #dbeafe;background:#eff6ff;color:#1e40af;font-size:.78rem;">
        <i class="bi bi-1-circle me-1"></i>Primer Ciclo
    </button>
    <button type="button" class="btn btn-sm fw-semibold" id="tab-segundo"
            onclick="filtrarCiclo('segundo')"
            style="border-radius:8px;border:1.5px solid #d1fae5;background:#ecfdf5;color:#065f46;font-size:.78rem;">
        <i class="bi bi-2-circle me-1"></i>Segundo Ciclo
    </button>
</div>

@if($asignaciones->isEmpty())
    <div class="table-card">
        <div class="empty-state">
            <i class="bi bi-diagram-3"></i>
            <h6 class="fw-semibold mb-1" style="color:#6b7280;">No hay asignaciones registradas</h6>
            <p class="mb-3" style="font-size:.83rem;">Asigna docentes a grupos y asignaturas para habilitar el registro de calificaciones.</p>
            <a href="{{ route('admin.asignaciones.create') }}" class="btn btn-sm fw-semibold"
               style="background:var(--primary);color:#fff;border-radius:8px;">
                <i class="bi bi-plus-lg me-1"></i>Crear primera asignación
            </a>
        </div>
    </div>
@else
    @php
        $niveles = [1=>'1ro',2=>'2do',3=>'3ro',4=>'4to',5=>'5to',6=>'6to'];

        // Agrupar por ciclo → grupo → asignaciones, ordenando por nivel de grado
        $byCiclo = $asignaciones
            ->sortBy(fn($a) => $a->grupo?->grado?->nivel ?? 99)
            ->groupBy(fn($a) => $a->grupo?->grado?->ciclo ?? 'primer_ciclo');

        $cicloConfig = [
            'primer_ciclo'  => ['label' => 'Primer Ciclo',  'icon' => 'bi-1-circle', 'bg' => '#eff6ff', 'color' => '#1e40af', 'border' => '#bfdbfe'],
            'segundo_ciclo' => ['label' => 'Segundo Ciclo', 'icon' => 'bi-2-circle', 'bg' => '#ecfdf5', 'color' => '#065f46', 'border' => '#6ee7b7'],
        ];
    @endphp

    @foreach(['primer_ciclo','segundo_ciclo'] as $cicloKey)
        @if($byCiclo->has($cicloKey))
        @php
            $cfg = $cicloConfig[$cicloKey];
            $cicloAsigs = $byCiclo[$cicloKey];
            $byGrupo = $cicloAsigs->groupBy(fn($a) => $a->grupo_id);
        @endphp

        {{-- Encabezado de ciclo --}}
        <div class="d-flex align-items-center gap-2 mb-2 ciclo-header" data-ciclo="{{ $cicloKey }}"
             style="padding:.5rem .75rem;border-radius:10px;background:{{ $cfg['bg'] }};border:1.5px solid {{ $cfg['border'] }};">
            <i class="bi {{ $cfg['icon'] }}" style="color:{{ $cfg['color'] }};font-size:1rem;"></i>
            <span style="font-size:.78rem;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:{{ $cfg['color'] }};">
                {{ $cfg['label'] }}
            </span>
            <span style="background:{{ $cfg['color'] }};color:#fff;border-radius:20px;padding:.05rem .45rem;font-size:.65rem;font-weight:700;">
                {{ $cicloAsigs->count() }}
            </span>
        </div>

        <div class="table-card mb-4 ciclo-block" data-ciclo="{{ $cicloKey }}">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Asignatura</th>
                            <th>Grupo</th>
                            <th>Docente</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($byGrupo as $grupoId => $grupoAsignaciones)
                            @php
                                $firstA = $grupoAsignaciones->first();
                                $g = $firstA->grupo;
                                $pref   = $niveles[$g->grado->nivel ?? 0] ?? ($g->grado->nivel.'mo');
                                $gLabel = $pref . ' ' . ($g->seccion->nombre ?? '');
                            @endphp
                            <tr>
                                <td colspan="5" class="p-0">
                                    <div class="section-divider">
                                        <i class="bi bi-grid-3x3-gap"></i>
                                        {{ $gLabel }}
                                        <span class="count-badge">{{ $grupoAsignaciones->count() }}</span>
                                        @if($g->aula)
                                            <span style="font-weight:400;opacity:.7;">&middot; Aula {{ $g->aula }}</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @foreach($grupoAsignaciones->sortBy(fn($a) => $a->asignatura->nombre) as $asig)
                                @php
                                    $color = $asig->asignatura->color ?? '#6b7280';
                                    $r  = hexdec(substr(ltrim($color,'#'),0,2));
                                    $g2 = hexdec(substr(ltrim($color,'#'),2,2));
                                    $b  = hexdec(substr(ltrim($color,'#'),4,2));
                                    $bgStyle = "background:rgba({$r},{$g2},{$b},.1);color:{$color};border-color:rgba({$r},{$g2},{$b},.25);";
                                @endphp
                                <tr>
                                    <td>
                                        <span class="asignatura-badge" style="{{ $bgStyle }}">
                                            <span class="asignatura-dot" style="background:{{ $color }};"></span>
                                            {{ $asig->asignatura->nombre }}
                                            @if($asig->asignatura->codigo)
                                                <span style="opacity:.6;font-weight:500;">({{ $asig->asignatura->codigo }})</span>
                                            @endif
                                        </span>
                                        @if($asig->asignatura->horas_semanales)
                                            <div style="font-size:.68rem;color:#9ca3af;margin-top:.2rem;padding-left:.2rem;">
                                                {{ $asig->asignatura->horas_semanales }} hrs/sem
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="grupo-chip">
                                            {{ $pref . ' ' . ($asig->grupo->seccion->nombre ?? '') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($asig->docente_id)
                                        <div class="docente-cell">
                                            <div class="docente-avatar">
                                                {{ strtoupper(substr($asig->docente?->apellidos ?? 'D', 0, 1)) }}
                                            </div>
                                            <div>
                                                <div style="font-weight:600;color:#1e293b;font-size:.82rem;line-height:1.2;">
                                                    {{ $asig->docente->nombre_completo }}
                                                </div>
                                                @if($asig->docente->especialidad)
                                                    <div style="font-size:.7rem;color:#9ca3af;">{{ $asig->docente->especialidad }}</div>
                                                @endif
                                            </div>
                                        </div>
                                        @else
                                        {{-- Sin docente: mostrar selector rápido --}}
                                        <form method="POST"
                                              action="{{ route('admin.asignaciones.asignarDocente', $asig) }}"
                                              class="d-flex gap-1 align-items-center">
                                            @csrf @method('PATCH')
                                            <select name="docente_id" class="form-select form-select-sm"
                                                    style="font-size:.75rem;border-radius:6px;min-width:140px;border-color:#fbbf24;">
                                                <option value="">— Asignar docente —</option>
                                                @foreach(\App\Models\Docente::activos()->orderBy('apellidos')->get() as $doc)
                                                    <option value="{{ $doc->id }}">{{ $doc->nombre_completo }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-warning"
                                                    style="font-size:.7rem;padding:.2rem .5rem;border-radius:6px;"
                                                    title="Guardar docente">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </td>
                                    <td>
                                        <span style="font-size:.78rem;font-weight:600;">
                                            <span class="status-dot {{ $asig->activo ? 'activo' : 'inactivo' }}"></span>
                                            {{ $asig->activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.asignaciones.destroy', $asig) }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar la asignación de {{ $asig->asignatura->nombre }} en {{ $gLabel }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-action"
                                                    style="background:#fff0f0;color:var(--secondary);border:1px solid #fecaca;"
                                                    title="Eliminar">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    @endforeach
@endif

@push('scripts')
<script>
function filtrarCiclo(ciclo) {
    document.querySelectorAll('.ciclo-block, .ciclo-header').forEach(el => {
        if (ciclo === 'todos') {
            el.style.display = '';
        } else {
            const key = ciclo === 'primer' ? 'primer_ciclo' : 'segundo_ciclo';
            el.style.display = el.dataset.ciclo === key ? '' : 'none';
        }
    });
    ['todos','primer','segundo'].forEach(t => {
        const btn = document.getElementById('tab-' + t);
        if (!btn) return;
        btn.classList.toggle('active', t === ciclo);
        btn.style.opacity = t === ciclo ? '1' : '.65';
        btn.style.fontWeight = t === ciclo ? '800' : '600';
    });
}
</script>
@endpush

@endsection
