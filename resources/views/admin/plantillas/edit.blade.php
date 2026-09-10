@extends('layouts.admin')
@section('page-title', 'Editar Plantilla — ' . $def['label'])

@push('styles')
<style>
    .page-header { display:flex; align-items:center; gap:.75rem; margin-bottom:1.5rem; }
    .page-header h1 { font-size:1.3rem; font-weight:800; color:var(--primary); margin:0; }
    .card-panel { background:#fff; border-radius:14px; border:1px solid #e5e7eb; padding:1.5rem; margin-bottom:1.5rem; }
    .var-chip { display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .6rem; border-radius:8px; background:#f0f7ff; border:1px solid #bfdbfe; color:#1d4ed8; font-size:.78rem; font-weight:600; font-family:monospace; cursor:pointer; margin:.15rem; }
    .var-chip:hover { background:#dbeafe; }
    .var-desc { font-size:.72rem; color:#6b7280; margin:0 .15rem .5rem; }
    #plantilla-preview-wa { background:#e5ddd5; border-radius:10px; padding:1rem; min-height:120px; }
    .wa-bubble { background:#dcf8c6; border-radius:8px; padding:.6rem .8rem; font-size:.85rem; white-space:pre-wrap; max-width:100%; box-shadow:0 1px 1px rgba(0,0,0,.1); }
    [data-theme="dark"] #plantilla-preview-wa { background:#0b141a; }
    [data-theme="dark"] .wa-bubble { background:#005c4b; color:#e9edef; }
    [data-theme="dark"] .card-panel { background:#1e293b; border-color:#334155; }
    [data-theme="dark"] .var-chip { background:rgba(59,130,246,.15); border-color:#334155; color:#93c5fd; }
    [data-theme="dark"] .var-desc { color:#94a3b8; }
</style>
@endpush

@section('content')
<div class="page-header">
    <a href="{{ route('admin.plantillas.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1>{{ $def['label'] }}</h1>
        <p class="text-muted small mb-0">Canal: {{ \App\Models\PlantillaComunicacion::CANALES[$canal] }}</p>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger border-0 rounded-3 mb-3">
        @foreach($errors->all() as $e)<div><i class="bi bi-x-circle me-1"></i>{{ $e }}</div>@endforeach
    </div>
@endif

@if(!empty($def['requiere'] ?? []))
    {{-- Construido con llaves separadas por punto -- Blade escanea TODO el
         archivo buscando "{{"..."}}" adyacentes sin distinguir @php de un
         echo; escribir '{{'/'}}'  literales aquí rompe la compilación. --}}
    @php($reqLabels = array_map(fn ($v) => '{' . '{' . $v . '}' . '}', $def['requiere']))
    <div class="p-3 rounded-3 mb-3" style="background:#fef3c7; border:1px solid #fde68a; font-size:.85rem; color:#92400e;">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        Este mensaje debe incluir
        @foreach($reqLabels as $i => $label)
            <code>{{ $label }}</code>
            @if($i !== array_key_last($reqLabels))
                y
            @endif
        @endforeach
        o el destinatario no podrá completar la acción.
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('admin.plantillas.update', [$evento, $canal]) }}">
            @csrf
            @method('PUT')

            <div class="card-panel">
                @if($canal === 'email')
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:.83rem;">Asunto</label>
                        <input type="text" id="plantilla-asunto" name="asunto" maxlength="200" class="form-control"
                               value="{{ old('asunto', $fila->asunto) }}" placeholder="Deja vacío para usar el asunto de siempre">
                    </div>
                @endif

                <div class="mb-2">
                    <label class="form-label fw-600" style="font-size:.83rem;">Mensaje</label>
                    @if($canal === 'whatsapp')
                        <textarea id="plantilla-cuerpo" name="cuerpo" rows="10" maxlength="1500" class="form-control font-monospace"
                                  placeholder="Deja vacío para usar el mensaje de siempre">{{ old('cuerpo', $fila->cuerpo) }}</textarea>
                        <div class="form-text"><span id="plantilla-contador">0</span>/1500 — formato WhatsApp: *negrita*, _cursiva_. No se admite HTML.</div>
                    @else
                        <div id="contenido-editor" class="bg-white rounded-3"></div>
                        <textarea id="contenido-input" name="cuerpo" class="d-none">{{ old('cuerpo', $fila->cuerpo) }}</textarea>
                        <div class="form-text">Deja vacío para usar el diseño de siempre. No se admiten imágenes.</div>
                    @endif
                </div>

                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" name="activa" value="1" id="plantilla-activa"
                           {{ old('activa', $fila->exists ? $fila->activa : true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="plantilla-activa">Plantilla activa</label>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar</button>
                <button type="button" class="btn btn-outline-secondary" id="btn-cargar-default">Cargar texto predeterminado</button>
                @if($fila->exists)
                    <form method="POST" action="{{ route('admin.plantillas.destroy', [$evento, $canal]) }}"
                          onsubmit="return confirm('¿Restaurar el texto predeterminado? Se perderá la personalización.');" class="ms-auto">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">Restaurar predeterminado</button>
                    </form>
                @endif
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="card-panel" style="position:sticky; top:1rem;">
            <div class="fw-700 mb-2" style="font-size:.85rem;">Variables disponibles</div>
            @foreach($def['variables'] as $clave => $info)
                @php($etiquetaVar = '{' . '{' . $clave . '}' . '}')
                <div>
                    <span class="var-chip" data-var="{{ $clave }}">{{ $etiquetaVar }}</span>
                </div>
                <div class="var-desc">{{ $info['desc'] }}</div>
            @endforeach

            <hr>
            <div class="fw-700 mb-2" style="font-size:.85rem;">Vista previa</div>
            @if($canal === 'whatsapp')
                <div id="plantilla-preview-wa"><div class="wa-bubble" id="plantilla-preview-texto"></div></div>
            @else
                <iframe id="plantilla-preview-email" sandbox="" style="width:100%; height:260px; border:1px solid #e5e7eb; border-radius:8px;"></iframe>
            @endif
        </div>
    </div>
</div>

@if($canal === 'email')
    @push('scripts')
        @vite('resources/js/publicaciones-editor.js')
    @endpush
@endif

@push('scripts')
<script>
const EJEMPLOS = @json($ejemplos);
const DEFAULT_TEXTO = @json($default);
const CANAL = @json($canal);

function sustituirEjemplos(txt) {
    Object.keys(EJEMPLOS).forEach(k => { txt = txt.split('{' + '{' + k + '}' + '}').join(EJEMPLOS[k]); });
    return txt.replace(/\{\{\s*[A-Za-z0-9_]+\s*\}\}/g, '');
}

function actualizarPreview() {
    let texto = '';
    if (CANAL === 'whatsapp') {
        texto = document.getElementById('plantilla-cuerpo')?.value || '';
        texto = texto || DEFAULT_TEXTO;
        const sustituido = sustituirEjemplos(texto);
        const bubble = document.getElementById('plantilla-preview-texto');
        bubble.textContent = sustituido;
        // Mini-markdown de WhatsApp SOLO sobre el texto ya escapado (textContent lo hizo arriba).
        let html = bubble.innerHTML;
        html = html.replace(/\*([^*]+)\*/g, '<strong>$1</strong>').replace(/_([^_]+)_/g, '<em>$1</em>');
        bubble.innerHTML = html;

        const contador = document.getElementById('plantilla-contador');
        if (contador) contador.textContent = (document.getElementById('plantilla-cuerpo')?.value || '').length;
    } else {
        const input = document.getElementById('contenido-input');
        const cuerpo = (input?.value || '') || DEFAULT_TEXTO;
        const sustituido = sustituirEjemplos(cuerpo);
        const iframe = document.getElementById('plantilla-preview-email');
        if (iframe) {
            iframe.srcdoc = '<div style="font-family:sans-serif;padding:1rem;">' + sustituido + '</div>';
        }
    }
}

document.getElementById('plantilla-cuerpo')?.addEventListener('input', actualizarPreview);
document.addEventListener('DOMContentLoaded', function () {
    actualizarPreview();
    // Quill sincroniza el textarea oculto en su propio listener 'text-change'
    // (publicaciones-editor.js), no hay un evento nativo del textarea que
    // observar desde aquí -- poll simple y suficiente para una vista previa.
    setInterval(actualizarPreview, 800);
});

document.querySelectorAll('.var-chip').forEach(chip => {
    chip.addEventListener('click', function () {
        const variable = '{' + '{' + this.dataset.var + '}' + '}';
        const textarea = document.getElementById('plantilla-cuerpo');
        if (textarea) {
            const pos = textarea.selectionStart ?? textarea.value.length;
            textarea.value = textarea.value.slice(0, pos) + variable + textarea.value.slice(pos);
            textarea.focus();
            textarea.selectionStart = textarea.selectionEnd = pos + variable.length;
            actualizarPreview();
        } else if (window.quillEditor) {
            const sel = window.quillEditor.getSelection();
            window.quillEditor.insertText(sel ? sel.index : 0, variable);
        }
    });
});

document.getElementById('btn-cargar-default')?.addEventListener('click', function () {
    const textarea = document.getElementById('plantilla-cuerpo');
    if (textarea) {
        textarea.value = DEFAULT_TEXTO;
    } else if (window.quillEditor) {
        // dangerouslyPasteHTML (no root.innerHTML directo) para que Quill
        // dispare 'text-change' y su propio sync() actualice el textarea
        // oculto -- si no, la vista previa y el guardado no verían el cambio
        // hasta la próxima edición manual.
        window.quillEditor.setText('');
        window.quillEditor.clipboard.dangerouslyPasteHTML(0, DEFAULT_TEXTO);
    }
    actualizarPreview();
});
</script>
@endpush
@endsection
