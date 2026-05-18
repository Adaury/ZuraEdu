@extends('layouts.portal')
@section('page-title', 'Ficha — ' . ($matricula->estudiante?->nombre_completo ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'estudiantes'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.estudiantes', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-people-fill"></i>Estudiantes
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.asistencia', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-calendar-check"></i>Asistencia
    </a>
@endsection

@push('styles')
<style>
.ficha-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem; }
@media(max-width:640px){ .ficha-grid { grid-template-columns:1fr; } }

.info-row { display:flex; gap:.4rem; padding:.4rem 0; border-bottom:1px solid #f1f5f9; font-size:.8rem; }
.info-row:last-child { border-bottom:none; }
.info-label { color:#94a3b8; font-weight:600; min-width:110px; flex-shrink:0; }
.info-val { color:#1e293b; font-weight:500; }

.asist-chip { display:inline-flex; align-items:center; gap:.35rem; border-radius:99px; padding:.3rem .75rem; font-size:.75rem; font-weight:700; }
</style>
@endpush

@section('content')

{{-- Cabecera --}}
<div style="display:flex;align-items:flex-start;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.estudiantes', $asignacion) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;flex-shrink:0;margin-top:.1rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <a href="{{ route('portal.docente.estudiantes.ficha.pdf', [$asignacion, $matricula]) }}"
       target="_blank"
       style="background:#dc2626;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;flex-shrink:0;margin-top:.1rem;margin-left:auto;">
        <i class="bi bi-file-earmark-pdf-fill"></i>Reporte PDF
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-person-vcard-fill" style="color:#1e3a8a;"></i>
            Ficha del Estudiante
        </h1>
        <div style="font-size:.75rem;color:#64748b;margin-top:.15rem;">
            {{ $asignacion->asignatura?->nombre }} &mdash; {{ $asignacion->grupo?->nombre_completo ?? '—' }}
        </div>
    </div>
</div>

{{-- Perfil principal --}}
<div class="prt-card" style="margin-bottom:1rem;">
    <div style="padding:1rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
        {{-- Avatar --}}
        <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#1e3a8a,#2563eb);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.6rem;font-weight:900;color:#fff;">
            {{ strtoupper(substr($matricula->estudiante?->nombres ?? 'E', 0, 1)) }}
        </div>
        <div style="flex:1;min-width:0;">
            <div style="font-size:1.05rem;font-weight:900;color:#1e293b;">
                {{ $matricula->estudiante?->apellidos }}, {{ $matricula->estudiante?->nombres }}
            </div>
            <div style="font-size:.78rem;color:#64748b;margin-top:.15rem;display:flex;flex-wrap:wrap;gap:.75rem;">
                @if($matricula->estudiante?->cedula)
                <span><i class="bi bi-credit-card-2-front me-1"></i>{{ $matricula->estudiante->cedula }}</span>
                @endif
                @if($matricula->estudiante?->fecha_nacimiento)
                <span><i class="bi bi-calendar3 me-1"></i>{{ $matricula->estudiante->fecha_nacimiento->format('d/m/Y') }} ({{ $matricula->estudiante->edad }} años)</span>
                @endif
                @if($matricula->estudiante?->sexo)
                <span><i class="bi bi-gender-ambiguous me-1"></i>{{ $matricula->estudiante->sexo === 'M' ? 'Masculino' : 'Femenino' }}</span>
                @endif
            </div>
            @if($matricula->estudiante?->telefono || $matricula->estudiante?->email)
            <div style="font-size:.75rem;color:#64748b;margin-top:.1rem;display:flex;flex-wrap:wrap;gap:.75rem;">
                @if($matricula->estudiante?->telefono)
                <span><i class="bi bi-telephone me-1"></i>{{ $matricula->estudiante->telefono }}</span>
                @endif
                @if($matricula->estudiante?->email)
                <span><i class="bi bi-envelope me-1"></i>{{ $matricula->estudiante->email }}</span>
                @endif
            </div>
            @endif
        </div>

        {{-- Nota final badge --}}
        @php
            $nf = $notaFinal;
            $nfBg  = $nf === null ? '#f1f5f9' : ($nf >= 90 ? '#dbeafe' : ($nf >= 70 ? '#dcfce7' : ($nf >= 65 ? '#fef9c3' : '#fee2e2')));
            $nfClr = $nf === null ? '#94a3b8' : ($nf >= 90 ? '#1d4ed8' : ($nf >= 70 ? '#15803d' : ($nf >= 65 ? '#92400e' : '#dc2626')));
        @endphp
        <div style="text-align:center;background:{{ $nfBg }};border-radius:12px;padding:.65rem 1rem;flex-shrink:0;">
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:{{ $nfClr }};margin-bottom:.1rem;">Nota Final</div>
            <div style="font-size:2rem;font-weight:900;line-height:1;color:{{ $nfClr }};">{{ $nf !== null ? $nf : '—' }}</div>
            <div style="font-size:.68rem;color:{{ $nfClr }};margin-top:.1rem;">
                @if($nf !== null)
                    {{ $nf >= 65 ? 'Aprobado' : 'Reprobado' }}
                @else
                    Sin nota
                @endif
            </div>
        </div>
    </div>
</div>

<div class="ficha-grid">

    {{-- Notas por período --}}
    <div class="prt-card">
        <div class="prt-card-header">
            <i class="bi bi-journal-check" style="color:#2563eb;"></i>
            <h3>Notas por período</h3>
        </div>
        <div style="padding:.75rem 1rem;">
            @if($periodos->isEmpty())
            <p style="font-size:.8rem;color:#94a3b8;text-align:center;padding:.5rem 0;">Sin períodos configurados.</p>
            @else
            @foreach($periodos as $periodo)
            @php
                $np = $notasPeriodo[$periodo->numero] ?? null;
                $npClr = $np === null ? '#94a3b8' : ($np >= 70 ? '#15803d' : ($np >= 65 ? '#92400e' : '#dc2626'));
                $npBg  = $np === null ? '#f1f5f9' : ($np >= 70 ? '#dcfce7' : ($np >= 65 ? '#fef9c3' : '#fee2e2'));
            @endphp
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.45rem 0;border-bottom:1px solid #f1f5f9;">
                <span style="font-size:.8rem;color:#374151;font-weight:600;">
                    <i class="bi bi-calendar3 me-1" style="color:#64748b;font-size:.7rem;"></i>{{ $periodo->nombre }}
                </span>
                <span style="background:{{ $npBg }};color:{{ $npClr }};border-radius:7px;padding:.2rem .6rem;font-weight:800;font-size:.82rem;min-width:40px;text-align:center;">
                    {{ $np !== null ? number_format($np, 0) : '—' }}
                </span>
            </div>
            @endforeach
            @endif
        </div>
    </div>

    {{-- Resumen de asistencia --}}
    <div class="prt-card">
        <div class="prt-card-header">
            <i class="bi bi-calendar-check-fill" style="color:#10b981;"></i>
            <h3>Asistencia</h3>
            @if($asistResumen['pct'] !== null)
            <span style="margin-left:auto;font-weight:800;font-size:.85rem;color:{{ $asistResumen['pct'] >= 80 ? '#15803d' : '#dc2626' }};">
                {{ $asistResumen['pct'] }}%
            </span>
            @endif
        </div>
        <div style="padding:.75rem 1rem;">
            @if($asistResumen['total'] === 0)
            <p style="font-size:.8rem;color:#94a3b8;text-align:center;padding:.5rem 0;">Sin registros de asistencia.</p>
            @else
            {{-- Barra visual --}}
            @php
                $pct = $asistResumen['pct'] ?? 0;
            @endphp
            <div style="background:#f1f5f9;border-radius:99px;height:10px;overflow:hidden;margin-bottom:.85rem;">
                <div style="width:{{ $pct }}%;height:100%;border-radius:99px;background:{{ $pct >= 80 ? '#22c55e' : ($pct >= 60 ? '#f59e0b' : '#ef4444') }};transition:width .4s;"></div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;">
                <div style="background:#dcfce7;border-radius:9px;padding:.5rem .75rem;text-align:center;">
                    <div style="font-size:1.2rem;font-weight:900;color:#15803d;">{{ $asistResumen['presentes'] }}</div>
                    <div style="font-size:.65rem;color:#15803d;font-weight:700;">Presentes</div>
                </div>
                <div style="background:#fee2e2;border-radius:9px;padding:.5rem .75rem;text-align:center;">
                    <div style="font-size:1.2rem;font-weight:900;color:#dc2626;">{{ $asistResumen['ausentes'] }}</div>
                    <div style="font-size:.65rem;color:#dc2626;font-weight:700;">Ausentes</div>
                </div>
                <div style="background:#fef9c3;border-radius:9px;padding:.5rem .75rem;text-align:center;">
                    <div style="font-size:1.2rem;font-weight:900;color:#92400e;">{{ $asistResumen['tardes'] }}</div>
                    <div style="font-size:.65rem;color:#92400e;font-weight:700;">Tardanzas</div>
                </div>
                <div style="background:#ede9fe;border-radius:9px;padding:.5rem .75rem;text-align:center;">
                    <div style="font-size:1.2rem;font-weight:900;color:#6d28d9;">{{ $asistResumen['excusas'] }}</div>
                    <div style="font-size:.65rem;color:#6d28d9;font-weight:700;">Excusas</div>
                </div>
            </div>
            <div style="font-size:.68rem;color:#94a3b8;text-align:center;margin-top:.5rem;">
                {{ $asistResumen['total'] }} sesiones registradas
            </div>
            @endif
        </div>
    </div>

</div>

{{-- Representantes --}}
@php $representantes = $matricula->estudiante?->representantes ?? collect(); @endphp
@if($representantes->isNotEmpty())
<div class="prt-card" style="margin-bottom:1rem;">
    <div class="prt-card-header">
        <i class="bi bi-people-fill" style="color:#7c3aed;"></i>
        <h3>Representantes</h3>
    </div>
    <div>
        @foreach($representantes as $rep)
        <div style="padding:.7rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.75rem;">
            <div style="width:36px;height:36px;border-radius:50%;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:800;color:#7c3aed;font-size:.85rem;">
                {{ strtoupper(substr($rep->nombres ?? 'R', 0, 1)) }}
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:.83rem;font-weight:700;color:#1e293b;">{{ $rep->nombre_completo }}</div>
                <div style="font-size:.7rem;color:#64748b;margin-top:.1rem;display:flex;flex-wrap:wrap;gap:.6rem;">
                    @if($rep->pivot->parentesco)
                    <span><i class="bi bi-person-heart me-1"></i>{{ $rep->pivot->parentesco }}</span>
                    @endif
                    @if($rep->telefono)
                    <span><i class="bi bi-telephone me-1"></i>{{ $rep->telefono }}</span>
                    @endif
                    @if($rep->email)
                    <span><i class="bi bi-envelope me-1"></i>{{ $rep->email }}</span>
                    @endif
                </div>
            </div>
            @if($rep->pivot->es_principal)
            <span style="background:#dbeafe;color:#1d4ed8;border-radius:99px;font-size:.65rem;font-weight:700;padding:.15rem .5rem;flex-shrink:0;">Principal</span>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Instrumentos evaluados --}}
@if($instrumentos->isNotEmpty())
<div class="prt-card" style="margin-bottom:1rem;">
    <div class="prt-card-header">
        <i class="bi bi-clipboard-check-fill" style="color:#10b981;"></i>
        <h3>Instrumentos evaluados</h3>
        <span style="margin-left:auto;font-size:.72rem;color:#64748b;">{{ $instrumentos->count() }} instrumento(s)</span>
    </div>
    <div>
        @foreach($instrumentos as $inst)
        @php $eval = $inst->evaluaciones->first(); @endphp
        <div style="padding:.65rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.75rem;">
            <div style="flex:1;min-width:0;">
                <div style="font-size:.82rem;font-weight:700;color:#1e293b;">{{ $inst->titulo }}</div>
                <div style="font-size:.7rem;color:#64748b;margin-top:.1rem;">
                    {{ $inst->tipo_label ?? $inst->tipo }}
                    · {{ $inst->criterios->count() }} criterio(s)
                </div>
            </div>
            @if($eval)
            @php
                $pond = $eval->ponderacion ?? null;
                $pbg  = $pond === null ? '#f1f5f9' : ($pond >= 70 ? '#dcfce7' : ($pond >= 65 ? '#fef9c3' : '#fee2e2'));
                $pclr = $pond === null ? '#94a3b8' : ($pond >= 70 ? '#15803d' : ($pond >= 65 ? '#92400e' : '#dc2626'));
            @endphp
            <span style="background:{{ $pbg }};color:{{ $pclr }};border-radius:7px;padding:.2rem .55rem;font-weight:800;font-size:.82rem;flex-shrink:0;min-width:42px;text-align:center;">
                {{ $pond !== null ? number_format($pond, 1) : '—' }}
            </span>
            @else
            <span style="background:#f1f5f9;color:#94a3b8;border-radius:7px;padding:.2rem .55rem;font-size:.75rem;flex-shrink:0;">Sin evaluar</span>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Gamificación --}}
