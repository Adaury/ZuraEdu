{{--
    Shared calendar widget for all portals.
    Requires: $eventos (JSON array), $schoolYear (optional)
    Caller sets: $portalPrefix (string) — 'estudiante'|'padre'|'docente'
--}}
<style>
.cal-wrap     { background:#fff; border-radius:16px; box-shadow:0 2px 12px rgba(15,23,42,.07); overflow:hidden; }
.cal-nav      { display:flex; align-items:center; justify-content:space-between; padding:.85rem 1.1rem; background:#0f172a; }
.cal-nav-btn  { background:rgba(255,255,255,.12); border:none; color:#fff; border-radius:8px; width:34px; height:34px; font-size:1.1rem; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background .15s; }
.cal-nav-btn:hover { background:rgba(255,255,255,.22); }
.cal-nav-title{ color:#fff; font-size:.95rem; font-weight:800; text-transform:capitalize; }
.cal-filters  { padding:.65rem 1rem; display:flex; gap:.4rem; flex-wrap:wrap; border-bottom:1px solid #f1f5f9; background:#fafafa; }
.cal-fpill    { border:1.5px solid #e2e8f0; border-radius:99px; padding:.25rem .7rem; font-size:.7rem; font-weight:700; cursor:pointer; background:#fff; color:#64748b; transition:all .15s; }
.cal-fpill.active { background:#0f172a; color:#fff; border-color:#0f172a; }
.cal-grid     { display:grid; grid-template-columns:repeat(7, 1fr); }
.cal-dh       { text-align:center; font-size:.68rem; font-weight:700; color:#94a3b8; padding:.5rem 0; border-bottom:1px solid #f1f5f9; }
.cal-cell     { min-height:52px; padding:.35rem .3rem; border-right:1px solid #f8fafc; border-bottom:1px solid #f8fafc; cursor:pointer; transition:background .12s; display:flex; flex-direction:column; align-items:center; gap:.15rem; }
.cal-cell:hover { background:#eff6ff; }
.cal-cell.today .cal-num { background:#2563eb; color:#fff; border-radius:50%; width:22px; height:22px; display:flex; align-items:center; justify-content:center; }
.cal-cell.selected { background:#eff6ff; outline:2px solid #2563eb; outline-offset:-2px; }
.cal-cell.cal-blank { background:#fafafa; cursor:default; }
.cal-num      { font-size:.78rem; font-weight:600; color:#374151; width:22px; height:22px; display:flex; align-items:center; justify-content:center; }
.cal-dots     { display:flex; gap:2px; flex-wrap:wrap; justify-content:center; max-width:38px; }
.cdot         { width:6px; height:6px; border-radius:50%; flex-shrink:0; }
.cdot-more    { font-size:.55rem; color:#94a3b8; font-weight:700; }

.cal-panel    { border-top:2px solid #eff6ff; }
.cal-panel-hdr{ padding:.75rem 1rem; background:#f8fafc; display:flex; align-items:center; justify-content:space-between; }
.cal-panel-date{ font-size:.85rem; font-weight:700; color:#1e293b; text-transform:capitalize; }
.cal-panel-close{ border:none; background:none; color:#94a3b8; cursor:pointer; font-size:1.1rem; padding:.2rem; }
.cal-event-item{ display:flex; align-items:flex-start; gap:.6rem; padding:.6rem 1rem; border-bottom:1px solid #f8fafc; }
.cal-event-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; margin-top:.28rem; }
.cal-event-name{ font-size:.82rem; font-weight:600; color:#1e293b; }
.cal-event-tipo{ font-size:.7rem; color:#94a3b8; margin-top:.1rem; }
.cal-event-desc{ font-size:.73rem; color:#64748b; margin-top:.1rem; }
.cal-empty    { text-align:center; padding:1.25rem; color:#94a3b8; font-size:.8rem; }

.cal-legend   { padding:.65rem 1rem; display:flex; gap:.6rem; flex-wrap:wrap; border-top:1px solid #f1f5f9; background:#fafafa; }
.cal-leg-item { display:flex; align-items:center; gap:.3rem; font-size:.68rem; color:#64748b; }
.cal-leg-dot  { width:8px; height:8px; border-radius:50%; }
</style>

<div style="margin-bottom:1rem;">
    <h1 style="font-size:1rem;font-weight:800;margin:0;color:#0f172a;"><i class="bi bi-calendar3 me-2" style="color:#2563eb;"></i>Calendario Escolar</h1>
    @if($schoolYear)
    <div style="font-size:.75rem;color:#64748b;margin-top:.2rem;">Año escolar: {{ $schoolYear->nombre }}</div>
    @endif
</div>

<div class="prt-card" style="padding:0;overflow:hidden;">
    {{-- Navegación de mes --}}
    <div class="cal-nav">
        <button class="cal-nav-btn" onclick="cambiarMes(-1)"><i class="bi bi-chevron-left"></i></button>
        <span class="cal-nav-title" id="calTitle">Cargando...</span>
        <button class="cal-nav-btn" onclick="cambiarMes(1)"><i class="bi bi-chevron-right"></i></button>
    </div>

    {{-- Filtros por tipo --}}
    <div class="cal-filters">
        <button class="cal-fpill active" data-tipo="all" onclick="setFiltro(this,'all')">Todos</button>
        <button class="cal-fpill" data-tipo="examen" onclick="setFiltro(this,'examen')">📝 Exámenes</button>
        <button class="cal-fpill" data-tipo="feriado" onclick="setFiltro(this,'feriado')">🏖 Feriados</button>
        <button class="cal-fpill" data-tipo="actividad" onclick="setFiltro(this,'actividad')">🎉 Actividades</button>
        <button class="cal-fpill" data-tipo="reunion" onclick="setFiltro(this,'reunion')">🤝 Reuniones</button>
        <button class="cal-fpill" data-tipo="periodo" onclick="setFiltro(this,'periodo')">📅 Períodos</button>
        <button class="cal-fpill" data-tipo="suspension" onclick="setFiltro(this,'suspension')">🚫 Suspensiones</button>
    </div>

    {{-- Grilla del mes --}}
    <div id="calGrid" class="cal-grid"></div>

    {{-- Panel día seleccionado --}}
    <div id="dayPanel" class="cal-panel" style="display:none;">
        <div class="cal-panel-hdr">
            <span class="cal-panel-date" id="panelDate"></span>
            <button class="cal-panel-close" onclick="cerrarPanel()"><i class="bi bi-x-lg"></i></button>
        </div>
        <div id="panelList"></div>
    </div>

    {{-- Leyenda --}}
    <div class="cal-legend">
        <div class="cal-leg-item"><div class="cal-leg-dot" style="background:#dc2626;"></div>Examen</div>
        <div class="cal-leg-item"><div class="cal-leg-dot" style="background:#d97706;"></div>Feriado</div>
        <div class="cal-leg-item"><div class="cal-leg-dot" style="background:#7c3aed;"></div>Actividad</div>
        <div class="cal-leg-item"><div class="cal-leg-dot" style="background:#0d9488;"></div>Reunión</div>
        <div class="cal-leg-item"><div class="cal-leg-dot" style="background:#2563eb;"></div>Período</div>
        <div class="cal-leg-item"><div class="cal-leg-dot" style="background:#6b7280;"></div>Otro</div>
    </div>
</div>

<script>
const EVENTOS = @json($eventos);

let calYear  = {{ now()->year }};
let calMonth = {{ now()->month }};
let calFiltro  = 'all';
let calDiaSelec = null;

const TIPO_LABELS = {
    entrega_notas:   'Entrega de Notas',
    examen:          'Examen',
    suspension:      'Suspensión',
    inicio_periodo:  'Inicio de Período',
    fin_periodo:     'Fin de Período',
    actividad:       'Actividad',
    feriado:         'Feriado',
    reunion:         'Reunión',
    otro:            'Otro',
    evento_academico:'Evento Académico',
    evento_deportivo:'Evento Deportivo',
    evento_cultural: 'Evento Cultural',
    evento_social:   'Evento Social',
};

function tipoLabel(tipo) {
    return TIPO_LABELS[tipo] || tipo;
}

function eventMatchesFiltro(e) {
    if (calFiltro === 'all') return true;
    if (calFiltro === 'examen') return e.tipo === 'examen';
    if (calFiltro === 'feriado') return e.tipo === 'feriado';
    if (calFiltro === 'actividad') return e.tipo === 'actividad' || e.fuente === 'evento';
    if (calFiltro === 'reunion') return e.tipo === 'reunion';
    if (calFiltro === 'periodo') return e.tipo === 'inicio_periodo' || e.tipo === 'fin_periodo';
    if (calFiltro === 'suspension') return e.tipo === 'suspension';
    return false;
}

function getEventosParaFecha(dateStr) {
    return EVENTOS.filter(e => {
        if (!eventMatchesFiltro(e)) return false;
        if (e.fin && e.fin >= dateStr) return e.inicio <= dateStr;
        return e.inicio === dateStr;
    });
}

function renderCalendar() {
    const firstDay = new Date(calYear, calMonth - 1, 1);
    const monthName = firstDay.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
    document.getElementById('calTitle').textContent = monthName.charAt(0).toUpperCase() + monthName.slice(1);

    const dayNames = ['Lu','Ma','Mi','Ju','Vi','Sa','Do'];
    let html = dayNames.map(d => `<div class="cal-dh">${d}</div>`).join('');

    let startDow = firstDay.getDay();
    startDow = startDow === 0 ? 6 : startDow - 1;

    const daysInMonth = new Date(calYear, calMonth, 0).getDate();
    const today = new Date().toISOString().split('T')[0];

    for (let i = 0; i < startDow; i++) {
        html += '<div class="cal-cell cal-blank"></div>';
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const m   = String(calMonth).padStart(2, '0');
        const dd  = String(d).padStart(2, '0');
        const ds  = `${calYear}-${m}-${dd}`;
        const evs = getEventosParaFecha(ds);
        const isToday = ds === today;
        const isSel   = ds === calDiaSelec;
        const dow     = new Date(calYear, calMonth - 1, d).getDay();
        const isWknd  = dow === 0 || dow === 6;

        html += `<div class="cal-cell${isToday ? ' today' : ''}${isSel ? ' selected' : ''}" onclick="selectDay('${ds}',${d})">
            <span class="cal-num" style="${isWknd && !isToday ? 'color:#94a3b8;' : ''}">${d}</span>
            <div class="cal-dots">
                ${evs.slice(0, 3).map(e => `<span class="cdot" style="background:${e.color}"></span>`).join('')}
                ${evs.length > 3 ? `<span class="cdot-more">+${evs.length - 3}</span>` : ''}
            </div>
        </div>`;
    }

    document.getElementById('calGrid').innerHTML = html;
}

function selectDay(ds, d) {
    calDiaSelec = ds;
    renderCalendar();

    const evs   = getEventosParaFecha(ds);
    const label = new Date(calYear, calMonth - 1, d)
        .toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' });
    document.getElementById('panelDate').textContent = label.charAt(0).toUpperCase() + label.slice(1);

    if (evs.length === 0) {
        document.getElementById('panelList').innerHTML =
            '<div class="cal-empty"><i class="bi bi-calendar-x" style="font-size:1.5rem;display:block;margin-bottom:.4rem;"></i>Sin eventos este día</div>';
    } else {
        document.getElementById('panelList').innerHTML = evs.map(e => `
            <div class="cal-event-item">
                <div class="cal-event-dot" style="background:${e.color};"></div>
                <div>
                    <div class="cal-event-name">${e.titulo}</div>
                    ${e.desc ? `<div class="cal-event-desc">${e.desc}</div>` : ''}
                    <div class="cal-event-tipo">${tipoLabel(e.tipo)}</div>
                </div>
            </div>
        `).join('');
    }

    document.getElementById('dayPanel').style.display = 'block';
    document.getElementById('dayPanel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function cerrarPanel() {
    calDiaSelec = null;
    document.getElementById('dayPanel').style.display = 'none';
    renderCalendar();
}

function cambiarMes(delta) {
    calMonth += delta;
    if (calMonth > 12) { calMonth = 1; calYear++; }
    if (calMonth < 1)  { calMonth = 12; calYear--; }
    calDiaSelec = null;
    document.getElementById('dayPanel').style.display = 'none';
    renderCalendar();
}

function setFiltro(btn, tipo) {
    calFiltro = tipo;
    document.querySelectorAll('.cal-fpill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    calDiaSelec = null;
    document.getElementById('dayPanel').style.display = 'none';
    renderCalendar();
}

renderCalendar();
</script>
