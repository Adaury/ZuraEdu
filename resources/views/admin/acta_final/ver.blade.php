<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Acta Final de Calificaciones — {{ $grupo->nombre_completo ?? 'Grupo' }}</title>
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

.hdr { background:#fff; border:1.5px solid #1e3a6e; border-radius:8px; margin-bottom:1rem; overflow:hidden; font-size:.75rem; }
.hdr table { width:100%; border-collapse:collapse; }
.hdr td { border:1px solid #dbe3ef; padding:5px 8px; vertical-align:middle; }
.hdr .lbl { background:#eef3fb; font-weight:800; color:#1e3a6e; text-transform:uppercase; font-size:.68rem; white-space:nowrap; }
.hdr .grado-cell { text-align:center; width:120px; background:#eef3fb; }
.hdr .grado-num { font-size:1.5rem; font-weight:900; color:#1e3a6e; line-height:1.1; }
.hdr .grado-txt { font-size:.62rem; font-weight:800; color:#374151; letter-spacing:.02em; }
.hdr .val-ro { font-weight:600; }
.hdr-input {
    width:100%; border:1px solid transparent; background:transparent; border-radius:4px;
    padding:3px 5px; font-size:.75rem; font-family:inherit; font-weight:600; color:#111827;
}
.hdr-input:hover { border-color:#cbd5e1; }
.hdr-input:focus { outline:none; border-color:#1e3a6e; background:#fffbeb; box-shadow:0 0 0 2px #dbe3ef; }
.hdr-input.hdr-guardando { opacity:.5; }
.hdr-input.hdr-ok { box-shadow:0 0 0 2px #86efac; }
.hdr-input.hdr-error { box-shadow:0 0 0 2px #fca5a5; }
select.hdr-input { appearance:auto; cursor:pointer; }

.leyenda-edit { background:#fef9c3; border:1px solid #eab308; border-radius:6px; padding:.5rem .8rem; margin-bottom:.75rem; font-size:.75rem; color:#713f12; }

.tabla-wrap { overflow-x:auto; background:#fff; border-radius:8px; border:1px solid #e2e8f0; }
table.acta { border-collapse:collapse; font-size:.72rem; white-space:nowrap; }
table.acta th, table.acta td { border:1px solid #e2e8f0; padding:4px 6px; text-align:center; vertical-align:middle; }
table.acta thead th { background:#1e3a6e; color:#fff; font-weight:700; position:sticky; top:0; z-index:2; }
table.acta .col-nombre { text-align:left !important; white-space:normal; min-width:150px; position:sticky; left:0; background:#fff; z-index:1; font-weight:600; }
table.acta thead th.col-nombre { background:#1e3a6e; z-index:3; }
table.acta tbody tr:nth-child(even) td:not(.col-nombre) { background:#f8fafc; }
table.acta tbody tr:nth-child(even) td.col-nombre { background:#f1f5f9; }

.celda-input {
    width:48px; border:1.5px solid #cbd5e1; border-radius:5px; padding:2px 3px;
    text-align:center; font-size:.72rem; font-family:inherit;
}
.celda-input:focus { outline:none; border-color:#1e3a6e; box-shadow:0 0 0 2px #dbe3ef; }
.celda-input.editable { background:#fffbeb; border-color:#f59e0b; }
.celda-input:disabled { background:#f3f4f6; color:#9ca3af; border-color:#e5e7eb; }
.celda-ro { color:#374151; font-weight:600; }
.celda-guardando { opacity:.5; }
.celda-ok { box-shadow:0 0 0 2px #86efac !important; }
.celda-error { box-shadow:0 0 0 2px #fca5a5 !important; }

.sit-a { color:#065f46; background:#d1fae5 !important; font-weight:800; }
.sit-r { color:#991b1b; background:#fee2e2 !important; font-weight:800; }
.sit-p { color:#92400e; background:#fef3c7 !important; }

[data-theme="dark"] body { background:#0f172a; color:#e2e8f0; }
[data-theme="dark"] .hdr, [data-theme="dark"] .tabla-wrap { background:#1e293b; border-color:#334155; }
[data-theme="dark"] .hdr td { border-color:#334155; }
[data-theme="dark"] .hdr .lbl { background:#0f2440; }
[data-theme="dark"] table.acta td { border-color:#334155; }
[data-theme="dark"] table.acta .col-nombre { background:#1e293b; color:#e2e8f0; }
[data-theme="dark"] table.acta tbody tr:nth-child(even) td:not(.col-nombre) { background:#182234; }
</style>
</head>
<body>
<div class="wrap">

    <div class="topbar">
        <div>
            <h1><i class="bi bi-bank2"></i> Acta Final de Calificaciones — {{ $grupo->nombre_completo }}</h1>
            <div class="sub">{{ $grupo->grado?->ciclo_label ?? 'Primer Ciclo' }} · Año Escolar {{ $schoolYear->nombre }}</div>
        </div>
        <div style="display:flex;gap:.5rem;">
            <a href="javascript:history.back()" class="btn btn-back">← Volver</a>
            <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-pdf">📄 Descargar PDF</a>
        </div>
    </div>

    @php
        $tandaOpts  = ['' => '—', 'jee' => 'JEE', 'matutina' => 'Matutina', 'vespertina' => 'Vespertina', 'nocturna' => 'Nocturna'];
        $sectorOpts = ['' => '—', 'publico' => 'Público', 'privado' => 'Privado', 'semioficial' => 'Semioficial'];
        $zonaOpts   = ['' => '—', 'rural' => 'Rural', 'urbana' => 'Urbana', 'otra' => 'Otra'];
    @endphp
    <div class="hdr">
        <table>
            <tr>
                <td class="grado-cell" rowspan="6">
                    <div class="grado-num">{{ $grupo->grado?->nombre ?? '—' }}</div>
                    <div class="grado-txt">PRIMER CICLO<br>NIVEL SECUNDARIO</div>
                </td>
                <td class="lbl" style="width:150px;">Nombre del Centro</td>
                <td colspan="3">
                    @if($puedeEditarInstitucional)
                        <input type="text" class="hdr-input" maxlength="200" data-campo="nombre_institucion" value="{{ $inst['nombre_institucion'] }}" placeholder="Nombre del centro educativo">
                    @else
                        <span class="val-ro">{{ $inst['nombre_institucion'] ?: '—' }}</span>
                    @endif
                </td>
                <td class="lbl" style="width:130px;">Código del Centro</td>
                <td>
                    @if($puedeEditarInstitucional)
                        <input type="text" class="hdr-input" maxlength="30" data-campo="codigo_centro" value="{{ $inst['codigo_centro'] }}" placeholder="—">
                    @else
                        <span class="val-ro">{{ $inst['codigo_centro'] ?: '—' }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="lbl">Dirección Regional</td>
                <td colspan="3">
                    @if($puedeEditarInstitucional)
                        <input type="text" class="hdr-input" maxlength="100" data-campo="regional" value="{{ $inst['regional'] }}" placeholder="—">
                    @else
                        <span class="val-ro">{{ $inst['regional'] ?: '—' }}</span>
                    @endif
                </td>
                <td class="lbl">Distrito Educativo</td>
                <td>
                    @if($puedeEditarInstitucional)
                        <input type="text" class="hdr-input" maxlength="100" data-campo="distrito" value="{{ $inst['distrito'] }}" placeholder="—">
                    @else
                        <span class="val-ro">{{ $inst['distrito'] ?: '—' }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="lbl">Tanda</td>
                <td colspan="3">
                    @if($puedeEditarInstitucional)
                        <select class="hdr-input" data-campo="tanda">
                            @foreach($tandaOpts as $val => $label)
                                <option value="{{ $val }}" {{ $inst['tanda'] === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    @else
                        <span class="val-ro">{{ $tandaOpts[$inst['tanda']] ?? '—' }}</span>
                    @endif
                </td>
                <td class="lbl">Director/a Distrito</td>
                <td>
                    @if($puedeEditarInstitucional)
                        <input type="text" class="hdr-input" maxlength="150" data-campo="director_distrito" value="{{ $inst['director_distrito'] }}" placeholder="—">
                    @else
                        <span class="val-ro">{{ $inst['director_distrito'] ?: '—' }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="lbl">Sector</td>
                <td colspan="3">
                    @if($puedeEditarInstitucional)
                        <select class="hdr-input" data-campo="sector">
                            @foreach($sectorOpts as $val => $label)
                                <option value="{{ $val }}" {{ $inst['sector'] === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    @else
                        <span class="val-ro">{{ $sectorOpts[$inst['sector']] ?? '—' }}</span>
                    @endif
                </td>
                <td class="lbl">Director/a del Centro</td>
                <td>
                    @if($puedeEditarInstitucional)
                        <input type="text" class="hdr-input" maxlength="150" data-campo="nombre_director" value="{{ $inst['nombre_director'] }}" placeholder="—">
                    @else
                        <span class="val-ro">{{ $inst['nombre_director'] ?: '—' }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="lbl">Zona</td>
                <td colspan="3">
                    @if($puedeEditarInstitucional)
                        <select class="hdr-input" data-campo="zona">
                            @foreach($zonaOpts as $val => $label)
                                <option value="{{ $val }}" {{ $inst['zona'] === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    @else
                        <span class="val-ro">{{ $zonaOpts[$inst['zona']] ?? '—' }}</span>
                    @endif
                </td>
                <td class="lbl">Secretario/a Docente del Centro</td>
                <td>
                    @if($puedeEditarInstitucional)
                        <input type="text" class="hdr-input" maxlength="150" data-campo="secretario_docente" value="{{ $inst['secretario_docente'] }}" placeholder="—">
                    @else
                        <span class="val-ro">{{ $inst['secretario_docente'] ?: '—' }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="lbl">Año Escolar</td>
                <td colspan="3"><span class="val-ro">{{ $schoolYear->nombre }}</span></td>
                <td class="lbl">Sección</td>
                <td><span class="val-ro">{{ $grupo->seccion?->nombre ?? '—' }}</span></td>
            </tr>
        </table>
    </div>

    <div class="leyenda-edit">
        ✏️ Los campos resaltados (<strong>Completivo</strong> / <strong>Extraordinario</strong>) son editables — se guardan solos al salir del campo (Enter o clic afuera). "Final de Año" viene de las notas por período ya registradas; "Especial" se edita desde la Planilla Académica de cada materia.
        @if($puedeEditarInstitucional)
            <br>🏫 Los datos del centro (encabezado de arriba) también son editables — se guardan al salir de cada campo. El grado/nivel no se edita aquí porque es un dato estructural del grupo.
        @endif
    </div>

    <div class="tabla-wrap">
    <table class="acta" id="tabla-acta">
        <thead>
            <tr>
                <th rowspan="2">No.</th>
                <th class="col-nombre" rowspan="2">Apellidos y Nombres</th>
                @foreach($asignaciones as $asi)
                    <th colspan="4">{{ $asi->asignatura?->nombre ?? '—' }}</th>
                @endforeach
                <th colspan="2">Situación Final</th>
            </tr>
            <tr>
                @foreach($asignaciones as $asi)
                    <th title="Final de Año">F</th>
                    <th title="Completivo">C</th>
                    <th title="Extraordinario">E</th>
                    <th title="Especial">Es</th>
                @endforeach
                <th>Prom.</th>
                <th>Rep.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($filas as $fila)
            <tr>
                <td>{{ $fila['orden'] }}</td>
                <td class="col-nombre">
                    {{ $fila['matricula']->estudiante?->apellidos ?? '' }}, {{ $fila['matricula']->estudiante?->nombres ?? '—' }}
                </td>
                @foreach($asignaciones as $asi)
                    @php
                        $n = $fila['asignaturas'][$asi->id];
                        $editable = in_array($asi->id, $asignacionesEditables, true);
                        $matId = $fila['matricula']->id;
                    @endphp
                    <td class="celda-ro">{{ $n['final_ano'] !== null ? number_format($n['final_ano'], 0) : '—' }}</td>
                    <td>
                        <input type="number" min="0" max="100" step="1"
                               class="celda-input {{ $editable ? 'editable' : '' }}"
                               value="{{ $n['completivo'] !== null ? (int) round($n['completivo']) : '' }}"
                               data-matricula="{{ $matId }}" data-asignacion="{{ $asi->id }}" data-campo="nota_cc"
                               {{ $editable ? '' : 'disabled' }}>
                    </td>
                    <td>
                        <input type="number" min="0" max="100" step="1"
                               class="celda-input {{ $editable ? 'editable' : '' }}"
                               value="{{ $n['extraordinario'] !== null ? (int) round($n['extraordinario']) : '' }}"
                               data-matricula="{{ $matId }}" data-asignacion="{{ $asi->id }}" data-campo="nota_ce"
                               {{ $editable ? '' : 'disabled' }}>
                    </td>
                    <td class="celda-ro">{{ $n['especial'] !== null ? number_format($n['especial'], 0) : '—' }}</td>
                @endforeach
                <td style="font-weight:900;">{{ $fila['situacion_final'] === 'A' ? 'X' : '' }}</td>
                <td style="font-weight:900;">{{ $fila['situacion_final'] === 'R' ? 'X' : '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>

</div>

<script>
const GUARDAR_URLS = @json($guardarUrlPorAsignacion);
const METODO       = @json($metodoGuardado);
const CSRF         = document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';

document.getElementById('tabla-acta').addEventListener('change', async function (ev) {
    const el = ev.target;
    if (!el.classList.contains('celda-input') || el.disabled) return;

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
        // La Situación Final se recalcula sobre TODAS las materias del
        // estudiante -- se recarga para reflejarla sin duplicar esa lógica en JS.
        setTimeout(() => window.location.reload(), 450);
    } catch (e) {
        el.classList.remove('celda-guardando');
        el.classList.add('celda-error');
    }
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
