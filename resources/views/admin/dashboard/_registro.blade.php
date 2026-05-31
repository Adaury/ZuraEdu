{{-- ══ DASHBOARD REGISTRO ACADÉMICO ══ --}}

{{-- KPIs de registro --}}
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.estudiantes.index') }}" class="text-decoration-none d-block">
            <div class="stat-card" style="--c:#0d6efd;cursor:pointer;">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-num">{{ $statsRegistroAcad['estudiantes'] ?? 0 }}</div>
                    <div class="stat-label">Estudiantes Activos</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.matriculas.index') }}" class="text-decoration-none d-block">
            <div class="stat-card" style="--c:#10b981;cursor:pointer;">
                <div class="stat-icon"><i class="bi bi-card-list"></i></div>
                <div class="stat-body">
                    <div class="stat-num">{{ $statsRegistroAcad['matriculas_activas'] ?? 0 }}</div>
                    <div class="stat-label">Matrículas Activas</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.pre-matriculas.index') }}" class="text-decoration-none d-block">
            <div class="stat-card" style="--c:#f59e0b;cursor:pointer;">
                <div class="stat-icon"><i class="bi bi-inbox-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-num">{{ $statsRegistroAcad['prematriculas_pend'] ?? 0 }}</div>
                    <div class="stat-label">Pre-matrículas Pendientes</div>
                </div>
                @if(($statsRegistroAcad['prematriculas_pend'] ?? 0) > 0)
                <div style="position:absolute;bottom:14px;right:16px;font-size:.7rem;color:rgba(255,255,255,.7);font-weight:600;">
                    REVISAR <i class="bi bi-arrow-right"></i>
                </div>
                @endif
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.registro.index') }}" class="text-decoration-none d-block">
            <div class="stat-card" style="--c:#8b5cf6;cursor:pointer;">
                <div class="stat-icon"><i class="bi bi-table"></i></div>
                <div class="stat-body">
                    <div class="stat-num">{{ $statsRegistroAcad['grupos'] ?? 0 }}</div>
                    <div class="stat-label">Grupos / Secciones</div>
                </div>
            </div>
        </a>
    </div>
</div>

{{-- Alerta pre-matrículas --}}
@if(($statsRegistroAcad['prematriculas_pend'] ?? 0) > 0)
<div class="alert border-0 mb-4 d-flex align-items-center gap-3"
     style="background:#fef3c7;border-left:4px solid #f59e0b !important;border-radius:12px;padding:1rem 1.25rem;">
    <i class="bi bi-hourglass-split" style="color:#f59e0b;font-size:1.3rem;flex-shrink:0;"></i>
    <div style="flex:1;">
        <div class="fw-bold" style="color:#92400e;">
            {{ $statsRegistroAcad['prematriculas_pend'] }} pre-matrícula(s) esperando revisión
        </div>
        <div style="font-size:.82rem;color:#78350f;">Apruébalas o recházalas para completar el proceso.</div>
    </div>
    <a href="{{ route('admin.pre-matriculas.index') }}"
       class="btn btn-sm fw-semibold" style="background:#f59e0b;color:#fff;border-radius:8px;white-space:nowrap;">
        Revisar
    </a>
</div>
@endif

<div class="row g-4">
    {{-- Últimas matrículas --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0" style="color:#0d6efd;">
                    <i class="bi bi-card-list me-2"></i>Últimas Matrículas
                </h6>
                <a href="{{ route('admin.matriculas.index') }}" class="btn btn-sm btn-outline-primary" style="border-radius:8px;font-size:.78rem;">Ver todas</a>
            </div>
            <div class="card-body p-0">
                @forelse($statsRegistroAcad['ultimas_matriculas'] ?? [] as $mat)
                <div class="d-flex align-items-center gap-3 px-4 py-2" style="border-bottom:1px solid #f1f5f9;">
                    <div style="width:34px;height:34px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-person-fill" style="color:#0d6efd;font-size:.9rem;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="fw-semibold text-truncate" style="font-size:.82rem;">{{ $mat->estudiante?->nombre_completo ?? '—' }}</div>
                        <div style="font-size:.72rem;color:#94a3b8;">{{ $mat->grupo?->grado?->nombre }} {{ $mat->grupo?->seccion ?? '' }}</div>
                    </div>
                    <div style="font-size:.72rem;color:#94a3b8;white-space:nowrap;">{{ $mat->created_at?->format('d/m/Y') }}</div>
                </div>
                @empty
                <div class="text-center py-4 text-muted" style="font-size:.85rem;">Sin matrículas registradas.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Historial SIGERD --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0" style="color:#7c3aed;">
                    <i class="bi bi-cloud-upload me-2"></i>Exportaciones SIGERD
                </h6>
                <a href="{{ route('admin.sigerd.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;font-size:.78rem;">Exportar</a>
            </div>
            <div class="card-body p-0">
                @php
                    $tipoLabels = ['nomina_matricula'=>'Nómina','calificaciones'=>'Calificaciones','docentes'=>'Docentes','asistencia'=>'Asistencia'];
                @endphp
                @forelse($statsRegistroAcad['ultimas_exportaciones'] ?? [] as $log)
                <div class="d-flex align-items-center gap-3 px-4 py-2" style="border-bottom:1px solid #f1f5f9;">
                    <div style="width:34px;height:34px;border-radius:50%;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-file-earmark-arrow-down" style="color:#7c3aed;font-size:.9rem;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="fw-semibold" style="font-size:.82rem;">{{ $tipoLabels[$log->tipo] ?? $log->tipo }}</div>
                        <div style="font-size:.72rem;color:#94a3b8;">
                            {{ strtoupper($log->formato) }} · {{ $log->total_registros }} registros
                        </div>
                    </div>
                    <div style="font-size:.72rem;color:#94a3b8;white-space:nowrap;">{{ $log->created_at?->format('d/m/Y') }}</div>
                </div>
                @empty
                <div class="text-center py-4" style="color:#94a3b8;font-size:.85rem;">
                    <i class="bi bi-cloud-upload" style="font-size:1.5rem;display:block;margin-bottom:.5rem;color:#c4b5fd;"></i>
                    Sin exportaciones a SIGERD aún
                </div>
                @endforelse
            </div>
            @if(($statsRegistroAcad['ultimas_exportaciones'] ?? collect())->isEmpty())
            <div class="card-footer bg-white border-0 py-2 px-4">
                <a href="{{ route('admin.sigerd.index') }}" class="btn btn-sm w-100"
                   style="background:#ede9fe;color:#7c3aed;border-radius:8px;font-size:.8rem;font-weight:600;">
                    <i class="bi bi-cloud-upload me-1"></i>Exportar datos al MINERD
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
