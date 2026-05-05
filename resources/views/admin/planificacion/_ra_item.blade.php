<div class="ra-bloque border-bottom" style="padding:1rem;">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <strong class="text-success" style="font-size:.85rem;">
            <i class="bi bi-bookmark-check me-1"></i>Resultado de Aprendizaje #{{ is_numeric($idx) ? $idx + 1 : '?' }}
        </strong>
        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="eliminarRA(this)">
            <i class="bi bi-trash"></i>
        </button>
    </div>
    <div class="row g-2">
        {{-- Código RA --}}
        <div class="col-md-2">
            <label class="form-label form-label-sm fw-semibold mb-1">Código RA</label>
            <input type="text" name="ra[{{ $idx }}][ra_codigo]"
                   class="form-control form-control-sm font-monospace"
                   placeholder="RA8.1"
                   value="{{ old("ra.{$idx}.ra_codigo", $raItem['ra_codigo'] ?? '') }}">
        </div>
        {{-- Nivel taxonómico --}}
        <div class="col-md-3">
            <label class="form-label form-label-sm fw-semibold mb-1">Nivel Taxonómico</label>
            <input type="text" name="ra[{{ $idx }}][nivel_taxonomico]"
                   class="form-control form-control-sm"
                   placeholder="Aplicación 3"
                   value="{{ old("ra.{$idx}.nivel_taxonomico", $raItem['nivel_taxonomico'] ?? '') }}">
        </div>
        {{-- Descripción RA --}}
        <div class="col-md-7">
            <label class="form-label form-label-sm fw-semibold mb-1">Descripción del RA</label>
            <textarea name="ra[{{ $idx }}][ra_descripcion]"
                      class="form-control form-control-sm" rows="2"
                      placeholder="Seleccionar e instalar herramientas de desarrollo web…">{{ old("ra.{$idx}.ra_descripcion", $raItem['ra_descripcion'] ?? '') }}</textarea>
        </div>
        {{-- Elementos de capacidad --}}
        <div class="col-md-6">
            <label class="form-label form-label-sm fw-semibold mb-1">
                Elementos de Capacidad
                <span class="text-muted fw-normal">(uno por línea)</span>
            </label>
            <textarea name="ra[{{ $idx }}][elementos_capacidad]"
                      class="form-control form-control-sm" rows="4"
                      placeholder="1- Identificar los lenguajes informáticos...&#10;2- Emplear el lenguaje JS...&#10;3- Clasificar un IDE...">{{ old("ra.{$idx}.elementos_capacidad", $raItem['elementos_capacidad'] ?? '') }}</textarea>
        </div>
        {{-- Fechas --}}
        <div class="col-md-6">
            <label class="form-label form-label-sm fw-semibold mb-1">Fechas (Desde — Hasta)</label>
            <div class="fechas-container">
                <div class="fechas-list">
                @php
                    $fechasDesde = old("ra.{$idx}.fechas_desde", $raItem['fechas_desde'] ?? ['']);
                    $fechasHasta = old("ra.{$idx}.fechas_hasta", $raItem['fechas_hasta'] ?? ['']);
                    $maxF = max(count($fechasDesde), count($fechasHasta), 1);
                @endphp
                @for($f = 0; $f < $maxF; $f++)
                <div class="row g-1 mb-1 fecha-row align-items-center">
                    <div class="col-5">
                        <input type="date" name="ra[{{ $idx }}][fechas_desde][]"
                               class="form-control form-control-sm"
                               value="{{ $fechasDesde[$f] ?? '' }}">
                    </div>
                    <div class="col-5">
                        <input type="date" name="ra[{{ $idx }}][fechas_hasta][]"
                               class="form-control form-control-sm"
                               value="{{ $fechasHasta[$f] ?? '' }}">
                    </div>
                    <div class="col-2">
                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-1"
                                onclick="this.closest('.fecha-row').remove()">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
                @endfor
                </div>
                <button type="button" class="btn btn-outline-success btn-sm py-0 mt-1"
                        data-idx="{{ $idx }}" onclick="agregarFecha(this)">
                    <i class="bi bi-plus me-1"></i>Agregar fechas
                </button>
            </div>
        </div>
        {{-- Actividades --}}
        <div class="col-12">
            <label class="form-label form-label-sm fw-semibold mb-1">Actividades de Enseñanza-Aprendizaje</label>
            <textarea name="ra[{{ $idx }}][actividades]"
                      class="form-control form-control-sm" rows="3"
                      placeholder="Actividad 1: Responden y socializan cuestionario...&#10;Actividad 11: Crean el esqueleto de la página web...">{{ old("ra.{$idx}.actividades", $raItem['actividades'] ?? '') }}</textarea>
        </div>
        {{-- Instrumentos --}}
        <div class="col-md-6">
            <label class="form-label form-label-sm fw-semibold mb-1">Instrumentos de Evaluación</label>
            <textarea name="ra[{{ $idx }}][instrumentos_evaluacion]"
                      class="form-control form-control-sm" rows="3"
                      placeholder="Indagación de saberes previos.&#10;Rúbrica.&#10;Lista de cotejo.">{{ old("ra.{$idx}.instrumentos_evaluacion", $raItem['instrumentos_evaluacion'] ?? '') }}</textarea>
        </div>
        {{-- Contenidos --}}
        <div class="col-md-6">
            <label class="form-label form-label-sm fw-semibold mb-1">Contenidos</label>
            <textarea name="ra[{{ $idx }}][contenidos]"
                      class="form-control form-control-sm" rows="3"
                      placeholder="Descripción de la materia. Definir los diferentes lenguajes...">{{ old("ra.{$idx}.contenidos", $raItem['contenidos'] ?? '') }}</textarea>
        </div>
    </div>
</div>
