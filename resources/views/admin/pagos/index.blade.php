@extends('layouts.admin')
@section('page-title', 'Pagos y Colegiaturas')

@push('styles')
<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
.page-header h1 { font-size:1.35rem; font-weight:800; color:var(--primary); margin:0; }

.stat-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:1.1rem 1.4rem; display:flex; align-items:center; gap:1rem; }
.stat-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.25rem; flex-shrink:0; }
.stat-label { font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; }
.stat-value { font-size:1.4rem; font-weight:800; color:#111827; line-height:1.2; }

.filter-bar { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:1rem 1.25rem; margin-bottom:1.25rem; display:flex; flex-wrap:wrap; gap:.75rem; align-items:flex-end; }
.filter-bar select, .filter-bar input { font-size:.83rem; padding:.4rem .75rem; border-radius:8px; border:1px solid #d1d5db; background:#f9fafb; height:36px; }
.filter-bar select:focus, .filter-bar input:focus { outline:none; border-color:var(--primary); background:#fff; }

.table-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; }
.table-card table { margin:0; }
.table-card thead th { background:#f8fafc; font-size:.73rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; border-bottom:1px solid #e5e7eb; padding:.75rem 1rem; }
.table-card tbody td { font-size:.84rem; padding:.75rem 1rem; vertical-align:middle; border-bottom:1px solid #f3f4f6; }
.table-card tbody tr:last-child td { border-bottom:none; }
.table-card tbody tr:hover { background:#f9fafb; }

.badge-estado { padding:.25rem .65rem; border-radius:20px; font-size:.72rem; font-weight:700; }
.badge-pagado    { background:#d1fae5; color:#065f46; }
.badge-pendiente { background:#fef3c7; color:#92400e; }
.badge-vencido   { background:#fee2e2; color:#991b1b; }
.badge-cancelado { background:#f3f4f6; color:#6b7280; }

.btn-pagar { background:var(--primary); color:#fff; border:none; border-radius:7px; padding:.3rem .75rem; font-size:.78rem; font-weight:600; cursor:pointer; }
.btn-pagar:hover { opacity:.88; }
.btn-sm-outline { background:transparent; border:1px solid #d1d5db; border-radius:7px; padding:.3rem .6rem; font-size:.78rem; color:#374151; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:.3rem; }
.btn-sm-outline:hover { background:#f3f4f6; color:#374151; }

/* Modal pago rápido */
.modal-pago .modal-content { border-radius:14px; border:none; }
.modal-pago .modal-header { background:var(--primary); color:#fff; border-radius:14px 14px 0 0; }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1><i class="bi bi-cash-coin me-2" style="color:var(--primary)"></i>Pagos y Colegiaturas</h1>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalGenerar">
            <i class="bi bi-lightning-fill"></i> Generar Cuotas
        </button>
        <a href="{{ route('admin.pagos.resumen-mensual-pdf') }}" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-calendar3 me-1"></i>Resumen Mensual
        </a>
        <a href="{{ route('admin.pagos.resumen-mensual-excel') }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Resumen Excel
        </a>
        <a href="{{ route('admin.pagos.lista-pdf', request()->query()) }}" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.pagos.lista-excel', request()->query()) }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('admin.pagos.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Nuevo Pago
        </a>
        <a href="{{ route('admin.pagos.deudores') }}" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-exclamation-circle"></i> Deudores
        </a>
        <a href="{{ route('admin.pagos.config') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-gear"></i> Config
        </a>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#dbeafe;"><i class="bi bi-hourglass-split text-primary"></i></div>
            <div>
                <div class="stat-label">Pendiente</div>
                <div class="stat-value" style="color:#1d4ed8;">RD$ {{ number_format($resumen['pendiente'],2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#d1fae5;"><i class="bi bi-check-circle-fill text-success"></i></div>
            <div>
                <div class="stat-label">Cobrado</div>
                <div class="stat-value" style="color:#065f46;">RD$ {{ number_format($resumen['pagado'],2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2;"><i class="bi bi-exclamation-circle-fill text-danger"></i></div>
            <div>
                <div class="stat-label">Vencido</div>
                <div class="stat-value" style="color:#991b1b;">RD$ {{ number_format($resumen['vencido'],2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#f3f4f6;"><i class="bi bi-receipt text-secondary"></i></div>
            <div>
                <div class="stat-label">Total registros</div>
                <div class="stat-value">{{ $resumen['total'] }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Gráfica cobros por mes --}}
@if($cobrosPorMes->isNotEmpty())
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div style="font-size:.95rem;font-weight:800;color:#111827;">Cobros por Mes</div>
            <i class="bi bi-graph-up-arrow" style="color:#0f766e;font-size:1.2rem;"></i>
        </div>
        <canvas id="chartCobros" height="70"></canvas>
    </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartCobros'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($cobrosPorMes->keys()->map(fn($m) => \Carbon\Carbon::createFromFormat('Y-m',$m)->translatedFormat('M Y'))->toArray()) !!},
        datasets: [{
            label: 'Cobrado (RD$)',
            data: {!! json_encode($cobrosPorMes->values()) !!},
            backgroundColor: '#0d9488',
            borderRadius: 6,
        }],
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color:'#6b7280', font:{ size:11 } }, grid: { display:false } },
            y: { ticks: { color:'#6b7280', font:{ size:11 }, callback: v => 'RD$ ' + v.toLocaleString() }, grid: { color:'#f1f5f9' }, beginAtZero:true },
        },
    },
});
</script>
@endpush
@endif

{{-- Filtros --}}
<form method="GET" action="{{ route('admin.pagos.index') }}">
<div class="filter-bar">
    <div>
        <label style="font-size:.75rem;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem;">Estado</label>
        <select name="estado">
            <option value="">Todos</option>
            <option value="pendiente" {{ request('estado')=='pendiente' ? 'selected' : '' }}>Pendiente</option>
            <option value="pagado"    {{ request('estado')=='pagado'    ? 'selected' : '' }}>Pagado</option>
            <option value="vencido"   {{ request('estado')=='vencido'   ? 'selected' : '' }}>Vencido</option>
            <option value="cancelado" {{ request('estado')=='cancelado' ? 'selected' : '' }}>Cancelado</option>
        </select>
    </div>
    <div>
        <label style="font-size:.75rem;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem;">Grupo</label>
        <select name="grupo_id">
            <option value="">Todos los grupos</option>
            @foreach($grupos as $g)
                <option value="{{ $g->id }}" {{ request('grupo_id')==$g->id ? 'selected' : '' }}>
                    {{ $g->grado->nombre ?? '' }} {{ $g->seccion->nombre ?? '' }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label style="font-size:.75rem;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem;">Mes vencimiento</label>
        <select name="mes">
            <option value="">Todos los meses</option>
            @foreach(range(1,12) as $m)
                <option value="{{ $m }}" {{ request('mes')==$m ? 'selected' : '' }}>
                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                </option>
            @endforeach
        </select>
    </div>
    <div style="flex:1;min-width:180px;">
        <label style="font-size:.75rem;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem;">Buscar estudiante</label>
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o matrícula…" style="width:100%;">
    </div>
    <button type="submit" class="btn btn-primary btn-sm" style="height:36px;">Filtrar</button>
    <a href="{{ route('admin.pagos.index') }}" class="btn btn-outline-secondary btn-sm" style="height:36px;">Limpiar</a>
</div>
</form>

{{-- Tabla --}}
<div class="table-card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Grupo</th>
                <th>Concepto</th>
                <th>Monto</th>
                <th>Vencimiento</th>
                <th>Fecha Pago</th>
                <th>Estado</th>
                <th>Método</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($pagos as $pago)
            @php
                $est  = $pago->matricula->estudiante;
                $grp  = $pago->matricula->grupo;
            @endphp
            <tr>
                <td>
                    <a href="{{ route('admin.pagos.por-estudiante', $pago->matricula) }}" class="fw-semibold text-decoration-none" style="color:var(--primary);">
                        {{ $est->apellido }}, {{ $est->nombre }}
                    </a>
                    <div style="font-size:.72rem;color:#6b7280;">Matr. {{ $est->matricula ?? '—' }}</div>
                </td>
                <td style="font-size:.82rem;">{{ $grp->grado->nombre ?? '—' }} {{ $grp->seccion->nombre ?? '' }}</td>
                <td>{{ $pago->concepto }}</td>
                <td class="fw-bold">RD$ {{ number_format($pago->monto, 2) }}</td>
                <td style="font-size:.82rem;">{{ $pago->fecha_vencimiento->format('d/m/Y') }}</td>
                <td style="font-size:.82rem;">{{ $pago->fecha_pago ? $pago->fecha_pago->format('d/m/Y') : '—' }}</td>
                <td>
                    <span class="badge-estado badge-{{ $pago->estado }}">{{ $pago->estado_label }}</span>
                </td>
                <td style="font-size:.80rem;color:#6b7280;">{{ $pago->metodo_pago ? ucfirst($pago->metodo_pago) : '—' }}</td>
                <td>
                    <div class="d-flex gap-1">
                        @if(in_array($pago->estado, ['pendiente','vencido']))
                        <button class="btn-pagar"
                                onclick="abrirPagar({{ $pago->id }}, '{{ addslashes($pago->concepto) }}')"
                                title="Marcar pagado">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        @endif
                        <a href="{{ route('admin.pagos.edit', $pago) }}" class="btn-sm-outline" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.pagos.destroy', $pago) }}"
                              onsubmit="return confirm('¿Eliminar este registro?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-sm-outline" title="Eliminar" style="border-color:#fca5a5;color:#dc2626;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center py-4 text-muted">No hay registros que coincidan con los filtros.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Paginación --}}
<div class="mt-3 d-flex justify-content-center">
    {{ $pagos->links() }}
</div>

{{-- Modal: Marcar pagado --}}
<div class="modal fade modal-pago" id="modalPagar" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0"><i class="bi bi-cash-coin me-1"></i>Registrar Pago</h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPagar" method="POST">
                @csrf @method('PATCH')
                <div class="modal-body">
                    <p class="text-muted mb-3" style="font-size:.83rem;" id="pagarConcepto"></p>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.82rem;font-weight:600;">Método de pago</label>
                        <select name="metodo_pago" class="form-select form-select-sm" required>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label" style="font-size:.82rem;font-weight:600;">Referencia / No. recibo</label>
                        <input type="text" name="referencia" class="form-control form-control-sm" placeholder="Opcional">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg me-1"></i>Confirmar Pago</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Generar cuotas masivas --}}
<div class="modal fade" id="modalGenerar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header" style="background:var(--primary);color:#fff;border-radius:14px 14px 0 0;">
                <h6 class="modal-title mb-0"><i class="bi bi-lightning-fill me-1"></i>Generar Cuotas Masivas</h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.pagos.generar-cuotas') }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info py-2" style="font-size:.82rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        Se generará una cuota pendiente para cada estudiante activo. No se duplican si ya existe el mismo concepto y fecha.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Concepto</label>
                        <input type="text" name="concepto" class="form-control form-control-sm"
                               value="{{ \App\Helpers\Setting::get('payments_concept','Cuota escolar mensual') }}"
                               placeholder="Ej: Cuota Enero 2026" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Monto (RD$)</label>
                            <input type="number" name="monto" class="form-control form-control-sm" step="0.01" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:.83rem;">Fecha límite pago</label>
                            <input type="date" name="fecha_vencimiento" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Grupo (opcional)</label>
                        <select name="grupo_id" class="form-select form-select-sm">
                            <option value="">Todos los grupos activos</option>
                            @foreach($grupos as $g)
                                <option value="{{ $g->id }}">{{ $g->grado->nombre ?? '' }} {{ $g->seccion->nombre ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-lightning-fill me-1"></i>Generar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function abrirPagar(id, concepto) {
    document.getElementById('formPagar').action = `/admin/pagos/${id}/pagar`;
    document.getElementById('pagarConcepto').textContent = concepto;
    new bootstrap.Modal(document.getElementById('modalPagar')).show();
}

document.getElementById('formPagar').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = this;
    const res  = await fetch(form.action, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: new FormData(form),
    });
    const data = await res.json();
    if (data.ok) {
        bootstrap.Modal.getInstance(document.getElementById('modalPagar')).hide();
        location.reload();
    } else {
        alert('Error al registrar el pago.');
    }
});
</script>
@endpush
