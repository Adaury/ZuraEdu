@extends('layouts.portal')

@section('title', 'Salud Escolar — ' . $estudiante->nombres)

@section('role', 'padre')

@section('activeKey', 'salud')

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'salud', 'estudiante' => $estudiante])
@endsection

@section('content')

{{-- Encabezado --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.padre.hijo', $estudiante) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">Salud Escolar</h1>
        <div style="font-size:.75rem;color:#64748b;">{{ $estudiante->nombre_completo }}</div>
    </div>
</div>

{{-- Ficha de salud --}}
@if($ficha)
<div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:1.25rem;margin-bottom:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.05);">
    <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:1rem;">
        <div style="width:36px;height:36px;border-radius:10px;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-heart-pulse-fill" style="color:#dc2626;font-size:1rem;"></i>
        </div>
        <div style="font-weight:700;color:#1e293b;font-size:.92rem;">Ficha de Salud</div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:.75rem;">
        @if($ficha->tipo_sangre)
        <div style="background:#fef2f2;border-radius:10px;padding:.75rem 1rem;">
            <div style="font-size:.68rem;font-weight:600;color:#dc2626;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem;">Tipo de Sangre</div>
            <div style="font-weight:800;color:#1e293b;font-size:1.1rem;">{{ $ficha->tipo_sangre }}</div>
        </div>
        @endif

        @if($ficha->alergias)
        <div style="background:#fff7ed;border-radius:10px;padding:.75rem 1rem;">
            <div style="font-size:.68rem;font-weight:600;color:#ea580c;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem;">Alergias</div>
            <div style="font-size:.82rem;color:#374151;">{{ $ficha->alergias }}</div>
        </div>
        @endif

        @if($ficha->condiciones_medicas)
        <div style="background:#fff7ed;border-radius:10px;padding:.75rem 1rem;">
            <div style="font-size:.68rem;font-weight:600;color:#ea580c;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem;">Condiciones Médicas</div>
            <div style="font-size:.82rem;color:#374151;">{{ $ficha->condiciones_medicas }}</div>
        </div>
        @endif

        @if($ficha->medicamentos)
        <div style="background:#f0fdf4;border-radius:10px;padding:.75rem 1rem;">
            <div style="font-size:.68rem;font-weight:600;color:#16a34a;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem;">Medicamentos</div>
            <div style="font-size:.82rem;color:#374151;">{{ $ficha->medicamentos }}</div>
        </div>
        @endif

        @if($ficha->seguro_medico)
        <div style="background:#eff6ff;border-radius:10px;padding:.75rem 1rem;">
            <div style="font-size:.68rem;font-weight:600;color:#2563eb;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.2rem;">Seguro Médico</div>
            <div style="font-size:.82rem;color:#374151;">{{ $ficha->seguro_medico }}</div>
            @if($ficha->num_seguro)
            <div style="font-size:.72rem;color:#64748b;">No. {{ $ficha->num_seguro }}</div>
            @endif
        </div>
        @endif
    </div>

    {{-- Contacto de emergencia --}}
    @if($ficha->contacto_emergencia || $ficha->telefono_emergencia)
    <div style="margin-top:.85rem;padding:.75rem 1rem;background:#f8fafc;border-radius:10px;border-left:3px solid #dc2626;">
        <div style="font-size:.68rem;font-weight:700;color:#dc2626;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.3rem;">Contacto de Emergencia</div>
        @if($ficha->contacto_emergencia)
        <div style="font-size:.83rem;font-weight:600;color:#1e293b;">{{ $ficha->contacto_emergencia }}</div>
        @endif
        @if($ficha->telefono_emergencia)
        <div style="font-size:.8rem;color:#64748b;display:flex;align-items:center;gap:.35rem;margin-top:.1rem;">
            <i class="bi bi-telephone-fill" style="color:#dc2626;"></i>{{ $ficha->telefono_emergencia }}
        </div>
        @endif
    </div>
    @endif
</div>
@else
<div style="background:#fff;border-radius:14px;border:1px dashed #cbd5e1;padding:1.5rem;text-align:center;margin-bottom:1.25rem;color:#94a3b8;">
    <i class="bi bi-heart-pulse" style="font-size:2rem;margin-bottom:.5rem;display:block;"></i>
    <div style="font-weight:600;color:#64748b;margin-bottom:.3rem;">Sin ficha de salud registrada</div>
    <div style="font-size:.8rem;">Comuníquese con la dirección escolar para registrar la ficha médica de su representado.</div>
</div>
@endif

{{-- Incidentes médicos --}}
<div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.05);">
    <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:1rem;">
        <div style="width:36px;height:36px;border-radius:10px;background:#fef3c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-bandaid-fill" style="color:#d97706;font-size:1rem;"></i>
        </div>
        <div>
            <div style="font-weight:700;color:#1e293b;font-size:.92rem;">Incidentes Médicos</div>
            <div style="font-size:.72rem;color:#64748b;">Registro de atenciones e incidentes en la escuela</div>
        </div>
    </div>

    @if($incidentes->isEmpty())
    <div style="text-align:center;padding:1.5rem;color:#94a3b8;">
        <i class="bi bi-shield-check" style="font-size:1.8rem;margin-bottom:.5rem;display:block;color:#22c55e;"></i>
        <div style="font-size:.83rem;">Sin incidentes médicos registrados.</div>
    </div>
    @else
    <div style="display:flex;flex-direction:column;gap:.6rem;">
        @foreach($incidentes as $inc)
        @php
            $tipoColors = [
                'accidente'  => ['#fee2e2','#dc2626'],
                'enfermedad' => ['#fef3c7','#d97706'],
                'alergia'    => ['#fff7ed','#ea580c'],
                'otro'       => ['#f1f5f9','#64748b'],
            ];
            $tc = $tipoColors[$inc->tipo] ?? ['#f1f5f9','#64748b'];
        @endphp
        <div style="border:1px solid #e2e8f0;border-radius:10px;padding:.85rem 1rem;display:flex;gap:.75rem;align-items:flex-start;">
            <div style="width:32px;height:32px;border-radius:8px;background:{{ $tc[0] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-bandaid" style="color:{{ $tc[1] }};font-size:.9rem;"></i>
            </div>
            <div style="flex:1;">
                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.2rem;">
                    <span style="background:{{ $tc[0] }};color:{{ $tc[1] }};border-radius:99px;padding:.1rem .55rem;font-size:.68rem;font-weight:700;">
                        {{ ucfirst($inc->tipo) }}
                    </span>
                    <span style="font-size:.72rem;color:#94a3b8;">
                        {{ $inc->fecha->format('d/m/Y') }}@if($inc->hora) · {{ substr($inc->hora,0,5) }}@endif
                    </span>
                    @if($inc->notificado_representante)
                    <span style="background:#dcfce7;color:#166534;border-radius:99px;padding:.1rem .5rem;font-size:.65rem;font-weight:600;">
                        <i class="bi bi-check2"></i> Notificado
                    </span>
                    @endif
                </div>
                <div style="font-size:.83rem;color:#374151;">{{ $inc->descripcion }}</div>
                @if($inc->accion_tomada)
                <div style="font-size:.76rem;color:#64748b;margin-top:.25rem;">
                    <strong>Acción:</strong> {{ $inc->accion_tomada }}
                </div>
                @endif
                @if($inc->remitido_a)
                <div style="font-size:.76rem;color:#64748b;">
                    <strong>Remitido a:</strong> {{ $inc->remitido_a }}
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

@endsection
