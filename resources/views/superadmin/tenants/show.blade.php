@extends('layouts.superadmin')
@section('title', $tenant->nombre_institucion)
@section('content')

@php
    $estadoColors = ['activo'=>'success','prueba'=>'warning','suspendido'=>'danger','cancelado'=>'dark'];
    $planColors   = ['free'=>'secondary','pro'=>'primary','premium'=>'warning'];
    $suscActiva   = $tenant->subscriptionActiva();
    $diasRest     = $suscActiva ? $suscActiva->diasRestantes() : 0;
    $estaVencida  = $tenant->estaVencido();
@endphp

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div class="d-flex align-items-center gap-3">
        <div style="width:56px;height:56px;border-radius:14px;background:{{ $tenant->color_primario }};color:#fff;font-size:1.3rem;font-weight:900;display:flex;align-items:center;justify-content:center;">
            {{ strtoupper(substr($tenant->nombre_institucion,0,2)) }}
        </div>
        <div>
            <h4 class="fw-bold mb-1">{{ $tenant->nombre_institucion }}</h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-{{ $estadoColors[$tenant->estado] ?? 'secondary' }}">{{ $tenant->label_estado }}</span>
                <span class="badge bg-{{ $planColors[$tenant->plan] ?? 'secondary' }}">{{ strtoupper($tenant->plan) }}</span>
                <code style="font-size:.75rem;">{{ $tenant->dominio }}.zuraedu.com</code>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ $tenant->url }}" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-box-arrow-up-right me-1"></i>Abrir
        </a>
        <a href="{{ route('superadmin.tenants.edit', $tenant) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <form method="POST" action="{{ route('superadmin.tenants.toggle-estado', $tenant) }}" class="d-inline">
            @csrf
            <button class="btn btn-sm btn-{{ $tenant->estado==='activo' ? 'warning' : 'success' }}">
                <i class="bi bi-{{ $tenant->estado==='activo' ? 'pause-circle' : 'play-circle' }} me-1"></i>
                {{ $tenant->estado==='activo' ? 'Suspender' : 'Activar' }}
            </button>
        </form>
        <form method="POST" action="{{ route('superadmin.tenants.enter-panel', $tenant) }}" class="d-inline">
            @csrf
            <button class="btn btn-sm btn-indigo"
                style="background:#4f46e5;color:#fff;border:none;"
                title="Administrar esta institución como si fueras su admin">
                <i class="bi bi-box-arrow-in-right me-1"></i>Entrar al panel
            </button>
        </form>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalPago">
            <i class="bi bi-credit-card me-1"></i>Registrar Pago
        </button>
    </div>
</div>

