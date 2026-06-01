<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>ZuraEdu — Encuesta de Interés</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --navy:#0A2463; --blue:#1565C0; --teal:#0097A7; --teal-l:#E0F7FA; --teal-d:#00838F;
    --green:#2E7D32; --green-l:#E8F5E9; --green-d:#1B5E20; --gold:#FFB300;
    --gray:#546E7A; --lgray:#ECEFF1; --border:#CFD8DC; --white:#FFFFFF;
    --text:#1A2B3C; --muted:#607D8B; --bg:#F5F7FA;
  }
  body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; display:flex; flex-direction:column; align-items:center; padding:0 16px 60px; }
  .top-bar { width:100%; background:var(--navy); padding:14px 24px; display:flex; align-items:center; gap:10px; margin-bottom:32px; }
  .top-bar .logo { font-size:20px; font-weight:600; color:#fff; letter-spacing:-.3px; }
  .top-bar .logo span { color:var(--gold); }
  .card { background:var(--white); border-radius:16px; box-shadow:0 2px 16px rgba(10,36,99,.08); width:100%; max-width:620px; overflow:hidden; }
  .tabs { display:flex; border-bottom:1.5px solid var(--border); }
  .tab { flex:1; padding:16px 12px; font-size:14px; font-weight:500; color:var(--muted); cursor:pointer; border:none; background:transparent; display:flex; flex-direction:column; align-items:center; gap:5px; border-bottom:3px solid transparent; transition:all .2s; font-family:'DM Sans',sans-serif; }
  .tab svg { width:22px; height:22px; stroke:currentColor; fill:none; stroke-width:1.8; }
  .tab.active.doc { color:var(--teal); border-bottom-color:var(--teal); }
  .tab.active.adm { color:var(--green); border-bottom-color:var(--green); }
  .form-body { padding:28px 28px 24px; }
  .badge { display:inline-flex; align-items:center; gap:6px; font-size:11px; font-weight:600; letter-spacing:.5px; text-transform:uppercase; padding:4px 10px; border-radius:20px; margin-bottom:10px; }
  .badge.doc { background:var(--teal-l); color:var(--teal-d); }
  .badge.adm { background:var(--green-l); color:var(--green-d); }
  .form-title { font-size:20px; font-weight:600; color:var(--navy); margin-bottom:6px; line-height:1.35; }
  .form-sub   { font-size:13px; color:var(--muted); margin-bottom:20px; }
  .progress-wrap { height:4px; background:var(--lgray); border-radius:2px; margin-bottom:24px; }
  .progress-fill { height:100%; border-radius:2px; transition:width .4s ease; }
  .fill-doc { background:var(--teal); } .fill-adm { background:var(--green); }
  .step { display:none; } .step.active { display:block; }
  .q-label { font-size:15px; font-weight:600; color:var(--navy); margin-bottom:4px; }
  .q-sub   { font-size:12px; color:var(--muted); margin-bottom:16px; }
  .chips { display:flex; flex-wrap:wrap; gap:8px; }
  .chip { padding:7px 15px; border:1.5px solid var(--border); border-radius:24px; font-size:13px; font-family:'DM Sans',sans-serif; cursor:pointer; color:var(--text); background:var(--white); transition:all .15s; }
  .chip:hover { border-color:var(--teal); }
  .chip.sel-doc { border-color:var(--teal); background:var(--teal-l); color:var(--teal-d); font-weight:500; }
  .chip.sel-adm { border-color:var(--green); background:var(--green-l); color:var(--green-d); font-weight:500; }
  .opts { display:flex; flex-direction:column; gap:8px; }
  .opt { display:flex; align-items:center; gap:12px; padding:12px 14px; border:1.5px solid var(--border); border-radius:10px; cursor:pointer; transition:all .15s; background:var(--white); }
  .opt:hover { border-color:#90A4AE; background:#FAFBFC; }
  .opt.sel-doc { border-color:var(--teal); background:var(--teal-l); }
  .opt.sel-adm { border-color:var(--green); background:var(--green-l); }
  .opt input[type=radio] { accent-color:var(--teal); width:16px; height:16px; flex-shrink:0; }
  .opt-txt { font-size:14px; color:var(--text); }
  .opt.sel-doc .opt-txt { color:var(--teal-d); font-weight:500; }
  .opt.sel-adm .opt-txt { color:var(--green-d); font-weight:500; }
  .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
  .likert { display:flex; gap:8px; }
  .lk { flex:1; border:1.5px solid var(--border); border-radius:10px; padding:10px 4px; text-align:center; cursor:pointer; transition:all .15s; background:var(--white); }
  .lk:hover { background:var(--lgray); }
  .lk.sel-doc { border-color:var(--teal); background:var(--teal-l); }
  .lk.sel-adm { border-color:var(--green); background:var(--green-l); }
  .lk .n { font-size:18px; font-weight:600; color:var(--navy); }
  .lk.sel-doc .n { color:var(--teal-d); } .lk.sel-adm .n { color:var(--green-d); }
  .lk .l { font-size:10px; color:var(--muted); margin-top:2px; }
  .lk.sel-doc .l, .lk.sel-adm .l { font-weight:500; }
  .likert-hints { display:flex; justify-content:space-between; margin-top:6px; font-size:11px; color:var(--muted); }
  textarea { width:100%; padding:12px 14px; border:1.5px solid var(--border); border-radius:10px; font-size:13px; font-family:'DM Sans',sans-serif; color:var(--text); background:var(--white); resize:vertical; min-height:100px; transition:border-color .15s; }
  textarea:focus { outline:none; border-color:var(--teal); }
  .nav { display:flex; justify-content:space-between; align-items:center; margin-top:24px; padding-top:20px; border-top:1px solid var(--lgray); }
  .btn { padding:10px 22px; border-radius:8px; font-size:14px; font-weight:500; font-family:'DM Sans',sans-serif; cursor:pointer; border:1.5px solid var(--border); background:var(--white); color:var(--text); transition:all .15s; }
  .btn:hover { background:var(--lgray); } .btn:disabled { opacity:.35; cursor:not-allowed; }
  .btn.pdoc { background:var(--teal); color:#fff; border-color:var(--teal); } .btn.pdoc:hover { background:var(--teal-d); }
  .btn.padm { background:var(--green); color:#fff; border-color:var(--green); } .btn.padm:hover { background:var(--green-d); }
  .step-count { font-size:12px; color:var(--muted); font-weight:500; }
  .done-wrap { padding:32px 28px; text-align:center; }
  .done-icon { font-size:48px; margin-bottom:12px; }
  .done-icon.doc { color:var(--teal); } .done-icon.adm { color:var(--green); }
  .done-title { font-size:20px; font-weight:600; color:var(--navy); margin-bottom:8px; }
  .done-text  { font-size:14px; color:var(--muted); line-height:1.65; margin-bottom:20px; }
  .stat-row   { display:flex; gap:10px; margin-top:20px; }
  .stat { flex:1; background:var(--lgray); border-radius:10px; padding:14px 8px; text-align:center; }
  .stat .sn { font-size:24px; font-weight:600; }
  .stat.doc .sn { color:var(--teal); } .stat.adm .sn { color:var(--green); }
  .stat .sl { font-size:11px; color:var(--muted); margin-top:3px; }
  .done-cta { display:inline-block; margin-top:20px; padding:12px 28px; border-radius:8px; font-size:14px; font-weight:600; font-family:'DM Sans',sans-serif; text-decoration:none; cursor:pointer; border:none; transition:opacity .15s; }
  .done-cta:hover { opacity:.88; }
  .done-cta.doc { background:var(--teal); color:#fff; } .done-cta.adm { background:var(--green); color:#fff; }
  .contact-box { background:var(--lgray); border-radius:12px; padding:20px; margin-top:20px; text-align:left; }
  .contact-title { font-size:15px; font-weight:600; color:var(--navy); margin-bottom:14px; text-align:center; }
  .contact-row { display:flex; gap:10px; margin-bottom:10px; }
  .contact-field { flex:1; display:flex; flex-direction:column; gap:5px; margin-bottom:10px; }
  .contact-field label { font-size:12px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:.4px; }
  .contact-field input { padding:10px 12px; border:1.5px solid var(--border); border-radius:8px; font-size:14px; font-family:'DM Sans',sans-serif; color:var(--text); background:var(--white); transition:border-color .15s; width:100%; }
  .contact-field input:focus { outline:none; border-color:var(--teal); }
  .contact-info { display:flex; flex-direction:column; gap:3px; background:var(--white); border:1.5px solid var(--border); border-radius:8px; padding:12px 14px; margin-bottom:14px; }
  .contact-info-label { font-size:11px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:.4px; margin-bottom:4px; }
  .contact-info-val { font-size:14px; font-weight:500; color:var(--navy); }
  .contact-info-val.tel { color:var(--teal); font-size:15px; }
  .done-cta { width:100%; text-align:center; }
  .hidden { display:none !important; }
  .panel  { display:none; } .panel.active { display:block; }
  @media(max-width:640px){ .help-topbar{padding:.75rem 1rem;flex-wrap:wrap;} .grid2{grid-template-columns:1fr;} }
</style>
</head>
<body>

<div class="top-bar">
  <svg width="28" height="28" fill="none" stroke="#FFB300" stroke-width="2" viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5-10-5z"/><path d="M6 12v5c3.33 1.67 8.67 1.67 12 0v-5"/></svg>
  <div class="logo">Zura<span>Edu</span></div>
</div>

<div class="card">
  <div class="tabs">
    <button class="tab active doc" id="tab-doc" onclick="switchTab('doc')">
      <svg viewBox="0 0 24 24"><path d="M8 21h8M12 17v4M17 9l-5-5-5 5M7 13l5-5 5 5"/></svg>
      Docentes
    </button>
    <button class="tab adm" id="tab-adm" onclick="switchTab('adm')">
      <svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
      Personal administrativo
    </button>
  </div>

  {{-- ═══ PANEL DOCENTES ═══ --}}
  <div class="panel active" id="panel-doc">
    <div id="doc-form">
      <div class="form-body">
        <div class="badge doc">Encuesta para docentes</div>
        <div class="form-title">¿Le gustaría usar ZuraEdu en su trabajo diario?</div>
        <div class="form-sub">6 preguntas sobre su experiencia y necesidades en el aula.</div>
        <div class="progress-wrap"><div class="progress-fill fill-doc" id="pd" style="width:16%"></div></div>
        <div class="step active" id="d1">
          <p class="q-label">¿Qué nivel o área enseña?</p>
          <p class="q-sub">Seleccione la que mejor lo describe (puede elegir varias).</p>
          <div class="chips" id="d-nivel">
            <button class="chip" onclick="chip(this,'d-nivel','doc')">Inicial / Preprimaria</button>
            <button class="chip" onclick="chip(this,'d-nivel','doc')">Primaria 1.er ciclo</button>
            <button class="chip" onclick="chip(this,'d-nivel','doc')">Primaria 2.do ciclo</button>
            <button class="chip" onclick="chip(this,'d-nivel','doc')">Secundaria / Liceo</button>
            <button class="chip" onclick="chip(this,'d-nivel','doc')">Técnico / Politécnico</button>
            <button class="chip" onclick="chip(this,'d-nivel','doc')">Varias materias</button>
          </div>
        </div>
        <div class="step" id="d2">
          <p class="q-label">¿Cuánto tiempo le toma registrar notas y asistencia cada semana?</p>
          <p class="q-sub">Seleccione una opción.</p>
          <div class="opts">
            <div class="opt" onclick="radio(this,'d-tiempo','doc')"><input type="radio" name="d-tiempo"><span class="opt-txt">Menos de 1 hora</span></div>
            <div class="opt" onclick="radio(this,'d-tiempo','doc')"><input type="radio" name="d-tiempo"><span class="opt-txt">Entre 1 y 3 horas</span></div>
            <div class="opt" onclick="radio(this,'d-tiempo','doc')"><input type="radio" name="d-tiempo"><span class="opt-txt">Entre 3 y 5 horas</span></div>
            <div class="opt" onclick="radio(this,'d-tiempo','doc')"><input type="radio" name="d-tiempo"><span class="opt-txt">Más de 5 horas</span></div>
          </div>
        </div>
        <div class="step" id="d3">
          <p class="q-label">¿Qué partes de su trabajo le gustaría automatizar?</p>
          <p class="q-sub">Puede seleccionar varias.</p>
          <div class="chips grid2" id="d-auto">
            <button class="chip" onclick="chip(this,'d-auto','doc')">Registro de notas</button>
            <button class="chip" onclick="chip(this,'d-auto','doc')">Lista de asistencia</button>
            <button class="chip" onclick="chip(this,'d-auto','doc')">Generación de boletines</button>
            <button class="chip" onclick="chip(this,'d-auto','doc')">Planificación de clases</button>
            <button class="chip" onclick="chip(this,'d-auto','doc')">Mensajes a padres</button>
            <button class="chip" onclick="chip(this,'d-auto','doc')">Evaluaciones online</button>
            <button class="chip" onclick="chip(this,'d-auto','doc')">Informes de progreso</button>
            <button class="chip" onclick="chip(this,'d-auto','doc')">Calendario académico</button>
          </div>
        </div>
        <div class="step" id="d4">
          <p class="q-label">Si pudiera registrar notas y pasar lista desde su celular, ¿con qué frecuencia lo usaría?</p>
          <p class="q-sub">Seleccione una opción.</p>
          <div class="opts">
            <div class="opt" onclick="radio(this,'d-frec','doc')"><input type="radio" name="d-frec"><span class="opt-txt">Todos los días</span></div>
            <div class="opt" onclick="radio(this,'d-frec','doc')"><input type="radio" name="d-frec"><span class="opt-txt">Varias veces por semana</span></div>
            <div class="opt" onclick="radio(this,'d-frec','doc')"><input type="radio" name="d-frec"><span class="opt-txt">Solo en momentos clave</span></div>
            <div class="opt" onclick="radio(this,'d-frec','doc')"><input type="radio" name="d-frec"><span class="opt-txt">Preferiría solo la versión web</span></div>
          </div>
        </div>
        <div class="step" id="d5">
          <p class="q-label">¿Cuánto le interesaría contar con ZuraEdu en su centro?</p>
          <p class="q-sub">1 = poco interés · 5 = me interesa mucho</p>
          <div class="likert" id="d-interes">
            <div class="lk" onclick="likert('d-interes',this,1,'doc')"><div class="n">1</div><div class="l">Poco</div></div>
            <div class="lk" onclick="likert('d-interes',this,2,'doc')"><div class="n">2</div><div class="l"></div></div>
            <div class="lk" onclick="likert('d-interes',this,3,'doc')"><div class="n">3</div><div class="l">Neutral</div></div>
            <div class="lk" onclick="likert('d-interes',this,4,'doc')"><div class="n">4</div><div class="l"></div></div>
            <div class="lk" onclick="likert('d-interes',this,5,'doc')"><div class="n">5</div><div class="l">Mucho</div></div>
          </div>
          <div class="likert-hints"><span>Poco interés</span><span>Me interesa mucho</span></div>
        </div>
        <div class="step" id="d6">
          <p class="q-label">¿Tiene algún comentario o sugerencia?</p>
          <p class="q-sub">Opcional — su opinión nos ayuda a mejorar.</p>
          <textarea id="d-comment" placeholder="Escriba aquí sus comentarios..."></textarea>
        </div>
        <div class="nav" id="d-nav">
          <button class="btn" id="d-back" onclick="go('doc',-1)">← Atrás</button>
          <span class="step-count" id="d-sc">Paso 1 de 6</span>
          <button class="btn pdoc" id="d-next" onclick="go('doc',1)" disabled>Siguiente →</button>
        </div>
      </div>
    </div>
    <div class="done-wrap hidden" id="doc-done">
      <div class="done-icon doc">✓</div>
      <div class="done-title">¡Gracias, docente!</div>
      <div class="done-text">Sus respuestas nos ayudan a entender mejor las necesidades del aula. Complete sus datos y nos pondremos en contacto con usted.</div>
      <div class="stat-row" id="d-stats"></div>
      <div class="contact-box">
        <p class="contact-title">Solicitar demostración gratuita</p>
        <div class="contact-row">
          <div class="contact-field"><label>Nombre</label><input type="text" id="d-nombre" placeholder="Su nombre"></div>
          <div class="contact-field"><label>Apellido</label><input type="text" id="d-apellido" placeholder="Su apellido"></div>
        </div>
        <div class="contact-field"><label>Teléfono / WhatsApp</label><input type="tel" id="d-tel" placeholder="809-000-0000"></div>
        <div class="contact-info">
          <span class="contact-info-label">Lo atenderá directamente</span>
          <span class="contact-info-val">Ing. Adaury Abel Paulino Guaba</span>
          <span class="contact-info-val tel">📞 829-477-8613</span>
        </div>
        <button class="done-cta doc" id="d-cta" onclick="enviar('doc')">Enviar solicitud →</button>
        <p id="d-msg" style="display:none;margin-top:14px;font-size:13px;color:var(--muted);line-height:1.6;"></p>
      </div>
    </div>
  </div>

  {{-- ═══ PANEL ADMINISTRATIVO ═══ --}}
  <div class="panel" id="panel-adm">
    <div id="adm-form">
      <div class="form-body">
        <div class="badge adm">Encuesta para personal administrativo</div>
        <div class="form-title">¿Le gustaría gestionar su centro con ZuraEdu?</div>
        <div class="form-sub">6 preguntas sobre los procesos administrativos de su institución.</div>
        <div class="progress-wrap"><div class="progress-fill fill-adm" id="pa" style="width:16%"></div></div>
        <div class="step active" id="a1">
          <p class="q-label">¿Cuál es su función principal en el centro?</p>
          <p class="q-sub">Seleccione la que más se acerque.</p>
          <div class="opts">
            <div class="opt" onclick="radio(this,'a-rol','adm')"><input type="radio" name="a-rol"><span class="opt-txt">Secretaría / recepción</span></div>
            <div class="opt" onclick="radio(this,'a-rol','adm')"><input type="radio" name="a-rol"><span class="opt-txt">Contabilidad / finanzas</span></div>
            <div class="opt" onclick="radio(this,'a-rol','adm')"><input type="radio" name="a-rol"><span class="opt-txt">Coordinación académica</span></div>
            <div class="opt" onclick="radio(this,'a-rol','adm')"><input type="radio" name="a-rol"><span class="opt-txt">Recursos humanos / nómina</span></div>
            <div class="opt" onclick="radio(this,'a-rol','adm')"><input type="radio" name="a-rol"><span class="opt-txt">Dirección / subdirección</span></div>
          </div>
        </div>
        <div class="step" id="a2">
          <p class="q-label">¿Qué procesos le generan más carga de trabajo?</p>
          <p class="q-sub">Puede seleccionar varios.</p>
          <div class="chips grid2" id="a-carga">
            <button class="chip" onclick="chip(this,'a-carga','adm')">Matrícula y prematrículas</button>
            <button class="chip" onclick="chip(this,'a-carga','adm')">Cobros y seguimiento de pagos</button>
            <button class="chip" onclick="chip(this,'a-carga','adm')">Gestión de documentos</button>
            <button class="chip" onclick="chip(this,'a-carga','adm')">Comunicación con familias</button>
            <button class="chip" onclick="chip(this,'a-carga','adm')">Reportes para el MINERD</button>
            <button class="chip" onclick="chip(this,'a-carga','adm')">Nómina del personal</button>
            <button class="chip" onclick="chip(this,'a-carga','adm')">Control de inventario</button>
            <button class="chip" onclick="chip(this,'a-carga','adm')">Horarios y asignaciones</button>
          </div>
        </div>
        <div class="step" id="a3">
          <p class="q-label">¿Cómo maneja hoy el cobro de mensualidades y deudas?</p>
          <p class="q-sub">Seleccione la opción más cercana.</p>
          <div class="opts">
            <div class="opt" onclick="radio(this,'a-cobro','adm')"><input type="radio" name="a-cobro"><span class="opt-txt">Registro manual en papel</span></div>
            <div class="opt" onclick="radio(this,'a-cobro','adm')"><input type="radio" name="a-cobro"><span class="opt-txt">Hojas de Excel o Google Sheets</span></div>
            <div class="opt" onclick="radio(this,'a-cobro','adm')"><input type="radio" name="a-cobro"><span class="opt-txt">Un sistema de facturación externo</span></div>
            <div class="opt" onclick="radio(this,'a-cobro','adm')"><input type="radio" name="a-cobro"><span class="opt-txt">No llevamos registro sistemático</span></div>
          </div>
        </div>
        <div class="step" id="a4">
          <p class="q-label">¿Qué módulos administrativos le serían más útiles?</p>
          <p class="q-sub">Seleccione hasta 3 opciones.</p>
          <div class="chips grid2" id="a-mods">
            <button class="chip" onclick="chipMax(this,'a-mods','adm',3)">Control de pagos y deudores</button>
            <button class="chip" onclick="chipMax(this,'a-mods','adm',3)">Matrícula digital</button>
            <button class="chip" onclick="chipMax(this,'a-mods','adm',3)">Nómina del personal</button>
            <button class="chip" onclick="chipMax(this,'a-mods','adm',3)">Reportes ejecutivos</button>
            <button class="chip" onclick="chipMax(this,'a-mods','adm',3)">Inventario y equipos</button>
            <button class="chip" onclick="chipMax(this,'a-mods','adm',3)">Comunicados masivos</button>
            <button class="chip" onclick="chipMax(this,'a-mods','adm',3)">Exportación al MINERD</button>
            <button class="chip" onclick="chipMax(this,'a-mods','adm',3)">Carnet digital de acceso</button>
          </div>
        </div>
        <div class="step" id="a5">
          <p class="q-label">¿Cuánto le interesaría implementar ZuraEdu en su centro?</p>
          <p class="q-sub">1 = poco interés · 5 = me interesa mucho</p>
          <div class="likert" id="a-interes">
            <div class="lk" onclick="likert('a-interes',this,1,'adm')"><div class="n">1</div><div class="l">Poco</div></div>
            <div class="lk" onclick="likert('a-interes',this,2,'adm')"><div class="n">2</div><div class="l"></div></div>
            <div class="lk" onclick="likert('a-interes',this,3,'adm')"><div class="n">3</div><div class="l">Neutral</div></div>
            <div class="lk" onclick="likert('a-interes',this,4,'adm')"><div class="n">4</div><div class="l"></div></div>
            <div class="lk" onclick="likert('a-interes',this,5,'adm')"><div class="n">5</div><div class="l">Mucho</div></div>
          </div>
          <div class="likert-hints"><span>Poco interés</span><span>Me interesa mucho</span></div>
        </div>
        <div class="step" id="a6">
          <p class="q-label">¿Hay algo que quisiera que ZuraEdu resolviera en su centro?</p>
          <p class="q-sub">Opcional — cuéntenos con sus palabras.</p>
          <textarea id="a-comment" placeholder="Escriba aquí..."></textarea>
        </div>
        <div class="nav" id="a-nav">
          <button class="btn" id="a-back" onclick="go('adm',-1)">← Atrás</button>
          <span class="step-count" id="a-sc">Paso 1 de 6</span>
          <button class="btn padm" id="a-next" onclick="go('adm',1)" disabled>Siguiente →</button>
        </div>
      </div>
    </div>
    <div class="done-wrap hidden" id="adm-done">
      <div class="done-icon adm">✓</div>
      <div class="done-title">¡Gracias por su tiempo!</div>
      <div class="done-text">Sus respuestas nos ayudan a entender cómo ZuraEdu puede simplificar la gestión de su centro. Complete sus datos y nos pondremos en contacto.</div>
      <div class="stat-row" id="a-stats"></div>
      <div class="contact-box">
        <p class="contact-title">Solicitar demostración gratuita</p>
        <div class="contact-row">
          <div class="contact-field"><label>Nombre</label><input type="text" id="a-nombre" placeholder="Su nombre"></div>
          <div class="contact-field"><label>Apellido</label><input type="text" id="a-apellido" placeholder="Su apellido"></div>
        </div>
        <div class="contact-field"><label>Teléfono / WhatsApp</label><input type="tel" id="a-tel" placeholder="809-000-0000"></div>
        <div class="contact-info">
          <span class="contact-info-label">Lo atenderá directamente</span>
          <span class="contact-info-val">Ing. Adaury Abel Paulino Guaba</span>
          <span class="contact-info-val tel">📞 829-477-8613</span>
        </div>
        <button class="done-cta adm" id="a-cta" onclick="enviar('adm')">Enviar solicitud →</button>
        <p id="a-msg" style="display:none;margin-top:14px;font-size:13px;color:var(--muted);line-height:1.6;"></p>
      </div>
    </div>
  </div>
</div>

<script>
const ST = { doc:{cur:1,total:6,ans:{}}, adm:{cur:1,total:6,ans:{}} };
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

function switchTab(role){
  ['doc','adm'].forEach(r=>{
    document.getElementById('tab-'+r).classList.toggle('active',r===role);
    document.getElementById('panel-'+r).classList.toggle('active',r===role);
  });
}
function p(role){ return role==='doc'?'d':'a'; }

function updateNav(role){
  const s=ST[role], px=p(role);
  document.getElementById(px+'d').style.width=(s.cur/s.total*100)+'%';
  const back=document.getElementById(px+'-back');
  const next=document.getElementById(px+'-next');
  const sc=document.getElementById(px+'-sc');
  back.style.visibility=s.cur>1?'visible':'hidden';
  sc.textContent='Paso '+s.cur+' de '+s.total;
  next.textContent=s.cur===s.total?'Enviar ✓':'Siguiente →';
  checkValid(role);
}

function checkValid(role){
  const s=ST[role], a=s.ans, c=s.cur;
  const next=document.getElementById(p(role)+'-next');
  let ok=false;
  if(role==='doc'){
    if(c===1) ok=(a['d-nivel']||[]).length>0;
    else if(c===2) ok=!!a['d-tiempo'];
    else if(c===3) ok=(a['d-auto']||[]).length>0;
    else if(c===4) ok=!!a['d-frec'];
    else if(c===5) ok=!!a['d-interes'];
    else if(c===6) ok=true;
  } else {
    if(c===1) ok=!!a['a-rol'];
    else if(c===2) ok=(a['a-carga']||[]).length>0;
    else if(c===3) ok=!!a['a-cobro'];
    else if(c===4) ok=(a['a-mods']||[]).length>0;
    else if(c===5) ok=!!a['a-interes'];
    else if(c===6) ok=true;
  }
  next.disabled=!ok;
}

function showStep(role,n){
  const px=p(role);
  document.querySelectorAll('#panel-'+role+' .step').forEach(s=>s.classList.remove('active'));
  const el=document.getElementById(px+n);
  if(el) el.classList.add('active');
}

function go(role,dir){
  const s=ST[role], px=p(role);
  if(dir===1 && s.cur===s.total){
    s.ans[px+'-comment']=document.getElementById(px+'-comment').value;
    document.getElementById(role+'-form').classList.add('hidden');
    document.getElementById(role+'-done').classList.remove('hidden');
    buildStats(role);
    return;
  }
  s.cur=Math.max(1,Math.min(s.total,s.cur+dir));
  showStep(role,s.cur);
  updateNav(role);
}

function chip(el,group,role){
  const sel='sel-'+role;
  el.classList.toggle(sel);
  ST[role].ans[group]=Array.from(el.closest('.chips').querySelectorAll('.'+sel)).map(c=>c.textContent.trim());
  checkValid(role);
}

function chipMax(el,group,role,max){
  const sel='sel-'+role;
  const container=el.closest('.chips');
  if(!el.classList.contains(sel)){
    if(container.querySelectorAll('.'+sel).length>=max) return;
    el.classList.add(sel);
  } else { el.classList.remove(sel); }
  ST[role].ans[group]=Array.from(container.querySelectorAll('.'+sel)).map(c=>c.textContent.trim());
  checkValid(role);
}

function radio(el,group,role){
  const sel='sel-'+role;
  el.closest('.opts').querySelectorAll('.opt').forEach(o=>{o.classList.remove(sel);o.querySelector('input').checked=false;});
  el.classList.add(sel);
  el.querySelector('input').checked=true;
  ST[role].ans[group]=el.querySelector('.opt-txt').textContent;
  checkValid(role);
}

function likert(group,el,val,role){
  const sel='sel-'+role;
  el.closest('.likert').querySelectorAll('.lk').forEach(o=>o.classList.remove(sel));
  el.classList.add(sel);
  ST[role].ans[group]=val;
  checkValid(role);
}

function buildStats(role){
  const px=p(role), a=ST[role].ans, cls=role;
  const interes=a[px+'-interes']||0;
  const items=role==='doc'?(a['d-auto']||[]).length:(a['a-carga']||[]).length;
  const lbl=role==='doc'?'Tareas a automatizar':'Procesos a mejorar';
  document.getElementById(px+'-stats').innerHTML=
    '<div class="stat '+cls+'"><div class="sn">'+interes+'/5</div><div class="sl">Nivel de interés</div></div>'+
    '<div class="stat '+cls+'"><div class="sn">'+items+'</div><div class="sl">'+lbl+'</div></div>';
}

function enviar(role){
  const px=role==='doc'?'d':'a';
  const nombre=(document.getElementById(px+'-nombre')?.value||'').trim();
  const apellido=(document.getElementById(px+'-apellido')?.value||'').trim();
  const tel=(document.getElementById(px+'-tel')?.value||'').trim();
  if(!nombre||!apellido||!tel){ alert('Por favor complete su nombre, apellido y teléfono.'); return; }

  const btn=document.getElementById(px+'-cta');
  btn.disabled=true;
  btn.textContent='Enviando...';

  const s=ST[role];
  const interes=s.ans[px+'-interes']||0;

  fetch('{{ route("encuesta.store") }}', {
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
    body: JSON.stringify({
      tipo: role==='doc'?'docente':'administrativo',
      nombre, apellido, telefono:tel,
      nivel_interes: interes,
      respuestas: s.ans,
    })
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.ok){
      btn.textContent='¡Solicitud enviada! ✓';
      btn.style.opacity='0.7';
      const msg=document.getElementById(px+'-msg');
      msg.style.display='block';
      msg.innerHTML='<strong>'+nombre+' '+apellido+'</strong>, el Ing. Adaury Abel Paulino Guaba se comunicará con usted al número <strong>'+tel+'</strong> a la brevedad posible.';
    } else {
      btn.disabled=false;
      btn.textContent='Enviar solicitud →';
      alert('Hubo un error. Intente nuevamente.');
    }
  })
  .catch(()=>{ btn.disabled=false; btn.textContent='Enviar solicitud →'; alert('Error de conexión. Intente nuevamente.'); });
}

updateNav('doc');
updateNav('adm');
</script>
</body>
</html>
