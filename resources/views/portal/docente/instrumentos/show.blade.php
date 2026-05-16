@extends('layouts.portal')
@section('page-title', $instrumento->titulo)
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'instrumentos'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.instrumentos.index', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-clipboard-check-fill"></i>Instrum.
    </a>
    <a href="{{ route('portal.docente.plan-evaluacion.index', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-bar-chart-steps"></i>Plan Eval.
    </a>
@endsection

@push('styles')
<style>
.inp-criterio {
    width: 64px; text-align: center;
    border: 1.5px solid #e2e8f0; border-radius: 7px;
    padding: .3rem .2rem; font-size: .85rem; font-weight: 700;
    background: #fff; color: #1e293b;
    transition: border-color .15s, box-shadow .15s;
    -moz-appearance: textfield;
}
.inp-criterio::-webkit-inner-spin-button,
.inp-criterio::-webkit-outer-spin-button { -webkit-appearance: none; }
.inp-criterio:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.18); }
.inp-criterio.ok  { border-color: #10b981; }
.inp-criterio.err { border-color: #ef4444; }

.pond-badge {
    display: inline-block; min-width: 46px; text-align: center;
    font-weight: 800; font-size: .88rem; border-radius: 8px; padding: .22rem .4rem;
}
.pond-ok  { background: #dcfce7; color: #15803d; }
.pond-med { background: #fef9c3; color: #92400e; }
.pond-low { background: #fee2e2; color: #dc2626; }
.pond-empty { background: #f1f5f9; color: #94a3b8; }

.est-row { border-bottom: 1px solid #f1f5f9; transition: background .1s; }
.est-row:hover { background: #f8faff; }

.col-crit-hd {
    padding: .45rem .35rem; text-align: center;
    font-size: .68rem; font-weight: 700; color: #2563eb;
    white-space: nowrap;
}
</style>
@endpush

@section('content')

{{-- Cabecera --}}
<div style="display:flex;align-items:flex-start;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.instrumentos.index', $asignacion) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;flex-shrink:0;margin-top:.1rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;min-width:0;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;color:#1e293b;">{{ $instrumento->titulo }}</h1>
        <div style="font-size:.75rem;color:#64748b;margin-top:.15rem;">
            {{ $asignacion->asignatura?->nombre }} &mdash; {{ $asignacion->grupo?->nombre_completo ?? '—' }}
            @if($instrumento->periodo) &middot; {{ $instrumento->periodo->nombre }} @endif
        </div>
    </div>
    <a href="{{ route('portal.docente.instrumentos.pdf', [$asignacion, $instrumento]) }}"
       style="background:#dc2626;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;white-space:nowrap;flex-shrink:0;">
        <i class="bi bi-file-earmark-pdf"></i>PDF
    </a>
</div>

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;padding:.65rem 1rem;margin-bottom:1rem;font-size:.82rem;color:#15803d;display:flex;align-items:center;gap:.5rem;">
    <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
</div>
@endif

{{-- Info del instrumento --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem;margin-bottom:1rem;" class="inst-meta-grid">
    <div class="prt-card" style="padding:.75rem 1rem;">
        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.3rem;">Tipo</div>
        <div style="font-size:.85rem;font-weight:700;color:#1e293b;">
            <i class="bi bi-tag-fill me-1" style="color:#3b82f6;"></i>{{ $instrumento->tipo_label }}
        </div>
    </div>
    <div class="prt-card" style="padding:.75rem 1rem;">
        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.3rem;">Estudiantes</div>
        <div style="font-size:.85rem;font-weight:700;color:#1e293b;">
            <i class="bi bi-people-fill me-1" style="color:#10b981;"></i>{{ $matriculas->count() }}
            <span style="font-size:.72rem;color:#64748b;font-weight:400;">
                &middot; {{ $evaluaciones->count() }} evaluado(s)
            </span>
        </div>
    </div>
</div>

{{-- Criterios --}}
<div class="prt-card" style="margin-bottom:1rem;">
    <div class="prt-card-header">
        <i class="bi bi-list-check" style="color:#7c3aed;"></i>
        <h3>Criterios de evaluación</h3>
        <span style="margin-left:auto;font-size:.72rem;color:#64748b;">
            Total: <strong>{{ $instrumento->criterios->sum('peso_max') }}</strong> pts
        </span>
    </div>
    <div style="padding:.5rem 1rem;display:flex;flex-wrap:wrap;gap:.5rem;">
        @foreach($instrumento->criterios->sortBy('orden') as $crit)
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:.45rem .75rem;font-size:.78rem;display:flex;align-items:center;gap:.5rem;">
            <span style="font-weight:600;color:#374151;">{{ $crit->nombre }}</span>
            <span style="background:#dbeafe;color:#1d4ed8;border-radius:99px;font-size:.68rem;font-weight:700;padding:.1rem .45rem;">{{ $crit->peso_max }} pts</span>
        </div>
        @endforeach
    </div>
    @if($instrumento->tipo === 'rubrica' && $instrumento->niveles_desempeno)
    <div style="padding:.5rem 1rem 1rem;display:flex;flex-wrap:wrap;gap:.4rem;">
        <span style="font-size:.72rem;font-weight:700;color:#64748b;width:100%;margin-bottom:.2rem;">Niveles de desempeño:</span>
        @foreach($instrumento->niveles_desempeno as $n)
        <span style="background:#f3e8ff;color:#7c3aed;border-radius:99px;font-size:.7rem;font-weight:600;padding:.15rem .55rem;">
            {{ $n['label'] }} ({{ $n['valor'] }})
        </span>
        @endforeach
    </div>
    @endif
</div>

{{-- Formulario de evaluación --}}
<div class="prt-card">
    <div class="prt-card-header">
        <i class="bi bi-pencil-square" style="color:#10b981;"></i>
        <h3>Registro de notas</h3>
        @if($evaluaciones->count() > 0)
        <span style="margin-left:auto;background:#dcfce7;color:#15803d;border-radius:99px;font-size:.68rem;font-weight:700;padding:.15rem .55rem;">
            {{ $evaluaciones->count() }}/{{ $matriculas->count() }} guardados
        </span>
        @endif
    </div>

    @if($matriculas->isEmpty())
    <div style="padding:2rem;text-align:center;color:#94a3b8;font-size:.85rem;">
        <i class="bi bi-people" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>
        No hay estudiantes activos en esta asignación.
    </div>
    @else

    <form method="POST" action="{{ route('portal.docente.instrumentos.guardar', [$asignacion, $instrumento]) }}">
        @csrf

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                        <th style="padding:.5rem 1rem;text-align:left;font-size:.72rem;font-weight:700;color:#374151;min-width:170px;">Estudiante</th>
                        @foreach($instrumento->criterios->sortBy('orden') as $crit)
                        <th class="col-crit-hd" style="min-width:72px;">
                            <div style="max-width:80px;overflow:hidden;text-overflow:ellipsis;" title="{{ $crit->nombre }}">
                                {{ Str::limit($crit->nombre, 14) }}
                            </div>
                            <div style="font-size:.62rem;color:#94a3b8;font-weight:400;">/{{ $crit->peso_max }}</div>
                        </th>
                        @endforeach
                        @if($instrumento->tipo === 'rubrica')
                        <th class="col-crit-hd" style="min-width:110px;">Nivel</th>
                        @endif
                        <th class="col-crit-hd" style="min-width:72px;">Total</th>
                        <th style="padding:.45rem .5rem;font-size:.68rem;font-weight:700;color:#374151;min-width:120px;">Observación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($matriculas as $mat)
                    @php $ev = $evaluaciones[$mat->id] ?? null; @endphp
                    <tr class="est-row" data-mat="{{ $mat->id }}">
                        <td style="padding:.55rem 1rem;font-size:.82rem;font-weight:600;color:#1e293b;white-space:nowrap;">
                            {{ $mat->estudiante->apellidos }}, {{ $mat->estudiante->nombres }}
                        </td>

                        @foreach($instrumento->criterios->sortBy('orden') as $crit)
                        <td style="padding:.4rem .3rem;text-align:center;">
                            <input type="number"
                                   name="evaluaciones[{{ $mat->id }}][puntajes][{{ $crit->id }}]"
                                   class="inp-criterio"
                                   data-criterio="{{ $crit->id }}"
                                   data-mat="{{ $mat->id }}"
                                   data-max="{{ $crit->peso_max }}"
                                   min="0" max="{{ $crit->peso_max }}" step="0.5"
                                   value="{{ $ev?->getPuntajeCriterio($crit->id) ?? '' }}"
                                   oninput="calcPond({{ $mat->id }})">
                        </td>
                        @endforeach

                        @if($instrumento->tipo === 'rubrica')
                        <td style="padding:.4rem .3rem;">
                            <select name="evaluaciones[{{ $mat->id }}][nivel_desempeno]"
                                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:7px;padding:.3rem .4rem;font-size:.75rem;color:#374151;background:#fff;">
                                <option value="">—</option>
                                @foreach($instrumento->niveles_desempeno ?? [] as $n)
                                <option value="{{ $n['label'] }}" @selected($ev?->nivel_desempeno === $n['label'])>
                                    {{ $n['label'] }}
                                </option>
                                @endforeach
                            </select>
                        </td>
                        @endif

                        <td style="padding:.4rem .3rem;text-align:center;">
                            <input type="number"
                                   name="evaluaciones[{{ $mat->id }}][ponderacion]"
                                   id="pond-{{ $mat->id }}"
                                   class="inp-criterio"
                                   min="0" max="100" step="0.01"
                                   value="{{ $ev?->ponderacion ?? '' }}"
                                   readonly
                                   style="background:#f8fafc;cursor:default;">
                        </td>

                        <td style="padding:.4rem .5rem;">
                            <input type="text"
                                   name="evaluaciones[{{ $mat->id }}][observacion]"
                                   style="width:100%;border:1.5px solid #e2e8f0;border-radius:7px;padding:.3rem .5rem;font-size:.75rem;color:#374151;background:#fff;"
                                   value="{{ $ev?->observacion ?? '' }}"
                                   placeholder="Obs...">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="padding:.85rem 1rem;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;gap:.6rem;flex-wrap:wrap;align-items:center;">
            <span style="font-size:.75rem;color:#64748b;flex:1;">
                <i class="bi bi-info-circle me-1"></i>
                Total = promedio ponderado de los criterios (escala 0–100).
            </span>
            <button type="submit"
                    style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;border-radius:9px;padding:.5rem 1.4rem;font-size:.83rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.45rem;">
                <i class="bi bi-floppy"></i>Guardar evaluaciones
            </button>
        </div>
    </form>

    @endif
</div>

@endsection

@push('scripts')
<script>
const criteriosInfo = @json($instrumento->criterios->sortBy('orden')->values()->map(fn($c) => ['id'=>$c->id,'peso_max'=>(float)$c->peso_max]));

function calcPond(matId) {
    let sum = 0, total = 0;
    criteriosInfo.forEach(c => {
        const inp = document.querySelector(`.inp-criterio[data-criterio="${c.id}"][data-mat="${matId}"]`);
        const val = parseFloat(inp?.value);
        if (!isNaN(val)) {
            // Validar rango
            if (val > c.peso_max) inp.value = c.peso_max;
            inp.classList.toggle('ok', val >= 0);
        }
        sum   += parseFloat(inp?.value) || 0;
        total += c.peso_max;
    });
    const pond = document.getElementById('pond-' + matId);
    if (!pond) return;
    if (total > 0 && document.querySelectorAll(`.inp-criterio[data-mat="${matId}"]`).length > 0) {
        const val = ((sum / total) * 100).toFixed(2);
        pond.value = val;
        pond.className = 'inp-criterio ' + (val >= 65 ? 'ok' : 'err');
    } else {
        pond.value = '';
        pond.className = 'inp-criterio';
    }
}

// Inicializar totales al cargar
document.querySelectorAll('tbody tr[data-mat]').forEach(tr => calcPond(+tr.dataset.mat));
</script>
@endpush