@if(!empty($tieneGamif))
@php
$catInfo = [
    'academico'     => ['label' => 'Académico',     'color' => '#2563eb'],
    'asistencia'    => ['label' => 'Asistencia',    'color' => '#059669'],
    'conducta'      => ['label' => 'Conducta',      'color' => '#8b5cf6'],
    'participacion' => ['label' => 'Participación', 'color' => '#f59e0b'],
    'extra'         => ['label' => 'Extra',         'color' => '#64748b'],
];
@endphp
<div class="prt-card" style="margin-bottom:1rem;">
    <div class="prt-card-header">
        <i class="bi bi-trophy-fill" style="color:#f59e0b;"></i>
        <h3>Gamificación</h3>
        <div style="margin-left:auto;display:flex;gap:.5rem;align-items:center;">
            <span style="background:#eef2ff;color:#4338ca;border-radius:99px;font-size:.72rem;font-weight:800;padding:.15rem .55rem;">{{ number_format($gamifPuntos) }} pts</span>
            @if($gamifInsignias->isNotEmpty())
            <span style="background:#fef9c3;color:#92400e;border-radius:99px;font-size:.7rem;font-weight:700;padding:.12rem .48rem;">⭐ {{ $gamifInsignias->count() }} insignia(s)</span>
            @endif
        </div>
    </div>
    <div style="padding:.75rem 1rem;">
        @if((int)$gamifPuntos === 0 && $gamifInsignias->isEmpty())
        <p style="font-size:.8rem;color:#94a3b8;text-align:center;padding:.5rem 0;">Sin puntos ni insignias registrados aún.</p>
        @else
        {{-- Insignias obtenidas --}}
        @if($gamifInsignias->isNotEmpty())
        <div style="display:flex;flex-wrap:wrap;gap:.45rem;margin-bottom:.75rem;">
            @foreach($gamifInsignias as $ins)
            <span style="background:#fef9c3;color:#92400e;border-radius:99px;font-size:.7rem;font-weight:700;padding:.18rem .6rem;">
                ⭐ {{ \App\Models\InsigniaEstudiante::TIPOS[$ins->tipo]['label'] ?? $ins->tipo }}
            </span>
            @endforeach
        </div>
        @endif
        {{-- Desglose por categoría --}}
        @if($gamifCategoria->isNotEmpty())
        @foreach($gamifCategoria as $cat)
        @php $ci = $catInfo[$cat->categoria] ?? ['label' => $cat->categoria, 'color' => '#64748b']; @endphp
        <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.25rem;font-size:.8rem;">
            <span style="background:{{ $ci['color'] }}18;color:{{ $ci['color'] }};border-radius:99px;padding:.1rem .45rem;font-size:.7rem;font-weight:700;min-width:75px;text-align:center;">{{ $ci['label'] }}</span>
            <div style="flex:1;height:7px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                <div style="width:{{ min(100, round($cat->total / max(1,$gamifPuntos) * 100)) }}%;height:100%;background:{{ $ci['color'] }};border-radius:99px;"></div>
            </div>
            <span style="font-weight:800;color:{{ $ci['color'] }};min-width:38px;text-align:right;">{{ $cat->total }} pts</span>
        </div>
        @endforeach
        @endif
        {{-- Últimos 10 puntos --}}
        @if($gamifHistorial->isNotEmpty())
        <div style="margin-top:.65rem;border-top:1px solid #f1f5f9;padding-top:.65rem;">
            <div style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.35rem;">Últimas asignaciones</div>
            @foreach($gamifHistorial as $p)
            @php $ci = $catInfo[$p->categoria] ?? ['label' => $p->categoria, 'color' => '#64748b']; @endphp
            <div style="display:flex;align-items:center;gap:.5rem;padding:.3rem 0;border-bottom:1px solid #f8fafc;font-size:.78rem;">
                <span style="background:{{ $ci['color'] }}18;color:{{ $ci['color'] }};border-radius:5px;padding:.08rem .38rem;font-size:.67rem;font-weight:700;flex-shrink:0;">{{ $ci['label'] }}</span>
                <span style="flex:1;color:#374151;font-weight:500;" title="{{ $p->concepto }}">{{ \Illuminate\Support\Str::limit($p->concepto, 40) }}</span>
                <span style="font-size:.72rem;color:#94a3b8;flex-shrink:0;">{{ \Carbon\Carbon::parse($p->fecha)->format('d/m') }}</span>
                <span style="font-weight:800;color:{{ $ci['color'] }};flex-shrink:0;">+{{ $p->puntos }}</span>
            </div>
            @endforeach
        </div>
        @endif
        @endif
    </div>
    <div style="padding:.5rem 1rem;border-top:1px solid #f1f5f9;">
        <a href="{{ route('portal.docente.gamificacion', ['asignacion_id' => $asignacion->id]) }}"
           style="font-size:.75rem;color:#4338ca;font-weight:700;text-decoration:none;">
            <i class="bi bi-trophy me-1"></i>Ver ranking del grupo →
        </a>
    </div>