{{-- Accesos rápidos al panel --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;background:linear-gradient(135deg,#f8fafc,#f0f4ff);border:1px solid #e0e7ff!important;">
    <div class="card-body py-3 px-4">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-lightning-charge-fill text-warning"></i>
            <span class="fw-bold" style="font-size:.82rem;color:#4338ca;">Acceso rápido al panel de {{ $tenant->nombre_institucion }}</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <form method="POST" action="{{ route('superadmin.tenants.enter-panel', $tenant) }}" class="d-inline">
                @csrf
                <input type="hidden" name="destino" value="/admin/dashboard">
                <button class="btn btn-sm" style="background:#4f46e5;color:#fff;border:none;border-radius:8px;">
                    <i class="bi bi-speedometer2 me-1"></i>Dashboard
                </button>
            </form>
            <form method="POST" action="{{ route('superadmin.tenants.enter-panel', $tenant) }}" class="d-inline">
                @csrf
                <input type="hidden" name="destino" value="/admin/homepage">
                <button class="btn btn-sm" style="background:#0891b2;color:#fff;border:none;border-radius:8px;">
                    <i class="bi bi-palette-fill me-1"></i>Personalizar homepage
                </button>
            </form>
            <form method="POST" action="{{ route('superadmin.tenants.enter-panel', $tenant) }}" class="d-inline">
                @csrf
                <input type="hidden" name="destino" value="/admin/estudiantes">
                <button class="btn btn-sm" style="background:#059669;color:#fff;border:none;border-radius:8px;">
                    <i class="bi bi-people-fill me-1"></i>Estudiantes
                </button>
            </form>
            <form method="POST" action="{{ route('superadmin.tenants.enter-panel', $tenant) }}" class="d-inline">
                @csrf
                <input type="hidden" name="destino" value="/admin/docentes">
                <button class="btn btn-sm" style="background:#d97706;color:#fff;border:none;border-radius:8px;">
                    <i class="bi bi-person-badge-fill me-1"></i>Docentes
                </button>
            </form>
            <form method="POST" action="{{ route('superadmin.tenants.enter-panel', $tenant) }}" class="d-inline">
                @csrf
                <input type="hidden" name="destino" value="/admin/reportes">
                <button class="btn btn-sm" style="background:#7c3aed;color:#fff;border:none;border-radius:8px;">
                    <i class="bi bi-bar-chart-fill me-1"></i>Reportes
                </button>
            </form>
            <form method="POST" action="{{ route('superadmin.tenants.enter-panel', $tenant) }}" class="d-inline">
                @csrf
                <input type="hidden" name="destino" value="/admin/bachillerato-tecnico">
                <button class="btn btn-sm" style="background:#1e3a6e;color:#fff;border:none;border-radius:8px;">
                    <i class="bi bi-mortarboard-fill me-1"></i>Bachillerato Técnico
                </button>
            </form>
            <a href="{{ $tenant->url }}" target="_blank"
               class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                <i class="bi bi-globe me-1"></i>Ver página pública
            </a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Stats de uso --}}
<div class="row g-3 mb-4">
    @foreach([
        ['Estudiantes', $stats['estudiantes'], 'bi-people-fill', '#3b82f6', $tenant->max_estudiantes],
        ['Docentes',    $stats['docentes'],    'bi-person-badge-fill', '#22c55e', $tenant->max_docentes],
        ['Usuarios',    $stats['usuarios'],    'bi-person-fill', '#f59e0b', $tenant->max_usuarios ?? 999],
    ] as [$label, $val, $icon, $color, $max])
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi {{ $icon }}" style="color:{{ $color }};font-size:1.1rem;"></i>
                    <span class="fw-semibold small">{{ $label }}</span>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <span style="font-size:1.8rem;font-weight:800;color:{{ $color }};">{{ $val }}</span>
                    <span class="text-muted small">/ {{ number_format($max) }}</span>
                </div>
                <div class="progress mt-2" style="height:5px;">
                    <div class="progress-bar" style="width:{{ $max>0?min(100,round($val/$max*100)):0 }}%;background:{{ $color }};border-radius:4px;"></div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-3">

    {{-- Columna izquierda --}}
    <div class="col-lg-5">

        {{-- Suscripción actual --}}
        <div class="card border-0 shadow-sm mb-3" style="border-radius:16px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-credit-card-2-front me-2 text-primary"></i>Suscripción</h6>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalPago">
                        <i class="bi bi-plus me-1"></i>Nuevo pago
                    </button>
                </div>

                @if($suscActiva)
                <div class="rounded-3 p-3 mb-3" style="background:{{ $estaVencida ? '#fee2e2' : '#f0fdf4' }};border:1px solid {{ $estaVencida ? '#fecaca' : '#bbf7d0' }};">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-semibold" style="font-size:.9rem;">
                            {{ strtoupper($suscActiva->plan?->slug ?? $tenant->plan) }}
                            · {{ ucfirst($suscActiva->ciclo) }}
                        </span>
                        <span class="badge bg-{{ $estaVencida ? 'danger' : 'success' }}">
                            {{ $estaVencida ? 'Vencida' : 'Activa' }}
                        </span>
                    </div>
                    <div class="d-flex gap-3 flex-wrap" style="font-size:.82rem;color:#374151;">
                        <span><i class="bi bi-calendar-check me-1"></i>{{ $suscActiva->fecha_inicio->format('d/m/Y') }}</span>
                        <span><i class="bi bi-calendar-x me-1"></i>{{ $suscActiva->fecha_fin->format('d/m/Y') }}</span>
                        <span><i class="bi bi-cash me-1"></i>$ {{ number_format($suscActiva->monto_pagado, 2) }}</span>
                    </div>
                    @if(! $estaVencida)
                    <div class="mt-2">
                        <div class="d-flex justify-content-between" style="font-size:.76rem;color:#6b7280;">
                            <span>{{ $diasRest }} días restantes</span>
                            <span>{{ $suscActiva->fecha_fin->format('d M Y') }}</span>
                        </div>
                        <div class="progress mt-1" style="height:4px;">
                            @php
                                $total = $suscActiva->fecha_inicio->diffInDays($suscActiva->fecha_fin);
                                $pct   = $total > 0 ? round(($total - $diasRest) / $total * 100) : 0;
                            @endphp
                            <div class="progress-bar bg-success" style="width:{{ $pct }}%;border-radius:4px;"></div>
                        </div>
                    </div>
                    @endif
                </div>
                @else
                <div class="alert alert-warning py-2 mb-3" style="font-size:.85rem;">
                    <i class="bi bi-exclamation-triangle me-2"></i>Sin suscripción activa.
                    @if($tenant->fecha_vencimiento && $tenant->estaVencido())
                        Venció el {{ $tenant->fecha_vencimiento->format('d/m/Y') }}.
                    @endif
                </div>
                @endif

                {{-- Historial de suscripciones --}}
                @php $historial = $tenant->subscriptions->sortByDesc('created_at')->take(5); @endphp
                @if($historial->count() > 1)
                <div>
                    <p class="text-muted mb-2" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;">Historial</p>
                    @foreach($historial->skip(1) as $sub)
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom" style="font-size:.8rem;">
                        <div>
                            <span class="fw-semibold">{{ strtoupper($sub->plan?->slug ?? '?') }}</span>
                            <span class="text-muted ms-2">{{ $sub->fecha_inicio->format('d/m/Y') }} → {{ $sub->fecha_fin->format('d/m/Y') }}</span>
                        </div>
                        <span class="badge bg-{{ in_array($sub->estado,['activa','prueba'])?'success':'secondary' }}" style="font-size:.65rem;">
                            {{ ucfirst($sub->estado) }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- Info general --}}
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-info"></i>Información</h6>
                @foreach([
                    ['Tipo',        ucfirst($tenant->tipo)],
                    ['Ciudad',      $tenant->ciudad ?? '—'],
                    ['Email',       $tenant->email_contacto ?? '—'],
                    ['Teléfono',    $tenant->telefono_contacto ?? '—'],
                    ['Registro',    $tenant->fecha_registro?->format('d/m/Y') ?? '—'],
                    ['Vencimiento', $tenant->fecha_vencimiento?->format('d/m/Y') ?? 'Sin límite'],
                    ['Dominio',     $tenant->dominio . '.zuraedu.com'],
                ] as [$l, $v])
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted small">{{ $l }}</span>
                    <span class="fw-semibold small">{{ $v }}</span>
                </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- Columna derecha: Módulos / Features --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-grid-3x3-gap me-2 text-purple" style="color:#6366f1;"></i>Módulos del sistema</h6>
                    <small class="text-muted">Click para activar/desactivar</small>
                </div>

                @php
                    $categorias = [
                        'Académico'      => ['asistencia','calificaciones','boletines','reportes','competencias','horarios'],
                        'Portales'       => ['portal_padre','portal_estudiante','portal_docente'],
                        'Comunicación'   => ['comunicados','calendario','whatsapp','classroom'],
                        'Gestión'        => ['pagos','admisiones','nomina','biblioteca','inventario','disciplina'],
                        'Complementario' => ['tutorias','seguimiento_social','gamificacion','proyectos','reconocimientos','evaluaciones_docentes','area_tecnica','modo_publico','cafeteria','transporte','salud','reuniones'],
                    ];
                @endphp

                @foreach($categorias as $cat => $claves)
                <div class="mb-3">
                    <p class="text-muted mb-2" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;font-weight:600;">{{ $cat }}</p>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($claves as $key)
                        @php
                            $f = $featureMap[$key] ?? null;
                            $activo = $f?->activo ?? false;
                            $label = $features[$key] ?? $key;
                        @endphp
                        <form method="POST" action="{{ route('superadmin.tenants.toggle-feature', $tenant) }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="feature" value="{{ $key }}">
                            <button type="submit"
                                class="btn btn-sm {{ $activo ? 'btn-success' : 'btn-outline-secondary' }}"
                                style="font-size:.72rem;padding:.28rem .65rem;border-radius:20px;"
                                title="{{ $activo ? 'Clic para desactivar' : 'Clic para activar' }}">
                                <i class="bi bi-{{ $activo ? 'check-lg' : 'x' }} me-1"></i>{{ $label }}
                            </button>
                        </form>
                        @endforeach
                    </div>
                </div>
                @endforeach

            </div>
        </div>
    </div>

