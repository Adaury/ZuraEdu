<div class="ra-bloque-portal" data-ra-idx="{{ $idx }}">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem;flex-wrap:wrap;gap:.4rem;">
        <span style="font-size:.75rem;font-weight:700;color:#15803d;">
            <i class="bi bi-bookmark-check me-1"></i>Resultado de Aprendizaje
        </span>
        <div style="display:flex;gap:.35rem;align-items:center;">
            <button type="button" onclick="abrirIaRA(this)"
                    style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:6px;padding:.2rem .6rem;font-size:.73rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.3rem;">
                <i class="bi bi-stars"></i> Generar con ZuraIA
            </button>
            <button type="button" onclick="eliminarRA(this)"
                    style="background:#fee2e2;color:#dc2626;border:none;border-radius:6px;padding:.2rem .5rem;font-size:.75rem;cursor:pointer;">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>

    {{-- Panel IA (oculto por defecto) --}}
    <div class="zura-ia-panel" style="display:none;background:#f0f9ff;border:1.5px solid #7dd3fc;border-radius:8px;padding:.75rem;margin-bottom:.7rem;">
        <div style="font-size:.72rem;font-weight:800;color:#0369a1;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;">
            <i class="bi bi-stars me-1"></i> ZuraIA — Asistente de Planificación
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:.5rem;">
            <div style="grid-column:1/-1;">
                <label style="font-size:.71rem;font-weight:700;color:#374151;display:block;margin-bottom:.2rem;">
                    ¿Sobre qué trata este RA? <span style="font-weight:400;color:#94a3b8;">(breve descripción)</span>
                </label>
                <input type="text" class="ia-hint prt-inp"
                       placeholder="Ej: Instalación y configuración de servidores web Apache..."
                       style="font-size:.8rem;">
            </div>
            <div>
                <label style="font-size:.71rem;font-weight:700;color:#374151;display:block;margin-bottom:.2rem;">Código RA (opcional)</label>
                <input type="text" class="ia-ra-codigo prt-inp" placeholder="RA8.1" style="font-size:.8rem;">
            </div>
            <div>
                <label style="font-size:.71rem;font-weight:700;color:#374151;display:block;margin-bottom:.2rem;">Nivel Taxonómico</label>
                <select class="ia-nivel prt-inp" style="font-size:.8rem;">
                    <option>Conocimiento</option>
                    <option>Comprensión</option>
                    <option selected>Aplicación</option>
                    <option>Análisis</option>
                    <option>Síntesis</option>
                    <option>Evaluación</option>
                </select>
            </div>
            <div style="grid-column:1/-1;">
                <label style="font-size:.71rem;font-weight:700;color:#374151;display:block;margin-bottom:.2rem;">Contexto adicional <span style="font-weight:400;color:#94a3b8;">(opcional)</span></label>
                <input type="text" class="ia-contexto prt-inp"
                       placeholder="Ej: estudiantes de 3er año, enfoque práctico..."
                       style="font-size:.8rem;">
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:.5rem;">
            <button type="button" onclick="ejecutarIaRA(this)"
                    style="background:#1d4ed8;color:#fff;border:none;border-radius:7px;padding:.35rem .9rem;font-size:.78rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.4rem;">
                <i class="bi bi-stars"></i>
                <span class="ia-btn-txt">Generar con IA</span>
            </button>
            <span class="ia-spinner" style="display:none;font-size:.78rem;color:#0369a1;">
                <i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite;"></i> Generando…
            </span>
            <span class="ia-error" style="display:none;font-size:.76rem;color:#dc2626;"></span>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.55rem;">
        <div>
            <label class="prt-field-lbl">Código RA</label>
            <input type="text" name="ra[{{ $idx }}][ra_codigo]" class="prt-inp campo-ra-codigo"
                   placeholder="RA8.1"
                   value="{{ old("ra.{$idx}.ra_codigo", $raItem['ra_codigo'] ?? '') }}">
        </div>
        <div>
            <label class="prt-field-lbl">Nivel Taxonómico</label>
            <input type="text" name="ra[{{ $idx }}][nivel_taxonomico]" class="prt-inp campo-nivel"
                   placeholder="Aplicación 3"
                   value="{{ old("ra.{$idx}.nivel_taxonomico", $raItem['nivel_taxonomico'] ?? '') }}">
        </div>
        <div style="grid-column:1/-1;">
            <label class="prt-field-lbl">Descripción del RA</label>
            <textarea name="ra[{{ $idx }}][ra_descripcion]" class="prt-inp campo-ra-descripcion" rows="2"
                      placeholder="Seleccionar e instalar herramientas de desarrollo web…">{{ old("ra.{$idx}.ra_descripcion", $raItem['ra_descripcion'] ?? '') }}</textarea>
        </div>
        <div>
            <label class="prt-field-lbl">Elementos de Capacidad <span style="font-weight:400;color:#94a3b8;">(uno por línea)</span></label>
            <textarea name="ra[{{ $idx }}][elementos_capacidad]" class="prt-inp campo-elementos" rows="4"
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
            <textarea name="ra[{{ $idx }}][actividades]" class="prt-inp campo-actividades" rows="3"
                      placeholder="Actividad 1: Responden y socializan cuestionario...&#10;Actividad 11: Crean el esqueleto de la página web...">{{ old("ra.{$idx}.actividades", $raItem['actividades'] ?? '') }}</textarea>
        </div>
        <div>
            <label class="prt-field-lbl">Instrumentos de Evaluación</label>
            <textarea name="ra[{{ $idx }}][instrumentos_evaluacion]" class="prt-inp campo-instrumentos" rows="3"
                      placeholder="Indagación de saberes previos.&#10;Rúbrica.&#10;Lista de cotejo.">{{ old("ra.{$idx}.instrumentos_evaluacion", $raItem['instrumentos_evaluacion'] ?? '') }}</textarea>
        </div>
        <div>
            <label class="prt-field-lbl">Contenidos</label>
            <textarea name="ra[{{ $idx }}][contenidos]" class="prt-inp campo-contenidos" rows="3"
                      placeholder="Definir los diferentes lenguajes de programación web...">{{ old("ra.{$idx}.contenidos", $raItem['contenidos'] ?? '') }}</textarea>
        </div>
    </div>
</div>
