@extends('layouts.admin')
@section('page-title', 'Estado de Cuenta — ' . $matricula->estudiante->nombre_completo)

@push('styles')
<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
.page-header h1 { font-size:1.25rem; font-weight:800; color:var(--primary); margin:0; }

.estudiante-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:1.25rem 1.5rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap; }
.est-avatar { width:52px; height:52px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.3rem; font-weight:800; flex-shrink:0; }
.est-name { font-size:1.1rem; font-weight:700; color:#111827; }
.est-meta { font-size:.8rem; color:#6b7280; }

.totales-bar { display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem; }
.total-chip { background:#fff; border-radius:10px; border:1px solid #e5e7eb; padding:.75rem 1.1rem; flex:1; min-width:140px; }
.total-chip .label { font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; }
.total-chip .value { font-size:1.25rem; font-weight:800; }

.table-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; }
.table-card table { margin:0; }
.table-card thead th { background:#f8fafc; font-size:.73rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; border-bottom:1px solid #e5e7eb; padding:.75rem 1rem; }
.table-card tbody td { font-size:.84rem; padding:.75rem 1rem; vertical-align:middle; border-bottom:1px solid #f3f4f6; }
.table-card tbody tr:last-child td { border-bottom:none; }

.badge-estado { padding:.25rem .65rem; border-radius:20px; font-size:.72rem; font-weight:700; }
.badge-pagado    { background:#d1fae5; color:#065f46; }
.badge-pendiente { background:#fef3c7; color:#92400e; }
.badge-vencido   { background:#fee2e2; color:#991b1b; }
.badge-cancelado { background:#f3f4f6; color:#6b7280; }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1>
        <a href="{{ route('admin.pagos.index') }}" class="text-decoration-none me-2" style="color:#6b7280;">
            <i class="bi bi-arrow-left"></i>
        </a>
        Estado de Cuenta
    </h1>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.pagos.estado-cuenta-pdf', $matricula) }}"
           target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>Estado de Cuenta PDF
        </a>
        <a href="{{ route('admin.pagos.create') }}?matricula={{ $matricula->id }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Nuevo Pago
        </a>
    </div>
</div>

{{-- Info del estudiante --}}
<div class="estudiante-card">
    <div class="est-avatar">{{ strtoupper(substr($matricula->estudiante->nombre, 0, 1)) }}</div>
    <div>
        <div class="est-name">{{ $matricula->estudiante->nombre_completo }}</div>
        <div class="est-meta">
            Matrícula: <strong>{{ $matricula->estudiante->matricula ?? '—' }}</strong>
            &nbsp;·&nbsp;
            Grupo: <strong>{{ $matricula->grupo->grado->nombre ?? '—' }} {{ $matricula->grupo->seccion->nombre ?? '' }}</strong>
        </div>
    </div>
    <div class="ms-auto">
        <a href="{{ route('admin.perfiles.estudiante', $matricula->estudiante) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-person"></i> Ver Perfil
        </a>
    </div>
</div>

{{-- Totales --}}
<div class="totales-bar">
    <div class="total-chip">
        <div class="label">Total Pagado</div>
        <div class="value" style="color:#065f46;">RD$ {{ number_format($totales['pagado'], 2) }}</div>
    </div>
    <div class="total-chip">
        <div class="label">Por Pagar</div>
        <div class="value" style="color:#92400e;">RD$ {{ number_format($totales['pendiente'], 2) }}</div>
    </div>
    <div class="total-chip">
        <div class="label">Total Registros</div>
        <div class="value">{{ $pagos->count() }}</div>
    </div>
</div>

{{-- Tabla --}}
<div class="table-card">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>Concepto</th>
                <th>Monto</th>
                <th>Vencimiento</th>
                <th>Fecha Pago</th>
                <th>Estado</th>
                <th>Método</th>
                <th>Referencia</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($pagos as $pago)
            <tr>
                <td class="fw-semibold">{{ $pago->concepto }}</td>
                <td>RD$ {{ number_format($pago->monto, 2) }}</td>
                <td>{{ $pago->fecha_vencimiento->format('d/m/Y') }}</td>
                <td>{{ $pago->fecha_pago ? $pago->fecha_pago->format('d/m/Y') : '—' }}</td>
                <td><span class="badge-estado badge-{{ $pago->estado }}">{{ $pago->estado_label }}</span></td>
                <td style="color:#6b7280;">{{ $pago->metodo_pago ? ucfirst($pago->metodo_pago) : '—' }}</td>
                <td style="font-size:.78rem;color:#6b7280;">{{ $pago->referencia ?? '—' }}</td>
                <td>
                    <div class="d-flex gap-1">
                        @if($pago->estado === 'pagado')
                        <a href="{{ route('admin.pagos.recibo', $pago) }}" target="_blank"
                           class="btn btn-sm btn-outline-primary py-1" style="font-size:.75rem;" title="Recibo PDF">
                            <i class="bi bi-receipt"></i>
                        </a>
                        @endif
                        @if(in_array($pago->estado, ['pendiente','vencido']))
                        <button class="btn btn-sm btn-success py-1"
                                onclick="abrirPagar({{ $pago->id }}, '{{ addslashes($pago->concepto) }}')"
                                style="font-size:.75rem;">
                            <i class="bi bi-check-lg"></i> Pagar
                        </button>
                        @endif
                        <a href="{{ route('admin.pagos.edit', $pago) }}" class="btn btn-sm btn-outline-secondary py-1" style="font-size:.75rem;">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.pagos.destroy', $pago) }}"
                              onsubmit="return confirm('¿Eliminar este registro?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger py-1" style="font-size:.75rem;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center py-4 text-muted">Sin registros de pagos.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal: Marcar pagado --}}
<div class="modal fade" id="modalPagar" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header py-2" style="background:var(--primary);color:#fff;border-radius:14px 14px 0 0;">
                <h6 class="modal-title mb-0"><i class="bi bi-cash-coin me-1"></i>Registrar Pago</h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPagar" method="POST">
                @csrf @method('PATCH')
                <div class="modal-body">
                    <p class="text-muted mb-3" style="font-size:.83rem;" id="pagarConcepto"></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Método de pago</label>
                        <select name="metodo_pago" class="form-select form-select-sm" required>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Referencia / No. recibo</label>
                        <input type="text" name="referencia" class="form-control form-control-sm" placeholder="Opcional">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg me-1"></i>Confirmar</button>
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
    const res  = await fetch(this.action, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: new FormData(this),
    });
    const data = await res.json();
    if (data.ok) { location.reload(); }
    else { alert('Error al registrar el pago.'); }
});
</script>
@endpush
