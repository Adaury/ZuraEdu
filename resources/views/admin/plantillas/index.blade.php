@extends('layouts.admin')
@section('page-title', 'Plantillas de Mensajes')

@push('styles')
<style>
    .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
    .page-header h1 { font-size:1.45rem; font-weight:800; color:var(--primary); margin:0; }
    .card-panel { background:#fff; border-radius:14px; border:1px solid #e5e7eb; padding:1.5rem; margin-bottom:1.5rem; }
    .section-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--primary); border-bottom:2px solid var(--primary); padding-bottom:.4rem; margin-bottom:1.1rem; }
    .plantilla-tabs { display:flex; gap:.5rem; margin-bottom:1.25rem; }
    .plantilla-tab { padding:.55rem 1.1rem; border-radius:10px; border:1.5px solid #e5e7eb; background:#fff; font-weight:600; font-size:.85rem; cursor:pointer; color:#475569; }
    .plantilla-tab.active { border-color:var(--primary); background:#f0f7ff; color:var(--primary); }
    .plantilla-fila { display:flex; align-items:center; justify-content:space-between; padding:.75rem 1rem; border-radius:10px; background:#f8fafc; border:1px solid #e5e7eb; margin-bottom:.5rem; }
    .plantilla-fila-label { font-size:.88rem; font-weight:600; }
    .badge-personalizada { background:#dcfce7; color:#166534; }
    .badge-predeterminada { background:#f1f5f9; color:#64748b; }

    [data-theme="dark"] .card-panel { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .plantilla-tab { background:#1e293b; border-color:#334155; color:#94a3b8; }
    [data-theme="dark"] .plantilla-tab.active { background:#162032; }
    [data-theme="dark"] .plantilla-fila { background:#162032; border-color:#334155; }
    [data-theme="dark"] .badge-personalizada { background:rgba(34,197,94,.15); color:#4ade80; }
    [data-theme="dark"] .badge-predeterminada { background:rgba(148,163,184,.15); color:#94a3b8; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1><i class="bi bi-chat-square-text me-2"></i>Plantillas de Mensajes</h1>
        <p class="text-muted small mb-0">Personaliza el texto de los mensajes automáticos de WhatsApp y correo. Lo que no personalices sigue enviándose exactamente como hoy.</p>
    </div>
    <a href="{{ route('admin.sistema.whatsapp') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-toggle-on me-1"></i>Encendido/apagado de notificaciones
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 mb-3">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="plantilla-tabs">
    <button type="button" class="plantilla-tab active" data-canal="whatsapp" onclick="mostrarCanal('whatsapp')">
        <i class="bi bi-whatsapp me-1"></i>WhatsApp
    </button>
    <button type="button" class="plantilla-tab" data-canal="email" onclick="mostrarCanal('email')">
        <i class="bi bi-envelope me-1"></i>Correo electrónico
    </button>
</div>

@foreach(['whatsapp', 'email'] as $canalTab)
<div class="canal-panel" data-canal-panel="{{ $canalTab }}" style="{{ $canalTab !== 'whatsapp' ? 'display:none;' : '' }}">
    @foreach($grupos as $grupoKey => $grupoLabel)
        @php($items = $catalogo->where('canal', $canalTab)->where('grupo', $grupoKey))
        @continue($items->isEmpty())
        <div class="card-panel">
            <div class="section-title">{{ $grupoLabel }}</div>
            @foreach($items as $item)
                <div class="plantilla-fila">
                    <div class="plantilla-fila-label">{{ $item['label'] }}</div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill {{ $item['personalizada'] ? 'badge-personalizada' : 'badge-predeterminada' }}">
                            {{ $item['personalizada'] ? 'Personalizada' : 'Predeterminada' }}
                        </span>
                        @if($item['personalizada'])
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-toggle-plantilla"
                                    data-evento="{{ $item['evento'] }}" data-canal="{{ $item['canal'] }}"
                                    data-activa="{{ $item['activa'] ? '1' : '0' }}">
                                {{ $item['activa'] ? 'Activa' : 'Inactiva' }}
                            </button>
                        @endif
                        <a href="{{ route('admin.plantillas.edit', [$item['evento'], $item['canal']]) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</div>
@endforeach

@endsection

@push('scripts')
<script>
function mostrarCanal(canal) {
    document.querySelectorAll('.plantilla-tab').forEach(t => t.classList.toggle('active', t.dataset.canal === canal));
    document.querySelectorAll('.canal-panel').forEach(p => p.style.display = (p.dataset.canalPanel === canal ? '' : 'none'));
}

document.querySelectorAll('.btn-toggle-plantilla').forEach(btn => {
    btn.addEventListener('click', function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        fetch(`/admin/plantillas/${this.dataset.evento}/${this.dataset.canal}/activa`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        })
            .then(r => r.json())
            .then(data => {
                if (!data.ok) throw new Error();
                this.dataset.activa = data.activa ? '1' : '0';
                this.textContent = data.activa ? 'Activa' : 'Inactiva';
                if (window.SGEToast) window.SGEToast.show('Estado actualizado', 'success');
            })
            .catch(() => { if (window.SGEToast) window.SGEToast.show('No se pudo actualizar', 'error'); });
    });
});
</script>
@endpush
