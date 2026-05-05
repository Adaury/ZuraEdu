@extends('layouts.admin')
@section('page-title', 'Ficha de Salud — ' . $estudiante->nombre_completo)

@section('content')
<div class="container-fluid py-3">

{{-- Encabezado ──────────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-heart-pulse text-danger me-2"></i>Ficha de Salud
        </h4>
        <small class="text-muted">
            <a href="{{ route('admin.estudiantes.show', $estudiante) }}" class="text-decoration-none text-muted">
                {{ $estudiante->nombre_completo }}
            </a>
        </small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.salud.incidentes', ['estudiante_id' => $estudiante->id]) }}"
           class="btn btn-outline-warning btn-sm">
            <i class="bi bi-clipboard2-pulse me-1"></i>Ver incidentes
        </a>
        <a href="{{ route('admin.salud.ficha-pdf', $estudiante) }}"
           class="btn btn-outline-danger btn-sm" target="_blank">
            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
        </a>
        <a href="{{ route('admin.estudiantes.show', $estudiante) }}"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver al perfil
        </a>
    </div>
</div>

{{-- Alertas ─────────────────────────────────────────────────────────────── --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2 mb-3">
    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-3">

    {{-- Datos del estudiante ────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center pt-4">
                <img src="{{ $estudiante->foto_url }}" alt="Foto"
                     class="rounded-circle mb-3 border border-3 border-danger-subtle"
                     style="width:90px;height:90px;object-fit:cover;">
                <h6 class="fw-bold mb-1">{{ $estudiante->nombre_completo }}</h6>
                <div class="text-muted small mb-2">Matrícula: {{ $estudiante->numero_matricula ?? '—' }}</div>
                @if($estudiante->cedula)
                <div class="badge bg-secondary-subtle text-secondary mb-2">Cédula: {{ $estudiante->cedula }}</div>
                @endif

                <hr>
                <div class="text-start small">
                    <div class="mb-1">
                        <span class="text-muted">Fecha nac.:</span>
                        <strong>{{ $estudiante->fecha_nacimiento?->format('d/m/Y') ?? '—' }}</strong>
                    </div>
                    <div class="mb-1">
                        <span class="text-muted">Edad:</span>
                        <strong>{{ $estudiante->edad ?? '—' }} años</strong>
                    </div>
                    <div class="mb-1">
                        <span class="text-muted">Sexo:</span>
                        <strong>{{ $estudiante->sexo === 'M' ? 'Masculino' : ($estudiante->sexo === 'F' ? 'Femenino' : '—') }}</strong>
                    </div>
                    <div class="mb-1">
                        <span class="text-muted">Tutor:</span>
                        <strong>{{ $estudiante->tutor_nombre ?? '—' }}</strong>
                    </div>
                    <div>
                        <span class="text-muted">Tel. tutor:</span>
                        <strong>{{ $estudiante->tutor_telefono ?? '—' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Formulario ficha de salud ───────────────────────────────────────── --}}
    <div class="col-12 col-lg-9">
        <form action="{{ route('admin.salud.guardar-ficha', $estudiante) }}" method="POST">
            @csrf

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-danger text-white py-2">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-droplet-half me-2"></i>Información Médica General
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        {{-- Tipo de sangre --}}
                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold small">Tipo de Sangre</label>
                            <select name="tipo_sangre" class="form-select form-select-sm @error('tipo_sangre') is-invalid @enderror">
                                <option value="">— No registrado —</option>
                                @foreach($tiposSangre as $ts)
                                <option value="{{ $ts }}"
                                    {{ old('tipo_sangre', $ficha->tipo_sangre) === $ts ? 'selected' : '' }}>
                                    {{ $ts }}
                                </option>
                                @endforeach
                            </select>
                            @error('tipo_sangre')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Seguro médico --}}
                        <div class="col-6 col-md-4">
                            <label class="form-label fw-semibold small">Seguro Médico</label>
                            <input type="text" name="seguro_medico"
                                   class="form-control form-control-sm @error('seguro_medico') is-invalid @enderror"
                                   value="{{ old('seguro_medico', $ficha->seguro_medico) }}"
                                   placeholder="Ej: ARS Humano, Senasa…">
                            @error('seguro_medico')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Número de seguro --}}
                        <div class="col-12 col-md-5">
                            <label class="form-label fw-semibold small">No. de Póliza / Afiliado</label>
                            <input type="text" name="num_seguro"
                                   class="form-control form-control-sm @error('num_seguro') is-invalid @enderror"
                                   value="{{ old('num_seguro', $ficha->num_seguro) }}"
                                   placeholder="Número de afiliado o póliza">
                            @error('num_seguro')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Alergias --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">
                                <i class="bi bi-flower1 text-warning me-1"></i>Alergias Conocidas
                            </label>
                            <textarea name="alergias" rows="3"
                                      class="form-control form-control-sm @error('alergias') is-invalid @enderror"
                                      placeholder="Ej: penicilina, mariscos, polvo, látex…">{{ old('alergias', $ficha->alergias) }}</textarea>
                            @error('alergias')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Condiciones médicas --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">
                                <i class="bi bi-activity text-danger me-1"></i>Condiciones Médicas
                            </label>
                            <textarea name="condiciones_medicas" rows="3"
                                      class="form-control form-control-sm @error('condiciones_medicas') is-invalid @enderror"
                                      placeholder="Ej: diabetes, asma, epilepsia, hipertensión…">{{ old('condiciones_medicas', $ficha->condiciones_medicas) }}</textarea>
                            @error('condiciones_medicas')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Medicamentos --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold small">
                                <i class="bi bi-capsule text-primary me-1"></i>Medicamentos de Uso Regular
                            </label>
                            <textarea name="medicamentos" rows="2"
                                      class="form-control form-control-sm @error('medicamentos') is-invalid @enderror"
                                      placeholder="Nombre del medicamento, dosis, frecuencia…">{{ old('medicamentos', $ficha->medicamentos) }}</textarea>
                            @error('medicamentos')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- Contacto de emergencia --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-warning text-dark py-2">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-telephone-fill me-2"></i>Contacto de Emergencia
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-7">
                            <label class="form-label fw-semibold small">Nombre del Contacto</label>
                            <input type="text" name="contacto_emergencia"
                                   class="form-control form-control-sm @error('contacto_emergencia') is-invalid @enderror"
                                   value="{{ old('contacto_emergencia', $ficha->contacto_emergencia) }}"
                                   placeholder="Nombre completo del contacto de emergencia">
                            @error('contacto_emergencia')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label fw-semibold small">Teléfono de Emergencia</label>
                            <input type="text" name="telefono_emergencia"
                                   class="form-control form-control-sm @error('telefono_emergencia') is-invalid @enderror"
                                   value="{{ old('telefono_emergencia', $ficha->telefono_emergencia) }}"
                                   placeholder="Ej: 809-000-0000">
                            @error('telefono_emergencia')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.estudiantes.show', $estudiante) }}" class="btn btn-outline-secondary btn-sm">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-danger btn-sm px-4">
                    <i class="bi bi-floppy me-1"></i>Guardar Ficha
                </button>
            </div>

        </form>
    </div>

</div>

{{-- Historial reciente de incidentes ─────────────────────────────────────── --}}
@if($incidentes->isNotEmpty())
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header py-2 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-clock-history text-warning me-2"></i>Últimos Incidentes Médicos
        </h6>
        <a href="{{ route('admin.salud.incidentes', ['estudiante_id' => $estudiante->id]) }}"
           class="btn btn-outline-warning btn-sm">Ver todos</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Acción tomada</th>
                        <th>Remitido a</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($incidentes as $inc)
                    @php $ti = $inc->tipo_info; @endphp
                    <tr>
                        <td class="small">{{ $inc->fecha->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge rounded-pill"
                                  style="background:{{ $ti['bg'] }};color:{{ $ti['color'] }};">
                                <i class="bi {{ $ti['icon'] }} me-1"></i>{{ $ti['label'] }}
                            </span>
                        </td>
                        <td class="small">{{ Str::limit($inc->descripcion, 60) }}</td>
                        <td class="small">{{ Str::limit($inc->accion_tomada, 50) }}</td>
                        <td class="small text-muted">{{ $inc->remitido_a ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

</div>
@endsection
