@extends('layouts.admin')
@section('page-title', 'Editor de Página de Inicio')

@push('styles')
<style>
    .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
    .page-header h1 { font-size:1.45rem; font-weight:800; color:var(--primary); margin:0; }
    .card-panel { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:1.5rem; margin-bottom:1.5rem; }
    .section-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--primary); border-bottom:2px solid var(--primary); padding-bottom:.4rem; margin-bottom:1.1rem; }
    .form-label-custom { font-size:.83rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
    .form-control-custom { border:1.5px solid #d1d5db; border-radius:8px; padding:.5rem .75rem; font-size:.875rem; width:100%; transition:border-color .15s; }
    .form-control-custom:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(30,64,175,.1); }
    .preview-hero { background:linear-gradient(140deg,#0a0f2e 0%,#1e3a8a 55%,#1d4ed8 100%); border-radius:12px; padding:2.5rem 2rem; text-align:center; color:#fff; margin-bottom:1.5rem; }
    .preview-hero h2 { font-size:1.8rem; font-weight:900; margin:.5rem 0; }
    .preview-hero h2 em { font-style:normal; background:linear-gradient(135deg,#6ee7b7,#34d399); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
    .preview-hero p { color:rgba(255,255,255,.7); font-size:.9rem; margin-bottom:1rem; }
    .preview-badge { display:inline-flex; align-items:center; gap:.4rem; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.2); color:rgba(255,255,255,.88); border-radius:99px; padding:.3rem .8rem; font-size:.72rem; font-weight:600; margin-bottom:.75rem; }
    .stat-preview-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; background:#111827; border-radius:10px; padding:1.25rem; margin-bottom:1.5rem; }
    .stat-preview-item { text-align:center; }
    .stat-preview-n { font-size:1.6rem; font-weight:900; color:#fff; line-height:1; }
    .stat-preview-n span { color:#10b981; }
    .stat-preview-d { font-size:.72rem; color:rgba(255,255,255,.45); margin-top:.2rem; }
    .stat-input-group { display:grid; grid-template-columns:1fr auto 1fr; gap:.5rem; align-items:end; }
    .stat-input-group .form-control-custom.suffix { width:60px; text-align:center; }
    .color-swatch { width:28px; height:28px; border-radius:6px; display:inline-block; vertical-align:middle; margin-left:.5rem; border:2px solid #e5e7eb; }

    [data-theme="dark"] .card-panel { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .color-swatch { border-color: #334155; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1><i class="bi bi-display me-2"></i>Editor de Página de Inicio</h1>
        <p class="text-muted small mb-0">Personaliza los textos y estadísticas de la página pública</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('landing') }}" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-box-arrow-up-right me-1"></i>Ver página
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Vista previa del hero --}}
<div class="preview-hero">
    <div class="preview-badge">
        <span style="width:7px;height:7px;background:#10b981;border-radius:50%;display:inline-block;"></span>
        <span id="prev-badge">{{ $settings['landing_hero_badge'] ?? 'Sistema educativo completo · Listo para usar' }}</span>
    </div>
    <h2>
        <span id="prev-title">{{ $settings['landing_hero_title'] ?? 'Gestión educativa' }}</span><br>
        <em id="prev-title-em">{{ $settings['landing_hero_title_em'] ?? 'inteligente' }}</em>
    </h2>
    <p id="prev-sub">{{ $settings['landing_hero_sub'] ?? 'La plataforma todo-en-uno para centros educativos modernos.' }}</p>
</div>

<form method="POST" action="{{ route('admin.sistema.landing.update') }}">
    @csrf

    {{-- Sección Hero --}}
    <div class="card-panel">
        <div class="section-title"><i class="bi bi-stars me-1"></i>Sección Hero</div>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label-custom">Texto del badge (insignia superior)</label>
                <input type="text" name="landing_hero_badge" class="form-control-custom"
                    value="{{ $settings['landing_hero_badge'] ?? 'Sistema educativo completo · Listo para usar' }}"
                    oninput="document.getElementById('prev-badge').textContent=this.value">
                <small class="text-muted">Ej: "Versión 2025 disponible · Gratis para centros"</small>
            </div>
            <div class="col-md-6">
                <label class="form-label-custom">Título principal</label>
                <input type="text" name="landing_hero_title" class="form-control-custom"
                    value="{{ $settings['landing_hero_title'] ?? 'Gestión educativa' }}"
                    oninput="document.getElementById('prev-title').textContent=this.value">
            </div>
            <div class="col-md-6">
                <label class="form-label-custom">Palabra destacada (verde graduado)</label>
                <input type="text" name="landing_hero_title_em" class="form-control-custom"
                    value="{{ $settings['landing_hero_title_em'] ?? 'inteligente' }}"
                    oninput="document.getElementById('prev-title-em').textContent=this.value">
            </div>
            <div class="col-12">
                <label class="form-label-custom">Subtítulo / descripción</label>
                <textarea name="landing_hero_sub" class="form-control-custom" rows="2"
                    oninput="document.getElementById('prev-sub').textContent=this.value">{{ $settings['landing_hero_sub'] ?? 'La plataforma todo-en-uno para centros educativos modernos. Notas, asistencia, horarios y comunicación con padres desde un solo lugar.' }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label-custom">Texto botón primario (Ingresar)</label>
                <input type="text" name="landing_cta_primary" class="form-control-custom"
                    value="{{ $settings['landing_cta_primary'] ?? 'Ingresar al sistema' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label-custom">Texto botón secundario (Demo)</label>
                <input type="text" name="landing_cta_secondary" class="form-control-custom"
                    value="{{ $settings['landing_cta_secondary'] ?? 'Ver demo' }}">
            </div>
        </div>
    </div>

    {{-- Sección Estadísticas --}}
    <div class="card-panel">
        <div class="section-title"><i class="bi bi-bar-chart-fill me-1"></i>Barra de Estadísticas</div>
        <p class="text-muted small mb-3">Los 4 números que se muestran debajo del hero. El sufijo va en verde (ej: +, %, /7).</p>

        <div class="stat-preview-grid mb-3" id="stat-previews">
            @foreach([1,2,3,4] as $i)
            <div class="stat-preview-item">
                <div class="stat-preview-n">
                    <span id="prev-sn{{ $i }}">{{ $settings["landing_stat{$i}_n"] ?? ['500','30','99','24'][$i-1] }}</span><span style="color:#10b981;" id="prev-ss{{ $i }}">{{ $settings["landing_stat{$i}_s"] ?? ['+','+','%','/7'][$i-1] }}</span>
                </div>
                <div class="stat-preview-d" id="prev-sd{{ $i }}">{{ $settings["landing_stat{$i}_d"] ?? ['Estudiantes gestionados','Docentes activos','Tiempo de actividad','Disponibilidad'][$i-1] }}</div>
            </div>
            @endforeach
        </div>

        <div class="row g-3">
            @php
                $statDefaults = [
                    1 => ['n'=>'500','s'=>'+','d'=>'Estudiantes gestionados'],
                    2 => ['n'=>'30','s'=>'+','d'=>'Docentes activos'],
                    3 => ['n'=>'99','s'=>'%','d'=>'Tiempo de actividad'],
                    4 => ['n'=>'24','s'=>'/7','d'=>'Disponibilidad'],
                ];
            @endphp
            @foreach([1,2,3,4] as $i)
            <div class="col-md-6">
                <label class="form-label-custom">Estadística {{ $i }}</label>
                <div class="d-flex gap-2 align-items-end">
                    <div style="flex:1">
                        <small class="text-muted d-block mb-1">Número</small>
                        <input type="text" name="landing_stat{{ $i }}_n" class="form-control-custom"
                            value="{{ $settings['landing_stat'.$i.'_n'] ?? $statDefaults[$i]['n'] }}"
                            placeholder="500"
                            oninput="document.getElementById('prev-sn{{ $i }}').textContent=this.value">
                    </div>
                    <div style="width:70px">
                        <small class="text-muted d-block mb-1">Sufijo</small>
                        <input type="text" name="landing_stat{{ $i }}_s" class="form-control-custom"
                            value="{{ $settings['landing_stat'.$i.'_s'] ?? $statDefaults[$i]['s'] }}"
                            placeholder="+"
                            oninput="document.getElementById('prev-ss{{ $i }}').textContent=this.value">
                    </div>
                    <div style="flex:2">
                        <small class="text-muted d-block mb-1">Descripción</small>
                        <input type="text" name="landing_stat{{ $i }}_d" class="form-control-custom"
                            value="{{ $settings['landing_stat'.$i.'_d'] ?? $statDefaults[$i]['d'] }}"
                            placeholder="Descripción"
                            oninput="document.getElementById('prev-sd{{ $i }}').textContent=this.value">
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Testimonio --}}
    <div class="card-panel">
        <div class="section-title"><i class="bi bi-chat-quote-fill me-1"></i>Experiencia de Cliente (Testimonio)</div>

        {{-- Preview --}}
        <div style="background:#f8fafc;border-radius:10px;padding:1.5rem 2rem;text-align:center;margin-bottom:1.25rem;border:1px solid #e5e7eb;">
            <div style="font-size:2.5rem;color:#dbeafe;line-height:1;margin-bottom:.5rem;font-family:Georgia,serif;">"</div>
            <p id="prev-cita" style="font-size:.9rem;color:#374151;line-height:1.8;font-style:italic;margin-bottom:1rem;">{{ $settings['landing_testimonio_cita'] ?? 'Desde que implementamos este sistema, el tiempo dedicado a la gestión administrativa se redujo a la mitad.' }}</p>
            <div style="display:flex;align-items:center;justify-content:center;gap:.75rem;">
                <div id="prev-avatar" style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#1e3a8a,#3b82f6);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:.95rem;">{{ strtoupper(substr($settings['landing_testimonio_nombre'] ?? 'M', 0, 1)) }}</div>
                <div style="text-align:left;">
                    <div id="prev-nombre" style="font-size:.82rem;font-weight:700;color:#111827;">{{ $settings['landing_testimonio_nombre'] ?? 'María González' }}</div>
                    <div id="prev-cargo" style="font-size:.7rem;color:#6b7280;">{{ $settings['landing_testimonio_cargo'] ?? 'Directora Académica · Centro Educativo Demo' }}</div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label-custom">Cita / testimonio</label>
                <textarea name="landing_testimonio_cita" class="form-control-custom" rows="3"
                    oninput="document.getElementById('prev-cita').textContent=this.value">{{ $settings['landing_testimonio_cita'] ?? 'Desde que implementamos este sistema, el tiempo dedicado a la gestión administrativa se redujo a la mitad. Los padres ahora tienen acceso inmediato a las calificaciones y la comunicación con los docentes mejoró notablemente.' }}</textarea>
            </div>
            <div class="col-md-5">
                <label class="form-label-custom">Nombre del cliente</label>
                <input type="text" name="landing_testimonio_nombre" class="form-control-custom"
                    value="{{ $settings['landing_testimonio_nombre'] ?? 'María González' }}"
                    oninput="document.getElementById('prev-nombre').textContent=this.value;document.getElementById('prev-avatar').textContent=this.value.charAt(0).toUpperCase()">
            </div>
            <div class="col-md-7">
                <label class="form-label-custom">Cargo / institución</label>
                <input type="text" name="landing_testimonio_cargo" class="form-control-custom"
                    value="{{ $settings['landing_testimonio_cargo'] ?? 'Directora Académica · Centro Educativo Demo' }}"
                    oninput="document.getElementById('prev-cargo').textContent=this.value">
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <button type="reset" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar
        </button>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save me-1"></i>Guardar cambios
        </button>
    </div>
</form>
@endsection
