@extends('layouts.portal')
@section('title', 'Quiz — '.$material->titulo)

@section('sidebar')
    @include('portal.estudiante._sidebar', ['activeKey' => 'classroom'])
@endsection

@section('content')

@php $color = $claseVirtual->portada_color ?? '#4f46e5'; @endphp

<div class="mb-4 p-4" style="background:{{ $color }};border-radius:18px;position:relative;overflow:hidden;">
    <div style="position:absolute;top:-30px;right:-30px;width:130px;height:130px;background:rgba(255,255,255,.07);border-radius:50%;"></div>
    <a href="{{ route('portal.estudiante.classroom.show', $claseVirtual) }}" class="btn btn-sm mb-3" style="background:rgba(255,255,255,.2);color:#fff;border:none;">
        <i class="bi bi-arrow-left me-1"></i>Volver al Aula
    </a>
    <h4 class="text-white fw-bold mb-1">{{ $material->titulo }}</h4>
    <small class="text-white opacity-75">{{ $claseVirtual->nombre }} &bull; {{ $claseVirtual->asignacion?->asignatura?->nombre }}</small>
</div>

<div class="row justify-content-center">
<div class="col-lg-7">

    <div class="card border-0 shadow-sm mb-3" style="border-radius:16px;">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <div style="width:70px;height:70px;background:#eef2ff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                    <i class="bi bi-clipboard-check-fill" style="font-size:2rem;color:#4f46e5;"></i>
                </div>
                <h5 class="fw-bold">{{ $material->titulo }}</h5>
                @if($material->contenido)
                <p class="text-muted">{{ $material->contenido }}</p>
                @endif
            </div>

            <div class="row g-3 mb-4">
                <div class="col-4 text-center">
                    <div style="background:#eef2ff;border-radius:12px;padding:1rem;">
                        <div style="font-size:1.5rem;font-weight:800;color:#4f46e5;">{{ $quiz->preguntas->count() }}</div>
                        <div style="font-size:.78rem;color:#6366f1;">Preguntas</div>
                    </div>
                </div>
                <div class="col-4 text-center">
                    <div style="background:#f0fdf4;border-radius:12px;padding:1rem;">
                        <div style="font-size:1.5rem;font-weight:800;color:#16a34a;">{{ $quiz->duracion_minutos ?? '∞' }}</div>
                        <div style="font-size:.78rem;color:#16a34a;">{{ $quiz->duracion_minutos ? 'Minutos' : 'Sin límite' }}</div>
                    </div>
                </div>
                <div class="col-4 text-center">
                    <div style="background:#fef3c7;border-radius:12px;padding:1rem;">
                        <div style="font-size:1.5rem;font-weight:800;color:#d97706;">{{ $quiz->intentos_max }}</div>
                        <div style="font-size:.78rem;color:#d97706;">Intento(s) máx.</div>
                    </div>
                </div>
            </div>

            @if($intentosPrevios->isNotEmpty())
            <div class="mb-4">
                <h6 class="fw-semibold mb-2">Intentos anteriores</h6>
                @foreach($intentosPrevios as $prev)
                <div class="d-flex align-items-center gap-3 p-2 rounded-3 mb-2" style="background:#F8FAFC;border:1px solid #E5E7EB;">
                    <i class="bi bi-check-circle-fill text-success"></i>
                    <div class="flex-grow-1">
                        <div style="font-size:.85rem;">Intento #{{ $prev->numero_intento }}</div>
                        <div style="font-size:.75rem;color:#6b7280;">{{ $prev->finalizado_en?->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="text-end">
                        @if($prev->puntuacion !== null)
                        <span class="badge {{ $prev->porcentaje >= 60 ? 'bg-success' : 'bg-danger' }}">
                            {{ $prev->puntuacion }}/{{ $prev->puntuacion_max }} pts
                        </span>
                        @endif
                    </div>
                    <a href="{{ route('portal.estudiante.quiz.resultado', [$claseVirtual, $material, $prev]) }}"
                       class="btn btn-sm btn-outline-secondary" style="border-radius:8px;font-size:.75rem;">Ver</a>
                </div>
                @endforeach
            </div>
            @endif

            <div class="alert alert-info border-0" style="background:#EFF6FF;border-radius:10px;font-size:.85rem;">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Antes de comenzar:</strong>
                @if($quiz->duracion_minutos) Tienes {{ $quiz->duracion_minutos }} minutos. @endif
                @if($quiz->aleatorizar_preguntas) Las preguntas serán en orden aleatorio. @endif
                Una vez iniciado, el tiempo corre. Responde todas las preguntas antes de enviar.
            </div>

            <form method="POST" action="{{ route('portal.estudiante.quiz.comenzar', [$claseVirtual, $material]) }}">
                @csrf
                <button type="submit" class="btn btn-primary w-100 fw-bold py-3" style="border-radius:12px;font-size:1rem;">
                    <i class="bi bi-play-fill me-2"></i>Comenzar Quiz
                </button>
            </form>
        </div>
    </div>

</div>
</div>
@endsection
