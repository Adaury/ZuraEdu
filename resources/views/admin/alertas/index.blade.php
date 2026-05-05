@extends('layouts.admin')

@section('page-title', 'Notificaciones')

@push('styles')
<style>
    .alerta-item {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        transition: background .15s;
        padding: 1rem 1.25rem;
        margin-bottom: .6rem;
    }
    .alerta-item.no-leida { background: #f0f4ff; border-color: #c7d7fd; }
    .alerta-item:hover { background: #f8faff; }
    .nivel-dot {
        width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: 5px;
    }

    [data-theme="dark"] .alerta-item { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .alerta-item.no-leida { background: #0c1f3f; border-color: #1d4ed8; }
    [data-theme="dark"] .alerta-item:hover { background: #162032; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1e3a6e;">
            <i class="bi bi-bell me-2"></i>Notificaciones
        </h4>
        <p class="text-muted mb-0" style="font-size:.875rem;">Alertas y avisos del sistema</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.alertas.pdf') }}" target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.alertas.excel') }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <button class="btn btn-outline-secondary btn-sm" id="btnLeerTodas">
            <i class="bi bi-check2-all me-1"></i>Marcar todas como leídas
        </button>
    </div>
</div>

@if($alertas->isEmpty())
<div class="empty-state-enhanced">
    <div class="empty-illustration"><i class="bi bi-bell-slash"></i></div>
    <div class="empty-title">Sin notificaciones</div>
    <div class="empty-desc">No tienes notificaciones pendientes en este momento.</div>
</div>
@else
<div id="alertas-list">
    @foreach($alertas as $alerta)
    @php
        $niveles = [
            'danger'  => ['bg' => '#fee2e2', 'dot' => '#ef4444', 'icon' => 'bi-exclamation-triangle-fill text-danger'],
            'warning' => ['bg' => '#fffbeb', 'dot' => '#f59e0b', 'icon' => 'bi-exclamation-circle-fill text-warning'],
            'success' => ['bg' => '#f0fdf4', 'dot' => '#22c55e', 'icon' => 'bi-check-circle-fill text-success'],
            'info'    => ['bg' => '#eff6ff', 'dot' => '#3b82f6', 'icon' => 'bi-info-circle-fill text-info'],
        ];
        $n = $niveles[$alerta->nivel] ?? $niveles['info'];
    @endphp
    <div class="alerta-item {{ !$alerta->leida ? 'no-leida' : '' }}" id="alerta-{{ $alerta->id }}">
        <div class="d-flex align-items-start gap-3">
            <div class="nivel-dot" style="background:{{ $n['dot'] }};margin-top:6px;"></div>
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <span class="fw-bold" style="font-size:.88rem;color:#1e293b;">{{ $alerta->titulo }}</span>
                    @if(!$alerta->leida)
                    <span class="badge bg-primary" style="font-size:.6rem;">Nueva</span>
                    @endif
                    <span class="badge text-bg-light" style="font-size:.65rem;">
                        {{ \App\Models\AlertaSistema::tiposLabels()[$alerta->tipo] ?? $alerta->tipo }}
                    </span>
                </div>
                <p class="mb-1" style="font-size:.83rem;color:#4b5563;">{{ $alerta->mensaje }}</p>
                <span class="text-muted" style="font-size:.75rem;">
                    <i class="bi bi-clock me-1"></i>{{ $alerta->created_at->diffForHumans() }}
                </span>
            </div>
            <div class="d-flex gap-1 flex-shrink-0">
                @if(!$alerta->leida)
                <button class="btn btn-sm btn-outline-primary btn-leer" data-id="{{ $alerta->id }}"
                        style="font-size:.72rem;padding:.2rem .5rem;">
                    <i class="bi bi-check2"></i>
                </button>
                @endif
                <button class="btn btn-sm btn-outline-danger btn-borrar" data-id="{{ $alerta->id }}"
                        style="font-size:.72rem;padding:.2rem .5rem;">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{ $alertas->links() }}
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Marcar una como leída
    document.querySelectorAll('.btn-leer').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            fetch(`/admin/alertas/${id}/leer`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
            }).then(() => {
                const item = document.getElementById('alerta-' + id);
                item.classList.remove('no-leida');
                this.remove();
            });
        });
    });

    // Borrar alerta
    document.querySelectorAll('.btn-borrar').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            fetch(`/admin/alertas/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            }).then(() => {
                document.getElementById('alerta-' + id).remove();
            });
        });
    });

    // Marcar todas como leídas
    document.getElementById('btnLeerTodas')?.addEventListener('click', function() {
        fetch('/admin/alertas/leer-todas', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }).then(() => {
            document.querySelectorAll('.alerta-item').forEach(el => el.classList.remove('no-leida'));
            document.querySelectorAll('.btn-leer').forEach(btn => btn.remove());
            document.querySelectorAll('.badge.bg-primary').forEach(b => b.remove());
        });
    });
});
</script>
@endpush
