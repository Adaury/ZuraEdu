<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Boletín de Nota — {{ $matricula->estudiante?->nombres }} {{ $matricula->estudiante?->apellidos }}</title>
<link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI', Arial, sans-serif; font-size:.85rem; color:#111827; background:#f1f5f9; }
.wrap { max-width:100%; padding:1rem 1.25rem 3rem; }

.topbar { display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap; margin-bottom:1rem; }
.topbar h1 { font-size:1.1rem; font-weight:800; color:#1e3a6e; }
.topbar .sub { font-size:.78rem; color:#6b7280; margin-top:2px; }
.btn { display:inline-flex; align-items:center; gap:.35rem; border:none; border-radius:8px; padding:.45rem .9rem; font-size:.8rem; font-weight:700; text-decoration:none; cursor:pointer; }
.btn-back { background:#e5e7eb; color:#374151; }
.btn-pdf  { background:#c0392b; color:#fff; }

/* ── "No. X  Estudiante: Apellidos, Nombres" ── */
.no-est { background:#dbeafe; border:1px solid #1e3a6e; border-radius:8px; padding:.6rem .9rem; margin-bottom:.6rem; display:flex; align-items:center; gap:.6rem; font-size:.9rem; }
.no-est .badge-no { background:#1e3a6e; color:#fff; font-weight:800; border-radius:6px; padding:.15rem .6rem; }
.no-est .nombre { font-weight:800; color:#111827; }

/* ── Barra de título ── */
.titulo-rendimiento { text-align:center; font-weight:800; color:#1e3a6e; background:#eaf2ff; border:1.5px solid #1e3a6e; border-radius:6px; padding:.4rem; margin-bottom:.6rem; text-transform:uppercase; letter-spacing:.05em; font-size:.85rem; }

.leyenda-edit { background:#fef9c3; border:1px solid #eab308; border-radius:6px; padding:.5rem .8rem; margin-bottom:.75rem; font-size:.75rem; color:#713f12; }

.tabla-wrap { overflow-x:auto; background:#fff; border-radius:8px; border:1px solid #e2e8f0; margin-bottom:.9rem; }
table.tbl { border-collapse:collapse; font-size:.7rem; white-space:nowrap; width:100%; }
table.tbl th, table.tbl td { border:1px solid #dbe3ef; padding:3px 5px; text-align:center; vertical-align:middle; }
table.tbl thead th { background:#1e3a6e; color:#fff; font-weight:700; }
table.tbl thead tr.sub th { background:#eef3fb; color:#1e3a6e; font-size:.64rem; }
table.tbl .col-mat { text-align:left !important; white-space:normal; min-width:130px; font-weight:600; background:#fff; }
table.tbl tbody tr:nth-child(even) td { background:#f8fafc; }
table.tbl tbody tr:nth-child(even) td.col-mat { background:#f1f5f9; }
.col-cf { font-weight:800; background:#dbeafe !important; }
.prom-row td { background:#1e3a6e !important; color:#fff !important; font-weight:800; }

.celda-input {
    width:42px; border:1.5px solid #cbd5e1; border-radius:5px; padding:2px 3px;
    text-align:center; font-size:.7rem; font-family:inherit;
}
.celda-input:focus { outline:none; border-color:#1e3a6e; box-shadow:0 0 0 2px #dbe3ef; }
.celda-input.editable { background:#fffbeb; border-color:#f59e0b; }
.celda-input:disabled { background:#f3f4f6; color:#9ca3af; border-color:#e5e7eb; }
.celda-guardando { opacity:.5; }
.celda-ok { box-shadow:0 0 0 2px #86efac !important; }
.celda-error { box-shadow:0 0 0 2px #fca5a5 !important; }
.sit-a { color:#065f46; font-weight:800; }
.sit-r { color:#991b1b; font-weight:800; }

/* ── Firmas ── */
.firmas { width:100%; border-collapse:collapse; margin:1.2rem 0; }
.firmas td { text-align:center; padding:0 10px; vertical-align:bottom; font-size:.75rem; }
.firmas .linea { border-top:1.3px solid #374151; margin-top:26px; padding-top:4px; font-weight:700; min-height:1.1rem; }
.firmas .cargo { font-size:.68rem; color:#6b7280; margin-top:1px; }
.firmas .hdr-input { text-align:center; }

.hdr-input {
    width:100%; border:1px solid transparent; background:transparent; border-radius:4px;
    padding:3px 5px; font-size:.8rem; font-family:inherit; font-weight:700; color:#111827; text-align:center;
}
.hdr-input:hover { border-color:#cbd5e1; }
.hdr-input:focus { outline:none; border-color:#1e3a6e; background:#fffbeb; box-shadow:0 0 0 2px #dbe3ef; }
.hdr-input.hdr-guardando { opacity:.5; }
.hdr-input.hdr-ok { box-shadow:0 0 0 2px #86efac; }
.hdr-input.hdr-error { box-shadow:0 0 0 2px #fca5a5; }

/* ── Resumen de asistencia ── */
.resumen-asist { display:flex; gap:1.5rem; align-items:flex-start; flex-wrap:wrap; }
.resumen-asist .tabla-wrap { flex:1 1 320px; margin-bottom:0; }
.resumen-anual { background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:.7rem 1rem; text-align:center; min-width:150px; }
.resumen-anual .pct { font-size:1.6rem; font-weight:900; color:#c0392b; }
.resumen-anual .lbl { font-size:.68rem; color:#6b7280; text-transform:uppercase; font-weight:700; }

[data-theme="dark"] body { background:#0f172a; color:#e2e8f0; }
[data-theme="dark"] .no-est { background:#0f2440; }
[data-theme="dark"] .titulo-rendimiento { background:#0f2440; }
[data-theme="dark"] .tabla-wrap, [data-theme="dark"] .resumen-anual { background:#1e293b; border-color:#334155; }
[data-theme="dark"] table.tbl td, [data-theme="dark"] table.tbl th { border-color:#334155; }
[data-theme="dark"] table.tbl thead tr.sub th { background:#0f2440; color:#e2e8f0; }
[data-theme="dark"] table.tbl .col-mat { background:#1e293b; color:#e2e8f0; }
[data-theme="dark"] table.tbl tbody tr:nth-child(even) td { background:#182234; }
</style>
</head>
<body>
<div class="wrap">

    <div class="topbar">
        <div>
            <h1><i class="bi bi-journal-bookmark-fill"></i> Boletín de Nota — {{ $matricula->estudiante?->nombres }} {{ $matricula->estudiante?->apellidos }}</h1>
            <div class="sub">{{ $matricula->grupo?->nombre_completo ?? '—' }} · Año Escolar {{ $schoolYear->nombre ?? '—' }}</div>
        </div>
        <div style="display:flex;gap:.5rem;">
            <a href="javascript:history.back()" class="btn btn-back">← Volver</a>
            <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-pdf">📄 Descargar PDF</a>
        </div>
    </div>

    <div class="no-est">
        <span class="badge-no">No. {{ $matricula->numero_orden ?? '—' }}</span>
        <span>Estudiante:</span>
        <span class="nombre">{{ $matricula->estudiante?->apellidos }}, {{ $matricula->estudiante?->nombres }}</span>
    </div>

    <div class="titulo-rendimiento">Calificación de Rendimiento Estudiantil</div>

    @php $decAnual = ($matricula->grupo?->grado?->esPrimerCiclo()) ? 0 : 1; @endphp

    @if(!empty($competenciasFundamentales) && $competenciasFundamentales->isNotEmpty())
    @php $cfComps = \App\Models\CalificacionAcademica::COMPETENCIAS; @endphp

    <div class="leyenda-edit">
        ✏️ <strong>Calificación Completiva / Extraordinaria</strong> (C.E.C. / C.E.Ex.) editables por Administrador o Coordinación.
        @if($puedeEditarInstitucional)
            <strong>Prueba Especial</strong> (C.F. / C.E.) también editable — exclusivo del Administrador. Los promedios P1-P4 vienen de la Planilla Académica de cada materia.
        @else
            La Prueba Especial (C.F. / C.E.) y los datos de firma solo los edita el Administrador.
        @endif
    </div>

    <div class="tabla-wrap">
    <table class="tbl">
        <thead>
            <tr>
                <th class="col-mat" rowspan="2">Competencias Fundamentales</th>
                @foreach($cfComps as $comp)
                    <th colspan="5">{{ $comp['nombre'] }}</th>
                @endforeach
                <th rowspan="2">Prom.<br>Comp.<br>(C.F.)</th>
                <th rowspan="2">%<br>Asist.</th>
                <th colspan="2">Calif. Completiva</th>
                <th colspan="2">Calif. Extraordinaria</th>
                <th colspan="2">Prueba Especial</th>
                <th colspan="2">Sit.<br>Final</th>
            </tr>
            <tr class="sub">
                @foreach($cfComps as $comp)
                    <th>P1</th><th>P2</th><th>P3</th><th>P4</th><th>CFC</th>
                @endforeach
                <th>50% C.F.</th>
                <th>C.E.C.</th>
                <th>30% C.F.</th>
                <th>C.E.Ex.</th>
                <th>C.F.</th>
                <th>C.E</th>
                <th>A</th>
                <th>R</th>
            </tr>
        </thead>
        <tbody>
            @foreach($competenciasFundamentales as $cal)
                @php
                    $editable = in_array($cal->asignacion_id, $asignacionesEditables, true);
                    $editableEspecial = $editable && $puedeEditarInstitucional;
                    $mitadCf = $cal->nota_final !== null ? round($cal->nota_final * 0.5) : null;
                    $treintaCf = $cal->nota_final !== null ? round($cal->nota_final * 0.3) : null;
                @endphp
            <tr>
                <td class="col-mat">{{ $cal->asignacion?->asignatura?->nombre ?? '—' }}</td>
                @foreach($cfComps as $c => $comp)
                    @for($p = 1; $p <= 4; $p++)
                        <td>{{ $cal->{"avg_comp{$c}_p{$p}"} !== null ? number_format($cal->{"avg_comp{$c}_p{$p}"}, 0) : '—' }}</td>
                    @endfor
                    <td style="font-weight:800;">{{ $cal->{"prom_comp{$c}"} !== null ? number_format($cal->{"prom_comp{$c}"}, 0) : '—' }}</td>
                @endforeach
                <td class="col-cf">{{ $cal->nota_final !== null ? number_format($cal->nota_final, 0) : '—' }}</td>
                <td>{{ $cal->pct_asistencia !== null ? number_format($cal->pct_asistencia, 0) : '—' }}</td>
                <td>{{ $mitadCf ?? '—' }}</td>
                <td>
                    <input type="number" min="0" max="100" step="1"
                           class="celda-input {{ $editable ? 'editable' : '' }}"
                           value="{{ $cal->nota_cc !== null ? (int) round($cal->nota_cc) : '' }}"
                           data-matricula="{{ $matricula->id }}" data-asignacion="{{ $cal->asignacion_id }}" data-campo="nota_cc"
                           {{ $editable ? '' : 'disabled' }}>
                </td>
                <td>{{ $treintaCf ?? '—' }}</td>
                <td>
                    <input type="number" min="0" max="100" step="1"
                           class="celda-input {{ $editable ? 'editable' : '' }}"
                           value="{{ $cal->nota_ce !== null ? (int) round($cal->nota_ce) : '' }}"
                           data-matricula="{{ $matricula->id }}" data-asignacion="{{ $cal->asignacion_id }}" data-campo="nota_ce"
                           {{ $editable ? '' : 'disabled' }}>
                </td>
                <td>
                    <input type="number" min="0" max="100" step="1"
                           class="celda-input {{ $editableEspecial ? 'editable' : '' }}"
                           value="{{ $cal->eval_cf !== null ? (int) round($cal->eval_cf) : '' }}"
                           data-matricula="{{ $matricula->id }}" data-asignacion="{{ $cal->asignacion_id }}" data-campo="eval_cf"
                           {{ $editableEspecial ? '' : 'disabled' }}>
                </td>
                <td>
                    <input type="number" min="0" max="100" step="1"
                           class="celda-input {{ $editableEspecial ? 'editable' : '' }}"
                           value="{{ $cal->eval_ce !== null ? (int) round($cal->eval_ce) : '' }}"
                           data-matricula="{{ $matricula->id }}" data-asignacion="{{ $cal->asignacion_id }}" data-campo="eval_ce"
                           {{ $editableEspecial ? '' : 'disabled' }}>
                </td>
                <td class="sit-a">{{ $cal->situacion === 'A' ? 'X' : '' }}</td>
                <td class="sit-r">{{ $cal->situacion === 'R' ? 'X' : '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @endif

    <table class="firmas">
        <tr>
            <td style="width:33%;">
                <div class="linea">{{ $matricula->grupo?->tutor?->name ?? '—' }}</div>
                <div class="cargo">Maestro/a Guía</div>
            </td>
            <td style="width:33%;">
                <div class="linea">
                    @if($puedeEditarInstitucional)
                        <input type="text" class="hdr-input" maxlength="150" data-campo="coordinador_pedagogico" value="{{ $inst['coordinador_pedagogico'] }}" placeholder="Nombre completo">
                    @else
                        {{ $inst['coordinador_pedagogico'] ?: '—' }}
                    @endif
                </div>
                <div class="cargo">Coordinador/a Pedagógico/a</div>
            </td>
            <td style="width:33%;">
                <div class="linea">
                    @if($puedeEditarInstitucional)
                        <input type="text" class="hdr-input" maxlength="150" data-campo="nombre_director" value="{{ $inst['nombre_director'] }}" placeholder="Nombre completo">
                    @else
                        {{ $inst['nombre_director'] ?: '—' }}
                    @endif
                </div>
                <div class="cargo">Director/a</div>
            </td>
        </tr>
    </table>

    <div class="seccion-titulo" style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#1e3a6e;margin:0 0 .4rem;">Resumen de Asistencia del/la Estudiante</div>
    <div class="resumen-asist">
        <div class="tabla-wrap">
        <table class="tbl">
            <thead>
                <tr><th>Período</th><th>% Asistencia</th><th>% Ausencia</th></tr>
            </thead>
            <tbody>
                @foreach($periodos as $p)
                    @php $ap = $asistenciaPorPeriodo[$p->id] ?? ['pct_asistencia' => null, 'pct_ausencia' => null]; @endphp
                    <tr>
                        <td class="col-mat">{{ $p->nombre_corto ?? 'P'.$p->numero }}</td>
                        <td>{{ $ap['pct_asistencia'] !== null ? $ap['pct_asistencia'].'%' : '—' }}</td>
                        <td>{{ $ap['pct_ausencia'] !== null ? $ap['pct_ausencia'].'%' : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        <div class="resumen-anual">
            <div class="lbl">% Anual Asistencia</div>
            <div class="pct">{{ ($asistenciaTotales['pct'] ?? null) !== null ? $asistenciaTotales['pct'].'%' : '—' }}</div>
        </div>
    </div>

</div>

<script>
const GUARDAR_URLS = @json($guardarUrlPorAsignacion);
const METODO       = @json($metodoGuardado);
const CSRF         = document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';

document.querySelectorAll('.celda-input').forEach(function (el) {
    el.addEventListener('change', async function () {
        if (el.disabled) return;

        const asignacionId = el.dataset.asignacion;
        const url = GUARDAR_URLS[asignacionId];
        if (!url) return;

        el.classList.add('celda-guardando');
        el.classList.remove('celda-ok', 'celda-error');

        try {
            const res = await fetch(url, {
                method: METODO,
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({
                    matricula_id:   el.dataset.matricula,
                    asignacion_id:  asignacionId,
                    campo:          el.dataset.campo,
                    valor:          el.value === '' ? null : el.value,
                }),
            });
            const json = await res.json();
            el.classList.remove('celda-guardando');

            if (!res.ok || json.error) {
                el.classList.add('celda-error');
                return;
            }

            el.classList.add('celda-ok');
            setTimeout(() => window.location.reload(), 450);
        } catch (e) {
            el.classList.remove('celda-guardando');
            el.classList.add('celda-error');
        }
    });
});

const GUARDAR_INSTITUCIONAL_URL = @json($guardarInstitucionalUrl);

document.querySelectorAll('.hdr-input').forEach(function (el) {
    el.addEventListener('change', async function () {
        if (!GUARDAR_INSTITUCIONAL_URL) return;

        el.classList.add('hdr-guardando');
        el.classList.remove('hdr-ok', 'hdr-error');

        try {
            const res = await fetch(GUARDAR_INSTITUCIONAL_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ campo: el.dataset.campo, valor: el.value }),
            });
            el.classList.remove('hdr-guardando');

            if (!res.ok) {
                el.classList.add('hdr-error');
                return;
            }
            el.classList.add('hdr-ok');
            setTimeout(() => el.classList.remove('hdr-ok'), 1500);
        } catch (e) {
            el.classList.remove('hdr-guardando');
            el.classList.add('hdr-error');
        }
    });
});
</script>
</body>
</html>
