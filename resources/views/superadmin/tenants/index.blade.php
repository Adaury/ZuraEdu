@extends('layouts.superadmin')
@section('title', 'Instituciones')
@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-building-fill me-2" style="color:#6366f1;"></i>Panel de la Plataforma</h4>
        <p class="text-muted small mb-0">Gestión global de instituciones registradas en ZuraEdu</p>
    </div>
    <a href="{{ route('superadmin.tenants.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Nueva Institución
    </a>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    @foreach([
        ['Total', $stats['total'], 'bi-building', '#6366f1'],
        ['Activos', $stats['activos'], 'bi-check-circle-fill', '#22c55e'],
        ['En Prueba', $stats['prueba'], 'bi-hourglass-split', '#f59e0b'],
        ['Suspendidos', $stats['suspendidos'], 'bi-x-circle-fill', '#ef4444'],
        ['Premium', $stats['premium'], 'bi-star-fill', '#f59e0b'],
        ['Pro', $stats['pro'], 'bi-star-half', '#3b82f6'],
        ['Free', $stats['free'], 'bi-circle', '#94a3b8'],
    ] as [$label, $val, $icon, $color])
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-body py-3 px-3 d-flex align-items-center gap-3">
                <div style="width:42px;height:42px;background:{{ $color }}22;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi {{ $icon }}" style="font-size:1.1rem;color:{{ $color }};"></i>
                </div>
                <div>
                    <div class="fw-bold" style="font-size:1.3rem;line-height:1;">{{ $val }}</div>
                    <div class="text-muted" style="font-size:.75rem;">{{ $label }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Filtros --}}
<div class="card border-0 shadow-sm mb-3" style="border-radius:14px;">
    <div class="card-body py-2 px-3">
        <form class="row g-2 align-items-center" method="GET">
            <div class="col-md-5">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Buscar por nombre o dominio...">
            </div>
            <div class="col-md-3">
                <select name="estado" class="form-select form-select-sm">
                    <option value="">Todos los estados</option>
                    @foreach(['activo'=>'Activo','prueba'=>'En Prueba','suspendido'=>'Suspendido','cancelado'=>'Cancelado'] as $v=>$l)
                    <option value="{{ $v }}" @selected(request('estado')===$v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="plan" class="form-select form-select-sm">
                    <option value="">Todos los planes</option>
                    @foreach(['free'=>'Free','pro'=>'Pro','premium'=>'Premium'] as $v=>$l)
                    <option value="{{ $v }}" @selected(request('plan')===$v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button class="btn btn-primary btn-sm flex-1">Filtrar</button>
                <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-outline-secondary btn-sm">✕</a>
            </div>
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead style="background:#f8fafc;font-size:.78rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;">
                <tr>
                    <th class="px-3 py-3">Institución</th>
                    <th>Dominio</th>
                    <th>Plan</th>
                    <th>Estado</th>
                    <th>Suscripción</th>
                    <th class="text-end pe-3">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @forelse($tenants as $t)
            <tr>
                <td class="px-3">
                    <div class="d-flex align-items-center gap-2">
                        @if($t->logo_url)
                            <img src="{{ $t->logo_url }}" style="width:32px;height:32px;border-radius:8px;object-fit:cover;">
                        @else
                            <div style="width:32px;height:32px;border-radius:8px;background:{{ $t->color_primario }};color:#fff;font-size:.75rem;font-weight:800;display:flex;align-items:center;justify-content:center;">
                                {{ strtoupper(substr($t->nombre_institucion,0,2)) }}
                            </div>
                        @endif
                        <div>
                            <div class="fw-semibold" style="font-size:.88rem;">{{ $t->nombre_institucion }}</div>
                            <small class="text-muted">{{ ucfirst($t->tipo) }}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <code style="font-size:.78rem;">{{ $t->dominio }}.zuraedu.com</code>
                    @if($t->dominio_personalizado)
                    <br><small class="text-muted">{{ $t->dominio_personalizado }}</small>
                    @endif
                </td>
                <td>
                    @php $planColors = ['free'=>'secondary','pro'=>'primary','premium'=>'warning']; @endphp
                    <span class="badge bg-{{ $planColors[$t->plan] ?? 'secondary' }}">{{ strtoupper($t->plan) }}</span>
                </td>
                <td>
                    @php $estadoColors = ['activo'=>'success','prueba'=>'warning','suspendido'=>'danger','cancelado'=>'dark']; @endphp
                    <span class="badge bg-{{ $estadoColors[$t->estado] ?? 'secondary' }}" style="font-size:.72rem;">
                        {{ $t->label_estado }}
                    </span>
                </td>
                <td style="font-size:.82rem;">
                    @if($t->fecha_vencimiento)
                        @php
                            $dias = max(0, (int) now()->diffInDays($t->fecha_vencimiento, false));
                            $vencido = $t->estaVencido();
                        @endphp
                        @if($vencido)
                            <span class="badge bg-danger" style="font-size:.7rem;">Vencido</span>
                            <div class="text-danger" style="font-size:.75rem;">{{ $t->fecha_vencimiento->format('d/m/Y') }}</div>
                        @elseif($dias <= 7)
                            <span class="badge bg-warning text-dark" style="font-size:.7rem;">{{ $dias }}d restantes</span>
                            <div class="text-muted" style="font-size:.75rem;">{{ $t->fecha_vencimiento->format('d/m/Y') }}</div>
                        @else
                            <span class="badge bg-success" style="font-size:.7rem;">{{ $dias }}d restantes</span>
                            <div class="text-muted" style="font-size:.75rem;">{{ $t->fecha_vencimiento->format('d/m/Y') }}</div>
                        @endif
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td class="text-end pe-3">
                    <div class="d-flex gap-1 justify-content-end">
                        <a href="{{ route('superadmin.tenants.show', $t) }}" class="btn btn-sm btn-outline-primary" title="Ver detalles">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('superadmin.tenants.edit', $t) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('superadmin.tenants.enter-panel', $t) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm" style="background:#4f46e5;color:#fff;border:none;" title="Entrar al panel de esta institución">
                                <i class="bi bi-box-arrow-in-right"></i>
                            </button>
                        </form>
                        <a href="{{ route('superadmin.tenants.show', $t) }}#pago" class="btn btn-sm btn-outline-success" title="Registrar pago">
                            <i class="bi bi-credit-card"></i>
                        </a>
                        <form method="POST" action="{{ route('superadmin.tenants.toggle-estado', $t) }}" class="d-inline"
                            onsubmit="return confirm(this.dataset.msg)"
                            data-msg="{{ $t->estado==='activo' ? 'Suspender acceso a '.$t->nombre_institucion.'?' : 'Activar acceso a '.$t->nombre_institucion.'?' }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-{{ $t->estado==='activo'?'warning':'success' }}"
                                title="{{ $t->estado==='activo'?'Suspender':'Activar' }}">
                                <i class="bi bi-{{ $t->estado==='activo'?'pause-circle':'play-circle' }}"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-5 text-muted">No hay instituciones registradas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($tenants->hasPages())
    <div class="card-footer bg-white border-0 py-2">
        {{ $tenants->withQueryString()->links() }}
    </div>
    @endif
</div>

@endsection
