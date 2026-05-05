@extends('layouts.portal')
@section('page-title', 'Mi Horario')
@section('portal-name', 'Portal del Estudiante')

@section('sidebar')
    @include('portal.estudiante._sidebar', ['activeKey' => 'horario'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.estudiante.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.estudiante.horario') }}" class="prt-nav-item active">
        <i class="bi bi-calendar3"></i>Horario
    </a>
    <a href="{{ route('portal.estudiante.boletin') }}" class="prt-nav-item">
        <i class="bi bi-file-earmark-text-fill"></i>Boletín
    </a>
    <a href="{{ route('portal.estudiante.notificaciones') }}" class="prt-nav-item">
        <i class="bi bi-bell-fill"></i>Notif.
    </a>
@endsection

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.5rem;">
    <h2 style="font-size:1rem;font-weight:800;margin:0;">
        <i class="bi bi-calendar-week me-2" style="color:#6366f1;"></i>Mi Horario Semanal
    </h2>
    <div style="display:flex;gap:.5rem;align-items:center;">
        @if($matricula)
        <span style="font-size:.75rem;color:var(--prt-muted);">
            {{ $matricula->grupo?->grado?->nombre }} — Sección {{ $matricula->grupo?->seccion?->nombre }}
        </span>
        @endif
        @if($horarioActivo && !empty($gridHorario))
        <a href="{{ route('portal.estudiante.horario.pdf') }}" target="_blank"
           style="display:inline-flex;align-items:center;gap:.3rem;background:#dc2626;color:#fff;border-radius:7px;padding:.28rem .75rem;font-size:.72rem;font-weight:600;text-decoration:none;">
            <i class="bi bi-file-earmark-pdf-fill"></i> PDF
        </a>
        <a href="{{ route('portal.estudiante.horario.excel') }}"
           style="display:inline-flex;align-items:center;gap:.3rem;background:#15803d;color:#fff;border-radius:7px;padding:.28rem .75rem;font-size:.72rem;font-weight:600;text-decoration:none;">
            <i class="bi bi-file-earmark-excel-fill"></i> Excel
        </a>
        @endif
    </div>
</div>

<div class="prt-card">
    <div class="prt-card-body" style="padding:.5rem;">
        @if($horarioActivo && !empty($gridHorario))
        <div class="table-responsive">
        <table class="sch-table">
            <thead>
                <tr>
                    <th style="width:60px;">Hora</th>
                    @foreach($diasConfig as $dia)
                        <th>{{ ucfirst($dia === 'miercoles' ? 'Miércoles' : ucfirst($dia)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php
                    $palette  = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#84cc16','#f97316','#6366f1'];
                    $ci       = 0;
                    $colorMap = [];
                @endphp
                @foreach($franjasHorario as $franja)
                    @if($franja->es_recreo)
                    <tr class="sch-recreo">
                        <td colspan="{{ count($diasConfig) + 1 }}">
                            <i class="bi bi-cup-hot me-1"></i>Recreo
                            {{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }} –
                            {{ \Carbon\Carbon::parse($franja->hora_fin)->format('H:i') }}
                        </td>
                    </tr>
                    @else
                    <tr>
                        <td class="franja-col">
                            {{ $franja->nombre ?? 'F'.$franja->numero }}<br>
                            <span style="font-size:.6rem;color:#9ca3af;">
                                {{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }}
                            </span>
                        </td>
                        @foreach($diasConfig as $dia)
                        <td>
                            @if(isset($gridHorario[$franja->id][$dia]))
                                @php
                                    $d      = $gridHorario[$franja->id][$dia];
                                    $asigId = $d->asignacion?->asignatura_id ?? 0;
                                    if (!isset($colorMap[$asigId])) { $colorMap[$asigId] = $palette[$ci % count($palette)]; $ci++; }
                                @endphp
                                <div class="sch-cell" style="background:{{ $colorMap[$asigId] }};">
                                    {{ \Illuminate\Support\Str::limit($d->asignacion?->asignatura?->nombre ?? '—', 18) }}
                                    @if($d->asignacion?->docente)
                                    <div style="font-size:.6rem;opacity:.82;margin-top:.15rem;">
                                        {{ $d->asignacion->docente->nombres ?? '' }}
                                    </div>
                                    @endif
                                    @if($d->aula)
                                    <div style="font-size:.6rem;opacity:.75;">{{ $d->aula->nombre }}</div>
                                    @endif
                                </div>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        </div>

        {{-- Leyenda de materias --}}
        @php
            $materiasEnHorario = collect();
            foreach ($gridHorario as $fraRow) {
                foreach ($fraRow as $det) {
                    $id   = $det->asignacion?->asignatura_id ?? 0;
                    $nom  = $det->asignacion?->asignatura?->nombre ?? '—';
                    $doc  = $det->asignacion?->docente;
                    if (!$materiasEnHorario->has($id)) {
                        $materiasEnHorario->put($id, ['nombre' => $nom, 'docente' => $doc, 'color' => $colorMap[$id] ?? '#94a3b8']);
                    }
                }
            }
        @endphp
        @if($materiasEnHorario->isNotEmpty())
        <div style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:.4rem;">
            @foreach($materiasEnHorario as $mat)
            <span style="display:inline-flex;align-items:center;gap:.35rem;background:#f8faff;border:1px solid #e2e8f0;border-radius:6px;padding:.25rem .6rem;font-size:.72rem;">
                <span style="width:9px;height:9px;border-radius:50%;background:{{ $mat['color'] }};flex-shrink:0;"></span>
                <span>{{ $mat['nombre'] }}</span>
                @if($mat['docente'])
                <span style="color:#9ca3af;">· {{ $mat['docente']->nombres ?? '' }}</span>
                @endif
            </span>
            @endforeach
        </div>
        @endif

        @else
        <div style="text-align:center;padding:3rem;color:var(--prt-muted);">
            <i class="bi bi-calendar3-week" style="font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.4;"></i>
            El horario no está disponible aún.
        </div>
        @endif
    </div>
</div>

@endsection
