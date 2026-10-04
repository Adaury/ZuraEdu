@extends('layouts.admin')

@section('page-title', 'Copia de mis datos')

@section('content')
<div class="space-y-6" style="max-width:880px;">

    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Copia de mis datos</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Descarga un archivo ZIP con la información de <strong>{{ tenant()?->nombre_institucion }}</strong>, una hoja (CSV) por cada tabla.
            Sirve para guardar una copia propia o abrirla en Excel.
        </p>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card" style="border-radius:12px;">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">¿Qué incluye?</h2>
            <ul class="mb-3" style="line-height:1.7;">
                <li>Estudiantes, matrículas, calificaciones, asistencia, grupos, horarios, pagos y todo lo académico y administrativo del colegio
                    (<strong>{{ $tablas }} tablas</strong>).</li>
                <li>Solo datos de <strong>su colegio</strong>. Nunca de otros.</li>
            </ul>

            <h2 class="h6 fw-bold mb-2">¿Qué NO incluye?</h2>
            <ul class="mb-3" style="line-height:1.7;">
                <li>Contraseñas, claves ni credenciales de integraciones.</li>
                <li>Mensajes privados y notificaciones.</li>
                <li>Archivos subidos (fotos, entregas, documentos).</li>
                @if(! $puedeSensibles)
                    <li>Datos sensibles (salud, disciplina, trabajo social, nómina y evaluaciones docentes): los exporta la Dirección.</li>
                @endif
            </ul>

            <div class="alert alert-warning" style="font-size:.85rem;">
                <i class="bi bi-shield-exclamation me-1"></i>
                El archivo contiene datos personales de estudiantes y familias. Guárdelo en un lugar seguro y no lo comparta.
                Esta descarga queda registrada en el log de actividad.
            </div>
            <div class="alert alert-info" style="font-size:.85rem;">
                <i class="bi bi-info-circle me-1"></i>
                Es una copia para consulta y archivo; no permite restaurar el sistema por sí sola.
            </div>

            <form method="POST" action="{{ route('admin.respaldo-colegio.descargar') }}" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').innerText='Preparando… puede tardar';">
                @csrf
                @if($puedeSensibles)
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="incluir_sensibles" value="1" id="sens">
                        <label class="form-check-label" for="sens">
                            Incluir datos sensibles (salud, disciplina, trabajo social, nómina, evaluaciones docentes)
                        </label>
                    </div>
                @endif
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-cloud-arrow-down me-1"></i>Descargar copia (ZIP)
                </button>
                <span class="text-muted ms-2" style="font-size:.8rem;">Máximo 3 descargas por hora.</span>
            </form>
        </div>
    </div>
</div>
@endsection
