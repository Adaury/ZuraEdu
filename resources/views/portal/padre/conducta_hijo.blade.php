@extends('layouts.portal')
@section('title', 'Conducta de ' . $estudiante->nombres)
@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'conducta', 'estudiante' => $estudiante])
@endsection

@section('content')
<div class="prt-page-header">
    <div>
        <h4 class="prt-page-title">
            <i class="bi bi-stars me-2"></i>Conducta — {{ $estudiante->nombre_completo }}
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
        <i class="bi bi-stars" style="font-size:2.5rem;opacity:.4;"></i>
        <p class="mt-3 mb-0">El estudiante no tiene una matrícula activa.</p>
    </div>
</div>
@elseif($registros->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-stars" style="font-size:2.5rem;opacity:.4;"></i>
        <p class="mt-3 mb-0">Los docentes aún no han registrado conducta para {{ $estudiante->nombres }} este año.</p>
    </div>
</div>
@else

{{-- Leyenda de escala --}}
<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach(array_reverse($escala, true) as $val => $esc)
    <span style="background:{{ $esc['bg'] }};color:{{ $esc['color'] }};border-radius:99px;padding:3px 12px;font-size:.75rem;font-weight:700;">
        {{ $esc['label'] }} — {{ $esc['nombre'] }}
    </span>
    @endforeach
</div>

@foreach($periodos as $periodo)
@php $regs = $registros->get($periodo->id, collect()); @endphp
@if($regs->isNotEmpty())
<div class="mb-4">
    <h6 class="fw-bold mb-2" style="color:#1e40af;font-size:.82rem;text-transform:uppercase;letter-spacing:.06em;">
        <i class="bi bi-calendar3 me-1"></i>{{ $periodo->nombre }}
    </h6>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:.82rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th>Materia / Docente</th>
                        @foreach($indicadores as $campo => $ind)
                        <th style="text-align:center;width:70px;">
                            <i class="bi {{ $ind['icon'] }}"></i>
                            <br><span style="font-size:.68rem;">{{ $ind['label'] }}</span>
                        </th>
                        @endforeach
                        <th style="text-align:center;width:70px;">Concepto</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($regs as $reg)
                @php
                    $concepto = $reg->concepto;
                    $escCon   = $concepto ? ($escala[$concepto] ?? null) : null;
                @endphp
                <tr>
                    <td>
                        <div class="fw-semibold" style="color:#1e293b;">{{ $reg->asignacion?->asignatura?->nombre ?? '—' }}</div>
                        <small class="text-muted">{{ $reg->asignacion?->docente?->nombre_completo ?? '—' }}</small>
                    </td>
                    @foreach($indicadores as $campo => $ind)
                    @php
                        $val   = $reg->$campo;
                        $escV  = $val ? ($escala[$val] ?? null) : null;
                    @endphp
                    <td style="text-align:center;">
                        @if($escV)
                        <span style="background:{{ $escV['bg'] }};color:{{ $escV['color'] }};border-radius:6px;padding:2px 7px;font-weight:700;font-size:.75rem;">
                            {{ $escV['label'] }}
                        </span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    @endforeach
                    <td style="text-align:center;">
                        @if($escCon)
                        <span style="background:{{ $escCon['bg'] }};color:{{ $escCon['color'] }};border-radius:6px;padding:3px 9px;font-weight:800;font-size:.78rem;">
                            {{ $escCon['label'] }}
                        </span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td style="font-size:.78rem;color:#475569;">
                        {{ $reg->observaciones ? \Illuminate\Support\Str::limit($reg->observaciones, 80) : '' }}
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endforeach

@endif
@endsection
