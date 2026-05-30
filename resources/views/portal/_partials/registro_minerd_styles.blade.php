<style>
/* ── Info bar ── */
.minerd-info-bar {
    display:flex; flex-wrap:wrap; gap:.75rem 1.5rem; align-items:center;
    background:linear-gradient(135deg,#1e3a6e,#2d5aa0);
    color:#fff; border-radius:14px; padding:1rem 1.5rem; margin-bottom:1rem;
}
.mib-item { display:flex; flex-direction:column; gap:.1rem; }
.mib-label { font-size:.6rem; opacity:.65; font-weight:700; text-transform:uppercase; letter-spacing:.07em; }
.mib-val   { font-weight:700; font-size:.88rem; }
.mib-sep   { width:1px; height:32px; background:rgba(255,255,255,.2); flex-shrink:0; }

/* ── Leyenda ── */
.leyenda-minerd {
    display:flex; flex-wrap:wrap; gap:.4rem; align-items:center;
    background:#f8fafc; border:1px solid #e5e7eb; border-radius:10px;
    padding:.5rem .875rem; margin-bottom:1rem; font-size:.72rem;
}
.ley-tit   { font-weight:700; color:#374151; margin-right:.2rem; }
.ley-chip  { padding:.15rem .55rem; border-radius:20px; font-weight:700; font-size:.7rem; }

/* ── Acordeón ── */
.minerd-accordion { display:flex; flex-direction:column; gap:.6rem; }
.mrd-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; overflow:hidden;
    box-shadow:0 1px 6px rgba(0,0,0,.05); }
.mrd-card-header {
    width:100%; display:flex; align-items:center; justify-content:space-between;
    gap:.75rem; padding:.875rem 1.25rem; background:#fff;
    border:none; cursor:pointer; text-align:left;
    transition:background .12s;
}
.mrd-card-header:hover { background:#f8faff; }
.mrd-card-header.open  { background:#eff6ff; border-bottom:1px solid #e0e7ff; }
.mrd-mat-nombre { font-weight:800; font-size:.92rem; color:#111827; }
.mrd-docente    { font-size:.72rem; color:#6b7280; font-weight:500; }
.mrd-prom-chip  {
    padding:.2rem .65rem; border-radius:20px; font-weight:800; font-size:.8rem;
    display:inline-flex; align-items:center; gap:.25rem;
}
.mrd-chevron {
    font-size:.85rem; color:#6b7280; transition:transform .2s;
    flex-shrink:0;
}
.mrd-card-header.open .mrd-chevron { transform:rotate(180deg); }

.mrd-card-body { overflow:hidden; transition:max-height .25s ease; }
.mrd-card-body.collapsed { display:none; }

/* ── Tabla CE/IL ── */
.mrd-tbl-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; padding:.75rem 1.25rem 0; }
.mrd-tbl { border-collapse:collapse; width:100%; font-size:.78rem; }
.mrd-tbl td, .mrd-tbl th { border:1px solid #e2e8f0; padding:.3rem .5rem; }

.mrd-th-ce   { background:#1e3a6e; color:#fff; font-size:.72rem; font-weight:700;
    text-align:left; padding:.4rem .75rem; }
.mrd-th-per  { background:#e8edf8; color:#1e3a6e; font-size:.68rem; font-weight:700;
    text-align:center; min-width:46px; }
.mrd-th-prom { background:#f0fdf4; color:#15803d; font-size:.68rem; font-weight:700;
    text-align:center; min-width:50px; }

/* Fila de cabecera de CE */
.mrd-tr-ce .mrd-td-ce-header {
    background:#2d5aa0; color:#fff; font-size:.73rem; font-weight:700;
    padding:.3rem .75rem; letter-spacing:.02em;
}

/* Fila IL */
.mrd-tr-il .mrd-td-il-name {
    padding:.35rem .75rem; color:#374151; font-size:.76rem;
    display:flex; align-items:flex-start; gap:.4rem; min-width:160px;
}
.mrd-tr-alt { background:#f8fafc; }
.mrd-il-badge {
    display:inline-block; background:#e8edf8; color:#1e3a6e;
    border-radius:4px; padding:.1rem .38rem; font-size:.64rem; font-weight:700;
    white-space:nowrap; flex-shrink:0;
}
.mrd-il-texto { color:#374151; line-height:1.35; }

/* Celda valor */
.mrd-td-val  { text-align:center; font-weight:700; font-size:.8rem; }
.mrd-td-prom { text-align:center; font-weight:700; font-size:.78rem; }

/* Fila prom CE */
.mrd-tr-prom-ce .mrd-td-prom-ce-label {
    background:#f0fdf4; color:#15803d; font-size:.7rem; font-weight:700;
    padding:.3rem .75rem; text-transform:uppercase; letter-spacing:.04em;
}

/* Pie de materia */
.mrd-mat-footer {
    display:flex; flex-wrap:wrap; align-items:center; gap:.6rem;
    padding:.75rem 1.25rem; border-top:1px solid #f3f4f6; margin-top:.25rem;
    background:#fafafa;
}
.mrd-prom-final {
    font-weight:800; font-size:.9rem; padding:.2rem .65rem; border-radius:8px;
}
.mrd-sit-badge {
    display:inline-flex; align-items:center; font-size:.75rem; font-weight:700;
    padding:.2rem .65rem; border-radius:20px;
}
.mrd-sit-aprobado  { background:#dcfce7; color:#15803d; }
.mrd-sit-reprobado { background:#fee2e2; color:#991b1b; }

/* ── Resumen general ── */
.mrd-resumen {
    display:flex; flex-wrap:wrap; gap:.75rem; margin-top:1.25rem;
    padding:1rem 1.25rem; background:#fff; border:1px solid #e5e7eb;
    border-radius:14px; box-shadow:0 1px 6px rgba(0,0,0,.04);
}
.mrd-res-item  { display:flex; flex-direction:column; align-items:center; gap:.25rem; }
.mrd-res-label { font-size:.68rem; color:#6b7280; font-weight:700; text-transform:uppercase; letter-spacing:.05em; }
.mrd-res-val   {
    font-weight:900; font-size:1.25rem; padding:.25rem .75rem;
    border-radius:10px; min-width:50px; text-align:center; line-height:1.2;
}

/* Dark mode */
[data-theme="dark"] .mrd-card { background:#1e293b; border-color:#334155; }
[data-theme="dark"] .mrd-card-header { background:#1e293b; }
[data-theme="dark"] .mrd-card-header:hover { background:#0f172a; }
[data-theme="dark"] .mrd-card-header.open { background:#0c1f3f; border-color:#334155; }
[data-theme="dark"] .mrd-mat-nombre { color:#e2e8f0; }
[data-theme="dark"] .mrd-tbl td, [data-theme="dark"] .mrd-tbl th { border-color:#334155; }
[data-theme="dark"] .mrd-tr-alt { background:#162032; }
[data-theme="dark"] .mrd-mat-footer { background:#162032; border-color:#334155; }
[data-theme="dark"] .mrd-resumen { background:#1e293b; border-color:#334155; }
[data-theme="dark"] .leyenda-minerd { background:#1e293b; border-color:#334155; }
[data-theme="dark"] .mrd-il-texto { color:#94a3b8; }
</style>