</div>
@endif

{{-- Observaciones --}}
<div class="prt-card">
    <div class="prt-card-header">
        <i class="bi bi-chat-square-text" style="color:#f59e0b;"></i>
        <h3>Observaciones</h3>
        <div style="margin-left:auto;display:flex;gap:.5rem;align-items:center;">
            <span style="font-size:.72rem;color:#64748b;">{{ $observaciones->count() }}</span>
            <a href="{{ route('portal.docente.observaciones', $asignacion) }}?estudiante={{ $matricula->estudiante_id }}"
               style="background:#fffbeb;color:#92400e;border-radius:7px;padding:.25rem .65rem;font-size:.72rem;font-weight:700;text-decoration:none;white-space:nowrap;">
                <i class="bi bi-plus-circle me-1"></i>Nueva
            </a>
        </div>
    </div>
    @if($observaciones->isEmpty())
    <div style="padding:1.5rem;text-align:center;color:#94a3b8;font-size:.82rem;">
        <i class="bi bi-chat-square" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>
        No hay observaciones registradas para este estudiante en esta asignatura.
    </div>
    @else
    <div>
        @foreach($observaciones as $obs)
        @php $ti = $obs->tipo_info; @endphp
        <div style="padding:.7rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;gap:.65rem;">
            <div style="width:30px;height:30px;border-radius:50%;background:{{ $ti['color'] }}18;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:.1rem;">
                <i class="bi {{ $ti['icon'] }}" style="color:{{ $ti['color'] }};font-size:.75rem;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.25rem;">
                    <span style="font-size:.68rem;font-weight:700;background:{{ $ti['color'] }}18;color:{{ $ti['color'] }};border-radius:99px;padding:.1rem .45rem;">{{ $ti['label'] }}</span>
                    <span style="font-size:.67rem;color:#94a3b8;">{{ $obs->created_at->format('d/m/Y') }}</span>
                    @if($obs->privada)
                    <span style="font-size:.65rem;color:#6b7280;"><i class="bi bi-lock-fill me-1"></i>Privada</span>
                    @endif
                </div>
                <p style="font-size:.8rem;color:#374151;margin:0;line-height:1.45;">{{ $obs->texto }}</p>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

@endsection
