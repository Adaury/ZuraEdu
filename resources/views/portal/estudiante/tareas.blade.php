@extends('layouts.portal')
@section('page-title', 'Mis Tareas')
@section('portal-name', 'Portal Estudiante')

@section('sidebar')
    @include('portal.estudiante._sidebar', ['activeKey' => 'tareas'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.estudiante.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.estudiante.tareas') }}" class="prt-nav-item active">
        <i class="bi bi-check2-square"></i>Tareas
    </a>
    <a href="{{ route('portal.estudiante.notificaciones') }}" class="prt-nav-item">
        <i class="bi bi-bell-fill"></i>Notif.
    </a>
@endsection

@push('styles')
<style>
.tarea-card {
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem 1.1rem;
    margin-bottom: .7rem;
    border-left: 4px solid var(--t-color, #3b82f6);
    transition: box-shadow .15s;
}
.tarea-card:hover { box-shadow: 0 3px 14px rgba(59,130,246,.10); }
.badge-est {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .2rem .65rem;
    border-radius: 99px;
    font-size: .7rem;
    font-weight: 700;
    color: #fff;
}
.badge-tipo-est {
    display: inline-block;
    padding: .15rem .45rem;
    border-radius: 99px;
    font-size: .67rem;
    font-weight: 700;
    color: #fff;
}
.dias-restantes {
    font-size: .7rem;
    font-weight: 700;
    padding: .15rem .45rem;
    border-radius: 6px;
}
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;margin-bottom:1.25rem;">
    <h2 style="font-size:1rem;font-weight:800;margin:0;">
        <i class="bi bi-check2-square me-2" style="color:#3b82f6;"></i>Mis Tareas
    </h2>
    @php
        $pendientes = $tareas->filter(fn($t) => ($entregas->get($t->id)?->estado ?? 'pendiente') === 'pendiente' && !$t->fecha_limite->isPast())->count();
        $vencidas   = $tareas->filter(fn($t) => ($entregas->get($t->id)?->estado ?? 'pendiente') === 'pendiente' && $t->fecha_limite->isPast())->count();
    @endphp
    @if($pendientes > 0 || $vencidas > 0)
    <div class="d-flex gap-2">
        @if($pendientes > 0)
        <span class="badge-est" style="background:#f59e0b;">
            <i class="bi bi-hourglass-split"></i>{{ $pendientes }} pendiente{{ $pendientes > 1 ? 's' : '' }}
        </span>
        @endif
        @if($vencidas > 0)
        <span class="badge-est" style="background:#ef4444;">
            <i class="bi bi-exclamation-triangle-fill"></i>{{ $vencidas }} vencida{{ $vencidas > 1 ? 's' : '' }}
        </span>
        @endif
    </div>
    @endif
</div>

{{-- Filtros --}}
@php
    $filtros = [
        'todas'     => ['label' => 'Todas',      'count' => $tareas->count()],
        'pendiente' => ['label' => 'Pendientes',  'count' => $tareas->filter(fn($t) => ($entregas->get($t->id)?->estado ?? 'pendiente') === 'pendiente' && !$t->fecha_limite->isPast())->count()],
        'entregada' => ['label' => 'Entregadas',  'count' => $tareas->filter(fn($t) => in_array($entregas->get($t->id)?->estado ?? '', ['entregada', 'revisada']))->count()],
        'vencida'   => ['label' => 'Vencidas',    'count' => $vencidas],
    ];
@endphp
<div class="d-flex gap-2 flex-wrap mb-3" x-data="{ filtro: 'todas' }">
    @foreach($filtros as $fk => $fv)
    <button
        @click="filtro = '{{ $fk }}'; aplicarFiltro('{{ $fk }}')"
        :class="filtro === '{{ $fk }}' ? 'btn-primary' : 'btn-outline-secondary'"
        class="btn btn-sm"
        style="font-size:.74rem;">
        {{ $fv['label'] }}
        @if($fv['count'] > 0)
        <span style="background:rgba(0,0,0,.15);border-radius:99px;padding:.05rem .35rem;font-size:.67rem;margin-left:.2rem;">{{ $fv['count'] }}</span>
        @endif
    </button>
    @endforeach
</div>

@if($tareas->isEmpty())
<div style="text-align:center;padding:3.5rem 1rem;color:var(--prt-muted);">
    <i class="bi bi-inbox" style="font-size:2.5rem;opacity:.3;"></i>
    <p style="margin-top:.75rem;font-size:.88rem;">No tienes tareas asignadas por el momento.</p>
</div>
@else

<div id="lista-tareas-est">
@foreach($tareas as $tarea)
@php
    $entrega      = $entregas->get($tarea->id);
    $estado       = $entrega?->estado ?? 'pendiente';
    $vencida      = $tarea->fecha_limite->isPast() && $estado === 'pendiente';
    $hoy          = now();
    $diasRestantes = $hoy->diffInDays($tarea->fecha_limite, false);

    // Estado visual
    if ($vencida) {
        $estadoLabel = 'Vencida';
        $estadoColor = '#ef4444';
        $estadoIcon  = 'bi-exclamation-triangle-fill';
    } elseif ($estado === 'revisada') {
        $estadoLabel = 'Revisada';
        $estadoColor = '#10b981';
        $estadoIcon  = 'bi-check-circle-fill';
    } elseif ($estado === 'entregada') {
        $estadoLabel = 'Entregada';
        $estadoColor = '#3b82f6';
        $estadoIcon  = 'bi-send-check-fill';
    } else {
        $estadoLabel = 'Pendiente';
        $estadoColor = '#f59e0b';
        $estadoIcon  = 'bi-hourglass-split';
    }

    // Color tipo
    $tipoColor = \App\Models\Tarea::COLORES_TIPO[$tarea->tipo] ?? '#6b7280';

    // Data-filtro: para el filtro JS
    if ($vencida) $dataFiltro = 'vencida';
    elseif (in_array($estado, ['entregada', 'revisada'])) $dataFiltro = 'entregada';
    else $dataFiltro = 'pendiente';
@endphp
<div class="tarea-card" style="--t-color:{{ $tipoColor }};"
     data-filtro="{{ $dataFiltro }}">
    <div class="d-flex align-items-start gap-2 flex-wrap">
        <div style="flex:1;min-width:0;">
            {{-- Materia + Tipo --}}
            <div class="d-flex gap-2 align-items-center flex-wrap mb-1">
                <span class="badge-tipo-est" style="background:{{ $tipoColor }};">
                    {{ $tarea->tipo_label }}
                </span>
                <span style="font-size:.74rem;color:var(--prt-muted);font-weight:600;">
                    {{ $tarea->asignacion?->asignatura?->nombre ?? '—' }}
                </span>
            </div>

            {{-- Título --}}
            <h3 style="font-size:.92rem;font-weight:700;margin:0 0 .2rem;color:#1e293b;">
                {{ $tarea->titulo }}
            </h3>

            {{-- Descripción --}}
            @if($tarea->descripcion)
            <p style="font-size:.78rem;color:var(--prt-muted);margin:0 0 .5rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                {{ $tarea->descripcion }}
            </p>
            @endif

            {{-- Meta --}}
            <div class="d-flex gap-3 flex-wrap align-items-center" style="font-size:.74rem;color:var(--prt-muted);">
                <span>
                    <i class="bi bi-calendar3 me-1"></i>{{ $tarea->fecha_limite->format('d/m/Y') }}
                    @if(!$vencida && $estado === 'pendiente')
                        @if($diasRestantes <= 0)
                        <span class="dias-restantes" style="background:#fef2f2;color:#dc2626;">hoy</span>
                        @elseif($diasRestantes <= 3)
                        <span class="dias-restantes" style="background:#fef3c7;color:#d97706;">{{ $diasRestantes }}d</span>
                        @else
                        <span class="dias-restantes" style="background:#f0fdf4;color:#16a34a;">{{ $diasRestantes }}d</span>
                        @endif
                    @endif
                </span>
                @if($tarea->puntos_valor)
                <span><i class="bi bi-star-fill me-1" style="color:#f59e0b;"></i>{{ $tarea->puntos_valor }} pts</span>
                @endif
                @if($entrega?->calificacion !== null)
                <span style="font-weight:700;color:#059669;">
                    <i class="bi bi-award-fill me-1"></i>{{ $entrega->calificacion }}
                    @if($tarea->puntos_valor) / {{ $tarea->puntos_valor }} @endif
                </span>
                @endif
            </div>

            {{-- Retroalimentación --}}
            @if($entrega?->notas_docente)
            <div style="background:#f0fdf4;border-left:3px solid #10b981;border-radius:6px;padding:.45rem .7rem;margin-top:.55rem;font-size:.76rem;color:#065f46;">
                <i class="bi bi-chat-left-text-fill me-1"></i>
                <strong>Docente:</strong> {{ $entrega->notas_docente }}
            </div>
            @endif
        </div>

        {{-- Badge estado --}}
        <div class="flex-shrink-0 mt-1">
            <span class="badge-est" style="background:{{ $estadoColor }};">
                <i class="bi {{ $estadoIcon }}"></i>{{ $estadoLabel }}
            </span>
        </div>
    </div>
</div>
@endforeach
</div>

@endif

@push('scripts')
<script>
function aplicarFiltro(filtro) {
    document.querySelectorAll('#lista-tareas-est [data-filtro]').forEach(el => {
        if (filtro === 'todas' || el.dataset.filtro === filtro) {
            el.style.display = '';
        } else {
            el.style.display = 'none';
        }
    });
}
</script>
@endpush

@endsection
