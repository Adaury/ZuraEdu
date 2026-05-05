@extends('layouts.admin')
@section('page-title', $horario->nombre)

@section('content')

{{-- Alertas --}}
@foreach(['success','warning','error'] as $t)
@if(session($t))
<div class="alert alert-{{ $t === 'error' ? 'danger' : $t }} alert-dismissible fade show" role="alert" style="border-radius:12px;">
    {{ session($t) }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@endforeach

{{-- Header --}}
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('scheduling.horarios.index') }}" class="text-muted text-decoration-none" style="font-size:.82rem;">
                <i class="bi bi-arrow-left"></i> Horarios
            </a>
            <span style="color:#cbd5e1;">·</span>
            <h5 class="mb-0 fw-bold">{{ $horario->nombre }}</h5>
            @if($horario->estado === 'publicado')
            <span class="badge" style="background:#dcfce7;color:#15803d;border-radius:20px;font-size:.7rem;">Publicado</span>
            @else
            <span class="badge" style="background:#f1f5f9;color:#64748b;border-radius:20px;font-size:.7rem;">Borrador</span>
            @endif
        </div>
        <div style="font-size:.78rem;color:#64748b;margin-top:3px;">
            Puntaje: <strong style="color:{{ $horario->score >= 90 ? '#16a34a' : ($horario->score >= 70 ? '#d97706' : '#dc2626') }}">{{ $horario->score }}%</strong>
            &nbsp;·&nbsp; Generado: {{ $horario->generado_en?->format('d/m/Y H:i') ?? '—' }}
        </div>
    </div>
    <div class="d-flex gap-2">
        {{-- Filtro por curso --}}
        <form method="GET" class="d-flex gap-2 align-items-center">
            <select name="curso_id" class="form-select form-select-sm" style="border-radius:8px;max-width:200px;" onchange="this.form.submit()">
                <option value="">Todos los cursos</option>
                @foreach($cursos as $c)
                <option value="{{ $c->id }}" {{ $cursoId == $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                @endforeach
            </select>
        </form>
        {{-- Publicar / Despublicar --}}
        <form action="{{ route('scheduling.horarios.publicar', $horario) }}" method="POST">
            @csrf
            <button class="btn btn-sm {{ $horario->estado === 'publicado' ? 'btn-outline-secondary' : 'btn-success' }}"
                    style="border-radius:8px;">
                <i class="bi bi-{{ $horario->estado === 'publicado' ? 'eye-slash' : 'check-circle' }} me-1"></i>
                {{ $horario->estado === 'publicado' ? 'Despublicar' : 'Publicar' }}
            </button>
        </form>
    </div>
</div>

{{-- Conflictos sin asignar --}}
@if(!empty($horario->conflictos))
<div class="card border-warning mb-4" style="border-radius:12px;">
    <div class="card-header bg-warning bg-opacity-10 d-flex align-items-center gap-2 py-2 px-3">
        <i class="bi bi-exclamation-triangle text-warning"></i>
        <strong style="font-size:.85rem;">{{ count($horario->conflictos) }} clase(s) sin asignar</strong>
        <a href="{{ route('scheduling.horarios.generar') }}" class="ms-auto btn btn-warning btn-sm" style="border-radius:8px;font-size:.75rem;">
            Reintentar generación
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:.79rem;">
            <thead><tr><th class="ps-3">Materia</th><th>Profesor</th><th>Curso</th></tr></thead>
            <tbody>
            @foreach($horario->conflictos as $c)
            <tr>
                <td class="ps-3">{{ $c['materia'] }}</td>
                <td>{{ $c['profesor'] }}</td>
                <td>{{ $c['curso'] }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Grilla del horario --}}
@php
    $dias = ['lunes'=>'Lunes','martes'=>'Martes','miercoles'=>'Miércoles','jueves'=>'Jueves','viernes'=>'Viernes'];
@endphp

<div class="card border-0" style="border-radius:20px;box-shadow:0 4px 24px rgba(0,0,0,.06);overflow:hidden;">
    <div class="table-responsive">
        <table class="table table-bordered mb-0" style="font-size:.8rem;border-color:#f0f4f8;">
            <thead style="background:#f8faff;">
                <tr>
                    <th style="min-width:90px;font-size:.72rem;color:#6b7280;text-align:center;">Franja</th>
                    @foreach($dias as $dKey => $dLabel)
                    <th class="text-center" style="font-size:.76rem;color:#1e40af;font-weight:700;min-width:140px;">{{ $dLabel }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @foreach($franjas as $franja)
            @if($franja->es_recreo)
            <tr style="background:#fefce8;">
                <td colspan="6" class="text-center py-2" style="font-size:.76rem;color:#92400e;font-weight:600;border-color:#fde68a;">
                    <i class="bi bi-cup-hot me-1"></i>Recreo — {{ $franja->hora_inicio }} – {{ $franja->hora_fin }}
                </td>
            </tr>
            @else
            <tr>
                <td class="text-center align-middle" style="background:#f8faff;border-color:#f0f4f8;">
                    <div style="font-size:.7rem;font-weight:700;color:#374151;">{{ $franja->label }}</div>
                    <div style="font-size:.65rem;color:#94a3b8;">{{ $franja->hora_inicio }} – {{ $franja->hora_fin }}</div>
                </td>
                @foreach($dias as $dKey => $dLabel)
                @php $cel = $grid[$franja->id][$dKey] ?? null; @endphp
                <td class="text-center align-middle p-1" style="border-color:#f0f4f8;">
                    @if($cel)
                    @php
                        $color  = $cel->asignacion->materia->color ?? '#3b82f6';
                        $alpha  = '18';
                        $border = $color;
                    @endphp
                    <div style="background:{{ $color }}{{ $alpha }};border-left:3px solid {{ $border }};border-radius:8px;padding:.35rem .5rem;text-align:left;">
                        <div style="font-weight:700;font-size:.77rem;color:#1e293b;line-height:1.2;">
                            {{ $cel->asignacion->materia->nombre }}
                        </div>
                        <div style="font-size:.68rem;color:#64748b;margin-top:2px;">
                            <i class="bi bi-person-fill" style="font-size:.6rem;"></i>
                            {{ $cel->asignacion->profesor->apellidos }}
                        </div>
                        <div style="font-size:.65rem;color:#94a3b8;margin-top:1px;">
                            <i class="bi bi-people" style="font-size:.6rem;"></i>
                            {{ $cel->asignacion->curso->nombre }}
                            @if($cel->aula)
                                &nbsp;· {{ $cel->aula->nombre }}
                            @endif
                        </div>
                    </div>
                    @else
                    <span style="color:#e2e8f0;font-size:.75rem;">—</span>
                    @endif
                </td>
                @endforeach
            </tr>
            @endif
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
