{{-- ══ DASHBOARD COORDINADOR ══ --}}

{{-- KPIs académicos --}}
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.estudiantes.index') }}" class="text-decoration-none d-block">
            <div class="stat-card" style="--c:#0d6efd;cursor:pointer;">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-num">{{ $statsCoord['estudiantes'] ?? 0 }}</div>
                    <div class="stat-label">Estudiantes Activos</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.grupos.index') }}" class="text-decoration-none d-block">
            <div class="stat-card" style="--c:#f59e0b;cursor:pointer;">
                <div class="stat-icon"><i class="bi bi-diagram-3-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-num">{{ $statsCoord['grupos'] ?? 0 }}</div>
                    <div class="stat-label">Grupos Activos</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.observaciones.index') }}" class="text-decoration-none d-block">
            <div class="stat-card" style="--c:#8b5cf6;cursor:pointer;">
                <div class="stat-icon"><i class="bi bi-eye-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-num">{{ $statsCoord['observaciones'] ?? 0 }}</div>
                    <div class="stat-label">Observaciones</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.periodos.index') }}" class="text-decoration-none d-block">
            <div class="stat-card" style="--c:#10b981;cursor:pointer;">
                <div class="stat-icon"><i class="bi bi-calendar3"></i></div>
                <div class="stat-body">
                    <div class="stat-num">{{ $statsCoord['periodos_activos'] ?? 0 }}</div>
                    <div class="stat-label">Períodos Abiertos</div>
                </div>
            </div>
        </a>
    </div>
</div>

{{-- Alerta períodos por cerrar --}}
@if(($statsCoord['periodos_cerrando'] ?? collect())->isNotEmpty())
<div class="alert border-0 mb-4 d-flex align-items-start gap-3"
     style="background:#fff7ed;border-left:4px solid #f97316 !important;border-radius:12px;padding:1rem 1.25rem;">
    <i class="bi bi-clock-fill" style="color:#f97316;font-size:1.2rem;flex-shrink:0;margin-top:.1rem;"></i>
    <div>
        <div class="fw-bold" style="color:#9a3412;">Períodos próximos a cerrar</div>
        @foreach($statsCoord['periodos_cerrando'] as $per)
        <div style="font-size:.82rem;color:#7c2d12;margin-top:.2rem;">
            <strong>{{ $per->nombre }}</strong> — vence el {{ $per->fecha_fin?->format('d/m/Y') }}
            ({{ $per->fecha_fin?->diffForHumans() }})
        </div>
        @endforeach
    </div>
    <a href="{{ route('admin.periodos.index') }}" class="btn btn-sm ms-auto"
       style="background:#f97316;color:#fff;border-radius:8px;white-space:nowrap;">
        Ver períodos
    </a>
</div>
@endif

<div class="row g-4">
    {{-- Agenda próxima --}}
    @if(!empty($agendaProxima) && $agendaProxima->isNotEmpty())
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-header bg-white border-0 py-3 px-4">
                <h6 class="fw-bold mb-0" style="color:#2563eb;">
                    <i class="bi bi-calendar-event me-2"></i>Agenda — Próximos 7 días
                </h6>
            </div>
            <div class="card-body p-0">
                @foreach($agendaProxima->take(5) as $item)
                <div class="d-flex align-items-center gap-3 px-4 py-2" style="border-bottom:1px solid #f1f5f9;">
                    <div style="width:34px;height:34px;border-radius:50%;background:{{ $item['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi {{ $item['icon'] }}" style="color:{{ $item['color'] }};font-size:.85rem;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="fw-semibold text-truncate" style="font-size:.82rem;">{{ $item['titulo'] }}</div>
                        <div style="font-size:.72rem;color:#94a3b8;">
                            {{ \Carbon\Carbon::parse($item['fecha'])->format('d/m/Y') }} · {{ $item['sub'] }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Observaciones recientes --}}
    <div class="col-lg-{{ !empty($agendaProxima) && $agendaProxima->isNotEmpty() ? '6' : '12' }}">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0" style="color:#8b5cf6;">
                    <i class="bi bi-eye me-2"></i>Observaciones Recientes
                </h6>
                <a href="{{ route('admin.observaciones.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;font-size:.78rem;">
                    Ver todas
                </a>
            </div>
            <div class="card-body p-0">
                @forelse($statsCoord['obs_recientes'] ?? [] as $obs)
                <div class="d-flex align-items-start gap-3 px-4 py-2" style="border-bottom:1px solid #f1f5f9;">
                    <div style="width:34px;height:34px;border-radius:50%;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:.1rem;">
                        <i class="bi bi-eye-fill" style="color:#8b5cf6;font-size:.85rem;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="fw-semibold" style="font-size:.82rem;">
                            {{ $obs->asignacion?->docente?->user?->nombre_completo ?? '—' }}
                        </div>
                        <div style="font-size:.75rem;color:#64748b;">
                            {{ $obs->asignacion?->asignatura?->nombre }} ·
                            {{ $obs->asignacion?->grupo?->grado?->nombre }}
                        </div>
                        <div style="font-size:.72rem;color:#94a3b8;">
                            {{ $obs->created_at?->diffForHumans() }}
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-4 text-muted" style="font-size:.85rem;">Sin observaciones recientes.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Acciones rápidas coordinador --}}
<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#6b7280;">Acciones rápidas</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.calificaciones.index') }}" class="btn btn-sm" style="background:#dbeafe;color:#1e40af;border-radius:8px;font-size:.8rem;font-weight:600;">
                        <i class="bi bi-journal-check me-1"></i>Calificaciones
                    </a>
                    <a href="{{ route('admin.boletines.index') }}" class="btn btn-sm" style="background:#dcfce7;color:#166534;border-radius:8px;font-size:.8rem;font-weight:600;">
                        <i class="bi bi-file-earmark-text me-1"></i>Boletines
                    </a>
                    <a href="{{ route('admin.rendimiento.dashboard') }}" class="btn btn-sm" style="background:#ede9fe;color:#5b21b6;border-radius:8px;font-size:.8rem;font-weight:600;">
                        <i class="bi bi-bar-chart-fill me-1"></i>Rendimiento
                    </a>
                    <a href="{{ route('admin.planes-clase.index') }}" class="btn btn-sm" style="background:#fef3c7;color:#92400e;border-radius:8px;font-size:.8rem;font-weight:600;">
                        <i class="bi bi-journal-text me-1"></i>Planes de Clase
                    </a>
                    <a href="{{ route('admin.asistencia.index') }}" class="btn btn-sm" style="background:#fce7f3;color:#9d174d;border-radius:8px;font-size:.8rem;font-weight:600;">
                        <i class="bi bi-calendar-check me-1"></i>Asistencia
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
