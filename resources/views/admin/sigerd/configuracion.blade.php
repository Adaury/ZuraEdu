@extends('layouts.admin')
@section('title', 'Configuracion SIGERD')
@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h3 mb-0"><i class="bi bi-gear me-2"></i>Configuracion SIGERD</h1>
                <a href="{{ route('admin.sigerd.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
            </div>
            @if(session('success'))
            <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}</div>
            @endif
            @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background-color:#1e3a6e;color:white;"><i class="bi bi-building me-2"></i>Datos del Centro Educativo</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.sigerd.configuracion.guardar') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Codigo del Centro <span class="text-danger">*</span></label>
                                <input type="text" name="codigo_centro" class="form-control" required
                                    placeholder="Ej: 10-001-0001" value="{{ old('codigo_centro', $config?->codigo_centro) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombre del Centro</label>
                                <input type="text" name="nombre_centro" class="form-control"
                                    value="{{ old('nombre_centro', $config?->nombre_centro) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Regional MINERD</label>
                                <select name="regional" class="form-select">
                                    <option value="">-- Seleccionar Regional --</option>
                                    <option value="01" {{ old('regional', $config?->regional) == '01' ? 'selected' : '' }}>01 - Distrito Nacional</option>
                                    <option value="02" {{ old('regional', $config?->regional) == '02' ? 'selected' : '' }}>02 - Santiago</option>
                                    <option value="03" {{ old('regional', $config?->regional) == '03' ? 'selected' : '' }}>03 - La Vega</option>
                                    <option value="04" {{ old('regional', $config?->regional) == '04' ? 'selected' : '' }}>04 - San Francisco de Macoris</option>
                                    <option value="05" {{ old('regional', $config?->regional) == '05' ? 'selected' : '' }}>05 - San Pedro de Macoris</option>
                                    <option value="06" {{ old('regional', $config?->regional) == '06' ? 'selected' : '' }}>06 - San Juan de la Maguana</option>
                                    <option value="07" {{ old('regional', $config?->regional) == '07' ? 'selected' : '' }}>07 - Barahona</option>
                                    <option value="08" {{ old('regional', $config?->regional) == '08' ? 'selected' : '' }}>08 - Monte Cristi</option>
                                    <option value="09" {{ old('regional', $config?->regional) == '09' ? 'selected' : '' }}>09 - Mao-Valverde</option>
                                    <option value="10" {{ old('regional', $config?->regional) == '10' ? 'selected' : '' }}>10 - Puerto Plata</option>
                                    <option value="11" {{ old('regional', $config?->regional) == '11' ? 'selected' : '' }}>11 - La Romana</option>
                                    <option value="12" {{ old('regional', $config?->regional) == '12' ? 'selected' : '' }}>12 - Higuey</option>
                                    <option value="13" {{ old('regional', $config?->regional) == '13' ? 'selected' : '' }}>13 - Azua</option>
                                    <option value="14" {{ old('regional', $config?->regional) == '14' ? 'selected' : '' }}>14 - Bani</option>
                                    <option value="15" {{ old('regional', $config?->regional) == '15' ? 'selected' : '' }}>15 - Cotui</option>
                                    <option value="16" {{ old('regional', $config?->regional) == '16' ? 'selected' : '' }}>16 - Nagua</option>
                                    <option value="17" {{ old('regional', $config?->regional) == '17' ? 'selected' : '' }}>17 - San Cristobal</option>
                                    <option value="18" {{ old('regional', $config?->regional) == '18' ? 'selected' : '' }}>18 - Bonao</option>
                                    <option value="19" {{ old('regional', $config?->regional) == '19' ? 'selected' : '' }}>19 - Santiago Rodriguez</option>
                                    <option value="20" {{ old('regional', $config?->regional) == '20' ? 'selected' : '' }}>20 - El Seibo</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Distrito</label>
                                <input type="text" name="distrito" class="form-control"
                                    value="{{ old('distrito', $config?->distrito) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Modalidad</label>
                                <select name="modalidad" class="form-select">
                                    <option value="Regular" {{ old('modalidad', $config?->modalidad) == 'Regular' ? 'selected' : '' }}>Regular</option>
                                    <option value="Adultos" {{ old('modalidad', $config?->modalidad) == 'Adultos' ? 'selected' : '' }}>Adultos</option>
                                    <option value="Especial" {{ old('modalidad', $config?->modalidad) == 'Especial' ? 'selected' : '' }}>Especial</option>
                                    <option value="Laboral" {{ old('modalidad', $config?->modalidad) == 'Laboral' ? 'selected' : '' }}>Laboral</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Sector</label>
                                <select name="sector" class="form-select">
                                    <option value="Publico" {{ old('sector', $config?->sector) == 'Publico' ? 'selected' : '' }}>Publico</option>
                                    <option value="Privado" {{ old('sector', $config?->sector) == 'Privado' ? 'selected' : '' }}>Privado</option>
                                    <option value="Semi-oficial" {{ old('sector', $config?->sector) == 'Semi-oficial' ? 'selected' : '' }}>Semi-oficial</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Ano SIGERD</label>
                                <input type="text" name="anio_sigerd" class="form-control"
                                    placeholder="2025-2026" value="{{ old('anio_sigerd', $config?->anio_sigerd) }}">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn text-white" style="background-color:#1e3a6e;">
                                    <i class="bi bi-save"></i> Guardar Configuracion
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
