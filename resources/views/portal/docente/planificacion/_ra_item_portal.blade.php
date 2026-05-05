<div class="ra-bloque-portal">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem;">
        <span style="font-size:.75rem;font-weight:700;color:#15803d;">
            <i class="bi bi-bookmark-check me-1"></i>Resultado de Aprendizaje
        </span>
        <button type="button" onclick="eliminarRA(this)"
                style="background:#fee2e2;color:#dc2626;border:none;border-radius:6px;padding:.2rem .5rem;font-size:.75rem;cursor:pointer;">
            <i class="bi bi-trash"></i>
        </button>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.55rem;">
        <div>
            <label class="prt-field-lbl">Código RA</label>
            <input type="text" name="ra[{{ $idx }}][ra_codigo]" class="prt-inp"
                   placeholder="RA8.1"
                   value="{{ old("ra.{$idx}.ra_codigo", $raItem['ra_codigo'] ?? '') }}">
        </div>
        <div>
            <label class="prt-field-lbl">Nivel Taxonómico</label>
            <input type="text" name="ra[{{ $idx }}][nivel_taxonomico]" class="prt-inp"
                   placeholder="Aplicación 3"
                   value="{{ old("ra.{$idx}.nivel_taxonomico", $raItem['nivel_taxonomico'] ?? '') }}">
        </div>
        <div style="grid-column:1/-1;">
            <label class="prt-field-lbl">Descripción del RA</label>
            <textarea name="ra[{{ $idx }}][ra_descripcion]" class="prt-inp" rows="2"
                      placeholder="Seleccionar e instalar herramientas de desarrollo web…">{{ old("ra.{$idx}.ra_descripcion", $raItem['ra_descripcion'] ?? '') }}</textarea>
        </div>
        <div>
            <label class="prt-field-lbl">Elementos de Capacidad <span style="font-weight:400;color:#94a3b8;">(uno por línea)</span></label>
            <textarea name="ra[{{ $idx }}][elementos_capacidad]" class="prt-inp" rows="4"
                      placeholder="1- Identificar los lenguajes...&#10;2- Emplear JS...&#10;3- Clasificar un IDE...">{{ old("ra.{$idx}.elementos_capacidad", $raItem['elementos_capacidad'] ?? '') }}</textarea>
        </div>
        <div>
            <label class="prt-field-lbl">Fechas (Desde / Hasta)</label>
            <div class="fechas-container">
                <div class="fechas-list">
                @php
                    $fDesde = old("ra.{$idx}.fechas_desde", $raItem['fechas_desde'] ?? ['']);
                    $fHasta = old("ra.{$idx}.fechas_hasta", $raItem['fechas_hasta'] ?? ['']);
                    $maxF   = max(count((array)$fDesde), count((array)$fHasta), 1);
                @endphp
                @for($f = 0; $f < $maxF; $f++)
                <div class="fecha-row" style="display:flex;gap:.4rem;margin-bottom:.4rem;align-items:center;">
                    <input type="date" name="ra[{{ $idx }}][fechas_desde][]" class="prt-inp" style="flex:1;"
                           value="{{ $fDesde[$f] ?? '' }}">
                    <input type="date" name="ra[{{ $idx }}][fechas_hasta][]" class="prt-inp" style="flex:1;"
                           value="{{ $fHasta[$f] ?? '' }}">
                    <button type="button" onclick="this.closest('.fecha-row').remove()"
                        style="background:#fee2e2;color:#dc2626;border:none;border-radius:6px;padding:.25rem .5rem;cursor:pointer;font-size:.8rem;">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                @endfor
                </div>
                <button type="button" data-idx="{{ $idx }}" onclick="agregarFecha(this)"
                        style="background:#dcfce7;color:#15803d;border:none;border-radius:6px;padding:.2rem .65rem;font-size:.75rem;cursor:pointer;margin-top:.2rem;">
                    <i class="bi bi-plus me-1"></i>Agregar fechas
                </button>
            </div>
        </div>
        <div style="grid-column:1/-1;">
            <label class="prt-field-lbl">Actividades de Enseñanza-Aprendizaje</label>
            <textarea name="ra[{{ $idx }}][actividades]" class="prt-inp" rows="3"
                      placeholder="Actividad 1: Responden y socializan cuestionario...&#10;Actividad 11: Crean el esqueleto de la página web...">{{ old("ra.{$idx}.actividades", $raItem['actividades'] ?? '') }}</textarea>
        </div>
        <div>
            <label class="prt-field-lbl">Instrumentos de Evaluación</label>
            <textarea name="ra[{{ $idx }}][instrumentos_evaluacion]" class="prt-inp" rows="3"
                      placeholder="Indagación de saberes previos.&#10;Rúbrica.&#10;Lista de cotejo.">{{ old("ra.{$idx}.instrumentos_evaluacion", $raItem['instrumentos_evaluacion'] ?? '') }}</textarea>
        </div>
        <div>
            <label class="prt-field-lbl">Contenidos</label>
            <textarea name="ra[{{ $idx }}][contenidos]" class="prt-inp" rows="3"
                      placeholder="Definir los diferentes lenguajes de programación web...">{{ old("ra.{$idx}.contenidos", $raItem['contenidos'] ?? '') }}</textarea>
        </div>
    </div>
</div>
