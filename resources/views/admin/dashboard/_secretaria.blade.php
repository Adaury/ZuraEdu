{{-- ══ DASHBOARD SECRETARÍA / RECEPCIÓN ══ --}}

{{-- KPIs de registro --}}
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.estudiantes.index') }}" class="text-decoration-none d-block">
            <div class="stat-card" style="--c:#0d6efd;cursor:pointer;">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-num">{{ $statsRegistro['estudiantes'] ?? 0 }}</div>
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
                    <div class="stat-num">{{ $statsRegistro['matriculas_activas'] ?? 0 }}</div>
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
                    <div class="stat-num">{{ $statsRegistro['prematriculas_pend'] ?? 0 }}</div>
                    <div class="stat-label">Pre-matrículas Pendientes</div>
                </div>
                @if(($statsRegistro['prematriculas_pend'] ?? 0) > 0)
                <div style="position:absolute;bottom:14px;right:16px;font-size:.7rem;color:rgba(255,255,255,.7);font-weight:600;">
                    REVISAR <i class="bi bi-arrow-right"></i>
                </div>
                @endif
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.grupos.index') }}" class="text-decoration-none d-block">
            <div class="stat-card" style="--c:#8b5cf6;cursor:pointer;">
                <div class="stat-icon"><i class="bi bi-diagram-3-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-num">{{ $statsRegistro['grupos'] ?? 0 }}</div>
                    <div class="stat-label">Grupos Activos</div>
                </div>
            </div>
        </a>
    </div>
</div>

{{-- Alerta pre-matrículas pendientes --}}
@if(($statsRegistro['prematriculas_pend'] ?? 0) > 0)
<div class="alert border-0 mb-4 d-flex align-items-center gap-3"
     style="background:#fef3c7;border-left:4px solid #f59e0b !important;border-radius:12px;padding:1rem 1.25rem;">
    <i class="bi bi-hourglass-split" style="color:#f59e0b;font-size:1.3rem;flex-shrink:0;"></i>
    <div style="flex:1;">
        <div class="fw-bold" style="color:#92400e;">
            {{ $statsRegistro['prematriculas_pend'] }} pre-matrícula(s) esperando revisión
        </div>
        <div style="font-size:.82rem;color:#78350f;">Apruébalas o recházalas para completar el proceso de matrícula.</div>
    </div>
    <a href="{{ route('admin.pre-matriculas.index') }}"
       class="btn btn-sm fw-semibold" style="background:#f59e0b;color:#fff;border-radius:8px;white-space:nowrap;">
        Revisar ahora
    </a>
</div>
@endif

<div class="row g-4">
    {{-- Últimas matrículas --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0" style="color:#0d6efd;">
                    <i class="bi bi-card-list me-2"></i>Últimas Matrículas Registradas
                </h6>
                <a href="{{ route('admin.matriculas.index') }}" class="btn btn-sm btn-outline-primary" style="border-radius:8px;font-size:.78rem;">
                    Ver todas
                </a>
            </div>
            <div class="card-body p-0">
                @if(($statsRegistro['ultimas_matriculas'] ?? collect())->isEmpty())
                <div class="text-center py-4 text-muted" style="font-size:.85rem;">Sin matrículas registradas.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:.83rem;">
                        <thead style="background:#f8faff;">
                            <tr>
                                <th class="px-4 py-2 text-muted fw-semibold" style="font-size:.72rem;">Estudiante</th>
                                <th class="py-2 text-muted fw-semibold" style="font-size:.72rem;">Grupo</th>
                                <th class="py-2 text-muted fw-semibold" style="font-size:.72rem;">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($statsRegistro['ultimas_matriculas'] as $mat)
                            <tr>
                                <td class="px-4 py-2 fw-semibold">{{ $mat->estudiante?->nombre_completo ?? '—' }}</td>
                                <td class="py-2 text-muted" style="font-size:.8rem;">
                                    {{ $mat->grupo?->grado?->nombre }} {{ $mat->grupo?->seccion?->nombre ?? '' }}
                                </td>
                                <td class="py-2 text-muted" style="font-size:.78rem;">
                                    {{ $mat->created_at?->format('d/m/Y') ?? '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Pre-matrículas pendientes --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0" style="color:#f59e0b;">
                    <i class="bi bi-inbox me-2"></i>Pre-matrículas Pendientes
                </h6>
                <a href="{{ route('admin.pre-matriculas.index') }}" class="btn btn-sm btn-outline-warning" style="border-radius:8px;font-size:.78rem;">
                    Ver todas
                </a>
            </div>
            <div class="card-body p-0">
                @forelse($statsRegistro['prematriculas_rec'] ?? [] as $pm)
                <div class="d-flex align-items-center gap-3 px-4 py-2" style="border-bottom:1px solid #f1f5f9;">
                    <div style="width:34px;height:34px;border-radius:50%;background:#fef3c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-person-plus-fill" style="color:#f59e0b;font-size:.9rem;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="fw-semibold text-truncate" style="font-size:.82rem;">
                            {{ $pm->nombre_completo ?? ($pm->nombre . ' ' . $pm->apellidos) }}
                        </div>
                        <div style="font-size:.72rem;color:#94a3b8;">
                            Recibida {{ $pm->created_at?->diffForHumans() }}
                        </div>
                    </div>
                    <a href="{{ route('admin.pre-matriculas.show', $pm) }}"
                       class="btn btn-sm" style="background:#f59e0b;color:#fff;border-radius:7px;font-size:.72rem;padding:.25rem .6rem;white-space:nowrap;">
                        Revisar
                    </a>
                </div>
                @empty
                <div class="text-center py-4" style="color:#94a3b8;font-size:.85rem;">
                    <i class="bi bi-check-circle-fill" style="color:#10b981;font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>
                    Sin pre-matrículas pendientes
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
