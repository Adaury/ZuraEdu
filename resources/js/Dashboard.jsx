import React, { useEffect, useRef, useMemo } from 'react'
import { motion } from 'framer-motion'
import ReactApexChart from 'react-apexcharts'
import {
  Users, GraduationCap, BarChart3, CheckCircle2,
  CalendarCheck, Wallet, AlertTriangle, BookOpen,
  TrendingUp, TrendingDown, Minus, Award, Clock
} from 'lucide-react'

// ── Helpers ─────────────────────────────────────────────────────────────────

const isDark = () =>
  typeof document !== 'undefined' &&
  document.documentElement.dataset.theme === 'dark'

const palette = ['#3b82f6', '#22c55e', '#ef4444', '#f59e0b', '#8b5cf6', '#06b6d4', '#ec4899', '#14b8a6']

const baseChart = (type, extra = {}) => ({
  chart: {
    type,
    background: 'transparent',
    toolbar: { show: false },
    fontFamily: 'inherit',
    animations: { enabled: true, easing: 'easeinout', speed: 600 },
    ...extra,
  },
  colors: palette,
  theme: { mode: isDark() ? 'dark' : 'light' },
  grid: { borderColor: isDark() ? '#334155' : '#f1f5f9', strokeDashArray: 3 },
  tooltip: { theme: isDark() ? 'dark' : 'light' },
})

const fmt = (n, decimals = 1) =>
  n == null ? '—' : Number(n).toLocaleString('es-DO', { maximumFractionDigits: decimals, minimumFractionDigits: decimals })

const fmtMoney = (v) =>
  v == null ? '—' : `$${Number(v).toLocaleString('es-DO', { maximumFractionDigits: 0 })}`

const semColor = (p) => p >= 80 ? '#22c55e' : p >= 70 ? '#f59e0b' : '#ef4444'
const semLabel = (p) => p >= 80 ? 'Verde' : p >= 70 ? 'Amarillo' : 'Rojo'

// ── KPI Card ─────────────────────────────────────────────────────────────────

function KpiCard({ label, value, sub, icon: Icon, gradient, delay = 0, delta }) {
  const deltaEl = delta != null
    ? <span style={{
        fontSize: '.68rem', fontWeight: 700, padding: '.12rem .45rem', borderRadius: 99,
        background: delta > 0 ? 'rgba(34,197,94,.28)' : delta < 0 ? 'rgba(239,68,68,.28)' : 'rgba(255,255,255,.18)',
        display: 'inline-flex', alignItems: 'center', gap: 2,
      }}>
        {delta > 0 ? <TrendingUp size={10} /> : delta < 0 ? <TrendingDown size={10} /> : <Minus size={10} />}
        {delta > 0 ? '+' : ''}{delta}
      </span>
    : null

  return (
    <motion.div
      initial={{ opacity: 0, y: 24 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay, duration: 0.45, ease: 'easeOut' }}
      style={{ background: gradient, borderRadius: 16, padding: '1.2rem 1.35rem', color: '#fff', height: '100%' }}
    >
      <div style={{ display: 'flex', alignItems: 'flex-start', gap: 12 }}>
        <div style={{
          width: 46, height: 46, borderRadius: 12,
          background: 'rgba(255,255,255,.18)',
          display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0,
        }}>
          <Icon size={21} />
        </div>
        <div style={{ flex: 1, minWidth: 0 }}>
          <div style={{ fontSize: '.68rem', fontWeight: 700, opacity: .82, textTransform: 'uppercase', letterSpacing: '.06em', marginBottom: 2 }}>
            {label}
          </div>
          <div style={{ fontSize: '1.7rem', fontWeight: 900, lineHeight: 1, margin: '.12rem 0' }}>
            {value ?? '—'}
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 6, marginTop: 4 }}>
            {sub && <span style={{ fontSize: '.7rem', opacity: .76 }}>{sub}</span>}
            {deltaEl}
          </div>
        </div>
      </div>
    </motion.div>
  )
}

// ── Chart Card wrapper ────────────────────────────────────────────────────────

