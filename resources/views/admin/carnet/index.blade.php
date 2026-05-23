@extends('layouts.admin')
@section('page-title', 'ZuraEdu Carnet+')

@push('styles')
<style>
    .carnet-card {
        background: #fff;
        border: 1.5px solid #e5e7eb;
        border-radius: 12px;
        padding: 1rem;
        transition: border-color .15s, box-shadow .15s;
    }
    .carnet-card:hover { border-color: var(--primary); box-shadow: 0 4px 14px rgba(30,58,110,.1); }
    .carnet-avatar {
        width: 48px; height: 48px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e5e7eb;
        background: #f3f4f6;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 1.1rem; color: var(--primary);
        flex-shrink: 0;
    }
    .badge-activo    { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .badge-suspendido{ background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .badge-vencido   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    [data-theme="dark"] .carnet-card { background: #1e293b; border-color: #334155; }
</style>
@endpush

@section('content')

<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Carnet+'],
]" />

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-person-badge-fill me-2"></i>ZuraEdu Carnet+
        </h4>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">
            Identidad digital · QR · Acceso inteligente
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.carnet.checkin') }}" class="btn btn-success btn-sm" target="_blank">
            <i class="bi bi-qr-code-scan me-1"></i>Modo Kiosco
        </a>
        <a href="{{ route('admin.carnet.reportes') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-bar-chart me-1"></i>Reportes
        </a>
        <a href="{{ route('admin.carnet.historial') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-clock-history me-1"></i>Historial
        </a>
        <form action="{{ route('admin.carnet.generar-masivo') }}" method="POST" class="d-inline">
            @csrf
            <button class="btn btn-primary btn-sm" onclick="return confirm('¿Generar carnets para todos los estudiantes activos?')">
                <i class="bi bi-lightning me-1"></i>Generar Masivo
            </button>
        </form>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- KPIs rápidos --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div style="font-size:1.6rem;font-weight:800;color:var(--primary)">{{ $carnets->total() }}</div>
            <div style="font-size:.8rem;color:#6b7280;font-weight:600;">Total Carnets</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div style="font-size:1.6rem;font-weight:800;color:#16a34a">
                {{ $carnets->getCollection()->where('estado','activo')->count() }}
            </div>
            <div style="font-size:.8rem;color:#6b7280;font-weight:600;">Activos</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div style="font-size:1.6rem;font-weight:800;color:#d97706">
                {{ $carnets->getCollection()->where('estado','suspendido')->count() }}
            </div>
            <div style="font-size:.8rem;color:#6b7280;font-weight:600;">Suspendidos</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div style="font-size:1.6rem;font-weight:800;color:#6366f1">
                @php
                    use App\Models\CarnetAcceso;
                    echo CarnetAcceso::whereDate('created_at', today())->where('tipo_evento','entrada')->count();
                @endphp
            </div>
            <div style="font-size:.8rem;color:#6b7280;font-weight:600;">Entradas hoy</div>
        </div>
    </div>
</div>

{{-- Filtros --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <form class="d-flex gap-2 flex-wrap align-items-center">
            <input type="text" name="search" class="form-control form-control-sm" style="max-width:200px;"
                   placeholder="Buscar nombre..." value="{{ request('search') }}">
            <select name="estado" class="form-select form-select-sm" style="max-width:150px;">
                <option value="">Todos los estados</option>
                <option value="activo"     {{ request('estado')=='activo'     ? 'selected':'' }}>Activo</option>
                <option value="suspendido" {{ request('estado')=='suspendido' ? 'selected':'' }}>Suspendido</option>
                <option value="vencido"    {{ request('estado')=='vencido'    ? 'selected':'' }}>Vencido</option>
            </select>
            <button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filtrar</button>
            @if(request()->hasAny(['search','estado']))
            <a href="{{ route('admin.carnet.index') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
            @endif
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="font-size:.8rem;padding:.7rem 1rem;">Estudiante</th>
                        <th style="font-size:.8rem;">Carnet</th>
                        <th style="font-size:.8rem;">Grupo</th>
                        <th style="font-size:.8rem;">Estado</th>
                        <th style="font-size:.8rem;">Último acceso</th>
                        <th style="font-size:.8rem;text-align:right;padding-right:1rem;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($carnets as $carnet)
                <tr>
                    <td style="padding:.65rem 1rem;">
                        <div class="d-flex align-items-center gap-2">
                            @if($carnet->user?->foto)
                            <img src="{{ asset('storage/'.$carnet->user->foto) }}" class="carnet-avatar">
                            @else
                            <div class="carnet-avatar">{{ strtoupper(substr($carnet->nombre_completo,0,1)) }}</div>
                            @endif
                            <div>
                                <div class="fw-semibold" style="font-size:.9rem;">{{ $carnet->nombre_completo }}</div>
                                <div class="text-muted" style="font-size:.76rem;">{{ $carnet->user?->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge rounded-pill px-2 py-1" style="background:#e0e7ff;color:#3730a3;font-family:monospace;font-size:.78rem;">
                            {{ $carnet->numero_carnet }}
                        </span>
                    </td>
                    <td style="font-size:.84rem;">
                        {{ $carnet->matricula?->grupo?->nombre_completo ?? '—' }}
                    </td>
                    <td>
                        <span class="badge rounded-pill px-2 py-1 badge-{{ $carnet->estado }}" style="font-size:.75rem;">
                            {{ ucfirst($carnet->estado) }}
                        </span>
                    </td>
                    <td style="font-size:.82rem;color:#6b7280;">
                        @php $ultimo = $carnet->accesos()->latest()->first(); @endphp
                        {{ $ultimo ? $ultimo->created_at->diffForHumans() : 'Sin registros' }}
                    </td>
                    <td style="text-align:right;padding-right:1rem;">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('admin.carnet.pdf', $carnet) }}" target="_blank"
                               class="btn btn-outline-secondary btn-sm" title="PDF Carnet">
                                <i class="bi bi-id-card"></i>
                            </a>
                            <form action="{{ route('admin.carnet.suspender', $carnet) }}" method="POST">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm {{ $carnet->estado === 'activo' ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                        title="{{ $carnet->estado === 'activo' ? 'Suspender' : 'Activar' }}">
                                    <i class="bi bi-{{ $carnet->estado === 'activo' ? 'pause-circle' : 'play-circle' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('admin.carnet.destroy', $carnet) }}" method="POST"
                                  onsubmit="return confirm('¿Eliminar este carnet?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-person-badge" style="font-size:2.5rem;opacity:.25;display:block;margin-bottom:.5rem;"></i>
                        No hay carnets generados aún.
                        <br>
                        <form action="{{ route('admin.carnet.generar-masivo') }}" method="POST" class="mt-2">
                            @csrf
                            <button class="btn btn-primary btn-sm">
                                <i class="bi bi-lightning me-1"></i>Generar ahora
                            </button>
                        </form>
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($carnets->hasPages())
    <div class="card-footer bg-white border-top-0 py-2">
        {{ $carnets->links() }}
    </div>
    @endif
</div>

@endsection
