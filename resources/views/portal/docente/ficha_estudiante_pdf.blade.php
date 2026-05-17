<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:DejaVu Sans, sans-serif; font-size:9px; color:#1e293b; line-height:1.45; }

/* ── Cabecera ── */
.page-header {
    display:table; width:100%; border-bottom:2px solid #1e3a8a;
    padding-bottom:8px; margin-bottom:10px;
}
.logo-cell { display:table-cell; width:52px; vertical-align:middle; }
.logo-placeholder {
    width:44px; height:44px; border-radius:6px; background:#1e3a8a;
    color:#fff; font-size:14px; font-weight:800;
    display:table-cell; text-align:center; vertical-align:middle;
}
.inst-cell { display:table-cell; vertical-align:middle; padding-left:8px; }
.inst-name { font-size:11px; font-weight:800; color:#1e3a8a; }
.inst-sub  { font-size:7.5px; color:#64748b; margin-top:2px; }
.report-title-cell { display:table-cell; vertical-align:middle; text-align:right; width:130px; }
.report-badge {
    background:#1e3a8a; color:#fff; border-radius:5px;
    padding:4px 8px; font-size:8px; font-weight:800; letter-spacing:.04em;
}
.report-date { font-size:7px; color:#64748b; margin-top:2px; }

/* ── Perfil del estudiante ── */
.student-card {
    background:#f8fafc; border:1px solid #e2e8f0; border-radius:7px;
    padding:8px 10px; margin-bottom:10px; display:table; width:100%;
}
.avatar-cell { display:table-cell; width:50px; vertical-align:middle; }
.avatar-circle {
    width:44px; height:44px; border-radius:50%;
    background:linear-gradient(135deg,#1e3a8a,#2563eb);
    color:#fff; font-size:16px; font-weight:900;
    display:table-cell; text-align:center; vertical-align:middle;
}
.student-info-cell { display:table-cell; vertical-align:middle; padding-left:8px; }
.student-name { font-size:12px; font-weight:900; color:#1e293b; }
.student-meta { font-size:7.5px; color:#64748b; margin-top:2px; }
.nota-final-cell { display:table-cell; vertical-align:middle; text-align:center; width:64px; }
.nota-circle {
    width:52px; height:52px; border-radius:50%;
    display:table-cell; text-align:center; vertical-align:middle;
    font-size:16px; font-weight:900;
}

/* ── Secciones ── */
.section { margin-bottom:10px; }
.section-title {
    background:#1e3a8a; color:#fff; padding:3px 8px;
    font-size:8px; font-weight:800; border-radius:4px;
    margin-bottom:5px; text-transform:uppercase; letter-spacing:.05em;
    display:table; width:100%;
}
.section-title .s-icon { display:table-cell; width:14px; }
.section-title .s-text { display:table-cell; }

/* ── Tablas ── */
table { width:100%; border-collapse:collapse; font-size:8px; }
th { background:#e2e8f0; font-weight:700; padding:3px 5px; border:1px solid #cbd5e1; color:#475569; text-align:left; }
td { padding:3px 5px; border:1px solid #e2e8f0; vertical-align:middle; }
tr:nth-child(even) td { background:#f8fafc; }

/* ── Nota chips ── */
.chip { display:inline-block; padding:1px 5px; border-radius:4px; font-size:8px; font-weight:700; text-align:center; min-width:28px; }
.chip-ok   { background:#dcfce7; color:#166534; }
.chip-min  { background:#fef3c7; color:#92400e; }
.chip-low  { background:#fee2e2; color:#991b1b; }
.chip-nil  { background:#f1f5f9; color:#94a3b8; }
.chip-blue { background:#dbeafe; color:#1d4ed8; }

/* ── Asistencia ── */
.asist-row { display:table; width:100%; margin-bottom:4px; }
.asist-label { display:table-cell; font-size:7.5px; color:#475569; width:80px; }
.asist-val { display:table-cell; font-size:8px; font-weight:700; width:28px; }
.bar-bg { display:table-cell; vertical-align:middle; }
.bar-outer { background:#e2e8f0; border-radius:99px; height:5px; display:block; }
.bar-fill  { height:5px; border-radius:99px; display:block; }

/* ── Observaciones ── */
.obs-item { margin-bottom:5px; padding:4px 6px; border-radius:4px; page-break-inside:avoid; }
.obs-academica  { background:#dbeafe; border-left:3px solid #2563eb; }
.obs-conductual { background:#fee2e2; border-left:3px solid #ef4444; }
.obs-positiva   { background:#dcfce7; border-left:3px solid #16a34a; }
.obs-general    { background:#f1f5f9; border-left:3px solid #94a3b8; }
.obs-tipo { font-size:6.5px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
.obs-texto { font-size:7.5px; margin-top:1px; }
.obs-fecha { font-size:6.5px; color:#94a3b8; float:right; }

/* ── Conducta ── */
.conducta-grid { display:table; width:100%; }
.conducta-col  { display:table-cell; width:50%; vertical-align:top; padding-right:6px; }
.conducta-item { margin-bottom:3px; }
.conducta-lbl  { font-size:7.5px; color:#475569; }
.conducta-stars{ font-size:8px; }

/* ── Representantes ── */
.rep-row { display:table; width:100%; margin-bottom:4px; border:1px solid #e2e8f0; border-radius:4px; padding:4px 6px; }
.rep-icon { display:table-cell; width:18px; vertical-align:middle; font-size:12px; color:#1e3a8a; }
.rep-data { display:table-cell; vertical-align:middle; }
.rep-name { font-size:8.5px; font-weight:700; }
.rep-meta { font-size:7px; color:#64748b; }

/* ── Footer ── */
.footer {
    margin-top:16px; display:table; width:100%;
    border-top:1px solid #e2e8f0; padding-top:5px;
}
.sig-cell { display:table-cell; text-align:center; padding:0 8px; }
.sig-line { border-top:1px solid #475569; padding-top:2px; font-size:7px; color:#64748b; margin-top:14px; }

.two-col { display:table; width:100%; }
.col-left  { display:table-cell; width:50%; vertical-align:top; padding-right:6px; }
.col-right { display:table-cell; width:50%; vertical-align:top; padding-left:6px; }
</style>
</head>
<body>

@php
    $est       = $matricula->estudiante;
    $instName  = $tenant?->nombre_institucion ?? config('app.name');
    $logoUrl   = $tenant?->logo_url ?? null;
    $tipoLabel = ['academica'=>'Académica','conductual'=>'Conductual','positiva'=>'Positiva','general'=>'General'];
    $tipoColor = ['academica'=>'#2563eb','conductual'=>'#ef4444','positiva'=>'#16a34a','general'=>'#94a3b8'];
    $conductaLabels = ['puntualidad'=>'Puntualidad','participacion'=>'Participación','respeto'=>'Respeto',
                       'trabajo_equipo'=>'Trabajo en equipo','responsabilidad'=>'Responsabilidad','orden'=>'Orden'];
    $conductaConcepto = function($v) {
        if ($v >= 5) return 'Excelente';
        if ($v >= 4) return 'Muy Bueno';
        if ($v >= 3) return 'Bueno';
        if ($v >= 2) return 'Regular';
        return 'Deficiente';
    };
@endphp

{{-- ── CABECERA ────────────────────────────────────────────── --}}
<div class="page-header">
    <div class="logo-cell">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" width="44" height="44" style="object-fit:contain;">
        @else
            <table style="width:44px;height:44px;"><tr><td class="logo-placeholder">{{ strtoupper(substr($instName,0,2)) }}</td></tr></table>
        @endif
    </div>
    <div class="inst-cell">
        <div class="inst-name">{{ $instName }}</div>
        <div class="inst-sub">{{ $asignacion->asignatura?->nombre }} · {{ $asignacion->grupo?->nombre_completo ?? $asignacion->grupo?->nombre }}</div>
        <div class="inst-sub">Año escolar: {{ $schoolYear?->nombre ?? '—' }}</div>
    </div>
    <div class="report-title-cell">
        <div class="report-badge">REPORTE DE PROGRESO</div>
        <div class="report-date">Generado: {{ now()->format('d/m/Y H:i') }}</div>
        <div class="report-date">Docente: {{ $docente->nombre_completo ?? auth()->user()->name }}</div>
    </div>
</div>

{{-- ── PERFIL DEL ESTUDIANTE ────────────────────────────────── --}}
<div class="student-card">
    <div class="avatar-cell">
        <table style="width:44px;height:44px;"><tr><td class="avatar-circle">{{ strtoupper(substr($est?->nombres ?? 'E', 0, 1)) }}</td></tr></table>
    </div>
    <div class="student-info-cell">
        <div class="student-name">{{ $est?->apellidos }}, {{ $est?->nombres }}</div>
        <div class="student-meta">
            @if($est?->cedula) C.I.: {{ $est->cedula }} &nbsp;·&nbsp; @endif
            @if($est?->fecha_nacimiento) Nac.: {{ $est->fecha_nacimiento->format('d/m/Y') }} ({{ $est->edad }} años) &nbsp;·&nbsp; @endif
            @if($est?->sexo) {{ $est->sexo === 'M' ? 'Masculino' : 'Femenino' }} @endif
        </div>
        <div class="student-meta" style="margin-top:1px;">
            Grupo: {{ $asignacion->grupo?->nombre_completo ?? '—' }}
            @if($matricula->numero_matricula) &nbsp;·&nbsp; Matrícula: {{ $matricula->numero_matricula }} @endif
        </div>
    </div>
    <div class="nota-final-cell">
        @php
            $nfColor = $notaFinal === null ? '#94a3b8'
                : ($notaFinal >= 90 ? '#1d4ed8'
                : ($notaFinal >= 70 ? '#16a34a'
                : ($notaFinal >= 65 ? '#d97706' : '#dc2626')));
        @endphp
        <table style="width:52px;height:52px;margin:0 auto;">
            <tr><td class="nota-circle" style="background:{{ $nfColor }}14;border:2px solid {{ $nfColor }};color:{{ $nfColor }};">
                {{ $notaFinal ?? '—' }}
            </td></tr>
        </table>
        <div style="font-size:6.5px;text-align:center;color:#64748b;margin-top:2px;">Nota final</div>
    </div>
</div>

{{-- ── DOS COLUMNAS: NOTAS + ASISTENCIA ───────────────────────── --}}
<div class="two-col">
<div class="col-left">

{{-- NOTAS POR PERÍODO ── --}}
<div class="section">
    <div class="section-title">Notas por Período</div>

    @if($esTecnica)
    {{-- Técnica: tabla de RA por período --}}
    @php $firstCal = collect($notasPeriodo)->pluck('cal')->filter()->first(); @endphp
    <table>
        <thead>
            <tr>
                <th>Período</th>
                @if($firstCal)
                @php $numRA = collect($notasPeriodo)->pluck('ras')->first()?->count() ?? 3; @endphp
                @for($r = 1; $r <= $numRA; $r++)
                <th style="text-align:center;">RA{{$r}}</th>
                @endfor
                @endif
                <th style="text-align:center;">Final</th>
            </tr>
        </thead>
        <tbody>
            @foreach($periodos as $p)
            @php $pd = $notasPeriodo[$p->numero] ?? null; $cal = $pd['cal'] ?? null; @endphp
            <tr>
                <td style="font-weight:700;">P{{ $p->numero }}</td>
                @if($firstCal)
                @for($r = 1; $r <= $numRA; $r++)
                @php $v = $cal ? $cal->{"ra{$r}"} : null; @endphp
                <td style="text-align:center;">
                    @if($v !== null)
                    <span class="chip {{ $v >= 70 ? 'chip-ok' : ($v >= 50 ? 'chip-min' : 'chip-low') }}">{{ $v }}</span>
                    @else <span class="chip chip-nil">—</span> @endif
                </td>
                @endfor
                @endif
                <td style="text-align:center;">
                    @php $fn = $pd['final'] ?? null; @endphp
                    @if($fn !== null)
                    <span class="chip {{ $fn >= 70 ? 'chip-ok' : ($fn >= 50 ? 'chip-min' : 'chip-low') }}">{{ round($fn,1) }}</span>
                    @else <span class="chip chip-nil">—</span> @endif
                </td>
            </tr>
            @endforeach
            @if($notaFinal !== null)
            <tr style="background:#f0f9ff;">
                <td style="font-weight:800;">FINAL</td>
                @if($firstCal) @for($r = 1; $r <= $numRA; $r++) <td></td> @endfor @endif
                <td style="text-align:center;">
                    <span class="chip {{ $notaFinal >= 70 ? 'chip-ok' : 'chip-low' }}" style="font-size:9px;">{{ $notaFinal }}</span>
                </td>
            </tr>
            @endif
        </tbody>
    </table>

    @else
    {{-- Académica: 4 competencias por período --}}
    @php
        $compNames = ['Com.', 'Pens.', 'Cient.', 'Ética'];
    @endphp
    <table>
        <thead>
            <tr>
                <th>Per.</th>
                @foreach($compNames as $cn)<th style="text-align:center;font-size:7px;">{{ $cn }}</th>@endforeach
                <th style="text-align:center;">Prom.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($periodos as $p)
            @php
                $pd    = $notasPeriodo[$p->numero] ?? null;
                $comps = $pd['comps'] ?? [null,null,null,null];
                $vals  = array_filter($comps, fn($v) => $v !== null);
                $avg   = count($vals) ? round(array_sum($vals)/count($vals),1) : null;
            @endphp
            <tr>
                <td style="font-weight:700;">P{{ $p->numero }}</td>
                @foreach($comps as $cv)
                <td style="text-align:center;">
                    @if($cv !== null)
                    <span class="chip {{ $cv >= 70 ? 'chip-ok' : ($cv >= 50 ? 'chip-min' : 'chip-low') }}">{{ $cv }}</span>
                    @else <span class="chip chip-nil">—</span> @endif
                </td>
                @endforeach
                <td style="text-align:center;">
                    @if($avg !== null)
                    <span class="chip {{ $avg >= 70 ? 'chip-ok' : ($avg >= 50 ? 'chip-min' : 'chip-low') }}">{{ $avg }}</span>
                    @else <span class="chip chip-nil">—</span> @endif
                </td>
            </tr>
            @endforeach
            @if($notaFinal !== null)
            <tr style="background:#f0f9ff;">
                <td style="font-weight:800;" colspan="5">NOTA FINAL</td>
                <td style="text-align:center;">
                    <span class="chip {{ $notaFinal >= 70 ? 'chip-ok' : 'chip-low' }}" style="font-size:9px;">{{ $notaFinal }}</span>
                </td>
            </tr>
            @endif
        </tbody>
    </table>
    @if(!$esTecnica)
    <div style="font-size:6.5px;color:#64748b;margin-top:3px;">
        Com.=Comunicativa · Pens.=Pensamiento Lógico · Cient.=Científica/Tecnológica · Ética=Ética/Ciudadana
    </div>
    @endif
    @endif
</div>

{{-- ASISTENCIA ── --}}
<div class="section">
    <div class="section-title">Asistencia</div>
    @if($asistResumen['total'] > 0)
    <table style="margin-bottom:4px;">
        <tr>
            <td style="font-weight:700;">Total clases</td>
            <td style="text-align:center;"><strong>{{ $asistResumen['total'] }}</strong></td>
            <td style="font-weight:700;">Presentes</td>
            <td style="text-align:center;"><span class="chip chip-ok">{{ $asistResumen['presentes'] }}</span></td>
        </tr>
        <tr>
            <td style="font-weight:700;">Ausentes</td>
            <td style="text-align:center;"><span class="chip chip-low">{{ $asistResumen['ausentes'] }}</span></td>
            <td style="font-weight:700;">Tardanzas</td>
            <td style="text-align:center;"><span class="chip chip-min">{{ $asistResumen['tardes'] }}</span></td>
        </tr>
        @if($asistResumen['excusas'] > 0)
        <tr>
            <td style="font-weight:700;">Excusas</td>
            <td style="text-align:center;"><span class="chip chip-blue">{{ $asistResumen['excusas'] }}</span></td>
            <td></td><td></td>
        </tr>
        @endif
    </table>
    @php
        $pct    = $asistResumen['pct'] ?? 0;
        $barClr = $pct >= 80 ? '#16a34a' : ($pct >= 60 ? '#d97706' : '#dc2626');
    @endphp
    <div style="margin-top:3px;">
        <div style="display:table;width:100%;">
            <div style="display:table-cell;font-size:8px;font-weight:700;color:{{ $barClr }};width:46px;">{{ $pct }}%</div>
            <div style="display:table-cell;vertical-align:middle;">
                <div class="bar-outer">
                    <div class="bar-fill" style="width:{{ min($pct,100) }}%;background:{{ $barClr }};"></div>
                </div>
            </div>
        </div>
        <div style="font-size:6.5px;color:#64748b;margin-top:1px;">% de asistencia efectiva</div>
    </div>
    @else
    <div style="font-size:7.5px;color:#94a3b8;font-style:italic;">Sin registros de asistencia.</div>
    @endif
</div>

{{-- CONDUCTA ── --}}
@if($conducta)
<div class="section">
    <div class="section-title">Conducta</div>
    <div class="conducta-grid">
        <div class="conducta-col">
            @foreach(array_slice($conductaLabels,0,3,true) as $field => $label)
            @php $v = $conducta->$field ?? null; @endphp
            <div class="conducta-item">
                <div class="conducta-lbl">{{ $label }}</div>
                <div style="font-size:8px;font-weight:700;color:{{ $v >= 4 ? '#16a34a' : ($v >= 3 ? '#d97706' : '#dc2626') }};">
                    {{ $v ?? '—' }}/5 — {{ $v ? $conductaConcepto($v) : '—' }}
                </div>
            </div>
            @endforeach
        </div>
        <div class="conducta-col">
            @foreach(array_slice($conductaLabels,3,3,true) as $field => $label)
            @php $v = $conducta->$field ?? null; @endphp
            <div class="conducta-item">
                <div class="conducta-lbl">{{ $label }}</div>
                <div style="font-size:8px;font-weight:700;color:{{ $v >= 4 ? '#16a34a' : ($v >= 3 ? '#d97706' : '#dc2626') }};">
                    {{ $v ?? '—' }}/5 — {{ $v ? $conductaConcepto($v) : '—' }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @if($conducta->observaciones)
    <div style="margin-top:3px;font-size:7.5px;color:#475569;background:#f8fafc;padding:3px 5px;border-radius:3px;">
        {{ $conducta->observaciones }}
    </div>
    @endif
</div>
@endif

</div>{{-- col-left --}}

<div class="col-right">

{{-- INSTRUMENTOS EVALUADOS ── --}}
@if($instrumentos->isNotEmpty())
<div class="section">
    <div class="section-title">Instrumentos Evaluados</div>
    <table>
        <thead>
            <tr>
                <th>Instrumento</th>
                <th>Tipo</th>
                <th style="text-align:center;">Puntaje</th>
            </tr>
        </thead>
        <tbody>
            @foreach($instrumentos as $inst)
            @php
                $eval  = $inst->evaluaciones->first();
                $pond  = $eval?->ponderacion;
                $tipos = ['lista_cotejo'=>'Lista','rubrica'=>'Rúbrica','escala_estimacion'=>'Escala'];
            @endphp
            <tr>
                <td>{{ \Illuminate\Support\Str::limit($inst->titulo, 35) }}</td>
                <td style="font-size:7px;">{{ $tipos[$inst->tipo] ?? $inst->tipo }}</td>
                <td style="text-align:center;">
                    @if($pond !== null)
                    <span class="chip {{ $pond >= 70 ? 'chip-ok' : ($pond >= 65 ? 'chip-min' : 'chip-low') }}">{{ round($pond) }}</span>
                    @else
                    <span class="chip chip-nil">S/E</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- OBSERVACIONES ── --}}
@if($observaciones->isNotEmpty())
<div class="section">
    <div class="section-title">Observaciones del Docente</div>
    @foreach($observaciones->take(8) as $obs)
    @php $cls = 'obs-' . ($obs->tipo ?? 'general'); @endphp
    <div class="obs-item {{ $cls }}" style="page-break-inside:avoid;">
        <div>
            <span class="obs-tipo" style="color:{{ $tipoColor[$obs->tipo] ?? '#64748b' }};">
                {{ $tipoLabel[$obs->tipo] ?? 'General' }}
            </span>
            @if($obs->privada)<span style="font-size:6px;color:#94a3b8;margin-left:4px;">● Privada</span>@endif
            <span class="obs-fecha">{{ $obs->created_at?->format('d/m/Y') }}</span>
        </div>
        <div class="obs-texto">{{ \Illuminate\Support\Str::limit($obs->texto, 120) }}</div>
    </div>
    @endforeach
    @if($observaciones->count() > 8)
    <div style="font-size:7px;color:#94a3b8;text-align:right;margin-top:2px;">
        + {{ $observaciones->count() - 8 }} observación(es) adicional(es)
    </div>
    @endif
</div>
@endif

{{-- REPRESENTANTES ── --}}
@if($matricula->estudiante?->representantes?->isNotEmpty())
<div class="section">
    <div class="section-title">Representantes / Tutores</div>
    @foreach($matricula->estudiante->representantes->take(3) as $rep)
    <div style="margin-bottom:4px;padding:4px 6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;">
        <div style="font-size:8.5px;font-weight:700;">
            {{ $rep->nombre_completo ?? ($rep->nombres . ' ' . $rep->apellidos) }}
            @if($rep->pivot?->es_principal ?? false)
            <span style="background:#1e3a8a;color:#fff;border-radius:3px;font-size:6px;padding:0 4px;margin-left:3px;">Principal</span>
            @endif
        </div>
        <div style="font-size:7px;color:#64748b;">
            @if($rep->pivot?->parentesco) {{ ucfirst($rep->pivot->parentesco) }} @endif
            @if($rep->telefono) · Tel: {{ $rep->telefono }} @endif
            @if($rep->email) · {{ $rep->email }} @endif
        </div>
    </div>
    @endforeach
</div>
@endif

</div>{{-- col-right --}}
</div>{{-- two-col --}}

{{-- ── FIRMAS ────────────────────────────────────────────────── --}}
<div class="footer">
    <div class="sig-cell">
        <div class="sig-line">Docente: {{ $docente->nombre_completo ?? auth()->user()->name }}</div>
    </div>
    <div class="sig-cell">
        <div class="sig-line">Coordinador(a) Académico(a)</div>
    </div>
    <div class="sig-cell">
        <div class="sig-line">Representante / Tutor</div>
    </div>
</div>

</body>
</html>