</div>

{{-- Nivel Inicial (solo SuperAdmin) --}}
@php
    $nivelInicialGrados = \App\Models\Grado::withoutTenant()
        ->where('tenant_id', $tenant->id)
        ->where('ciclo', 'inicial')
        ->get()
        ->keyBy('nombre');

    $gradosIniciales = [
        'prekinder' => ['nombre' => 'Pre-Kinder', 'icon' => 'bi-stars',          'desc' => '3 – 4 años'],
        'kinder'    => ['nombre' => 'Kinder',     'icon' => 'bi-balloon-heart-fill', 'desc' => '4 – 6 años'],
    ];
@endphp
<div class="card border-0 shadow-sm mb-4" style="border-radius:16px;border:2px solid #fef3c7!important;">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-1">
            <h6 class="fw-bold mb-0">
                <i class="bi bi-stars me-2" style="color:#f59e0b;"></i>Nivel Inicial
            </h6>
            <span class="badge text-dark" style="background:#fef3c7;font-size:.68rem;border:1px solid #fde68a;">
                Solo SuperAdmin
            </span>
        </div>
        <p class="text-muted mb-3" style="font-size:.82rem;">
            Habilita Pre-Kinder y Kinder para esta institución. Estos grados no aparecen en el wizard de configuración del administrador.
        </p>

        @if(session('success'))
        <div class="alert alert-success py-2 mb-3" style="font-size:.82rem;">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        </div>
        @endif

        <div class="d-flex gap-3 flex-wrap">
            @foreach($gradosIniciales as $tipo => $info)
            @php
                $g      = $nivelInicialGrados[$info['nombre']] ?? null;
                $activo = $g && $g->activo;
            @endphp
            <form method="POST"
                  action="{{ route('superadmin.tenants.nivel-inicial.toggle', [$tenant, $tipo]) }}">
                @csrf
                <button type="submit"
                    class="btn {{ $activo ? 'btn-success' : 'btn-outline-secondary' }}"
                    style="border-radius:12px;padding:.65rem 1.4rem;font-size:.88rem;">
                    <i class="bi {{ $info['icon'] }} me-2"></i>
                    <strong>{{ $info['nombre'] }}</strong>
                    <span class="ms-2 text-{{ $activo ? 'white' : 'muted' }}" style="font-size:.75rem;">
                        {{ $info['desc'] }}
                    </span>
                    @if($activo)
                    <span class="badge bg-white text-success ms-2" style="font-size:.65rem;">
                        <i class="bi bi-check2"></i> Activo
                    </span>
                    @else
                    <span class="badge bg-secondary ms-2" style="font-size:.65rem;opacity:.6;">
                        Inactivo
                    </span>
                    @endif
                </button>
            </form>
            @endforeach
        </div>

        @if($nivelInicialGrados->count())
        <p class="text-muted mt-2 mb-0" style="font-size:.75rem;">
            <i class="bi bi-info-circle me-1"></i>
            Clic en un grado activo para desactivarlo (no lo elimina, solo lo oculta).
        </p>
        @endif
    </div>
