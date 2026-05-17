@extends('layouts.admin')
@section('title', 'Centro de Integraciones')
@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0"><i class="bi bi-plugin me-2"></i> Centro de Integraciones</h1>
            <p class="text-muted">Conecta ZuraEdu SGE con sistemas externos</p>
        </div>
    </div>
    <div class="row g-4">
        {{-- SIGERD MINERD --}}
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm" style="background-color:#1e3a6e;">
                <div class="card-body text-white p-4">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-building fs-2 me-3"></i>
                        <div>
                            <h5 class="mb-0">SIGERD</h5>
                            <small>Sistema MINERD Republica Dominicana</small>
                        </div>
                        <span class="badge bg-success ms-auto">Activo</span>
                    </div>
                    <p class="mb-3">Exportaciones oficiales al sistema SIGERD del Ministerio de Educacion (MINERD). Nomina, calificaciones, docentes y asistencia.</p>
                    <a href="{{ route('admin.sigerd.index') }}" class="btn btn-light btn-sm">Abrir SIGERD <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
        {{-- Google Classroom --}}
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm bg-success">
                <div class="card-body text-white p-4">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-google fs-2 me-3"></i>
                        <div>
                            <h5 class="mb-0">Google Classroom</h5>
                            <small>ZuraClass Integration</small>
                        </div>
                        @php $classroomActive = class_exists('App\Models\ClassroomConfig') && \App\Models\ClassroomConfig::count() > 0; @endphp
                        <span class="badge {{ $classroomActive ? 'bg-warning' : 'bg-secondary' }} ms-auto">{{ $classroomActive ? 'Configurado' : 'Disponible' }}</span>
                    </div>
                    <p class="mb-3">Sincroniza clases, tareas y calificaciones con Google Classroom. Gestiona estudiantes y docentes automaticamente.</p>
                    @if(\Illuminate\Support\Facades\Route::has('admin.classroom.index'))
                    <a href="{{ route('admin.classroom.index') }}" class="btn btn-light btn-sm">Configurar <i class="bi bi-arrow-right"></i></a>
                    @else
                    <a href="#" class="btn btn-light btn-sm">Abrir <i class="bi bi-arrow-right"></i></a>
                    @endif
                </div>
            </div>
        </div>
        {{-- Office 365 --}}
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm bg-primary">
                <div class="card-body text-white p-4">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-microsoft fs-2 me-3"></i>
                        <div><h5 class="mb-0">Office 365</h5><small>Microsoft Education</small></div>
                        <span class="badge bg-secondary ms-auto">Proximamente</span>
                    </div>
                    <p class="mb-3">Integracion con Microsoft Teams, OneDrive y aplicaciones educativas de Office 365.</p>
                    <button class="btn btn-light btn-sm" disabled>Proximamente</button>
                </div>
            </div>
        </div>
        {{-- WhatsApp --}}
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm" style="background-color:#25D366;">
                <div class="card-body text-white p-4">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-whatsapp fs-2 me-3"></i>
                        <div><h5 class="mb-0">WhatsApp Business</h5><small>Notificaciones y alertas</small></div>
                        @php $waActive = \App\Helpers\Setting::moduleEnabled('whatsapp'); @endphp
                        <span class="badge {{ $waActive ? 'bg-warning text-dark' : 'bg-light text-dark' }} ms-auto">
                            {{ $waActive ? 'Activo' : 'Disponible' }}
                        </span>
                    </div>
                    <p class="mb-3">Envía notificaciones de notas, ausencias, pagos y avisos a representantes vía WhatsApp.</p>
                    <a href="{{ route('admin.sistema.whatsapp') }}" class="btn btn-light btn-sm">
                        Configurar <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
