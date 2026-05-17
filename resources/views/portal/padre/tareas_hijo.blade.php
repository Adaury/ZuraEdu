@extends('layouts.portal')
@section('title', 'Tareas de ' . $estudiante->nombres)
@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'tareas', 'estudiante' => $estudiante])
@endsection

@section('content')
<div class="prt-page-header">
    <div>
        <h4 class="prt-page-title">
            <i class="bi bi-check2-square me-2"></i>Tareas — {{ $estudiante->nombre_completo }}
        </h4>
        @if($matricula)
        <p class="prt-page-subtitle">{{ $matricula->grupo?->nombre_completo }} — {{ $schoolYear?->nombre }}</p>
        @endif
    </div>
    <a href="{{ route('portal.padre.hijo', $estudiante) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver al perfil
    </a>
</div>

@if(! $matricula)
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-check2-square" style="font-size:2.5rem;opacity:.4;"></i>
        <p class="mt-3 mb-0">El estudiante no tiene una matrícula activa.</p>
    </div>
</div>
@elseif($tareasPorMateria->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-check2-square" style="font-size:2.5rem;opacity:.4;"></i>
        <p class="mt-3 mb-0">No hay tareas registradas para {{ $estudiante->nombres }} este año.</p>
    </div>
</div>
@else

@foreach($tareasPorMateria as $grupo)
@php
    $asignacion = $grupo['asignacion'];
    $tareas     = $grupo['tareas'];
    $entregas   = $grupo['entregas'];
    $pendientes = $tareas->filter(fn($t) => !isset($entregas[$t->id]) || $entregas[$t->id]->estado === 'pendiente')->count();
@endphp
<div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
        <div style="width:36px;height:36px;background:linear-gradient(135deg,#1e40af,#3b82f6);border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-journals" style="color:#fff;font-size:.9rem;"></i>
        </div>
        <div class="flex-grow-1">
            <span class="fw-bold" style="color:#1e3a6e;">{{ $asignacion?->asignatura?->nombre ?? '—' }}</span>
            <small class="text-muted ms-2">
                <i class="bi bi-person me-1"></i>{{ $asignacion?->docente?->nombre_completo ?? '—' }}
            </small>
        </div>
        @if($pendientes > 0)
        <span class="badge" style="background:#fef3c7;color:#d97706;font-size:.72rem;">
            {{ $pendientes }} pendiente(s)
        </span>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @foreach($tareas as $tarea)
            @php
                $entrega   = $entregas[$tarea->id] ?? null;
                $estado    = $entrega?->estado ?? 'pendiente';
                $colores   = ['pendiente' => ['bg'=>'#fef3c7','c'=>'#d97706'], 'entregada' => ['bg'=>'#dbeafe','c'=>'#2563eb'], 'revisada' => ['bg'=>'#d1fae5','c'=>'#059669']];
                $col       = $colores[$estado] ?? ['bg'=>'#f1f5f9','c'=>'#64748b'];
                $vencida   = !$entrega && $tarea->fecha_limite->isPast();
            @endphp
            <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                <div style="width:8px;height:8px;border-radius:50%;background:{{ $vencida ? '#ef4444' : $col['c'] }};flex-shrink:0;"></div>
                <div class="flex-grow-1" style="min-width:0;">
                    <div class="fw-semibold text-truncate" style="font-size:.87rem;color:#1e293b;">
                        {{ $tarea->titulo }}
                    </div>
                    <small class="text-muted">
                        {{ \App\Models\Tarea::TIPOS[$tarea->tipo] ?? $tarea->tipo }}
                        &nbsp;·&nbsp;
                        <i class="bi bi-calendar3 me-1"></i>
                        <span style="{{ $vencida ? 'color:#ef4444;font-weight:600;' : '' }}">
                            {{ $tarea->fecha_limite->format('d/m/Y') }}
                            @if($vencida) (vencida) @endif
                        </span>
                        @if($tarea->puntos_valor)
                        &nbsp;·&nbsp; {{ $tarea->puntos_valor }} pts
                        @endif
                    </small>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <span style="background:{{ $col['bg'] }};color:{{ $col['c'] }};border-radius:99px;padding:2px 10px;font-size:.72rem;font-weight:700;">
                        {{ ucfirst($estado) }}
                    </span>
                    @if($entrega?->calificacion !== null && $tarea->puntos_valor)
                    <div style="font-size:.75rem;color:#64748b;margin-top:2px;">
                        {{ $entrega->calificacion }} / {{ $tarea->puntos_valor }}
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endforeach

@endif
@endsection