</div>

{{-- Modal Registrar Pago --}}
<div class="modal fade" id="modalPago" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:none;">
            <form method="POST" action="{{ route('superadmin.tenants.subscriptions.store', $tenant) }}">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-credit-card-fill me-2 text-success"></i>Registrar Pago
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4">
                    <p class="text-muted small mb-3">Institución: <strong>{{ $tenant->nombre_institucion }}</strong></p>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Plan</label>
                            <select name="plan" class="form-select form-select-sm" required>
                                <option value="free"    @selected($tenant->plan==='free')>Free</option>
                                <option value="pro"     @selected($tenant->plan==='pro')>Pro — $49/mes</option>
                                <option value="premium" @selected($tenant->plan==='premium')>Premium — $99/mes</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Ciclo</label>
                            <select name="ciclo" class="form-select form-select-sm" required>
                                <option value="mensual">Mensual</option>
                                <option value="anual">Anual</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Meses a acreditar</label>
                            <select name="meses" class="form-select form-select-sm" required>
                                @foreach([1,2,3,6,12,24] as $m)
                                <option value="{{ $m }}">{{ $m }} mes{{ $m>1?'es':'' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Monto pagado (USD)</label>
                            <input type="number" name="monto_pagado" class="form-control form-control-sm" step="0.01" min="0" value="0" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Método de pago</label>
                            <select name="metodo_pago" class="form-select form-select-sm">
                                <option value="">Seleccionar...</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="paypal">PayPal</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Referencia / Comprobante</label>
                            <input type="text" name="referencia_pago" class="form-control form-control-sm" placeholder="Nro. de transacción...">
                        </div>
                    </div>

                    @if($suscActiva && $suscActiva->fecha_fin->isFuture())
                    <div class="alert alert-info mt-3 py-2" style="font-size:.8rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        La suscripción activa vence el <strong>{{ $suscActiva->fecha_fin->format('d/m/Y') }}</strong>.
                        El nuevo período comenzará desde esa fecha.
                    </div>
                    @endif
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check-lg me-1"></i>Confirmar pago y activar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
