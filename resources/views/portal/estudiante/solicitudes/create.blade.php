@extends('layouts.portal-estudiante')
@section('title', 'Nueva Solicitud')
@section('activeKey', 'solicitudes')

@section('content')
<div class="prt-page-header">
    <div>
        <h1 class="prt-page-title"><i class="bi bi-plus-circle me-2"></i>Nueva Solicitud</h1>
        <p class="prt-page-sub">Envía una petición al equipo administrativo del centro</p>
    </div>
    <a href="{{ route('portal.estudiante.solicitudes.index') }}" class="prt-btn prt-btn-outline">
        <i class="bi bi-arrow-left"></i> Mis Solicitudes
    </a>
</div>

@if($errors->any())
<div class="prt-alert prt-alert-danger mb-3">
    <i class="bi bi-exclamation-circle-fill"></i>
    <ul style="margin:0;padding-left:1rem;">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('portal.estudiante.solicitudes.store') }}" enctype="multipart/form-data">
@csrf
<div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(15,23,42,.07);padding:1.75rem 2rem;">

    {{-- Tipo --}}
    <div style="margin-bottom:1.5rem;">
        <label style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#374151;display:block;margin-bottom:.75rem;">
            Tipo de solicitud <span style="color:#dc2626;">*</span>
        </label>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:.6rem;">
            @php
            $tipoIcons = [
                'justificacion_ausencia' => ['bi-calendar-x-fill','#d97706','#fffbeb'],
                'constancia_estudios'    => ['bi-file-earmark-text-fill','#2563eb','#eff6ff'],
                'certificado_notas'      => ['bi-award-fill','#7c3aed','#f5f3ff'],
                'solicitar_beca'         => ['bi-mortarboard-fill','#16a34a','#f0fdf4'],
                'cambio_datos'           => ['bi-person-gear-fill','#0891b2','#ecfeff'],
                'otro'                   => ['bi-three-dots-fill','#64748b','#f8fafc'],
            ];
            @endphp
            @foreach($tipos as $key => $label)
            @php [$icon,$color,$bg] = $tipoIcons[$key] ?? ['bi-question-circle','#64748b','#f8fafc']; @endphp
            <label style="cursor:pointer;">
                <input type="radio" name="tipo" value="{{ $key }}" {{ old('tipo') === $key ? 'checked' : '' }}
                       class="tipo-radio" style="display:none;">
                <div class="tipo-card" data-key="{{ $key }}"
                     style="border:2px solid #e2e8f0;border-radius:12px;padding:.85rem;text-align:center;transition:.15s;background:#fff;">
                    <div style="width:38px;height:38px;border-radius:10px;background:{{ $bg }};display:flex;align-items:center;justify-content:center;margin:0 auto .5rem;">
                        <i class="bi {{ $icon }}" style="color:{{ $color }};font-size:1rem;"></i>
                    </div>
                    <div style="font-size:.78rem;font-weight:600;color:#374151;">{{ $label }}</div>
                </div>
            </label>
            @endforeach
        </div>
        @error('tipo')<div style="color:#dc2626;font-size:.78rem;margin-top:.4rem;">{{ $message }}</div>@enderror
    </div>

    {{-- Asunto --}}
    <div style="margin-bottom:1.25rem;">
        <label style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#374151;display:block;margin-bottom:.4rem;">
            Asunto <span style="color:#dc2626;">*</span>
        </label>
        <input type="text" name="asunto" value="{{ old('asunto') }}" maxlength="200"
               placeholder="Describe brevemente tu solicitud"
               style="width:100%;padding:.65rem 1rem;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.9rem;outline:none;"
               oninput="document.getElementById('asuntoCount').textContent=this.value.length">
        <div style="text-align:right;font-size:.72rem;color:#94a3b8;margin-top:.2rem;">
            <span id="asuntoCount">{{ strlen(old('asunto','')) }}</span>/200
        </div>
        @error('asunto')<div style="color:#dc2626;font-size:.78rem;">{{ $message }}</div>@enderror
    </div>

    {{-- Descripción --}}
    <div style="margin-bottom:1.25rem;">
        <label style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#374151;display:block;margin-bottom:.4rem;">
            Descripción <span style="color:#dc2626;">*</span>
        </label>
        <textarea name="descripcion" rows="5" maxlength="2000"
                  placeholder="Explica con detalle tu solicitud..."
                  style="width:100%;padding:.65rem 1rem;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.9rem;resize:vertical;outline:none;"
                  oninput="document.getElementById('descCount').textContent=this.value.length">{{ old('descripcion') }}</textarea>
        <div style="text-align:right;font-size:.72rem;color:#94a3b8;margin-top:.2rem;">
            <span id="descCount">{{ strlen(old('descripcion','')) }}</span>/2000
        </div>
        @error('descripcion')<div style="color:#dc2626;font-size:.78rem;">{{ $message }}</div>@enderror
    </div>

    {{-- Fecha evento (solo para justificacion) --}}
    <div id="fechaEventoWrap" style="margin-bottom:1.25rem;display:{{ old('tipo') === 'justificacion_ausencia' ? 'block' : 'none' }};">
        <label style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#374151;display:block;margin-bottom:.4rem;">
            Fecha de la ausencia
        </label>
        <input type="date" name="fecha_evento" value="{{ old('fecha_evento') }}"
               style="padding:.65rem 1rem;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.9rem;outline:none;">
        @error('fecha_evento')<div style="color:#dc2626;font-size:.78rem;">{{ $message }}</div>@enderror
    </div>

    {{-- Adjunto --}}
    <div style="margin-bottom:1.75rem;">
        <label style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#374151;display:block;margin-bottom:.4rem;">
            Adjunto <span style="font-weight:400;color:#94a3b8;">(opcional)</span>
        </label>
        <input type="file" name="adjunto" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
               style="width:100%;padding:.6rem;border:1.5px dashed #e2e8f0;border-radius:10px;font-size:.85rem;background:#fafafa;">
        <div style="font-size:.72rem;color:#94a3b8;margin-top:.3rem;">Formatos: JPG, PNG, PDF, DOC, DOCX · Máx. 4 MB</div>
        @error('adjunto')<div style="color:#dc2626;font-size:.78rem;">{{ $message }}</div>@enderror
    </div>

    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
        <button type="submit"
                style="padding:.6rem 1.5rem;border-radius:10px;background:linear-gradient(135deg,#1e3a8a,#2563eb);color:#fff;font-size:.9rem;font-weight:700;border:none;cursor:pointer;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-send-fill"></i> Enviar Solicitud
        </button>
        <a href="{{ route('portal.estudiante.solicitudes.index') }}"
           style="padding:.6rem 1.25rem;border-radius:10px;background:#fff;border:1.5px solid #e2e8f0;color:#374151;font-size:.9rem;font-weight:600;text-decoration:none;">
            Cancelar
        </a>
    </div>
</div>
</form>

<style>
.tipo-card:hover { border-color:#3b82f6; background:#eff6ff!important; }
.tipo-card.selected { border-color:#2563eb!important; background:#eff6ff!important; box-shadow:0 0 0 3px rgba(59,130,246,.15); }
</style>
<script>
document.querySelectorAll('.tipo-radio').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.tipo-card').forEach(c => c.classList.remove('selected'));
        this.closest('label').querySelector('.tipo-card').classList.add('selected');
        document.getElementById('fechaEventoWrap').style.display = this.value === 'justificacion_ausencia' ? 'block' : 'none';
    });
    if (radio.checked) radio.closest('label').querySelector('.tipo-card').classList.add('selected');
});
</script>
@endsection