function ChartCard({ title, icon: Icon, children, delay = 0, style = {} }) {
  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay, duration: 0.45 }}
      style={{
        background: isDark() ? '#1e293b' : '#fff',
        borderRadius: 16, boxShadow: '0 2px 14px rgba(15,23,42,.07)',
        padding: '1.2rem 1.35rem', height: '100%', ...style
      }}
    >
      <div style={{ fontSize: '.82rem', fontWeight: 700, color: isDark() ? '#e2e8f0' : '#1e293b', marginBottom: '1rem', display: 'flex', alignItems: 'center', gap: 6 }}>
        {Icon && <Icon size={15} style={{ color: palette[0] }} />}
        {title}
      </div>
      {children}
    </motion.div>
  )
}

// ── Tabla Grupos ──────────────────────────────────────────────────────────────

function TablaGrupos({ grupos, title, icon: Icon, delay, tipo }) {
  return (
    <ChartCard title={title} icon={Icon} delay={delay}>
      <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '.8rem' }}>
        <thead>
          <tr style={{ background: isDark() ? '#0f172a' : '#f8fafc' }}>
            {['Grupo', 'Estudiantes', 'Promedio', '% Aprob.', ''].map(h => (
              <th key={h} style={{ padding: '.45rem .65rem', textAlign: 'left', fontSize: '.7rem', fontWeight: 700, color: isDark() ? '#94a3b8' : '#374151', textTransform: 'uppercase', letterSpacing: '.04em' }}>{h}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {(grupos || []).map((r, i) => {
            const prom = parseFloat(r.promedio_grupo || 0)
            const aprobPct = r.total_estudiantes > 0
              ? Math.round(r.total_aprobados / r.total_estudiantes * 100)
              : 0
            const nombre = `${r.grupo?.grado?.nombre ?? '—'} ${r.grupo?.seccion?.nombre ?? ''}`
            return (
              <tr key={i} style={{ borderBottom: `1px solid ${isDark() ? '#1e293b' : '#f1f5f9'}` }}>
                <td style={{ padding: '.45rem .65rem', fontWeight: 600, color: isDark() ? '#e2e8f0' : '#1e293b' }}>{nombre}</td>
                <td style={{ padding: '.45rem .65rem', color: isDark() ? '#94a3b8' : '#6b7280', textAlign: 'center' }}>{r.total_estudiantes}</td>
                <td style={{ padding: '.45rem .65rem', fontWeight: 700, color: semColor(prom), textAlign: 'center' }}>{fmt(prom)}</td>
                <td style={{ padding: '.45rem .65rem', textAlign: 'center', color: isDark() ? '#94a3b8' : '#6b7280' }}>{aprobPct}%</td>
                <td style={{ padding: '.45rem .65rem' }}>
                  <span style={{ width: 8, height: 8, borderRadius: '50%', background: semColor(prom), display: 'inline-block' }} />
                </td>
              </tr>
            )
          })}
        </tbody>
      </table>
    </ChartCard>
  )
}

// ── Dashboard principal ───────────────────────────────────────────────────────

export default function Dashboard({ data = {} }) {
  const {
    totalEstudiantes = 0, totalDocentes = 0,
    promedioInstitucional, tasaAprobacion = 0, pctAsistencia,
    statsPagos, asistenciaMes = {},
    promediosPorGrado = {}, matriculasPorGrado = {},
    tendenciaAsistencia = { labels: [], data: { presente: [], tardanza: [], ausente: [] } },
    distribucionDesempeno = {},
    topGrupos = [], bottomGrupos = [],
    promediosPorAsignatura = [], riesgoData = { totalEnRiesgo: 0, riesgoPorGrado: {} },
    statsDocentes = {}, comparativa = {},
    preMatriculaStats = {}, disciplinaPorTipo = {},
    schoolYear,
  } = data

  // ── KPI cards config ──────────────────────────────────────────────────────
  const kpis = [
    {
      label: 'Estudiantes Activos',
      value: Number(totalEstudiantes).toLocaleString('es-DO'),
      icon: Users,
      gradient: 'linear-gradient(135deg,#1e3a6e,#2563eb)',
      sub: schoolYear?.nombre ?? '',
    },
    {
      label: 'Docentes Activos',
      value: Number(totalDocentes).toLocaleString('es-DO'),
      icon: GraduationCap,
      gradient: 'linear-gradient(135deg,#4c1d95,#7c3aed)',
      sub: statsDocentes.con_notas ? `${statsDocentes.con_notas} con notas publicadas` : '',
    },
    {
      label: 'Promedio Institucional',
      value: promedioInstitucional ? fmt(promedioInstitucional) : '—',
      icon: BarChart3,
      gradient: 'linear-gradient(135deg,#0f766e,#0d9488)',
      delta: comparativa?.promedio ?? null,
    },
    {
      label: 'Tasa de Aprobación',
      value: `${tasaAprobacion}%`,
      icon: CheckCircle2,
      gradient: 'linear-gradient(135deg,#15803d,#16a34a)',
      delta: comparativa?.tasa ?? null,
    },
    {
      label: 'Asistencia del Mes',
      value: pctAsistencia != null ? `${pctAsistencia}%` : '—',
      icon: CalendarCheck,
      gradient: 'linear-gradient(135deg,#9a3412,#ea580c)',
      sub: (() => {
        const t = Object.values(asistenciaMes).reduce((a, b) => a + b, 0)
        return t > 0 ? `${t.toLocaleString()} registros` : ''
      })(),
    },
    {
      label: 'Estudiantes en Riesgo',
      value: Number(riesgoData.totalEnRiesgo || 0).toLocaleString('es-DO'),
      icon: AlertTriangle,
      gradient: 'linear-gradient(135deg,#7f1d1d,#dc2626)',
      sub: '≥ 2 materias bajo 70',
    },
  ]

  // ── Chart options ─────────────────────────────────────────────────────────

  const chartAsistencia = useMemo(() => ({
    ...baseChart('area'),
    series: [
      { name: 'Presente', data: tendenciaAsistencia.data.presente },
      { name: 'Tardanza', data: tendenciaAsistencia.data.tardanza },
      { name: 'Ausente',  data: tendenciaAsistencia.data.ausente },
    ],
    colors: ['#22c55e', '#f59e0b', '#ef4444'],
    xaxis: { categories: tendenciaAsistencia.labels, labels: { style: { fontSize: '11px' } } },
    yaxis: { labels: { style: { fontSize: '11px' } } },
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.5, opacityTo: 0.05, shadeIntensity: 1 } },
    dataLabels: { enabled: false },
    legend: { position: 'top', fontSize: '12px' },
  }), [tendenciaAsistencia])

  const chartDesempeno = useMemo(() => ({
    ...baseChart('donut'),
    series: Object.values(distribucionDesempeno),
    labels: Object.keys(distribucionDesempeno),
    colors: ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444'],
    plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Promedio', fontSize: '13px', fontWeight: 700 } } } } },
    dataLabels: { enabled: true, formatter: (v) => `${Number(v).toFixed(1)}%` },
    legend: { position: 'bottom', fontSize: '11px' },
  }), [distribucionDesempeno])

  const chartPromediosGrado = useMemo(() => ({
    ...baseChart('bar'),
    series: [{ name: 'Promedio', data: Object.values(promediosPorGrado) }],
    xaxis: { categories: Object.keys(promediosPorGrado), labels: { style: { fontSize: '11px' } } },
    yaxis: { min: 0, max: 100, labels: { style: { fontSize: '11px' } } },
    plotOptions: { bar: { borderRadius: 6, columnWidth: '50%', distributed: true } },
    colors: Object.values(promediosPorGrado).map(v => semColor(v)),
    dataLabels: { enabled: true, style: { fontSize: '11px', fontWeight: 700 } },
    legend: { show: false },
  }), [promediosPorGrado])

  const chartMatriculas = useMemo(() => ({
    ...baseChart('bar'),
    series: [{ name: 'Matrículas', data: Object.values(matriculasPorGrado) }],
    xaxis: { categories: Object.keys(matriculasPorGrado), labels: { style: { fontSize: '11px' } } },
    plotOptions: { bar: { horizontal: true, borderRadius: 5, barHeight: '55%' } },
    colors: ['#8b5cf6'],
    dataLabels: { enabled: true, style: { fontSize: '11px' } },
    legend: { show: false },
  }), [matriculasPorGrado])

  const asigData = useMemo(() => {
    const arr = Array.isArray(promediosPorAsignatura) ? promediosPorAsignatura : Object.values(promediosPorAsignatura)
    return arr.slice(0, 15)
  }, [promediosPorAsignatura])

  const chartAsignaturas = useMemo(() => ({
    ...baseChart('bar'),
    series: [{ name: 'Promedio', data: asigData.map(a => parseFloat(a.promedio)) }],
    xaxis: {
      categories: asigData.map(a => a.nombre?.length > 22 ? a.nombre.substring(0, 22) + '…' : a.nombre),
      labels: { style: { fontSize: '10px' } },
    },
    yaxis: { min: 0, max: 100, labels: { style: { fontSize: '10px' } } },
    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%', distributed: true } },
    colors: asigData.map(a => semColor(parseFloat(a.promedio))),
    dataLabels: { enabled: true, style: { fontSize: '10px', fontWeight: 700 } },
    legend: { show: false },
    annotations: {
      xaxis: [{ x: 70, borderColor: '#ef4444', strokeDashArray: 4, label: { text: 'Mín. 70', style: { fontSize: '10px', color: '#ef4444' } } }],
    },
  }), [asigData])

  const riesgoLabels = Object.keys(riesgoData.riesgoPorGrado || {})
  const riesgoVals   = Object.values(riesgoData.riesgoPorGrado || {})

  const chartRiesgo = useMemo(() => ({
    ...baseChart('bar'),
    series: [{ name: 'En Riesgo', data: riesgoVals }],
    xaxis: { categories: riesgoLabels, labels: { style: { fontSize: '11px' } } },
    plotOptions: { bar: { borderRadius: 6, columnWidth: '50%', distributed: true } },
    colors: riesgoVals.map(v => v >= 5 ? '#dc2626' : v >= 2 ? '#f59e0b' : '#22c55e'),
    dataLabels: { enabled: true, style: { fontSize: '11px', fontWeight: 700 } },
    legend: { show: false },
  }), [riesgoData])

  const chartDocentes = useMemo(() => ({
    ...baseChart('donut'),
    series: [statsDocentes.con_notas || 0, statsDocentes.sin_notas || 0],
    labels: ['Con notas publicadas', 'Sin notas aún'],
    colors: ['#22c55e', '#e5e7eb'],
    plotOptions: { pie: { donut: { size: '60%', labels: { show: true, total: { show: true, label: 'Total', fontSize: '13px' } } } } },
    dataLabels: { enabled: false },
    legend: { position: 'bottom', fontSize: '11px' },
  }), [statsDocentes])

  // ── Pagos mini-stats ──────────────────────────────────────────────────────
  const pagosItems = statsPagos ? [
    { label: 'Cobrado',   value: fmtMoney(statsPagos.cobrado),   color: '#22c55e' },
    { label: 'Pendiente', value: fmtMoney(statsPagos.pendiente), color: '#f59e0b' },
    { label: 'Vencido',   value: fmtMoney(statsPagos.vencido),   color: '#ef4444' },
  ] : []

  // ── Render ────────────────────────────────────────────────────────────────
  return (
    <div style={{ fontFamily: 'inherit' }}>

      {/* ── KPI Row ── */}
      <div className="row g-3 mb-4">
        {kpis.map((k, i) => (
          <div key={i} className="col-6 col-md-4 col-xl-2">
            <KpiCard {...k} delay={i * 0.07} />
          </div>
        ))}
      </div>

      {/* ── Asistencia + Desempeño ── */}
      <div className="row g-3 mb-4">
        <div className="col-lg-8">
          <ChartCard title="Tendencia de Asistencia — Últimos 6 Meses" icon={CalendarCheck} delay={0.1}>
            <ReactApexChart options={chartAsistencia} series={chartAsistencia.series} type="area" height={280} />
          </ChartCard>
        </div>
        <div className="col-lg-4">
          <ChartCard title="Distribución del Desempeño" icon={BarChart3} delay={0.15}>
            <ReactApexChart options={chartDesempeno} series={chartDesempeno.series} type="donut" height={280} />
          </ChartCard>
        </div>
      </div>

      {/* ── Promedios por Grado + Matrículas ── */}
      <div className="row g-3 mb-4">
        <div className="col-lg-6">
          <ChartCard title="Promedio por Grado" icon={BarChart3} delay={0.2}>
            <ReactApexChart options={chartPromediosGrado} series={chartPromediosGrado.series} type="bar" height={260} />
          </ChartCard>
        </div>
        <div className="col-lg-6">
          <ChartCard title="Matrículas Activas por Grado" icon={Users} delay={0.22}>
            <ReactApexChart options={chartMatriculas} series={chartMatriculas.series} type="bar" height={260} />
          </ChartCard>
        </div>
      </div>

      {/* ── Asignaturas + Riesgo ── */}
      <div className="row g-3 mb-4">
        <div className="col-lg-7">
          <ChartCard title="Promedio por Asignatura" icon={BookOpen} delay={0.25}>
            <ReactApexChart options={chartAsignaturas} series={chartAsignaturas.series} type="bar" height={Math.max(260, asigData.length * 28)} />
          </ChartCard>
        </div>
        <div className="col-lg-5">
          <ChartCard title="Riesgo Académico por Grado" icon={AlertTriangle} delay={0.27}>
            <ReactApexChart options={chartRiesgo} series={chartRiesgo.series} type="bar" height={260} />
            <div style={{ textAlign: 'center', marginTop: 8, fontSize: '.78rem', color: isDark() ? '#94a3b8' : '#6b7280' }}>
              Total en riesgo: <strong style={{ color: '#ef4444' }}>{riesgoData.totalEnRiesgo}</strong> estudiantes (≥ 2 materias &lt; 70)
            </div>
          </ChartCard>
        </div>
      </div>

      {/* ── Top / Bottom grupos ── */}
      <div className="row g-3 mb-4">
        <div className="col-lg-6">
          <TablaGrupos grupos={topGrupos} title="Top 5 Grupos — Mayor Promedio" icon={Award} delay={0.3} tipo="top" />
        </div>
        <div className="col-lg-6">
          <TablaGrupos grupos={bottomGrupos} title="Grupos que Necesitan Atención" icon={AlertTriangle} delay={0.32} tipo="bottom" />
        </div>
      </div>

      {/* ── Docentes + Pre-matrícula + Pagos ── */}
      <div className="row g-3 mb-4">
        {statsDocentes.activos > 0 && (
          <div className="col-lg-4">
            <ChartCard title="Estado de Notas — Docentes" icon={GraduationCap} delay={0.35}>
              <ReactApexChart options={chartDocentes} series={chartDocentes.series} type="donut" height={220} />
            </ChartCard>
          </div>
        )}

        {Object.keys(preMatriculaStats).length > 0 && (
          <div className="col-lg-4">
            <ChartCard title="Pre-Matrículas" icon={Clock} delay={0.37}>
              <div className="row g-2 mt-1">
                {[
                  { label: 'Pendientes', value: preMatriculaStats.pendientes || 0, color: '#f59e0b', bg: '#fffbeb' },
                  { label: 'Aprobadas',  value: preMatriculaStats.aprobadas  || 0, color: '#22c55e', bg: '#f0fdf4' },
                  { label: 'Rechazadas', value: preMatriculaStats.rechazadas || 0, color: '#ef4444', bg: '#fef2f2' },
                ].map(({ label, value, color, bg }) => (
                  <div key={label} className="col-4">
                    <div style={{ background: isDark() ? 'rgba(255,255,255,.05)' : bg, borderRadius: 10, padding: '.75rem', textAlign: 'center' }}>
                      <div style={{ fontSize: '1.5rem', fontWeight: 900, color }}>{value}</div>
                      <div style={{ fontSize: '.7rem', color: isDark() ? '#94a3b8' : '#6b7280', fontWeight: 600 }}>{label}</div>
                    </div>
                  </div>
                ))}
              </div>
            </ChartCard>
          </div>
        )}

        {pagosItems.length > 0 && (
          <div className="col-lg-4">
            <ChartCard title="Resumen de Pagos" icon={Wallet} delay={0.39}>
              <div className="d-flex flex-column gap-2 mt-1">
                {pagosItems.map(({ label, value, color }) => (
                  <div key={label} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '.6rem .85rem', borderRadius: 10, background: isDark() ? 'rgba(255,255,255,.04)' : '#f8fafc', borderLeft: `4px solid ${color}` }}>
                    <span style={{ fontSize: '.8rem', color: isDark() ? '#94a3b8' : '#374151', fontWeight: 600 }}>{label}</span>
                    <span style={{ fontSize: '.9rem', fontWeight: 800, color }}>{value}</span>
                  </div>
                ))}
              </div>
            </ChartCard>
          </div>
        )}
      </div>

    </div>
  )
}
