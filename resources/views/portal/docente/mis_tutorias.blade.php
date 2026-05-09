@extends('layouts.portal')
@section('page-title', 'Mis Tutorías')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'mis-tutorias'])
@endsection

@section('content')

{{-- Header --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.dashboard') }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1.05rem;font-weight:800;margin:0;">
            <i class="bi bi-person-hearts" style="color:#7c3aed;"></i>
            Mis Tutorías
        </h1>
        <div style="font-size:.75rem;color:#64748b;">
            @if($schoolYear) {{ $schoolYear->nombre }} · @endif
            {{ $tutorias->count() }} grupo(s) asignado(s)
        </div>
    </div>
</div>

@if(session('success'))
<div style="background:#d1fae5;border:1px solid #6ee7b7;border-radius:10px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.84rem;color:#065f46;display:flex;align-items:center;gap:.5rem;">
    <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
</div>
@endif

@if($tutorias->isEmpty())
<div style="text-align:center;padding:3rem 1rem;color:#94a3b8;">
    <i class="bi bi-person-hearts" style="font-size:3rem;display:block;margin-bottom:.75rem;color:#cbd5e1;"></i>
    <div style="font-weight:600;margin-bottom:.25rem;color:#64748b;">No tienes tutorías asignadas</div>
    <div style="font-size:.82rem;">Para este año escolar no tienes grupos de tutoría asignados.</div>
</div>
@else

@foreach($tutorias as $tutoria)
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;margin-bottom:1.5rem;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);">

    {{-- Header del grupo --}}
    <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:1rem 1.25rem;display:flex;align-items:center;gap:.75rem;">
        <div style="width:42px;height:42px;border-radius:10px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-people-fill" style="color:#fff;font-size:1.1rem;"></i>
        </div>
        <div style="flex:1;">
            <div style="color:#fff;font-weight:800;font-size:.95rem;">{{ $tutoria->grupo->nombre_completo ?? '—' }}</div>
            <div style="color:rgba(255,255,255,.75);font-size:.75rem;">
                {{ $tutoria->grupo->estudiantes->count() }} estudiante(s) ·
                {{ $tutoria->sesiones->count() }} sesión(es) registrada(s)
            </div>
        </div>
        @if($tutoria->descripcion)
        <div style="color:rgba(255,255,255,.7);font-size:.75rem;max-width:200px;text-align:right;">
            {{ \Str::limit($tutoria->descripcion, 60) }}
        </div>
        @endif
    </div>

    <div style="padding:1.25rem;">

        {{-- Sesiones registradas --}}
        @if($tutoria->sesiones->isNotEmpty())
        <div style="margin-bottom:1.25rem;">
            <div style="font-size:.72rem;text-transform:uppercase;font-weight:700;letter-spacing:.08em;color:#64748b;margin-bottom:.75rem;">
                <i class="bi bi-clock-history me-1"></i>Sesiones Registradas
            </div>
            <div style="display:flex;flex-direction:column;gap:.5rem;">
            @foreach($tutoria->sesiones->take(5) as $sesion)
            <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:.65rem 1rem;font-size:.82rem;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;flex-wrap:wrap;">
                    <div>
                        <span style="font-weight:700;color:#1e293b;">{{ $sesion->tema }}</span>
                        <span style="color:#94a3b8;font-size:.75rem;margin-left:.5rem;">{{ $sesion->fecha->format('d/m/Y') }}</span>
                    </div>
                    @if($sesion->proxima_sesion)
                    <span style="background:#ede9fe;color:#7c3aed;font-size:.68rem;padding:.1rem .5rem;border-radius:6px;font-weight:600;">
                        Próxima: {{ $sesion->proxima_sesion->format('d/m/Y') }}
                    </span>
                    @endif
                </div>
                @if($sesion->descripcion)
                <div style="color:#6b7280;margin-top:.25rem;font-size:.78rem;">{{ \Str::limit($sesion->descripcion, 100) }}</div>
                @endif
                @if($sesion->acuerdos)
                <div style="color:#065f46;margin-top:.2rem;font-size:.75rem;"><i class="bi bi-check-circle me-1"></i>{{ \Str::limit($sesion->acuerdos, 80) }}</div>
                @endif
            </div>
            @endforeach
            @if($tutoria->sesiones->count() > 5)
            <div style="text-align:center;font-size:.78rem;color:#94a3b8;">
                + {{ $tutoria->sesiones->count() - 5 }} sesión(es) más
                <a href="{{ route('admin.tutorias.sesiones', $tutoria) }}" style="color:#7c3aed;margin-left:.25rem;">Ver todas</a>
            </div>
            @endif
            </div>
        </div>
        @endif

        {{-- Formulario nueva sesión --}}
        <details style="border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
            <summary style="padding:.75rem 1rem;cursor:pointer;font-size:.82rem;font-weight:700;color:#374151;background:#f8fafc;list-style:none;display:flex;align-items:center;gap:.5rem;user-select:none;">
                <i class="bi bi-plus-circle-fill" style="color:#7c3aed;"></i>
                Registrar Nueva Sesión
            </summary>
            <div style="padding:1rem;border-top:1px solid #e2e8f0;">
                <form action="{{ route('portal.docente.mis-tutorias.sesion.store', $tutoria) }}" method="POST">
                    @csrf
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:.75rem;">
                        <div>
                            <label style="font-size:.75rem;font-weight:600;color:#374151;display:block;margin-bottom:.3rem;">Fecha <span style="color:#dc2626;">*</span></label>
                            <input type="date" name="fecha" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div>
                            <label style="font-size:.75rem;font-weight:600;color:#374151;display:block;margin-bottom:.3rem;">Próxima Sesión</label>
                            <input type="date" name="proxima_sesion" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div style="margin-bottom:.75rem;">
                        <label style="font-size:.75rem;font-weight:600;color:#374151;display:block;margin-bottom:.3rem;">Tema <span style="color:#dc2626;">*</span></label>
                        <input type="text" name="tema" class="form-control form-control-sm" placeholder="Tema principal de la sesión" required maxlength="255">
                    </div>
                    <div style="margin-bottom:.75rem;">
                        <label style="font-size:.75rem;font-weight:600;color:#374151;display:block;margin-bottom:.3rem;">Descripción / Desarrollo</label>
                        <textarea name="descripcion" class="form-control form-control-sm" rows="3" placeholder="Descripción de lo tratado en la sesión..." maxlength="2000"></textarea>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1rem;">
                        <div>
                            <label style="font-size:.75rem;font-weight:600;color:#374151;display:block;margin-bottom:.3rem;">Estudiantes Atendidos</label>
                            <textarea name="estudiantes_atendidos" class="form-control form-control-sm" rows="2" placeholder="Nombres o situaciones específicas..." maxlength="500"></textarea>
                        </div>
                        <div>
                            <label style="font-size:.75rem;font-weight:600;color:#374151;display:block;margin-bottom:.3rem;">Acuerdos / Compromisos</label>
                            <textarea name="acuerdos" class="form-control form-control-sm" rows="2" placeholder="Compromisos asumidos..." maxlength="1000"></textarea>
                        </div>
                    </div>
                    <div style="display:flex;justify-content:flex-end;gap:.5rem;">
                        <button type="reset" class="btn btn-sm btn-outline-secondary" style="border-radius:7px;font-size:.78rem;">Limpiar</button>
                        <button type="submit" class="btn btn-sm fw-semibold" style="background:#7c3aed;color:#fff;border-radius:7px;font-size:.78rem;padding:.35rem .9rem;">
                            <i class="bi bi-save me-1"></i>Registrar Sesión
                        </button>
                    </div>
                </form>
            </div>
        </details>

        {{-- Link a gestión admin --}}
        <div style="margin-top:.75rem;text-align:right;">
            <a href="{{ route('admin.tutorias.sesiones', $tutoria) }}" target="_blank"
               style="font-size:.75rem;color:#7c3aed;text-decoration:none;">
                <i class="bi bi-box-arrow-up-right me-1"></i>Ver todas las sesiones
            </a>
            <span style="color:#e2e8f0;margin:0 .5rem;">·</span>
            <a href="{{ route('admin.tutorias.informe-pdf', $tutoria) }}" target="_blank"
               style="font-size:.75rem;color:#dc2626;text-decoration:none;">
                <i class="bi bi-file-earmark-pdf me-1"></i>Informe PDF
            </a>
        </div>
    </div>
</div>
@endforeach
@endif

@endsection
