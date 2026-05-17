@extends('layouts.portal')
@section('page-title', 'Resultados — '.$rubrica->titulo)
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'rubricas'])
@endsection

@section('bottom-nav')
<a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item"><i class="bi bi-house-fill"></i>Inicio</a>
<a href="{{ route('portal.docente.rubricas.index') }}" class="prt-nav-item"><i class="bi bi-table"></i>Rúbricas</a>
<a href="{{ route('portal.docente.rubricas.aplicar', $rubrica) }}" class="prt-nav-item active"><i class="bi bi-bar-chart-fill"></i>Resultados</a>
@endsection

@push('styles')
<style>
.res-table { width:100%;border-collapse:collapse;font-size:.78rem; }
.res-table th { background:#f8fafc;font-weight:700;padding:.5rem .7rem;color:#475569;border-bottom:2px solid #e2e8f0; }
.res-table td { padding:.5rem .7rem;border-bottom:1px solid #f1f5f9;vertical-align:middle; }
.nivel-chip { display:inline-block;padding:.12rem .45rem;border-radius:99px;font-size:.67rem;font-weight:700;color:#fff; }
.pct-bar-bg { background:#e2e8f0;border-radius:99px;height:6px;width:80px;display:inline-block;vertical-align:middle; }
.pct-bar-fill { height:6px;border-radius:99px;transition:.3s; }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;gap:.7rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.rubricas.index') }}"
       style="color:#ec4899;text-decoration:none;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:.3rem;">
        <i class="bi bi-arrow-left"></i>Rúbricas
    </a>
    <span style="color:#cbd5e1;">›</span>
    <h1 style="font-size:1rem;font-weight:800;margin:0;flex:1;">Resultados: {{ $rubrica->titulo }}</h1>
    @if($asignacion)
    <a href="{{ route('portal.docente.rubricas.resultados.pdf', [$rubrica, 'asignacion_id' => $asignacion->id]) }}"
       target="_blank"
       style="background:#fee2e2;color:#b91c1c;border:1.5px solid #fca5a5;border-radius:8px;padding:.42rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.35rem;">
        <i class="bi bi-file-earmark-pdf-fill"></i>PDF
    </a>
    @if($stats && $stats['completados'] > 0)
    <button onclick="document.getElementById('modal-pasar-rubrica').style.display='flex'"
        style="background:#ede9fe;color:#6d28d9;border:1.5px solid #c4b5fd;border-radius:8px;padding:.42rem .85rem;font-size:.78rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:.35rem;">
        <i class="bi bi-arrow-up-right-square-fill"></i>Pasar Nota
    </button>
    @endif
    @endif
    <a href="{{ route('portal.docente.rubricas.aplicar', $rubrica) }}"
       style="background:#ec4899;color:#fff;border:none;border-radius:8px;padding:.42rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.35rem;">
        <i class="bi bi-play-fill"></i>Continuar aplicando
    </a>
</div>

{{-- Modal: Pasar nota a calificaciones --}}
@if($asignacion && $stats && $stats['completados'] > 0)
@php
    $esTecnicaRub = ($asignacion->area ?? '') === 'tecnica';
    $periodosRub  = \App\Models\Periodo::where('school_year_id', \App\Models\SchoolYear::actual()?->id)->orderBy('numero')->get();
@endphp
<div id="modal-pasar-rubrica" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:16px;padding:1.4rem 1.6rem;max-width:420px;width:100%;box-shadow:0 8px 32px rgba(0,0,0,.18);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h3 style="font-size:.93rem;font-weight:800;margin:0;color:#1e293b;">
                <i class="bi bi-arrow-up-right-square-fill me-2" style="color:#7c3aed;"></i>Pasar Puntaje a Calificaciones
            </h3>
            <button onclick="document.getElementById('modal-pasar-rubrica').style.display='none'"
                style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#6b7280;">✕</button>
        </div>
        <p style="font-size:.75rem;color:#64748b;margin-bottom:1rem;">
            El puntaje de cada estudiante (porcentaje × 100) se guardará en el campo seleccionado.
            Solo se procesan los <strong>{{ $stats['completados'] }}</strong> estudiantes evaluados.
        </p>

        @if($esTecnicaRub)
        <div style="margin-bottom:.8rem;">
            <label style="font-size:.75rem;font-weight:700;color:#374151;display:block;margin-bottom:.3rem;">Período</label>
            <select id="rub-periodo-id" style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.4rem .6rem;font-size:.82rem;">
                @foreach($periodosRub as $per)
                <option value="{{ $per->id }}">{{ $per->nombre ?? 'Período '.$per->numero }}</option>
                @endforeach
            </select>
        </div>
        <div style="margin-bottom:1rem;">
            <label style="font-size:.75rem;font-weight:700;color:#374151;display:block;margin-bottom:.3rem;">Campo a actualizar</label>
            <select id="rub-campo" style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.4rem .6rem;font-size:.82rem;">
                <option value="tareas">Tareas</option>
                <option value="practicas">Prácticas</option>
                <option value="participacion">Participación</option>
                <option value="proyecto">Proyecto</option>
                <option value="examen">Examen</option>
            </select>
        </div>
        @else
        <div style="margin-bottom:.8rem;">
            <label style="font-size:.75rem;font-weight:700;color:#374151;display:block;margin-bottom:.3rem;">Componente</label>
            <select id="rub-componente" style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.4rem .6rem;font-size:.82rem;">
                <option value="1">1 — Comunicativa</option>
                <option value="2">2 — Pensamiento Lógico</option>
                <option value="3">3 — Científica y Tecnológica</option>
                <option value="4">4 — Ética y Ciudadana</option>
            </select>
        </div>
        <div style="margin-bottom:1rem;">
            <label style="font-size:.75rem;font-weight:700;color:#374151;display:block;margin-bottom:.3rem;">Período</label>
            <select id="rub-periodo-num" style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.4rem .6rem;font-size:.82rem;">
                <option value="1">Período 1</option>
                <option value="2">Período 2</option>
                <option value="3">Período 3</option>
                <option value="4">Período 4</option>
            </select>
        </div>
        @endif

        <div id="rub-msg" style="font-size:.77rem;margin-bottom:.7rem;display:none;"></div>

        <div style="display:flex;gap:.6rem;justify-content:flex-end;">
            <button onclick="document.getElementById('modal-pasar-rubrica').style.display='none'"
                style="background:#f1f5f9;color:#374151;border:1.5px solid #e2e8f0;border-radius:8px;padding:.4rem 1rem;font-size:.8rem;font-weight:600;cursor:pointer;">
                Cancelar
            </button>
            <button id="btn-rub-confirmar" onclick="ejecutarPasarRubrica()"
                style="background:#7c3aed;color:#fff;border:none;border-radius:8px;padding:.4rem 1.1rem;font-size:.8rem;font-weight:700;cursor:pointer;">
                <i class="bi bi-check-lg"></i> Confirmar
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
async function ejecutarPasarRubrica() {
    const btn = document.getElementById('btn-rub-confirmar');
    const msg = document.getElementById('rub-msg');
    if (!confirm('¿Confirmar que se guardarán los puntajes en el libro de calificaciones?')) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Guardando...';
    msg.style.display = 'none';

    const esTecnica = {{ $esTecnicaRub ? 'true' : 'false' }};
    const body = { asignacion_id: {{ $asignacion->id }}, tipo_area: esTecnica ? 'tecnica' : 'academica' };
    if (esTecnica) {
        body.periodo_id = document.getElementById('rub-periodo-id')?.value;
        body.campo      = document.getElementById('rub-campo')?.value;
    } else {
        body.componente  = document.getElementById('rub-componente')?.value;
        body.periodo_num = document.getElementById('rub-periodo-num')?.value;
    }

    try {
        const r = await fetch('{{ route('portal.docente.rubricas.pasar-notas', $rubrica) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify(body)
        });
        const d = await r.json();
        msg.style.display = 'block';
        if (d.ok) {
            msg.style.cssText = 'color:#059669;background:#d1fae5;padding:.4rem .7rem;border-radius:8px;font-size:.77rem;margin-bottom:.7rem;';
            msg.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>' + d.mensaje;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Listo';
            btn.style.background = '#059669';
        } else {
            msg.style.cssText = 'color:#b91c1c;background:#fee2e2;padding:.4rem .7rem;border-radius:8px;font-size:.77rem;margin-bottom:.7rem;';
            msg.textContent = d.mensaje ?? 'Error al guardar.';
            btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i> Confirmar';
        }
    } catch(e) {
        msg.style.display = 'block'; msg.style.color = '#b91c1c';
        msg.textContent = 'Error de conexión.';
        btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i> Confirmar';
    }
}
</script>
@endpush
@endif

{{-- Selector asignación --}}
<div class="prt-card" style="padding:.8rem 1rem;margin-bottom:1rem;">
    <form method="GET" style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
            <label style="font-size:.72rem;font-weight:600;display:block;margin-bottom:.25rem;color:#64748b;">Clase / Grupo</label>
            <select name="asignacion_id" onchange="this.form.submit()"
                style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.48rem .7rem;font-size:.83rem;">
                <option value="">— Selecciona —</option>
                @foreach($asignaciones as $asg)
                <option value="{{ $asg->id }}" {{ $asignacion?->id == $asg->id ? 'selected':'' }}>
                    {{ $asg->asignatura?->nombre }} — {{ $asg->grupo?->nombre_completo }}
                </option>
                @endforeach
            </select>
        </div>
    </form>
</div>

@if($asignacion && $stats)

{{-- KPIs --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.65rem;margin-bottom:1.2rem;">
    <div class="prt-card" style="text-align:center;padding:.8rem;">
        <div style="font-size:1.4rem;font-weight:800;color:#ec4899;">{{ $stats['completados'] }}</div>
        <div style="font-size:.68rem;color:#64748b;">Evaluados</div>
    </div>
    <div class="prt-card" style="text-align:center;padding:.8rem;">
        <div style="font-size:1.4rem;font-weight:800;color:#94a3b8;">{{ $stats['pendientes'] }}</div>
        <div style="font-size:.68rem;color:#64748b;">Pendientes</div>
    </div>
    <div class="prt-card" style="text-align:center;padding:.8rem;">
        <div style="font-size:1.4rem;font-weight:800;color:#10b981;">{{ $stats['promedio'] }}%</div>
        <div style="font-size:.68rem;color:#64748b;">Promedio</div>
    </div>
    <div class="prt-card" style="text-align:center;padding:.8rem;">
        <div style="font-size:1.4rem;font-weight:800;color:#f59e0b;">{{ $stats['aprobados'] }}</div>
        <div style="font-size:.68rem;color:#64748b;">Aprobados ≥60%</div>
    </div>
</div>

{{-- Análisis por criterio --}}
<div class="prt-card" style="margin-bottom:1.2rem;padding:1rem;">
    <div class="prt-card-header" style="margin-bottom:.8rem;color:#ec4899;">
        <i class="bi bi-bar-chart-steps me-2"></i>Distribución por criterio
    </div>
    @foreach($rubrica->criterios as $ci => $crit)
    @php
        $conteos = array_fill(0, count($rubrica->niveles), 0);
        foreach($aplicaciones as $aplic) {
            $nivelIdx = $aplic->resultados[$ci] ?? null;
            if ($nivelIdx !== null) $conteos[$nivelIdx]++;
        }
        $total = $aplicaciones->count();
    @endphp
    <div style="margin-bottom:.8rem;">
        <div style="font-size:.78rem;font-weight:700;color:#475569;margin-bottom:.3rem;">
            {{ $crit['nombre'] }} <span style="color:#94a3b8;font-weight:500;">({{ $crit['puntos'] }} pts)</span>
        </div>
        <div style="display:flex;gap:.3rem;flex-wrap:wrap;">
            @foreach($rubrica->niveles as $ni => $nivel)
            @php $n = $conteos[$ni]; $pct = $total > 0 ? round($n/$total*100) : 0; @endphp
            <div style="display:flex;align-items:center;gap:.3rem;">
                <span style="background:{{ $nivel['color'] }};color:#fff;border-radius:99px;padding:.1rem .45rem;font-size:.67rem;font-weight:700;">{{ $nivel['nombre'] }}</span>
                <span style="font-size:.72rem;font-weight:700;color:#475569;">{{ $n }}</span>
                @if($total > 0)
                <div class="pct-bar-bg" style="width:50px;">
                    <div class="pct-bar-fill" style="width:{{ $pct }}%;background:{{ $nivel['color'] }};"></div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>

{{-- Tabla individual --}}
<div class="prt-card" style="overflow-x:auto;">
    <div class="prt-card-header" style="margin-bottom:.8rem;color:#ec4899;">
        <i class="bi bi-people me-2"></i>Resultados por estudiante
    </div>
    <table class="res-table">
        <thead>
            <tr>
                <th>Estudiante</th>
                @foreach($rubrica->criterios as $ci => $crit)
                <th style="text-align:center;">{{ Str::limit($crit['nombre'], 18) }}</th>
                @endforeach
                <th style="text-align:center;">Puntaje</th>
                <th style="text-align:center;">%</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($matriculas as $mat)
            @php $aplic = $aplicaciones->get($mat->id); @endphp
            <tr>
                <td>
                    <span style="font-weight:700;font-size:.82rem;">{{ $mat->estudiante?->nombre_completo ?? '—' }}</span>
                </td>
                @foreach($rubrica->criterios as $ci => $crit)
                @php
                    $nivelIdx = $aplic?->resultados[$ci] ?? null;
                    $nivel    = $nivelIdx !== null ? ($rubrica->niveles[$nivelIdx] ?? null) : null;
                @endphp
                <td style="text-align:center;">
                    @if($nivel)
                    <span class="nivel-chip" style="background:{{ $nivel['color'] }};">{{ $nivel['nombre'] }}</span>
                    @else
                    <span style="color:#cbd5e1;font-size:.75rem;">—</span>
                    @endif
                </td>
                @endforeach
                <td style="text-align:center;font-weight:700;color:{{ $aplic ? '#ec4899' : '#94a3b8' }};">
                    {{ $aplic ? $aplic->puntaje.' / '.$aplic->puntaje_max : '—' }}
                </td>
                <td style="text-align:center;">
                    @if($aplic)
                    <div style="display:flex;align-items:center;gap:.3rem;justify-content:center;">
                        <div class="pct-bar-bg">
                            <div class="pct-bar-fill" style="width:{{ $aplic->porcentaje }}%;background:{{ $aplic->porcentaje >= 60 ? '#10b981' : '#ef4444' }};"></div>
                        </div>
                        <span style="font-weight:700;font-size:.75rem;color:{{ $aplic->porcentaje >= 60 ? '#10b981' : '#ef4444' }};">{{ $aplic->porcentaje }}%</span>
                    </div>
                    @else
                    <span style="color:#cbd5e1;font-size:.75rem;">—</span>
                    @endif
                </td>
                <td style="font-size:.75rem;color:#64748b;max-width:160px;">
                    {{ Str::limit($aplic?->observaciones ?? '', 60) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@elseif($asignacion)
<div class="prt-card" style="text-align:center;padding:2.5rem;color:#94a3b8;">
    <i class="bi bi-clipboard-x" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
    <p style="margin:0;font-size:.85rem;">Aún no hay aplicaciones para esta clase.</p>
    <a href="{{ route('portal.docente.rubricas.aplicar', [$rubrica, 'asignacion_id' => $asignacion->id]) }}"
       style="display:inline-flex;align-items:center;gap:.35rem;margin-top:.75rem;background:#ec4899;color:#fff;border-radius:8px;padding:.45rem 1rem;font-size:.8rem;font-weight:700;text-decoration:none;">
        <i class="bi bi-play-fill"></i>Aplicar ahora
    </a>
</div>
@else
<div class="prt-card" style="text-align:center;padding:2.5rem;color:#94a3b8;">
    <i class="bi bi-people" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
    <p style="margin:0;font-size:.85rem;">Selecciona una clase para ver los resultados.</p>
</div>
@endif

@endsection
