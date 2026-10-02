@extends('layouts.admin')
@section('page-title', 'Integración con Odoo')

@push('styles')
<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; flex-wrap:wrap; gap:.75rem; }
.page-header h1 { font-size:1.25rem; font-weight:800; color:var(--primary); margin:0; }
.odoo-grid { display:grid; grid-template-columns:minmax(0,1.2fr) minmax(0,1fr); gap:1.25rem; align-items:start; }
@media (max-width: 992px) { .odoo-grid { grid-template-columns:1fr; } }
.o-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:1.4rem; margin-bottom:1.25rem; }
.o-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--primary); border-bottom:2px solid var(--primary); padding-bottom:.4rem; margin-bottom:1rem; }
.o-label { font-size:.83rem; font-weight:600; color:#374151; margin-bottom:.3rem; }
.o-hint { font-size:.74rem; color:#6b7280; }
.o-toggle { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; background:#f8fafc; border-radius:8px; padding:.7rem .9rem; margin-bottom:.7rem; }
.o-toggle .t { font-size:.85rem; font-weight:600; color:#374151; }
.o-stat { text-align:center; flex:1; }
.o-stat .n { font-size:1.5rem; font-weight:800; color:var(--primary); line-height:1; }
.o-stat .l { font-size:.7rem; color:#6b7280; }
.o-pill { display:inline-block; padding:.15rem .6rem; border-radius:20px; font-size:.72rem; font-weight:700; }
.o-ok { background:#dcfce7; color:#166534; } .o-mal { background:#fee2e2; color:#991b1b; } .o-pend { background:#f3f4f6; color:#374151; }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1><i class="bi bi-plug me-2" style="color:var(--primary)"></i>Integración con Odoo</h1>
</div>

<p class="text-muted" style="font-size:.85rem;max-width:760px;">
    Conecta el Odoo de tu centro: pega las credenciales y el sistema envía a Odoo los <strong>representantes como contactos</strong> y,
    si lo activas, las <strong>cuotas como facturas de cliente</strong>. Cada centro tiene su propia conexión y solo ve la suya.
</p>

@if(session('success'))<div class="alert alert-success py-2" style="font-size:.84rem;"><i class="bi bi-check-circle me-1"></i>{{ session('success') }}</div>@endif
@if(session('warning'))<div class="alert alert-warning py-2" style="font-size:.84rem;"><i class="bi bi-exclamation-triangle me-1"></i>{{ session('warning') }}</div>@endif
@if(session('error'))<div class="alert alert-danger py-2" style="font-size:.84rem;"><i class="bi bi-x-circle me-1"></i>{{ session('error') }}</div>@endif

<div class="odoo-grid">
    {{-- ── Credenciales ───────────────────────────────────────────────── --}}
    <div>
        <form method="POST" action="{{ route('admin.odoo.guardar') }}" class="o-card" autocomplete="off">
            @csrf @method('PUT')
            <div class="o-title">Credenciales de Odoo</div>

            <div class="mb-3">
                <label class="o-label" for="url">URL de Odoo</label>
                <input type="url" id="url" name="url" class="form-control form-control-sm @error('url') is-invalid @enderror"
                       value="{{ old('url', $conexion?->url) }}" placeholder="https://mi-colegio.odoo.com" required>
                @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="o-hint">La dirección con la que entras a Odoo, con https://</div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="o-label" for="base_datos">Base de datos</label>
                    <input type="text" id="base_datos" name="base_datos" class="form-control form-control-sm @error('base_datos') is-invalid @enderror"
                           value="{{ old('base_datos', $conexion?->base_datos) }}" required>
                    @error('base_datos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="o-hint">En Odoo Online suele ser el nombre antes de «.odoo.com».</div>
                </div>
                <div class="col-sm-6">
                    <label class="o-label" for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" class="form-control form-control-sm @error('usuario') is-invalid @enderror"
                           value="{{ old('usuario', $conexion?->usuario) }}" placeholder="correo con el que entras a Odoo" required>
                    @error('usuario')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="o-label" for="api_key">Clave de API</label>
                <input type="password" id="api_key" name="api_key" class="form-control form-control-sm @error('api_key') is-invalid @enderror"
                       placeholder="{{ $conexion ? '•••••••• guardada (déjala vacía para conservarla)' : 'Pega aquí la clave de API' }}" autocomplete="new-password">
                @error('api_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="o-hint">Se guarda cifrada y no se vuelve a mostrar. No uses tu contraseña de Odoo: crea una clave de API (ver la ayuda de abajo).</div>
            </div>

            <div class="o-title mt-4">Qué se envía a Odoo</div>
            <div class="o-toggle">
                <div><div class="t">Contactos</div><div class="o-hint">Cada representante se crea (o actualiza) como contacto en Odoo.</div></div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="sync_contactos" value="1" {{ old('sync_contactos', $conexion?->sync_contactos ?? true) ? 'checked' : '' }}></div>
            </div>
            <div class="o-toggle">
                <div><div class="t">Facturas de las cuotas</div><div class="o-hint">Cada cuota se crea como factura de cliente a nombre del representante principal del estudiante, sin impuestos.</div></div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="sync_facturas" value="1" {{ old('sync_facturas', $conexion?->sync_facturas) ? 'checked' : '' }}></div>
            </div>
            <div class="o-toggle">
                <div><div class="t">Publicar las facturas</div><div class="o-hint">Apagado: quedan en <em>borrador</em> para que contabilidad las revise. Encendido: se publican solas.</div></div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="publicar_facturas" value="1" {{ old('publicar_facturas', $conexion?->publicar_facturas) ? 'checked' : '' }}></div>
            </div>
            <div class="mb-3 mt-3">
                <label class="o-label" for="diario_id">Diario de ventas (opcional)</label>
                <input type="number" min="1" id="diario_id" name="diario_id" class="form-control form-control-sm" style="max-width:160px;"
                       value="{{ old('diario_id', $conexion?->diario_id) }}" placeholder="id en Odoo">
                <div class="o-hint">Si lo dejas vacío, Odoo usa su diario de ventas por defecto.</div>
            </div>

            <div class="o-toggle" style="background:#eff6ff;">
                <div><div class="t">Integración activa</div><div class="o-hint">Cuando está activa, se envía solo cada hora. Apágala para pausar sin perder las credenciales.</div></div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="activo" value="1" {{ old('activo', $conexion?->activo) ? 'checked' : '' }}></div>
            </div>

            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Guardar</button>
        </form>

        <div class="o-card">
            <div class="o-title">Cómo crear la clave de API en Odoo</div>
            <ol class="mb-0" style="font-size:.82rem;color:#374151;padding-left:1.1rem;">
                <li>Entra a Odoo con el usuario que usará la integración (recomendado: uno propio, con permiso de <em>Contactos</em> y <em>Facturación</em>).</li>
                <li>Arriba a la derecha: tu nombre → <strong>Preferencias</strong> → pestaña <strong>Seguridad de la cuenta</strong> → <strong>Nueva clave de API</strong>.</li>
                <li>Ponle un nombre (por ejemplo «Sistema escolar»), confirma tu contraseña y copia la clave que aparece <em>una sola vez</em>.</li>
                <li>Pégala aquí, guarda y pulsa <strong>Probar conexión</strong>.</li>
            </ol>
        </div>
    </div>

    {{-- ── Estado ──────────────────────────────────────────────────────── --}}
    <div>
        <div class="o-card">
            <div class="o-title">Estado</div>
            @if(! $conexion)
                <p class="text-muted mb-0" style="font-size:.85rem;">Todavía no hay una conexión. Guarda las credenciales para empezar.</p>
            @else
                <div class="mb-2">
                    @if($conexion->ultimo_test_ok === true)<span class="o-pill o-ok"><i class="bi bi-check-circle"></i> Conexión probada</span>
                    @elseif($conexion->ultimo_test_ok === false)<span class="o-pill o-mal"><i class="bi bi-x-circle"></i> La última prueba falló</span>
                    @else<span class="o-pill o-pend">Sin probar</span>@endif
                    <span class="o-pill {{ $conexion->activo ? 'o-ok' : 'o-pend' }}">{{ $conexion->activo ? 'Activa' : 'Pausada' }}</span>
                </div>
                <div class="o-hint mb-3">
                    @if($conexion->version_odoo)Odoo {{ $conexion->version_odoo }} · @endif
                    @if($conexion->ultimo_test_at)probada {{ $conexion->ultimo_test_at->diffForHumans() }}@endif
                    <br>@if($conexion->ultima_sync_at)Último envío: {{ $conexion->ultima_sync_at->diffForHumans() }}@else Aún no se ha enviado nada.@endif
                </div>

                <div class="d-flex mb-3 p-2" style="background:#f8fafc;border-radius:8px;">
                    <div class="o-stat"><div class="n">{{ $contactos }}</div><div class="l">contactos vinculados</div></div>
                    <div class="o-stat"><div class="n">{{ $facturas }}</div><div class="l">facturas vinculadas</div></div>
                </div>

                @if($conexion->ultimo_error)
                    <div class="alert alert-warning py-2" style="font-size:.78rem;white-space:pre-line;">{{ $conexion->ultimo_error }}</div>
                @endif

                <div class="d-flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.odoo.probar') }}">@csrf
                        <button class="btn btn-outline-primary btn-sm"><i class="bi bi-plug me-1"></i>Probar conexión</button></form>
                    <form method="POST" action="{{ route('admin.odoo.sincronizar') }}">@csrf
                        <button class="btn btn-outline-success btn-sm" {{ $conexion->activo ? '' : 'disabled' }}><i class="bi bi-arrow-repeat me-1"></i>Enviar ahora</button></form>
                    <form method="POST" action="{{ route('admin.odoo.desconectar') }}" onsubmit="return confirm('Se borrarán las credenciales y los vínculos. Lo que ya está en Odoo no se toca. ¿Desconectar?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-octagon me-1"></i>Desconectar</button></form>
                </div>
            @endif
        </div>

        @if($conErrores->isNotEmpty())
        <div class="o-card">
            <div class="o-title">Avisos recientes</div>
            @foreach($conErrores as $v)
                <div style="font-size:.78rem;border-bottom:1px solid #f1f5f9;padding:.35rem 0;">
                    <strong>{{ $v->entidad_tipo === 'pago' ? 'Cuota' : 'Representante' }} #{{ $v->entidad_id }}</strong>
                    <span class="text-muted">· {{ $v->updated_at->diffForHumans() }}</span><br>{{ $v->ultimo_error }}
                </div>
            @endforeach
        </div>
        @endif

        <div class="o-card">
            <div class="o-title">Qué hace y qué no</div>
            <ul class="mb-0" style="font-size:.8rem;color:#374151;padding-left:1.1rem;">
                <li>Envía en una sola dirección: <strong>sistema escolar → Odoo</strong>.</li>
                <li>No duplica: lo ya enviado queda vinculado y solo se vuelve a enviar si cambió.</li>
                <li>Si una cuota cambia <em>después</em> de enviarse, no se reescribe la factura: aparece un aviso para ajustarla en Odoo.</li>
                <li><strong>Todavía no</strong> registra el cobro de la factura en Odoo ni trae datos desde Odoo.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
