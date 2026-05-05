@php
    $planData    = $planClase ?? null;
    $momentoTipos = ['inicio' => 'Inicio', 'desarrollo' => 'Desarrollo', 'cierre' => 'Cierre'];
    $duraciones   = \App\Models\PlanClaseMomento::$tipoDuraciones;
    $isEdit       = isset($planClase) && $planClase;
@endphp

<div class="row g-4">
    <div class="col-lg-8">

        {{-- Info General --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-info-circle me-1"></i>Información General</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Título <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" class="form-control @error('titulo') is-invalid @enderror"
                        value="{{ old('titulo', $planData?->titulo) }}" required maxlength="200">
                    @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Plan <span class="text-danger">*</span></label>
                        <select name="tipo_plan" class="form-select @error('tipo_plan') is-invalid @enderror" required>
                            <option value="">-- Selecciona --</option>
                            @foreach(['diaria','semanal','quincenal','mensual'] as $t)
                                <option value="{{ $t }}" @selected(old('tipo_plan', $planData?->tipo_plan) === $t)>{{ ucfirst($t) }}</option>
                            @endforeach
                        </select>
                        @error('tipo_plan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Semana #</label>
                        <input type="number" name="semana" class="form-control" min="1" max="52"
                            value="{{ old('semana', $planData?->semana) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Área</label>
                        <input type="text" class="form-control" value="{{ ucfirst($asignacion->area ?? 'academica') }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control"
                            value="{{ old('fecha_inicio', $planData?->fecha_inicio?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control"
                            value="{{ old('fecha_fin', $planData?->fecha_fin?->format('Y-m-d')) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Momentos Pedagógicos --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-layers me-1"></i>Momentos Pedagógicos</div>
            <div class="card-body">
                @foreach($momentoTipos as $tipo => $label)
                @php $saved = $planData?->momentos->firstWhere('tipo', $tipo); @endphp
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

    <div class="col-lg-4">
        {{-- Intención Pedagógica --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-bullseye me-1"></i>Intención Pedagógica</div>
            <div class="card-body">
                <textarea name="intencion_pedagogica" class="form-control" rows="4"
                    placeholder="Propósito del plan...">{{ old('intencion_pedagogica', $planData?->intencion_pedagogica) }}</textarea>
            </div>
        </div>

        {{-- Estrategias --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-lightbulb me-1"></i>Estrategias Didácticas</div>
            <div class="card-body">
                @foreach($estrategias as $key => $nombre)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="estrategias[]"
                        value="{{ $key }}" id="pest_{{ $key }}"
                        @checked(in_array($key, old('estrategias', $planData?->estrategias ?? [])))>
                    <label class="form-check-label small" for="pest_{{ $key }}">{{ $nombre }}</label>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Archivo --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-paperclip me-1"></i>Archivo Adjunto</div>
            <div class="card-body">
                @if($isEdit && $planData?->tieneArchivo())
                <div class="alert alert-info py-2 small mb-2">
                    <i class="bi bi-file-earmark me-1"></i> {{ $planData->archivo_nombre }}
                </div>
                @endif
                <input type="file" name="archivo" class="form-control @error('archivo') is-invalid @enderror"
                    accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png">
                <div class="form-text">PDF, Word, Excel, PPT — máx. 10 MB</div>
                @error('archivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- Observación --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-chat-left-text me-1"></i>Observación</div>
            <div class="card-body">
                <textarea name="observacion" class="form-control" rows="3">{{ old('observacion', $planData?->observacion) }}</textarea>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> {{ $isEdit ? 'Actualizar Plan' : 'Guardar Plan' }}
            </button>
            <a href="{{ route('portal.docente.planes-clase.index', $asignacion) }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </div>
</div>
