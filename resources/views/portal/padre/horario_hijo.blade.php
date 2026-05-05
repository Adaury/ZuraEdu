@extends('layouts.portal')

@section('title', 'Horario de ' . $estudiante->nombres)

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'horario', 'estudiante' => $estudiante])
@endsection

@section('content')
<div class="prt-page-header">
    <div>
        <h4 class="prt-page-title">
            <i class="bi bi-calendar3 me-2"></i>Horario de {{ $estudiante->nombre_completo }}
        </h4>
        @if($matricula)
        <p class="prt-page-subtitle">
            {{ $matricula->grupo?->nombre_completo }} — {{ $schoolYear?->nombre }}
        </p>
        @endif
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('portal.padre.hijo', $estudiante) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
        @if($horarioActivo && !empty($gridHorario))
        <a href="{{ route('portal.padre.hijo.horario.pdf', $estudiante) }}" class="btn btn-sm btn-danger">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('portal.padre.hijo.horario.excel', $estudiante) }}" class="btn btn-sm btn-success">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        @endif
    </div>
</div>

@if(! $horarioActivo || empty($gridHorario))
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-calendar-x" style="font-size:2.5rem;opacity:.4;"></i>
        <p class="mt-3 mb-0">El horario aún no está disponible o no se ha publicado.</p>
    </div>
</div>
@else

@php
$colores = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#14b8a6','#f97316','#6366f1'];
$materias = [];
$idx = 0;
foreach ($gridHorario as $fId => $dias) {
    foreach ($dias as $dia => $detalle) {
        $nom = $detalle->asignacion?->asignatura?->nombre ?? '';
        if ($nom && ! isset($materias[$nom])) {
            $materias[$nom] = $colores[$idx % count($colores)];
            $idx++;
        }
    }
}
@endphp

<div class="card border-0 shadow-sm mb-3 overflow-x-auto">
    <div class="card-body p-0">
        <table class="sch-table w-100" style="min-width:560px;">
            <thead>
                <tr>
                    <th class="franja-col text-center" style="width:90px;font-size:.72rem;color:#6b7280;padding:.6rem .5rem;">Franja</th>
                    @foreach($diasConfig as $dia)
                    <th class="text-center" style="font-size:.78rem;font-weight:700;color:#1e3a6e;padding:.6rem .4rem;text-transform:capitalize;">
                        {{ ucfirst($dia) }}
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($franjasHorario as $franja)
                <tr>
                    <td class="franja-col text-center" style="font-size:.7rem;color:#6b7280;padding:.5rem .4rem;white-space:nowrap;">
                        @if($franja->es_recreo)
                            <span style="color:#f59e0b;font-weight:700;">Recreo</span>
                        @else
                            {{ \Carbon\Carbon::parse($franja->hora_inicio)->format('g:i A') }}<br>
                            <span style="font-size:.65rem;">{{ \Carbon\Carbon::parse($franja->hora_fin)->format('g:i A') }}</span>
                        @endif
                    </td>
                    @foreach($diasConfig as $dia)
                    @php $detalle = $gridHorario[$franja->id][$dia] ?? null; @endphp
                    <td class="sch-cell" style="padding:.35rem .3rem;vertical-align:top;">
                        @if($franja->es_recreo)
                            <div class="sch-recreo">Recreo</div>
                        @elseif($detalle)
                            @php $color = $materias[$detalle->asignacion?->asignatura?->nombre ?? ''] ?? '#6b7280'; @endphp
                            <div style="background:{{ $color }}18;border-left:3px solid {{ $color }};border-radius:5px;padding:.35rem .45rem;font-size:.72rem;min-height:48px;">
                                <div style="font-weight:700;color:{{ $color }};line-height:1.2;">
                                    {{ $detalle->asignacion?->asignatura?->nombre ?? '—' }}
                                </div>
                                <div style="color:#6b7280;font-size:.65rem;margin-top:.15rem;">
                                    {{ $detalle->asignacion?->docente?->nombre_completo ?? '' }}
                                </div>
                                @if($detalle->aula)
                                <div style="color:#9ca3af;font-size:.62rem;">{{ $detalle->aula->nombre }}</div>
                                @endif
                            </div>
                        @else
                            <div style="height:48px;"></div>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Leyenda --}}
@if(! empty($materias))
<div class="card border-0 shadow-sm">
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span style="font-size:.72rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Materias:</span>
            @foreach($materias as $nombre => $color)
            <span style="background:{{ $color }}18;border-left:3px solid {{ $color }};padding:.18rem .6rem;border-radius:4px;font-size:.72rem;font-weight:600;color:{{ $color }};">
                {{ $nombre }}
            </span>
            @endforeach
        </div>
    </div>
</div>
@endif

@endif
@endsection
