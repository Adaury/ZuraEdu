{{-- ══ DASHBOARD BIBLIOTECA ══ --}}

{{-- KPIs de biblioteca --}}
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.biblioteca.prestamos.index') }}" class="text-decoration-none d-block">
            <div class="stat-card" style="--c:#0d6efd;cursor:pointer;">
                <div class="stat-icon"><i class="bi bi-book-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-num">{{ $statsBiblioteca['prestamos_activos'] ?? 0 }}</div>
                    <div class="stat-label">Préstamos Activos</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="--c:#ef4444;">
            <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="stat-body">
                <div class="stat-num">{{ $statsBiblioteca['prestamos_vencidos'] ?? 0 }}</div>
                <div class="stat-label">Préstamos Vencidos</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="--c:#10b981;">
            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-body">
                <div class="stat-num">{{ $statsBiblioteca['total_devueltos_mes'] ?? 0 }}</div>
                <div class="stat-label">Devueltos este mes</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="{{ route('admin.recursos.index') }}" class="text-decoration-none d-block">
            <div class="stat-card" style="--c:#8b5cf6;cursor:pointer;">
                <div class="stat-icon"><i class="bi bi-archive-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-num">
                        {{ ($statsBiblioteca['prestamos_activos'] ?? 0) + ($statsBiblioteca['prestamos_vencidos'] ?? 0) }}
                    </div>
                    <div class="stat-label">Pendientes de Devolución</div>
                </div>
            </div>
        </a>
    </div>
</div>

{{-- Alerta vencidos --}}
@if(($statsBiblioteca['prestamos_vencidos'] ?? 0) > 0)
<div class="alert border-0 mb-4 d-flex align-items-center gap-3"
     style="background:#fef2f2;border-left:4px solid #ef4444 !important;border-radius:12px;padding:1rem 1.25rem;">
    <i class="bi bi-exclamation-triangle-fill" style="color:#ef4444;font-size:1.3rem;flex-shrink:0;"></i>
    <div style="flex:1;">
        <div class="fw-bold" style="color:#991b1b;">
            {{ $statsBiblioteca['prestamos_vencidos'] }} préstamo(s) vencido(s) sin devolver
        </div>
        <div style="font-size:.82rem;color:#7f1d1d;">Contacta a los estudiantes para regularizar la devolución.</div>
    </div>
    <a href="{{ route('admin.biblioteca.prestamos.index') }}"
       class="btn btn-sm fw-semibold" style="background:#ef4444;color:#fff;border-radius:8px;white-space:nowrap;">
        Ver vencidos
    </a>
</div>
@endif

{{-- Pendientes de devolución --}}
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0" style="color:#0d6efd;">
                    <i class="bi bi-clock-history me-2"></i>Pendientes de Devolución
                </h6>
                <a href="{{ route('admin.biblioteca.prestamos.index') }}"
                   class="btn btn-sm btn-outline-primary" style="border-radius:8px;font-size:.78rem;">
                    Ver todos
                </a>
            </div>
            @if(($statsBiblioteca['pendientes_devolver'] ?? collect())->isEmpty())
            <div class="card-body text-center py-5" style="color:#94a3b8;">
                <i class="bi bi-check-circle-fill" style="font-size:2rem;color:#10b981;display:block;margin-bottom:.5rem;"></i>
                <div class="fw-semibold" style="font-size:.9rem;">¡Todos los libros están devueltos!</div>
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:.83rem;">
                    <thead style="background:#f8faff;">
                        <tr>
                            <th class="px-4 py-2 text-muted fw-semibold" style="font-size:.72rem;">Estudiante</th>
                            <th class="py-2 text-muted fw-semibold" style="font-size:.72rem;">Libro</th>
                            <th class="py-2 text-muted fw-semibold text-center" style="font-size:.72rem;">Vence</th>
                            <th class="py-2 text-muted fw-semibold text-center" style="font-size:.72rem;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($statsBiblioteca['pendientes_devolver'] as $prestamo)
                    @php $esVencido = $prestamo->esta_vencido; @endphp
                    <tr class="{{ $esVencido ? 'table-danger' : '' }}">
                        <td class="px-4 py-2 fw-semibold">{{ $prestamo->estudiante?->nombre_completo ?? '—' }}</td>
                        <td class="py-2 text-muted">{{ $prestamo->libro?->titulo ?? '—' }}</td>
                        <td class="py-2 text-center" style="font-size:.78rem;">
                            {{ $prestamo->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="py-2 text-center">
                            @if($esVencido)
                            <span class="badge" style="background:#fee2e2;color:#dc2626;font-size:.7rem;">
                                <i class="bi bi-exclamation-triangle me-1"></i>Vencido
                            </span>
                            @else
                            <span class="badge" style="background:#dbeafe;color:#1d4ed8;font-size:.7rem;">
                                <i class="bi bi-book me-1"></i>Activo
                            </span>
                            @endif
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

{{-- Acciones rápidas --}}
<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#6b7280;">Acciones rápidas</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.biblioteca.prestamos.create') }}"
                       class="btn btn-sm" style="background:#dbeafe;color:#1e40af;border-radius:8px;font-size:.8rem;font-weight:600;">
                        <i class="bi bi-plus-circle me-1"></i>Nuevo Préstamo
                    </a>
                    <a href="{{ route('admin.recursos.index') }}"
                       class="btn btn-sm" style="background:#dcfce7;color:#166534;border-radius:8px;font-size:.8rem;font-weight:600;">
                        <i class="bi bi-archive me-1"></i>Inventario
                    </a>
                    <a href="{{ route('admin.biblioteca.prestamos.index') }}"
                       class="btn btn-sm" style="background:#fef3c7;color:#92400e;border-radius:8px;font-size:.8rem;font-weight:600;">
                        <i class="bi bi-clock-history me-1"></i>Historial
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
