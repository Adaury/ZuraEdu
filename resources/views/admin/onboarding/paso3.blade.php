@extends('admin.onboarding._layout')
@php $pasoActual = 3; @endphp

@push('styles')
<style>
/* ── Ciclo block ──────────────────────────────────────────── */
.ciclo-block { }
.ciclo-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: .85rem;
}
.ciclo-title { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; }
.ciclo-badge {
    display: inline-flex; align-items: center;
    padding: .25rem .8rem; border-radius: 20px;
    font-size: .73rem; font-weight: 700; letter-spacing: .03em;
    text-transform: uppercase;
}
.ciclo-badge.primer  { background: #eff6ff; color: #1d4ed8; border: 1.5px solid #bfdbfe; }
.ciclo-badge.segundo { background: #f0fdf4; color: #16a34a; border: 1.5px solid #bbf7d0; }
.ciclo-badge.otros   { background: #faf5ff; color: #7c3aed; border: 1.5px solid #ddd6fe; }
.ciclo-range { font-size: .78rem; color: #94a3b8; }
.btn-ciclo-toggle {
    font-size: .75rem; color: #64748b; background: none;
    border: 1px solid #e2e8f0; border-radius: 8px;
    padding: .3rem .75rem; cursor: pointer; transition: all .15s;
    white-space: nowrap;
}
.btn-ciclo-toggle:hover { border-color: #94a3b8; color: #374151; }

/* ── Grade cards grid ──────────────────────────────────────── */
.grados-ciclo {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: .65rem;
}

/* ── Individual grade card ─────────────────────────────────── */
.gc-card {
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    padding: .9rem;
    cursor: pointer;
    transition: border-color .18s, background .18s;
    user-select: none;
    background: #fff;
}
.gc-card:hover { border-color: #94a3b8; }
.gc-card.active { border-color: #10b981; background: #f0fdf4; }
.gc-card.active:hover { border-color: #059669; }

.gc-top {
    display: flex; align-items: flex-start;
    justify-content: space-between; gap: .4rem;
}
.gc-nombre { font-size: .84rem; font-weight: 700; color: #374151; line-height: 1.3; }
.gc-nivel  { font-size: .68rem; color: #94a3b8; margin-top: .1rem; }

.gc-check {
    width: 22px; height: 22px; border-radius: 50%;
    border: 2px solid #d1d5db;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; transition: all .15s;
    color: transparent; font-size: .72rem;
}
.gc-card.active .gc-check {
    background: #10b981; border-color: #10b981; color: #fff;
}

/* ── Section picker (visible only when card is active) ──────── */
.gc-secciones {
    margin-top: .75rem;
    padding-top: .6rem;
    border-top: 1px solid #d1fae5;
    display: none;
}
.gc-card.active .gc-secciones { display: block; }

.gc-sec-label {
    font-size: .65rem; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: .06em;
    margin-bottom: .45rem;
}
.gc-chips {
    display: flex; flex-wrap: wrap; gap: .3rem; align-items: center;
}

/* Section chip */
.chip-wrap { position: relative; display: inline-flex; }
.chip-wrap input[type=checkbox] { position: absolute; opacity: 0; width: 0; height: 0; }
.chip {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 30px; height: 28px; padding: 0 .5rem;
    border: 1.5px solid #d1fae5; border-radius: 7px;
    background: #fff; color: #374151;
    font-size: .78rem; font-weight: 700;
    cursor: pointer; transition: all .15s;
}
.chip-wrap input:checked + .chip {
    background: #10b981; border-color: #10b981; color: #fff;
}
.chip:hover { border-color: #6ee7b7; }

/* Add (+) button */
.chip-add {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px;
    border: 1.5px dashed #d1d5db; border-radius: 7px;
    background: transparent; color: #94a3b8;
    cursor: pointer; font-size: .85rem; transition: all .15s;
    flex-shrink: 0; padding: 0;
}
.chip-add:hover { border-color: #10b981; color: #10b981; }

/* Inline new-section input */
.nueva-sec-field {
    width: 50px; height: 28px; padding: 0 .4rem;
    border: 1.5px solid #10b981; border-radius: 7px;
    font-size: .78rem; font-weight: 700; text-align: center;
    color: #374151; outline: none;
    text-transform: uppercase;
    background: #fff;
}

/* ── Responsive ─────────────────────────────────────────────── */
@media (max-width: 620px) {
    .grados-ciclo { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 400px) {
    .grados-ciclo { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('wizard-content')

<div class="wizard-card-header">
    <h2>🏫 Grados y secciones</h2>
    <p>Selecciona los grados que ofrece tu institución y define cuántas secciones tendrá cada uno. Podrás modificarlo desde la configuración en cualquier momento.</p>
</div>

<form method="POST" action="{{ route('admin.onboarding.store', 3) }}">
@csrf

<div class="wizard-card-body">

    @if($errors->any())
    <div class="alert-error">
        <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    @php
        $ciclos = [
            [
                'key'    => 'primer',
                'label'  => '1er Ciclo',
                'range'  => '1ro · 2do · 3ro de Básica',
                'grados' => $grados->filter(fn($g) => $g->nivel >= 1 && $g->nivel <= 3)->values(),
            ],
            [
                'key'    => 'segundo',
                'label'  => '2do Ciclo',
                'range'  => '4to · 5to · 6to de Básica',
                'grados' => $grados->filter(fn($g) => $g->nivel >= 4 && $g->nivel <= 6)->values(),
            ],
            [
                'key'    => 'otros',
                'label'  => 'Grados superiores',
                'range'  => '7mo · 8vo de Básica',
                'grados' => $grados->filter(fn($g) => $g->nivel > 6 && $g->ciclo !== 'inicial')->values(),
            ],
        ];
    @endphp

    @foreach($ciclos as $ci => $ciclo)
    @if($ciclo['grados']->count())

    <div class="ciclo-block" style="{{ $ci > 0 ? 'margin-top:1.75rem;' : '' }}">

        {{-- Cycle header --}}
        <div class="ciclo-header">
            <div class="ciclo-title">
                <span class="ciclo-badge {{ $ciclo['key'] }}">{{ $ciclo['label'] }}</span>
                <span class="ciclo-range">{{ $ciclo['range'] }}</span>
            </div>
            <button type="button" class="btn-ciclo-toggle js-ciclo-toggle">
                Activar todos
            </button>
        </div>

        {{-- Grade cards --}}
        <div class="grados-ciclo js-grados-ciclo">
            @foreach($ciclo['grados'] as $grado)
            @php $isActive = $grado->activo !== false; @endphp

            <div class="gc-card {{ $isActive ? 'active' : '' }}"
                 id="gc-{{ $grado->id }}"
                 onclick="toggleGrado({{ $grado->id }})">

                {{-- Hidden checkbox — grade active flag --}}
                <input type="checkbox"
                       name="grados_activos[]"
                       value="{{ $grado->id }}"
                       id="gcheck-{{ $grado->id }}"
                       {{ $isActive ? 'checked' : '' }}
                       style="display:none">

                {{-- Card top row --}}
                <div class="gc-top">
                    <div>
                        <div class="gc-nombre">{{ $grado->nombre }}</div>
                        <div class="gc-nivel">Nivel {{ $grado->nivel }}</div>
                    </div>
                    <div class="gc-check">
                        <i class="bi bi-check2"></i>
                    </div>
                </div>

                {{-- Section picker (only visible when active) --}}
                <div class="gc-secciones" onclick="event.stopPropagation()">
                    <div class="gc-sec-label">Secciones</div>
                    <div class="gc-chips" id="chips-{{ $grado->id }}">
                        @foreach($seccionesDefault as $secNombre)
                        <label class="chip-wrap">
                            <input type="checkbox"
                                   name="secciones[{{ $grado->id }}][]"
                                   value="{{ $secNombre }}"
                                   {{ $loop->first ? 'checked' : '' }}>
                            <span class="chip">{{ $secNombre }}</span>
                        </label>
                        @endforeach

                        <button type="button"
                                class="chip-add"
                                onclick="addSeccion(event, {{ $grado->id }})"
                                title="Añadir sección">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                </div>

            </div>
            @endforeach
        </div>
    </div>

    @endif
    @endforeach

    {{-- Custom grade --}}
    <div style="margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid #e2e8f0;">
        <button type="button" onclick="toggleNuevoGrado()" class="btn-skip" style="color:#3b82f6;">
            <i class="bi bi-plus-circle" style="margin-right:.3rem;"></i>Agregar grado personalizado
        </button>

        <div id="nuevoGradoPanel" style="display:none;margin-top:1rem;">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nombre del grado</label>
                    <input type="text" name="nuevo_grado" class="form-control"
                           placeholder="Ej: 1ro de Bachillerato" maxlength="80">
                </div>
                <div class="form-group">
                    <label class="form-label">Ciclo</label>
                    <select name="nuevo_ciclo" class="form-control">
                        <option value="primer_ciclo">Primer Ciclo</option>
                        <option value="segundo_ciclo">Segundo Ciclo</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="wizard-card-footer">
    <a href="{{ route('admin.onboarding.show', 2) }}" class="btn btn-outline">
        <i class="bi bi-arrow-left"></i> Anterior
    </a>
    <button type="submit" class="btn btn-primary">
        Continuar <i class="bi bi-arrow-right"></i>
    </button>
</div>

</form>
@endsection

@push('scripts')
<script>
/* ── Toggle individual grade card ── */
function toggleGrado(id) {
    const card  = document.getElementById('gc-' + id);
    const check = document.getElementById('gcheck-' + id);
    const isNowActive = !card.classList.contains('active');
    card.classList.toggle('active', isNowActive);
    check.checked = isNowActive;
}

/* ── Toggle all grades in a cycle ── */
document.querySelectorAll('.js-ciclo-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var grid  = this.closest('.ciclo-block').querySelector('.js-grados-ciclo');
        var cards = grid.querySelectorAll('.gc-card');
        var allActive = Array.from(cards).every(function (c) {
            return c.classList.contains('active');
        });
        var toActive = !allActive;

        cards.forEach(function (card) {
            var id = card.id.replace('gc-', '');
            card.classList.toggle('active', toActive);
            var check = document.getElementById('gcheck-' + id);
            if (check) check.checked = toActive;
        });

        btn.textContent = toActive ? 'Desactivar todos' : 'Activar todos';
    });
});

/* ── Add custom section chip ── */
function addSeccion(event, gradoId) {
    event.stopPropagation();
    var chips = document.getElementById('chips-' + gradoId);
    var existing = chips.querySelector('.nueva-sec-field');
    if (existing) { existing.focus(); return; }

    var input = document.createElement('input');
    input.type = 'text';
    input.maxLength = 5;
    input.placeholder = 'D…';
    input.className = 'nueva-sec-field';

    input.addEventListener('click', function (e) { e.stopPropagation(); });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var nombre = this.value.trim().toUpperCase();
            if (!nombre) { this.remove(); return; }

            var ya = chips.querySelector('input[value="' + nombre + '"]');
            if (ya) { ya.checked = true; this.remove(); return; }

            var label = document.createElement('label');
            label.className = 'chip-wrap';

            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.name = 'secciones[' + gradoId + '][]';
            cb.value = nombre;
            cb.checked = true;

            var span = document.createElement('span');
            span.className = 'chip';
            span.textContent = nombre;

            label.appendChild(cb);
            label.appendChild(span);

            var addBtn = chips.querySelector('.chip-add');
            chips.insertBefore(label, addBtn);
            this.remove();
        }
        if (e.key === 'Escape') this.remove();
    });

    input.addEventListener('blur', function () {
        var self = this;
        setTimeout(function () {
            if (document.body.contains(self)) self.remove();
        }, 200);
    });

    var addBtn = chips.querySelector('.chip-add');
    chips.insertBefore(input, addBtn);
    input.focus();
}

/* ── Toggle custom grade panel ── */
function toggleNuevoGrado() {
    var panel = document.getElementById('nuevoGradoPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}
</script>
@endpush
