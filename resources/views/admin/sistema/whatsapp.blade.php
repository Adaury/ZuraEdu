@extends('layouts.admin')
@section('page-title', 'WhatsApp & Notificaciones')

@push('styles')
<style>
    .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
    .page-header h1 { font-size:1.45rem; font-weight:800; color:var(--primary); margin:0; }
    .card-panel { background:#fff; border-radius:14px; border:1px solid #e5e7eb; padding:1.5rem; margin-bottom:1.5rem; }
    .section-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--primary); border-bottom:2px solid var(--primary); padding-bottom:.4rem; margin-bottom:1.1rem; }
    .provider-option { border:2px solid #e5e7eb; border-radius:10px; padding:1rem; cursor:pointer; transition:all .2s; }
    .provider-option.selected, .provider-option:hover { border-color:var(--primary); background:#f0f7ff; }
    .provider-option .pname { font-weight:700; font-size:.9rem; }
    .provider-option .pdesc { font-size:.78rem; color:#6b7280; }
    .notif-toggle { display:flex; align-items:center; justify-content:space-between; padding:.75rem 1rem; border-radius:10px; background:#f8fafc; border:1px solid #e5e7eb; }
    .notif-toggle-label { font-size:.88rem; font-weight:600; }
    .notif-toggle-sub { font-size:.75rem; color:#6b7280; }

    [data-theme="dark"] .card-panel { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .provider-option { border-color: #334155; }
    [data-theme="dark"] .provider-option.selected, [data-theme="dark"] .provider-option:hover { background: #162032; border-color: var(--primary); }
    [data-theme="dark"] .provider-option .pdesc { color: #94a3b8; }
    [data-theme="dark"] .notif-toggle { background: #162032; border-color: #334155; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1><i class="bi bi-whatsapp me-2" style="color:#25d366;"></i>WhatsApp & Notificaciones</h1>
        <p class="text-muted small mb-0">Configura el envío de notificaciones automáticas a los representantes</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 mb-3">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 mb-3">
        <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger border-0 rounded-3 mb-3">
        @foreach($errors->all() as $e)<div><i class="bi bi-x-circle me-1"></i>{{ $e }}</div>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('admin.sistema.whatsapp.update') }}">
    @csrf

    {{-- ── ACTIVAR MÓDULO ─────────────────────────────────────────────── --}}
    <div class="card-panel">
        <div class="section-title"><i class="bi bi-toggle-on me-1"></i>Estado del módulo</div>

        <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background:#f0fdf4; border:1.5px solid #bbf7d0;">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" name="module_whatsapp" id="moduleWhatsapp" value="1"
                    {{ ($settings['module_whatsapp'] ?? '0') === '1' ? 'checked' : '' }}
                    style="width:3rem;height:1.6rem;">
            </div>
            <div>
                <div class="fw-700" style="font-size:.95rem;">Activar notificaciones WhatsApp</div>
                <div class="text-muted" style="font-size:.8rem;">Cuando está activo, el sistema enviará mensajes automáticos a los representantes.</div>
            </div>
        </div>
    </div>

    {{-- ── PROVEEDOR ───────────────────────────────────────────────────── --}}
    <div class="card-panel">
        <div class="section-title"><i class="bi bi-plug me-1"></i>Proveedor de mensajería</div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="provider-option {{ ($settings['whatsapp_provider'] ?? 'twilio') === 'twilio' ? 'selected' : '' }}"
                    for="provTwilio">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <input type="radio" name="whatsapp_provider" id="provTwilio" value="twilio"
                            {{ ($settings['whatsapp_provider'] ?? 'twilio') === 'twilio' ? 'checked' : '' }}
                            class="form-check-input mt-0">
                        <span class="pname">Twilio WhatsApp</span>
                    </div>
                    <div class="pdesc">Servicio confiable. Requiere Account SID, Auth Token y número Twilio.</div>
                </label>
            </div>
            <div class="col-md-6">
                <label class="provider-option {{ ($settings['whatsapp_provider'] ?? 'twilio') === 'meta' ? 'selected' : '' }}"
                    for="provMeta">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <input type="radio" name="whatsapp_provider" id="provMeta" value="meta"
                            {{ ($settings['whatsapp_provider'] ?? '') === 'meta' ? 'checked' : '' }}
                            class="form-check-input mt-0">
                        <span class="pname">Meta (WhatsApp Business API)</span>
                    </div>
                    <div class="pdesc">API oficial de Meta. Requiere Token de acceso y número de teléfono de negocio.</div>
                </label>
            </div>
        </div>

        {{-- Twilio fields --}}
        <div id="twilioFields" class="{{ ($settings['whatsapp_provider'] ?? 'twilio') !== 'twilio' ? 'd-none' : '' }}">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label fw-600" style="font-size:.83rem;">Account SID</label>
                    <input type="text" name="whatsapp_account_sid" class="form-control font-monospace"
                        value="{{ $settings['whatsapp_account_sid'] ?? '' }}"
                        placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                </div>
                <div class="col-md-7">
                    <label class="form-label fw-600" style="font-size:.83rem;">Auth Token</label>
                    <div class="input-group">
                        <input type="password" name="whatsapp_auth_token" id="authToken" class="form-control font-monospace"
                            value="{{ $settings['whatsapp_auth_token'] ?? '' }}"
                            placeholder="Tu auth token de Twilio">
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleToken()">
                            <i class="bi bi-eye" id="tokenEyeIcon"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-600" style="font-size:.83rem;">Número WhatsApp (From)</label>
                    <input type="text" name="whatsapp_from_number" class="form-control"
                        value="{{ $settings['whatsapp_from_number'] ?? '' }}"
                        placeholder="+14155238886">
                    <small class="text-muted">Formato: +1XXXXXXXXXX</small>
                </div>
            </div>
        </div>

        {{-- Meta fields --}}
        <div id="metaFields" class="{{ ($settings['whatsapp_provider'] ?? 'twilio') !== 'meta' ? 'd-none' : '' }}">
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label fw-600" style="font-size:.83rem;">Token de acceso permanente</label>
                    <div class="input-group">
                        <input type="password" name="whatsapp_auth_token" id="metaToken" class="form-control font-monospace"
                            value="{{ $settings['whatsapp_auth_token'] ?? '' }}"
                            placeholder="EAAxxxxxxxxxx...">
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleMetaToken()">
                            <i class="bi bi-eye" id="metaTokenEyeIcon"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-600" style="font-size:.83rem;">Phone Number ID</label>
                    <input type="text" name="whatsapp_from_number" class="form-control"
                        value="{{ $settings['whatsapp_from_number'] ?? '' }}"
                        placeholder="1234567890">
                    <small class="text-muted">ID del número en Meta Business</small>
                </div>
            </div>
        </div>
    </div>

    {{-- ── NOTIFICACIONES ──────────────────────────────────────────────── --}}
    <div class="card-panel">
        <div class="section-title"><i class="bi bi-bell me-1"></i>Tipos de notificación</div>
        <div class="d-flex flex-column gap-2">

            <div class="notif-toggle">
                <div>
                    <div class="notif-toggle-label"><i class="bi bi-clipboard-check me-1 text-primary"></i>Publicación de notas</div>
                    <div class="notif-toggle-sub">Notifica al representante cuando se publican las calificaciones del estudiante</div>
                </div>
                <div class="form-check form-switch mb-0 ms-3">
                    <input class="form-check-input" type="checkbox" name="whatsapp_notify_grades" value="1"
                        {{ ($settings['whatsapp_notify_grades'] ?? '1') === '1' ? 'checked' : '' }}
                        style="width:2.5rem;height:1.4rem;">
                </div>
            </div>

            <div class="notif-toggle">
                <div>
                    <div class="notif-toggle-label"><i class="bi bi-calendar-x me-1 text-danger"></i>Registro de ausencias</div>
                    <div class="notif-toggle-sub">Notifica al representante cuando el estudiante tiene una ausencia registrada</div>
                </div>
                <div class="form-check form-switch mb-0 ms-3">
                    <input class="form-check-input" type="checkbox" name="whatsapp_notify_absence" value="1"
                        {{ ($settings['whatsapp_notify_absence'] ?? '1') === '1' ? 'checked' : '' }}
                        style="width:2.5rem;height:1.4rem;">
                </div>
            </div>

            <div class="notif-toggle">
                <div>
                    <div class="notif-toggle-label"><i class="bi bi-megaphone me-1 text-warning"></i>Alertas del sistema</div>
                    <div class="notif-toggle-sub">Mensajes y comunicados importantes enviados manualmente por el administrador</div>
                </div>
                <div class="form-check form-switch mb-0 ms-3">
                    <input class="form-check-input" type="checkbox" name="whatsapp_notify_alerts" value="1"
                        {{ ($settings['whatsapp_notify_alerts'] ?? '1') === '1' ? 'checked' : '' }}
                        style="width:2.5rem;height:1.4rem;">
                </div>
            </div>

        </div>
    </div>

    {{-- ── INFORMACIÓN ─────────────────────────────────────────────────── --}}
    <div class="p-3 rounded-3 mb-4" style="background:#f0f9ff; border:1px solid #bae6fd; font-size:.82rem; color:#0369a1;">
        <i class="bi bi-info-circle-fill me-1"></i>
        <strong>Para que las notificaciones lleguen correctamente,</strong> asegúrate de que los representantes tengan
        su número de teléfono registrado en el sistema con el formato internacional: <code>+1XXXXXXXXXX</code>.
        El módulo solo envía mensajes cuando está activado y las credenciales son válidas.
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save me-1"></i>Guardar configuración
    </button>
</form>
@endsection

@push('scripts')
<script>
document.querySelectorAll('input[name="whatsapp_provider"]').forEach(radio => {
    radio.addEventListener('change', function () {
        document.getElementById('twilioFields').classList.toggle('d-none', this.value !== 'twilio');
        document.getElementById('metaFields').classList.toggle('d-none', this.value !== 'meta');
        document.querySelectorAll('.provider-option').forEach(el => el.classList.remove('selected'));
        this.closest('.provider-option').classList.add('selected');
    });
});

function toggleToken() {
    const inp = document.getElementById('authToken');
    const ico = document.getElementById('tokenEyeIcon');
    const isPass = inp.type === 'password';
    inp.type = isPass ? 'text' : 'password';
    ico.className = isPass ? 'bi bi-eye-slash' : 'bi bi-eye';
}
function toggleMetaToken() {
    const inp = document.getElementById('metaToken');
    const ico = document.getElementById('metaTokenEyeIcon');
    const isPass = inp.type === 'password';
    inp.type = isPass ? 'text' : 'password';
    ico.className = isPass ? 'bi bi-eye-slash' : 'bi bi-eye';
}
</script>
@endpush
