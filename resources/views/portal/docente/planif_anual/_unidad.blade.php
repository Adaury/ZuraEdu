@php $uid = $u->id; $sel = $u->competencias ?? []; @endphp
<div class="unidad-card {{ $open ? 'open' : '' }}" id="unidad-{{ $uid }}">
    <div class="unidad-header" onclick="toggleUnidad({{ $uid }})">
        <div class="unidad-num">{{ $u->numero }}</div>
        <input type="text" id="u-titulo-{{ $uid }}" value="{{ $u->titulo }}"
            onclick="event.stopPropagation()"
            oninput="autoGuardarUnidad({{ $uid }})"
            style="flex:1;font-weight:700;font-size:.88rem;border:none;outline:none;background:transparent;color:#1e293b;min-width:80px;"
            placeholder="Título de la unidad">
        @if($u->periodo)
        <span class="per-badge" id="hper-{{ $uid }}">{{ $u->periodo }}</span>
        @else
        <span class="per-badge" id="hper-{{ $uid }}" style="display:none;"></span>
        @endif
        @if($u->semanas)
        <span style="font-size:.68rem;color:#64748b;font-weight:600;">{{ $u->semanas }} sem.</span>
        @endif
        <div style="display:flex;gap:.3rem;align-items:center;margin-left:auto;">
            <span class="save-dot" id="dot-{{ $uid }}"></span>
            <button onclick="event.stopPropagation();toggleIaPanel({{ $uid }})" title="Generar con ZuraIA"
                style="background:#f5f3ff;border:1px solid #c4b5fd;border-radius:6px;color:#7c3aed;padding:.2rem .45rem;font-size:.72rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:.25rem;"><i class="bi bi-stars"></i>IA</button>
            <button onclick="event.stopPropagation();moverUnidad({{ $uid }},'up')" title="Subir"
                style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:.2rem .3rem;font-size:.85rem;"><i class="bi bi-chevron-up"></i></button>
            <button onclick="event.stopPropagation();moverUnidad({{ $uid }},'down')" title="Bajar"
                style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:.2rem .3rem;font-size:.85rem;"><i class="bi bi-chevron-down"></i></button>
            <button onclick="event.stopPropagation();eliminarUnidad({{ $uid }})" title="Eliminar"
                style="background:none;border:none;cursor:pointer;color:#ef4444;padding:.2rem .3rem;font-size:.85rem;"><i class="bi bi-trash3-fill"></i></button>
            <i class="bi bi-chevron-{{ $open ? 'up' : 'down' }}" style="color:#94a3b8;font-size:.8rem;"></i>
        </div>
    </div>
    <div class="unidad-body {{ $open ? 'open' : '' }}" id="body-{{ $uid }}">
        <div class="ia-panel" id="ia-panel-{{ $uid }}" style="display:none;background:linear-gradient(135deg,#f5f3ff,#eff6ff);border:1.5px solid #c4b5fd;border-radius:10px;padding:.7rem .85rem;margin-bottom:.85rem;">
            <div style="font-size:.74rem;font-weight:800;color:#5b21b6;margin-bottom:.4rem;"><i class="bi bi-stars"></i> Generar con ZuraIA</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:.5rem;">
                <input type="text" id="ia-hint-{{ $uid }}" class="field-inp" style="width:100%;" placeholder="Tema de la unidad (opcional)">
                <input type="text" id="ia-ctx-{{ $uid }}" class="field-inp" style="width:100%;" placeholder="Contexto adicional (opcional)">
            </div>
            <div style="display:flex;align-items:center;gap:.5rem;">
                <button type="button" onclick="ejecutarIaUnidad({{ $uid }})"
                    style="background:#7c3aed;color:#fff;border:none;border-radius:7px;padding:.35rem .8rem;font-size:.75rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:.35rem;">
                    <span class="ia-spinner-{{ $uid }}" style="display:none;width:11px;height:11px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;animation:spin .6s linear infinite;"></span>
                    <span class="ia-btn-txt-{{ $uid }}">Generar contenido</span>
                </button>
                <span class="ia-error-{{ $uid }}" style="display:none;color:#dc2626;font-size:.72rem;"></span>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.65rem;margin-bottom:.85rem;">
            <div>
                <label class="field-label">Período</label>
                <select id="u-periodo-{{ $uid }}" class="field-inp" style="width:100%;" onchange="autoGuardarUnidad({{ $uid }})">
                    <option value="">— Todos —</option>
                    @foreach(['P1'=>'Período 1','P2'=>'Período 2','P3'=>'Período 3','P4'=>'Período 4'] as $pv => $pl)
                    <option value="{{ $pv }}" {{ $u->periodo == $pv ? 'selected' : '' }}>{{ $pl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">Semanas estimadas</label>
                <input type="number" id="u-semanas-{{ $uid }}" class="field-inp" style="width:100%;"
                    min="1" max="20" value="{{ $u->semanas }}"
                    onchange="autoGuardarUnidad({{ $uid }})" placeholder="e.g. 4">
            </div>
            <div>
                <label class="field-label">Fechas</label>
                <div style="display:flex;gap:.3rem;align-items:center;">
                    <input type="date" id="u-fi-{{ $uid }}" class="field-inp" style="flex:1;"
                        value="{{ $u->fecha_inicio?->format('Y-m-d') }}"
                        onchange="autoGuardarUnidad({{ $uid }})">
                    <span style="color:#94a3b8;font-size:.7rem;">→</span>
                    <input type="date" id="u-ff-{{ $uid }}" class="field-inp" style="flex:1;"
                        value="{{ $u->fecha_fin?->format('Y-m-d') }}"
                        onchange="autoGuardarUnidad({{ $uid }})">
                </div>
            </div>
        </div>
        <div class="field-group">
            <label class="field-label"><i class="bi bi-bullseye"></i> Objetivos generales</label>
            <textarea class="field-ta" id="u-objetivos-{{ $uid }}" rows="2"
                placeholder="Objetivos de la unidad..."
                oninput="autoGuardarUnidad({{ $uid }})">{{ $u->objetivos }}</textarea>
        </div>
        <div class="field-group">
            <label class="field-label"><i class="bi bi-diagram-3-fill"></i> Competencias</label>
            <div style="display:flex;gap:.35rem;flex-wrap:wrap;">
                @foreach($competencias as $comp)
                <span class="comp-chip {{ in_array($comp, $sel) ? 'sel' : '' }}"
                    data-comp="{{ $comp }}"
                    onclick="toggleComp(this, {{ $uid }})"
                    title="{{ $comp }}">
                    {{ $comp }}
                </span>
                @endforeach
            </div>
        </div>
        <div class="field-group">
            <label class="field-label"><i class="bi bi-check2-all"></i> Indicadores de logro</label>
            <textarea class="field-ta" id="u-indicadores-{{ $uid }}" rows="2"
                placeholder="Indicadores de logro esperados..."
                oninput="autoGuardarUnidad({{ $uid }})">{{ $u->indicadores }}</textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem;">
            <div class="field-group">
                <label class="field-label"><i class="bi bi-list-ul"></i> Contenidos / Temas</label>
                <textarea class="field-ta" id="u-contenidos-{{ $uid }}" rows="3"
                    placeholder="Temas y contenidos..."
                    oninput="autoGuardarUnidad({{ $uid }})">{{ $u->contenidos }}</textarea>
            </div>
            <div class="field-group">
                <label class="field-label"><i class="bi bi-lightbulb-fill"></i> Estrategias / Actividades</label>
                <textarea class="field-ta" id="u-estrategias-{{ $uid }}" rows="3"
                    placeholder="Métodos y actividades..."
                    oninput="autoGuardarUnidad({{ $uid }})">{{ $u->estrategias }}</textarea>
            </div>
            <div class="field-group">
                <label class="field-label"><i class="bi bi-folder-fill"></i> Recursos</label>
                <textarea class="field-ta" id="u-recursos-{{ $uid }}" rows="2"
                    placeholder="Materiales, libros, TICs..."
                    oninput="autoGuardarUnidad({{ $uid }})">{{ $u->recursos }}</textarea>
            </div>
            <div class="field-group">
                <label class="field-label"><i class="bi bi-clipboard-check-fill"></i> Evaluación / Instrumentos</label>
                <textarea class="field-ta" id="u-evaluacion-{{ $uid }}" rows="2"
                    placeholder="Pruebas, rúbricas, portafolio..."
                    oninput="autoGuardarUnidad({{ $uid }})">{{ $u->evaluacion }}</textarea>
            </div>
        </div>
    </div>
</div>
