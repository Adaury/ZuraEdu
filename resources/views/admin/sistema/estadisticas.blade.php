@extends('layouts.admin')
@section('page-title', 'Estadísticas del Sistema')

@push('styles')
<style>
.stat-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:1rem; margin-bottom:1.5rem; }
.stat-tile { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:1.1rem 1.25rem; }
.stat-tile .val { font-size:1.8rem; font-weight:900; line-height:1.1; }
.stat-tile .lbl { font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-top:.3rem; }
.section-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--primary); border-bottom:2px solid var(--primary); padding-bottom:.4rem; margin:1.5rem 0 1rem; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-speedometer2 me-2"></i>Estadísticas del Sistema
        </h4>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">
            Resumen de uso y estado — {{ $sy?->nombre ?? 'Año escolar activo' }} · {{ now()->format('d/m/Y H:i') }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.sistema.reporte-anual') }}" target="_blank"
           class="btn btn-sm" style="background:#1e3a6e;color:#fff;">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>Resumen Anual PDF
        </a>
        <a href="{{ route('admin.sistema.reporte-ejecutivo') }}" target="_blank"
           class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>Informe Ejecutivo PDF
        </a>
        <a href="{{ route('admin.sistema.ficha-institucional') }}" target="_blank"
           class="btn btn-sm btn-outline-primary">
            <i class="bi bi-building me-1"></i>Ficha Institucional
        </a>
        <a href="{{ route('admin.sistema.actividad') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-clipboard-data me-1"></i>Log completo
        </a>
    </div>
</div>

{{-- Usuarios --}}
<div class="section-title">Usuarios del Sistema</div>
<div class="stat-grid">
    <div class="stat-tile" style="border-top:4px solid #2563eb;">
        <div class="val" style="color:#1d4ed8;">{{ $stats['usuarios_activos'] }}</div>
        <div class="lbl">Usuarios activos</div>
    </div>
    <div class="stat-tile" style="border-top:4px solid #10b981;">
        <div class="val" style="color:#065f46;">{{ $stats['logins_hoy'] }}</div>
        <div class="lbl">Logins hoy</div>
    </div>
    <div class="stat-tile" style="border-top:4px solid #6366f1;">
        <div class="val" style="color:#4338ca;">{{ $stats['logins_semana'] }}</div>
        <div class="lbl">Logins esta semana</div>
    </div>
    @foreach($stats['usuarios_roles'] as $rol => $cnt)
    <div class="stat-tile">
        <div class="val">{{ $cnt }}</div>
        <div class="lbl">{{ $rol }}</div>
    </div>
    @endforeach
</div>

{{-- Académico --}}
<div class="section-title">Datos Académicos</div>
<div class="stat-grid">
    <div class="stat-tile" style="border-top:4px solid #1d4ed8;">
        <div class="val" style="color:#1d4ed8;">{{ $stats['estudiantes'] }}</div>
        <div class="lbl">Estudiantes activos</div>
    </div>
    <div class="stat-tile" style="border-top:4px solid #047857;">
        <div class="val" style="color:#065f46;">{{ $stats['docentes'] }}</div>
        <div class="lbl">Docentes activos</div>
    </div>
    <div class="stat-tile" style="border-top:4px solid #7c3aed;">
        <div class="val" style="color:#5b21b6;">{{ $stats['grupos'] }}</div>
        <div class="lbl">Grupos este año</div>
    </div>
    <div class="stat-tile" style="border-top:4px solid #0891b2;">
        <div class="val" style="color:#0e7490;">{{ $stats['calificaciones'] }}</div>
        <div class="lbl">Registros de notas</div>
    </div>
    <div class="stat-tile" style="border-top:4px solid #16a34a;">
        <div class="val" style="color:#15803d;">{{ $stats['asistencias'] }}</div>
        <div class="lbl">Asistencias hoy</div>
    </div>
</div>

{{-- Comunicación --}}
<div class="section-title">Comunicación y Alertas</div>
<div class="stat-grid">
    <div class="stat-tile" style="border-top:4px solid #2563eb;">
        <div class="val" style="color:#1d4ed8;">{{ $stats['comunicados'] }}</div>
        <div class="lbl">Comunicados hoy</div>
    </div>
    <div class="stat-tile" style="border-top:4px solid #6366f1;">
        <div class="val" style="color:#4338ca;">{{ $stats['notificaciones_hoy'] }}</div>
        <div class="lbl">Notificaciones hoy</div>
    </div>
    <div class="stat-tile" style="border-top:4px solid #f59e0b;">
        <div class="val" style="color:#d97706;">{{ $stats['alertas_activas'] }}</div>
        <div class="lbl">Alertas sin leer</div>
    </div>
    @if($stats['pagos_pendientes'] !== null)
    <div class="stat-tile" style="border-top:4px solid #dc2626;">
        <div class="val" style="color:#991b1b;">{{ $stats['pagos_pendientes'] }}</div>
        <div class="lbl">Pagos pendientes</div>
    </div>
    @endif
</div>

{{-- Gráfica actividad semanal --}}
@if($actividadPorDia->isNotEmpty())
<div class="section-title">Actividad de la Última Semana</div>
<div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="card-body">
        <canvas id="chartActividad" height="80"></canvas>
    </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartActividad'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($actividadPorDia->keys()->map(fn($d) => \Carbon\Carbon::parse($d)->translatedFormat('d D'))->toArray()) !!},
        datasets: [{
            label: 'Acciones',
            data: {!! json_encode($actividadPorDia->values()) !!},
            backgroundColor: '#3b82f6',
            borderRadius: 6,
        }],
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: '#6b7280', font: { size: 11 } }, grid: { display: false } },
            y: { ticks: { color: '#6b7280', font: { size: 11 } }, grid: { color: '#f1f5f9' }, beginAtZero: true },
        },
    },
});
</script>
@endpush
@endif
@endsection
