@extends('layouts.admin')
@section('page-title', 'Editar Asignatura')

@push('styles')
<style>
    .form-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.5rem;
    }
    .section-label {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #94a3b8;
        margin-bottom: .75rem;
        padding-bottom: .4rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .ra-table th { font-size: .76rem; font-weight: 700; background: #f8fafc; }
    .ra-table td { vertical-align: middle; font-size: .85rem; }
    .peso-bar-wrap { height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; margin-top: 4px; }
    .peso-bar-fill { height: 100%; border-radius: 4px; transition: width .3s; }
    .peso-total-ok  { color: #16a34a; font-weight: 700; }
    .peso-total-bad { color: #dc2626; font-weight: 700; }
    [data-theme="dark"] .form-section { background: #1e293b; border-color: #334155; }
</style>
@endpush

@section('content')

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.82rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.asignaturas.index') }}" class="text-decoration-none">Asignaturas</a></li>
        <li class="breadcrumb-item active">Editar</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0" style="color:var(--primary)">
        <i class="bi bi-pencil me-2"></i>Editar Asignatura
    </h4>
</div>

<form method="POST" action="{{ route('admin.asignaturas.update', $asignatura) }}">
@csrf
@method('PUT')

<div class="row g-3">
    {{-- Left column --}}
    <div class="col-lg-8">
        <div class="form-card">
            <div class="section-label">Información General</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">Código <span class="text-muted fw-normal">(opcional)</span></label>
                    <input type="text" name="codigo" class="form-control @error('codigo') is-invalid @enderror"
                           value="{{ old('codigo', $asignatura->codigo) }}" placeholder="Ej: MAT-001" maxlength="20">
                    @error('codigo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre', $asignatura->nombre) }}" placeholder="Nombre de la asignatura" required>
                    @error('nombre')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">Descripción <span class="text-muted fw-normal">(opcional)</span></label>
                    <textarea name="descripcion" class="form-control @error('descripcion') is-invalid @enderror"
                              rows="3" placeholder="Descripción breve de la asignatura...">{{ old('descripcion', $asignatura->descripcion) }}</textarea>
                    @error('descripcion')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">Área</label>
                    <select name="area" class="form-select @error('area') is-invalid @enderror" required>
                        <option value="">— Seleccionar —</option>
                        <option value="academica" {{ old('area', $asignatura->area) === 'academica' ? 'selected' : '' }}>
                            Académica (Lengua, Matemáticas, Ciencias…)
                        </option>
                        <option value="tecnica" {{ old('area', $asignatura->area) === 'tecnica' ? 'selected' : '' }}>
                            Técnica (Tecnología, Especialidades…)
                        </option>
                    </select>
                    @error('area')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-8" id="area-id-wrapper">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">
                        Área curricular detallada <span class="text-muted fw-normal">(MINERD)</span>
                    </label>
                    <select name="area_id" class="form-select @error('area_id') is-invalid @enderror">
                        <option value="">— Sin área —</option>
                        @foreach($areas ?? [] as $ar)
                        <option value="{{ $ar->id }}"
                            {{ old('area_id', $asignatura->area_id) == $ar->id ? 'selected' : '' }}
                            style="color:{{ $ar->color ?? '#374151' }};">
                            {{ $ar->nombre }}
                            ({{ $ar->tipo === 'academica' ? 'Académica' : ($ar->tipo === 'tecnica' ? 'Técnica' : 'Ambas') }})
                        </option>
                        @endforeach
                    </select>
                    @error('area_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-8" id="familia-wrapper"
                     style="{{ old('area', $asignatura->area) === 'tecnica' ? '' : 'display:none;' }}">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">
                        Familia Profesional <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <select name="familia_id" class="form-select @error('familia_id') is-invalid @enderror">
                        <option value="">— Sin familia —</option>
                        @foreach($familias ?? [] as $fam)
                        <option value="{{ $fam->id }}"
                            {{ old('familia_id', $asignatura->familia_id) == $fam->id ? 'selected' : '' }}>
                            {{ $fam->nombre }}
                        </option>
                        @endforeach
                    </select>
                    @error('familia_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">Horas/semana</label>
                    <input type="number" name="horas_semanales"
                           class="form-control @error('horas_semanales') is-invalid @enderror"
                           value="{{ old('horas_semanales', $asignatura->horas_semanales) }}"
                           min="1" max="20" placeholder="Ej: 5">
                    @error('horas_semanales')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:.85rem;">
                        Núm. RAs <small class="text-muted fw-normal">(0 = componentes)</small>
                    </label>
                    <input type="number" name="num_ra"
                           class="form-control @error('num_ra') is-invalid @enderror"
                           value="{{ old('num_ra', $asignatura->num_ra ?? 0) }}"
                           min="0" max="10" placeholder="0">
                    @error('num_ra')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="text-muted mt-1" style="font-size:.73rem;">
                        Si &gt; 0, habilita evaluación por RA en asignaciones técnicas.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right column --}}
    <div class="col-lg-4">
        <div class="form-card mb-3">
            <div class="section-label">Apariencia</div>
            <label class="form-label fw-semibold" style="font-size:.85rem;">Color identificador</label>
            <div class="d-flex align-items-center gap-3">
                <input type="color" name="color" id="input-color"
                       class="form-control form-control-color @error('color') is-invalid @enderror"
                       value="{{ old('color', $asignatura->color ?? '#1e3a6e') }}"
                       style="width:60px;height:42px;padding:3px;border-radius:8px;cursor:pointer;">
                <div>
                    <div id="color-preview-label" style="font-size:.82rem;font-weight:600;color:#1e293b;">
                        {{ old('color', $asignatura->color ?? '#1e3a6e') }}
                    </div>
                    <div style="font-size:.72rem;color:#9ca3af;">Clic para cambiar</div>
                </div>
            </div>
            @error('color')
            <div class="text-danger mt-1" style="font-size:.8rem;">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-card">
            <div class="section-label">Estado</div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox"
                       name="activo" id="activo" value="1"
                       {{ old('activo', $asignatura->activo) ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="activo" style="font-size:.87rem;">
                    Asignatura activa
                </label>
            </div>
            <div class="text-muted mb-3" style="font-size:.75rem;">
                Las asignaturas inactivas no aparecen en las asignaciones.
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox"
                       name="es_basica" id="es_basica" value="1"
                       {{ old('es_basica', $asignatura->es_basica) ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="es_basica" style="font-size:.87rem;">
                    Materia básica (asignar automáticamente a grupos nuevos)
                </label>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-4">
    <a href="{{ route('admin.asignaturas.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Cancelar
    </a>
    <button type="submit" class="btn btn-primary px-5 fw-bold">
        <i class="bi bi-floppy me-2"></i>Guardar Cambios
    </button>
</div>

</form>

{{-- ── Resultados de Aprendizaje (solo si num_ra > 0) ─────────────────── --}}
@if(($asignatura->num_ra ?? 0) > 0)
<div class="form-card mt-4" id="ra-section">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <div class="section-label mb-0">Resultados de Aprendizaje</div>
            <small class="text-muted">Define la descripción y los puntos de cada RA. Los puntos deben sumar exactamente 100.</small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span id="peso-total-badge" class="badge bg-secondary px-3" style="font-size:.8rem;">Total: 0 pts</span>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-distribuir-ra">
                <i class="bi bi-distribute-horizontal me-1"></i>Distribuir equitativamente
            </button>
            <button type="button" class="btn btn-primary btn-sm px-4" id="btn-guardar-ras">
                <i class="bi bi-floppy me-1"></i>Guardar RAs
            </button>
        </div>
    </div>

    {{-- Visual distribution bar --}}
    <div class="peso-bar-wrap mb-3" id="ra-bar-wrap">
        @for($i = 1; $i <= $asignatura->num_ra; $i++)
        <div class="peso-bar-fill" id="bar-ra{{ $i }}" style="width:0%;display:inline-block;background:hsl({{ ($i-1) * 36 }},70%,50%);"></div>
        @endfor
    </div>

    <table class="table table-bordered table-sm ra-table mb-0">
        <thead>
            <tr>
                <th style="width:60px;">RA</th>
                <th>Descripción</th>
                <th style="width:140px;">Puntos</th>
            </tr>
        </thead>
        <tbody id="ra-tbody">
            @for($i = 1; $i <= $asignatura->num_ra; $i++)
            @php
                $raRecord = $ras->firstWhere('numero', $i);
                $pesoVal  = $raRecord?->peso ?? '';
                $descVal  = $raRecord?->descripcion ?? '';
            @endphp
            <tr>
                <td class="text-center fw-bold" style="color:#1e3a6e;">RA{{ $i }}</td>
                <td>
                    <input type="text" class="form-control form-control-sm ra-desc"
                           data-numero="{{ $i }}"
                           value="{{ $descVal }}"
                           placeholder="Descripción del RA {{ $i }}...">
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control ra-peso"
                               id="peso-ra{{ $i }}"
                               data-numero="{{ $i }}"
                               value="{{ $pesoVal }}"
                               min="0" max="100" step="0.01"
                               placeholder="—">
                        <span class="input-group-text">pts</span>
                    </div>
                </td>
            </tr>
            @endfor
        </tbody>
    </table>

    <div id="ra-msg" class="mt-2" style="min-height:1.4rem;font-size:.82rem;"></div>
</div>
@endif

@endsection

@push('scripts')
<script>
document.getElementById('input-color').addEventListener('input', function() {
    document.getElementById('color-preview-label').textContent = this.value;
});
const areaSelectE = document.querySelector('select[name="area"]');
const familiaWrapE = document.getElementById('familia-wrapper');
function toggleFamiliaEdit() {
    const esTecnica = areaSelectE.value === 'tecnica';
    familiaWrapE.style.display = esTecnica ? '' : 'none';
    if (!esTecnica) familiaWrapE.querySelector('select').value = '';
}
areaSelectE?.addEventListener('change', toggleFamiliaEdit);

@if(($asignatura->num_ra ?? 0) > 0)
(function () {
    const NUM_RA       = {{ $asignatura->num_ra }};
    const ROUTE_RAS    = "{{ route('admin.asignaturas.guardar-ras', $asignatura) }}";
    const CSRF         = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function pesoInputs() {
        return Array.from(document.querySelectorAll('.ra-peso'));
    }
    function calcTotal() {
        return pesoInputs().reduce((s, inp) => s + (parseFloat(inp.value) || 0), 0);
    }
    function actualizarBarra() {
        const total = calcTotal();
        pesoInputs().forEach(inp => {
            const n   = inp.dataset.numero;
            const bar = document.getElementById('bar-ra' + n);
            if (!bar) return;
            const p   = (parseFloat(inp.value) || 0);
            bar.style.width = (total > 0 ? (p / total * 100) : 0) + '%';
        });
        const badge = document.getElementById('peso-total-badge');
        const t     = Math.round(total * 100) / 100;
        badge.textContent = 'Total: ' + t + ' pts';
        badge.className   = 'badge px-3' + (Math.abs(t - 100) <= 0.5 ? ' bg-success' : ' bg-danger');
    }

    document.getElementById('ra-tbody').addEventListener('input', function(e) {
        if (e.target.classList.contains('ra-peso')) actualizarBarra();
    });

    document.getElementById('btn-distribuir-ra')?.addEventListener('click', function() {
        const base  = Math.floor(10000 / NUM_RA) / 100;
        let residuo = Math.round((100 - base * NUM_RA) * 100) / 100;
        pesoInputs().forEach((inp, idx) => {
            inp.value = idx === NUM_RA - 1 ? Math.round((base + residuo) * 100) / 100 : base;
        });
        actualizarBarra();
    });

    document.getElementById('btn-guardar-ras')?.addEventListener('click', function() {
        const msg = document.getElementById('ra-msg');
        const total = calcTotal();
        const allFilled = pesoInputs().every(inp => inp.value.trim() !== '');
        if (allFilled && Math.abs(total - 100) > 0.5) {
            msg.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Los puntos deben sumar 100 (actual: ' + Math.round(total*100)/100 + ').</span>';
            return;
        }

        const ras = [];
        for (let i = 1; i <= NUM_RA; i++) {
            const desc = document.querySelector('.ra-desc[data-numero="' + i + '"]');
            const peso = document.getElementById('peso-ra' + i);
            ras.push({
                numero:      i,
                descripcion: desc?.value ?? '',
                peso:        peso?.value !== '' ? peso.value : null,
            });
        }

        msg.innerHTML = '<span class="text-muted"><i class="bi bi-arrow-repeat me-1"></i>Guardando…</span>';
        fetch(ROUTE_RAS, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ ras }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                msg.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>RAs guardados correctamente.</span>';
                setTimeout(() => msg.innerHTML = '', 3000);
            } else {
                msg.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>' + (data.error ?? 'Error al guardar.') + '</span>';
            }
        })
        .catch(() => {
            msg.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Error de conexión.</span>';
        });
    });

    // Init bar on load
    actualizarBarra();
})();
@endif
</script>
@endpush
