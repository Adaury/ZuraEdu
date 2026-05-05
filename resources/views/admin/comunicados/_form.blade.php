{{-- Shared form for create/edit --}}
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" value="{{ old('titulo', $comunicado->titulo ?? '') }}"
                           class="form-control @error('titulo') is-invalid @enderror"
                           placeholder="Ej: Reunión de padres — 15 de marzo" maxlength="255" required>
                    @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Contenido <span class="text-danger">*</span></label>
                    <textarea name="cuerpo" rows="10"
                              class="form-control @error('cuerpo') is-invalid @enderror"
                              placeholder="Escriba el contenido del comunicado…" required>{{ old('cuerpo', $comunicado->cuerpo ?? '') }}</textarea>
                    @error('cuerpo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3" style="color:var(--primary);">Publicación</h6>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.83rem;">Fecha de publicación</label>
                    <input type="datetime-local" name="published_at"
                           value="{{ old('published_at', isset($comunicado) && $comunicado->published_at ? $comunicado->published_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"
                           class="form-control form-control-sm">
                    <div class="form-text">Déjalo vacío para guardar como borrador.</div>
                </div>
                @isset($comunicado)
                <div class="mb-3">
                    <div class="form-check">
                        <input type="hidden" name="activo" value="0">
                        <input type="checkbox" name="activo" value="1" id="chkActivo"
                               class="form-check-input"
                               {{ old('activo', $comunicado->activo) ? 'checked' : '' }}>
                        <label class="form-check-label" for="chkActivo" style="font-size:.83rem;">Activo</label>
                    </div>
                </div>
                @endisset
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3" style="color:var(--primary);">Destinatarios</h6>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.83rem;">Enviar a <span class="text-danger">*</span></label>
                    <select name="tipo_destinatarios" id="tipoDestinatarios"
                            class="form-select form-select-sm @error('tipo_destinatarios') is-invalid @enderror"
                            onchange="toggleGrupo(this.value)">
                        <option value="todos"         {{ old('tipo_destinatarios', $comunicado->tipo_destinatarios ?? '') === 'todos'         ? 'selected' : '' }}>Todos</option>
                        <option value="docentes"      {{ old('tipo_destinatarios', $comunicado->tipo_destinatarios ?? '') === 'docentes'      ? 'selected' : '' }}>Docentes</option>
                        <option value="coordinadores" {{ old('tipo_destinatarios', $comunicado->tipo_destinatarios ?? '') === 'coordinadores' ? 'selected' : '' }}>Coordinadores y Directivos</option>
                        <option value="grupo"         {{ old('tipo_destinatarios', $comunicado->tipo_destinatarios ?? '') === 'grupo'         ? 'selected' : '' }}>Grupo específico</option>
                    </select>
                    @error('tipo_destinatarios')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div id="grupoField" style="display:none;">
                    <label class="form-label fw-semibold" style="font-size:.83rem;">Grupo</label>
                    <select name="grupo_id" class="form-select form-select-sm @error('grupo_id') is-invalid @enderror">
                        <option value="">— Seleccionar —</option>
                        @foreach($grupos as $g)
                        <option value="{{ $g->id }}" {{ old('grupo_id', $comunicado->grupo_id ?? '') == $g->id ? 'selected' : '' }}>
                            {{ $g->nombre_completo }}
                        </option>
                        @endforeach
                    </select>
                    @error('grupo_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 mt-3 fw-semibold" style="border-radius:8px;">
            <i class="bi bi-floppy me-1"></i>{{ isset($comunicado) ? 'Actualizar' : 'Publicar Comunicado' }}
        </button>
    </div>
</div>

<script>
function toggleGrupo(val) {
    document.getElementById('grupoField').style.display = val === 'grupo' ? '' : 'none';
}
toggleGrupo(document.getElementById('tipoDestinatarios').value);
</script>
