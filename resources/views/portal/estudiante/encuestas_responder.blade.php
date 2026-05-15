@extends('layouts.portal-estudiante')
@section('title', $encuesta->titulo)

@section('activeKey', 'encuestas')

@section('content')

{{-- Encabezado --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;">
    <a href="{{ route('portal.estudiante.encuestas') }}"
       style="display:flex;align-items:center;justify-content:center;width:2rem;height:2rem;border-radius:8px;background:#f1f5f9;color:#64748b;text-decoration:none;flex-shrink:0;">
        <i class="bi bi-chevron-left"></i>
    </a>
    <div>
        <h2 style="font-size:.95rem;font-weight:800;margin:0;color:#1e293b;">{{ $encuesta->titulo }}</h2>
        @if($encuesta->descripcion)
            <p style="font-size:.75rem;color:#6b7280;margin:.2rem 0 0;">{{ $encuesta->descripcion }}</p>
        @endif
    </div>
</div>

@if(session('success'))
<div style="margin-bottom:1rem;padding:.75rem 1rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;color:#166534;font-size:.85rem;">
    <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
</div>
@endif

@if($errors->any())
<div style="margin-bottom:1rem;padding:.75rem 1rem;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;color:#b91c1c;font-size:.82rem;">
    @foreach($errors->all() as $error)
        <div><i class="bi bi-exclamation-circle me-1"></i>{{ $error }}</div>
    @endforeach
</div>
@endif

<form method="POST" action="{{ route('portal.estudiante.encuestas.guardar', $encuesta) }}">
    @csrf

    @foreach($encuesta->preguntas as $pregunta)
    <div class="prt-card" style="margin-bottom:.85rem;">
        <div class="prt-card-body" style="padding:.9rem 1.1rem;">
            <div style="display:flex;align-items:flex-start;gap:.6rem;margin-bottom:.85rem;">
                <span style="flex-shrink:0;width:1.5rem;height:1.5rem;border-radius:50%;background:#ede9fe;color:#7c3aed;font-size:.7rem;font-weight:800;display:flex;align-items:center;justify-content:center;">
                    {{ $loop->iteration }}
                </span>
                <p style="font-size:.875rem;font-weight:600;color:#1e293b;margin:0;line-height:1.5;">{{ $pregunta->texto }}</p>
            </div>

            @if($pregunta->tipo === 'opcion_multiple')
                <div style="padding-left:2.1rem;display:flex;flex-direction:column;gap:.5rem;">
                    @foreach($pregunta->opciones as $opcion)
                        <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-size:.85rem;color:#374151;">
                            <input type="radio"
                                   name="respuestas[{{ $pregunta->id }}][opcion_id]"
                                   value="{{ $opcion->id }}"
                                   style="accent-color:#8b5cf6;width:1rem;height:1rem;flex-shrink:0;">
                            {{ $opcion->texto }}
                        </label>
                    @endforeach
                </div>

            @elseif($pregunta->tipo === 'escala_1_5')
                <div style="padding-left:2.1rem;">
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                        @foreach([1,2,3,4,5] as $valor)
                            <label style="display:flex;flex-direction:column;align-items:center;gap:.3rem;cursor:pointer;">
                                <input type="radio"
                                       name="respuestas[{{ $pregunta->id }}][escala_valor]"
                                       value="{{ $valor }}"
                                       style="accent-color:#8b5cf6;width:1rem;height:1rem;">
                                <span style="width:2.5rem;height:2.5rem;border-radius:50%;border:2px solid #e5e7eb;display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:800;color:#374151;background:#fff;">
                                    {{ $valor }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p style="font-size:.7rem;color:#9ca3af;margin-top:.4rem;">1 = Muy malo &nbsp;·&nbsp; 5 = Excelente</p>
                </div>

            @else
                <div style="padding-left:2.1rem;">
                    <textarea name="respuestas[{{ $pregunta->id }}][respuesta_texto]"
                              rows="3"
                              placeholder="Escribe tu respuesta aquí..."
                              style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:.5rem .75rem;font-size:.85rem;resize:none;box-sizing:border-box;color:#374151;"></textarea>
                </div>
            @endif
        </div>
    </div>
    @endforeach

    <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:.5rem;">
        <a href="{{ route('portal.estudiante.encuestas') }}"
           style="padding:.5rem 1rem;border:1px solid #d1d5db;border-radius:8px;font-size:.85rem;color:#374151;text-decoration:none;background:#fff;">
            Cancelar
        </a>
        <button type="submit"
                style="padding:.5rem 1.25rem;background:#8b5cf6;color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:700;cursor:pointer;">
            Enviar respuestas
        </button>
    </div>
</form>

@endsection
