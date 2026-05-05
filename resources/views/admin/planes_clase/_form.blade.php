@php
    $isEdit = isset($plan) && $plan;
    $momentoTipos = ['inicio' => 'Inicio', 'desarrollo' => 'Desarrollo', 'cierre' => 'Cierre'];
    $duraciones   = \App\Models\PlanClaseMomento::$tipoDuraciones;
@endphp

@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-4">
    {{-- Left column --}}
    <div class="col-lg-8">

        {{-- Info General --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-info-circle me-1"></i>Información General</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Título <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" class="form-control @error('titulo') is-invalid @enderror"
                        value="{{ old('titulo', $plan?->titulo) }}" required maxlength="200">
                    @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Área <span class="text-danger">*</span></label>
                        <select name="area" class="form-select @error('area') is-invalid @enderror" required>
                            <option value="">-- Selecciona --</option>
                            <option value="academica" @selected(old('area', $plan?->area) === 'academica')>Académica</option>
                            <option value="tecnica"   @selected(old('area', $plan?->area) === 'tecnica')>Técnica</option>
                        </select>
                        @error('area')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Plan <span class="text-danger">*</span></label>
                        <select name="tipo_plan" class="form-select @error('tipo_plan') is-invalid @enderror" required>
                            <option value="">-- Selecciona --</option>
                            @foreach(['diaria','semanal','quincenal','mensual'] as $t)
                                <option value="{{ $t }}" @selected(old('tipo_plan', $plan?->tipo_plan) === $t)>{{ ucfirst($t) }}</option>
                            @endforeach
                        </select>
                        @error('tipo_plan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Semana #</label>
                        <input type="number" name="semana" class="form-control" min="1" max="52"
                            value="{{ old('semana', $plan?->semana) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control"
                            value="{{ old('fecha_inicio', $plan?->fecha_inicio?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control"
                            value="{{ old('fecha_fin', $plan?->fecha_fin?->format('Y-m-d')) }}">
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label">Asignación</label>
                        <select name="asignacion_id" class="form-select">
                            <option value="">-- Sin asignación --</option>
                            @foreach($asignaciones as $asi)
                                <option value="{{ $asi->id }}" @selected(old('asignacion_id', $plan?->asignacion_id) == $asi->id)>
                                    {{ $asi->asignatura->nombre }} — {{ $asi->grupo->nombre_completo ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Grado / Sección</label>
                        <input type="text" name="grado_seccion" class="form-control" maxlength="100"
                            value="{{ old('grado_seccion', $plan?->grado_seccion) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Momentos Pedagógicos --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-layers me-1"></i>Momentos Pedagógicos</div>
            <div class="card-body">
                @foreach($momentoTipos as $tipo => $label)
                @php
                    $saved = $plan?->momentos->firstWhere('tipo', $tipo);
                @endphp
                <div class="border rounded p-3 mb-3">
                    <h6 class="fw-bold mb-3 text-{{ ['inicio'=>'success','desarrollo'=>'primary','cierre'=>'warning'][$tipo] }}">
                        <i class="bi bi-{{ ['inicio'=>'play-circle','desarrollo'=>'arrow-right-circle','cierre'=>'stop-circle'][$tipo] }} me-1"></i>
                        {{ $label }}
                    </h6>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small">Duración (min)</label>
                            <input type="number" name="momentos[{{ $tipo }}][duracion_minutos]" class="form-control form-control-sm"
                                min="1" max="300"
                                value="{{ old("momentos.$tipo.duracion_minutos", $saved?->duracion_minutos ?? $duraciones[$tipo]) }}">
                        </div>
                        <div class="col-md-9">
                            <label class="form-label small">Área Curricular</label>
                            <input type="text" name="momentos[{{ $tipo }}][area_curricular]" class="form-control form-control-sm"
                                value="{{ old("momentos.$tipo.area_curricular", $saved?->area_curricular) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Competencias Específicas</label>
                            <textarea name="momentos[{{ $tipo }}][competencias_especificas]" class="form-control form-control-sm" rows="2">{{ old("momentos.$tipo.competencias_especificas", $saved?->competencias_especificas) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Contenidos</label>
                            <textarea name="momentos[{{ $tipo }}][contenidos]" class="form-control form-control-sm" rows="2">{{ old("momentos.$tipo.contenidos", $saved?->contenidos) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Actividades / Estrategias</label>
                            <textarea name="momentos[{{ $tipo }}][actividades]" class="form-control form-control-sm" rows="3">{{ old("momentos.$tipo.actividades", $saved?->actividades) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Indicador de Logro</label>
                            <textarea name="momentos[{{ $tipo }}][indicador_logro]" class="form-control form-control-sm" rows="2">{{ old("momentos.$tipo.indicador_logro", $saved?->indicador_logro) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Recursos</label>
                            <textarea name="momentos[{{ $tipo }}][recursos]" class="form-control form-control-sm" rows="2">{{ old("momentos.$tipo.recursos", $saved?->recursos) }}</textarea>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- Right column --}}
    <div class="col-lg-4">

        {{-- Intención Pedagógica --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-bullseye me-1"></i>Intención Pedagógica</div>
            <div class="card-body">
                <textarea name="intencion_pedagogica" class="form-control" rows="4"
                    placeholder="Describe el propósito del plan...">{{ old('intencion_pedagogica', $plan?->intencion_pedagogica) }}</textarea>
            </div>
        </div>

        {{-- Estrategias --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-lightbulb me-1"></i>Estrategias Didácticas</div>
            <div class="card-body">
                @foreach($estrategias as $key => $nombre)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="estrategias[]"
                        value="{{ $key }}" id="est_{{ $key }}"
                        @checked(in_array($key, old('estrategias', $plan?->estrategias ?? [])))>
                    <label class="form-check-label small" for="est_{{ $key }}">{{ $nombre }}</label>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Archivo --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-paperclip me-1"></i>Archivo Adjunto</div>
            <div class="card-body">
                @if($isEdit && $plan?->tieneArchivo())
                <div class="alert alert-info py-2 small mb-2">
                    <i class="bi bi-file-earmark me-1"></i>
                    <strong>Actual:</strong> {{ $plan->archivo_nombre }}
                    <a href="{{ route('admin.planes-clase.download', $plan) }}" class="ms-2"><i class="bi bi-download"></i></a>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="eliminar_archivo" id="eliminar_archivo" value="1">
                    <label class="form-check-label small text-danger" for="eliminar_archivo">Eliminar archivo actual</label>
                </div>
                <hr class="my-2">
                <label class="form-label small">Reemplazar con nuevo archivo:</label>
                @else
                <label class="form-label small">Subir archivo (PDF, Word, Excel, PPT, imágenes)</label>
                @endif
                <input type="file" name="archivo" class="form-control @error('archivo') is-invalid @enderror"
                    accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png">
                <div class="form-text">Máx. 10 MB</div>
                @error('archivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- Observación --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-chat-left-text me-1"></i>Observación</div>
            <div class="card-body">
                <textarea name="observacion" class="form-control" rows="3">{{ old('observacion', $plan?->observacion) }}</textarea>
            </div>
        </div>

        {{-- Estado --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-toggle-on me-1"></i>Estado</div>
            <div class="card-body">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="publicado" id="publicado" value="1"
                        @checked(old('publicado', $plan?->publicado))>
                    <label class="form-check-label" for="publicado">Publicado</label>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> {{ $isEdit ? 'Actualizar Plan' : 'Crear Plan' }}
            </button>
            <a href="{{ route('admin.planes-clase.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </div>
</div>
