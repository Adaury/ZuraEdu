@extends('layouts.portal')
@section('page-title', 'Plan de Evaluación — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'plan-evaluacion'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.instrumentos.index', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-clipboard-check-fill"></i>Instrum.
    </a>
    <a href="{{ route('portal.docente.plan-evaluacion.index', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-bar-chart-steps"></i>Plan Eval.
    </a>
@endsection

@section('content')
{{-- Cabecera --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.dashboard') }}" class="btn-back"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">Plan de Evaluación</h1>
        <div style="font-size:.75rem;color:#64748b;">{{ $asignacion->asignatura?->nombre }} — {{ $asignacion->grupo?->nombre_completo ?? '—' }}</div>
    </div>
    <a href="{{ route('portal.docente.plan-evaluacion.pdf', $asignacion) }}"
       style="background:#dc2626;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;white-space:nowrap;flex-shrink:0;">
        <i class="bi bi-file-earmark-pdf"></i>PDF
    </a>
</div>

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;padding:.65rem 1rem;margin-bottom:1rem;font-size:.82rem;color:#15803d;display:flex;align-items:center;gap:.5rem;">
    <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
</div>
@endif
@if($errors->has('total'))
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:.65rem 1rem;margin-bottom:1rem;font-size:.82rem;color:#991b1b;display:flex;align-items:center;gap:.5rem;">
    <i class="bi bi-exclamation-triangle-fill"></i>{{ $errors->first('total') }}
</div>
@endif

@if($periodos->isEmpty())
<div class="prt-card" style="text-align:center;padding:2.5rem;">
    <i class="bi bi-calendar-x" style="font-size:2rem;color:#94a3b8;display:block;margin-bottom:.75rem;"></i>
    <p style="color:#64748b;font-size:.88rem;">No hay períodos configurados para el año escolar activo.</p>
</div>
@else

{{-- Tabs de períodos --}}
<div x-data="{ tabActivo: {{ $periodos->first()->id }} }">

    {{-- Tab headers --}}
    <div style="display:flex;gap:.4rem;margin-bottom:1rem;flex-wrap:wrap;">
        @foreach($periodos as $periodo)
        @php $plan = $planes[$periodo->id] ?? null; $total = $plan?->total ?? 0; @endphp
        <button type="button"
                @click="tabActivo = {{ $periodo->id }}"
                :style="tabActivo === {{ $periodo->id }}
                    ? 'background:#1e3a8a;color:#fff;'
                    : 'background:#f1f5f9;color:#374151;'"
                style="border:none;border-radius:9px;padding:.45rem 1.1rem;font-size:.8rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.5rem;transition:all .15s;">
            <i class="bi bi-calendar3"></i>
            {{ $periodo->nombre }}
            @if($total === 100)
                <span style="background:#dcfce7;color:#15803d;border-radius:99px;font-size:.65rem;padding:.1rem .4rem;font-weight:800;">✔</span>
            @elseif($total > 0)
                <span style="background:#fef9c3;color:#92400e;border-radius:99px;font-size:.65rem;padding:.1rem .4rem;font-weight:800;">{{ $total }}%</span>
            @endif
        </button>
        @endforeach
    </div>

    {{-- Panel de cada período --}}
    @foreach($periodos as $periodo)
    @php
        $plan       = $planes[$periodo->id] ?? null;
        $instPeriod = $instrumentosPorPeriodo[$periodo->id] ?? collect();
        $total      = $plan?->total ?? 0;
    @endphp
    <div x-show="tabActivo === {{ $periodo->id }}" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

        {{-- Formulario de pesos --}}
        <div class="prt-card" style="margin-bottom:1rem;">
            <div class="prt-card-header">
                <div style="display:flex;align-items:center;gap:.6rem;">
                    <i class="bi bi-sliders" style="color:#3b82f6;"></i>
                    <h3>Distribución de pesos — {{ $periodo->nombre }}</h3>
                </div>
                @if($total === 100)
                <span style="background:#dcfce7;color:#15803d;border-radius:99px;font-size:.72rem;padding:.2rem .65rem;font-weight:700;">✔ 100% completo</span>
                @elseif($total > 0)
                <span style="background:#fee2e2;color:#991b1b;border-radius:99px;font-size:.72rem;padding:.2rem .65rem;font-weight:700;">{{ $total }}% / 100%</span>
                @endif
            </div>

            <form method="POST" action="{{ route('portal.docente.plan-evaluacion.guardar', $asignacion) }}"
                  x-data="pesosForm({
                      tareas:        {{ old('tareas', $plan?->tareas ?? 0) }},
                      practicas:     {{ old('practicas', $plan?->practicas ?? 0) }},
                      participacion: {{ old('participacion', $plan?->participacion ?? 0) }},
                      proyecto:      {{ old('proyecto', $plan?->proyecto ?? 0) }},
                      examen:        {{ old('examen', $plan?->examen ?? 0) }}
                  })" style="padding:1rem;">
                @csrf
                <input type="hidden" name="periodo_id" value="{{ $periodo->id }}">

                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:.75rem;margin-bottom:1rem;">
                    @foreach($categorias as $campo => $cat)
                    <div style="background:#f8fafc;border-radius:10px;padding:.75rem;border:1.5px solid #e2e8f0;">
                        <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.5rem;">
                            <i class="bi {{ $cat['icon'] }}" style="color:{{ $cat['color'] }};font-size:.9rem;"></i>
                            <span style="font-size:.75rem;font-weight:700;color:#374151;">{{ $cat['label'] }}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:.4rem;">
                            <input type="number" name="{{ $campo }}" min="0" max="100" step="5"
                                   value="{{ old($campo, $plan?->$campo ?? 0) }}"
                                   x-model="vals.{{ $campo }}"
                                   @input="recalc()"
                                   style="width:70px;border:1.5px solid #e2e8f0;border-radius:7px;padding:.3rem .5rem;font-size:.88rem;font-weight:700;text-align:center;color:{{ $cat['color'] }};">
                            <span style="font-size:.85rem;color:#64748b;">%</span>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Barra de progreso total --}}
                <div style="margin-bottom:1rem;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.3rem;">
                        <span style="font-size:.75rem;color:#64748b;font-weight:600;">Total</span>
                        <span x-text="total + '%'"
                              :style="total === 100 ? 'color:#15803d;font-weight:800;' : total > 100 ? 'color:#dc2626;font-weight:800;' : 'color:#f59e0b;font-weight:700;'"
                              style="font-size:.85rem;"></span>
                    </div>
                    <div style="background:#f1f5f9;border-radius:99px;height:8px;overflow:hidden;">
                        <div :style="`width:${Math.min(total,100)}%;background:${total===100?'#16a34a':total>100?'#dc2626':'#f59e0b'};height:100%;border-radius:99px;transition:width .2s;`"></div>
                    </div>
                    <p x-show="total !== 100" x-text="total > 100 ? 'Excede el 100%. Ajusta los valores.' : 'Los porcentajes deben sumar exactamente 100%.'"
                       style="font-size:.72rem;color:#dc2626;margin:.25rem 0 0;"></p>
                    <p x-show="total === 100" style="font-size:.72rem;color:#15803d;margin:.25rem 0 0;">✔ La distribución es correcta.</p>
                </div>

                <div style="margin-bottom:.75rem;">
                    <label style="font-size:.75rem;font-weight:600;color:#374151;display:block;margin-bottom:.3rem;">Observaciones (opcional)</label>
                    <textarea name="observaciones" rows="2" maxlength="1000"
                              style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.45rem .7rem;font-size:.8rem;resize:none;">{{ old('observaciones', $plan?->observaciones) }}</textarea>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;">
                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.8rem;color:#374151;">
                        <input type="checkbox" name="publicado" value="1" {{ $plan?->publicado ? 'checked' : '' }}
                               style="width:15px;height:15px;accent-color:#1d4ed8;">
                        Publicar (visible para padres/estudiantes)
                    </label>
                    <button type="submit"
                            :disabled="total !== 100"
                            style="background:linear-gradient(135deg,#1e3a8a,#2563eb);color:#fff;border:none;border-radius:9px;padding:.5rem 1.4rem;font-size:.83rem;font-weight:700;cursor:pointer;"
                            :style="total !== 100 ? 'opacity:.5;cursor:not-allowed;' : ''">
                        <i class="bi bi-floppy me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>

        {{-- Instrumentos vinculados a este período --}}
        <div class="prt-card">
            <div class="prt-card-header" style="flex-wrap:wrap;gap:.5rem;">
                <div style="display:flex;align-items:center;gap:.6rem;">
                    <i class="bi bi-clipboard-check-fill" style="color:#10b981;"></i>
                    <h3>Instrumentos — {{ $periodo->nombre }}</h3>
                </div>
                <div style="display:flex;gap:.5rem;margin-left:auto;flex-wrap:wrap;">
                    <a href="{{ route('portal.docente.plan-evaluacion.aplicar', [$asignacion, $periodo]) }}"
                       style="background:#059669;color:#fff;border-radius:8px;padding:.35rem .85rem;font-size:.75rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.35rem;">
                        <i class="bi bi-calculator"></i>Aplicar notas
                    </a>
                    <a href="{{ route('portal.docente.instrumentos.create', $asignacion) }}?periodo_id={{ $periodo->id }}"
                       style="background:#1d4ed8;color:#fff;border-radius:8px;padding:.35rem .85rem;font-size:.75rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.35rem;">
                        <i class="bi bi-plus-circle"></i>Nuevo instrumento
                    </a>
                </div>
            </div>

            @if($instPeriod->isEmpty())
            <div style="padding:1.5rem;text-align:center;color:#94a3b8;font-size:.82rem;">
                <i class="bi bi-clipboard-x" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>
                No hay instrumentos asignados a este período.<br>
                <a href="{{ route('portal.docente.instrumentos.create', $asignacion) }}?periodo_id={{ $periodo->id }}"
                   style="color:#2563eb;font-weight:600;">Crear el primero</a>
            </div>
            @else
            <div style="padding:0;">
                @foreach($instPeriod as $inst)
                <div style="padding:.7rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.75rem;">
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.83rem;font-weight:700;color:#1e293b;">{{ $inst->titulo }}</div>
                        <div style="font-size:.72rem;color:#64748b;">
                            {{ $inst->tipo_label }}
                            · {{ $inst->criterios->count() }} criterio(s)
                            @if($inst->publicado) · <span style="color:#15803d;">✔ Publicado</span> @endif
                        </div>
                    </div>
                    <a href="{{ route('portal.docente.instrumentos.show', [$asignacion, $inst]) }}"
                       style="background:#eff6ff;color:#1d4ed8;border-radius:7px;padding:.3rem .65rem;font-size:.72rem;font-weight:700;text-decoration:none;white-space:nowrap;">
                        <i class="bi bi-pencil-square me-1"></i>Evaluar
                    </a>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Instrumento sin período asignado --}}
            @php
            $sinPeriodo = \App\Models\InstrumentoEvaluacion::where('asignacion_id', $asignacion->id)
                ->whereNull('periodo_id')->count();
            @endphp
            @if($sinPeriodo > 0 && $loop->last)
            <div style="padding:.65rem 1rem;background:#fffbeb;border-top:1px solid #fde68a;font-size:.75rem;color:#92400e;">
                <i class="bi bi-info-circle me-1"></i>
                Hay <strong>{{ $sinPeriodo }}</strong> instrumento(s) sin período asignado.
                <a href="{{ route('portal.docente.instrumentos.index', $asignacion) }}" style="color:#1d4ed8;font-weight:600;">Ver todos</a>
            </div>
            @endif
        </div>

    </div>
    @endforeach

</div>{{-- x-data --}}
@endif

@endsection

@push('scripts')
<script>
function pesosForm(init) {
    return {
        vals: { ...init },
        total: 0,
        init() { this.recalc(); },
        recalc() {
            this.total = Object.values(this.vals).reduce((s, v) => s + (parseInt(v) || 0), 0);
        },
    };
}
</script>
@endpush
