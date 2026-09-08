<div class="carrusel">
    @if($seccion->datos->titulo || $seccion->datos->descripcion)
    <div class="carrusel-intro">
        @if($seccion->datos->titulo)<h2 class="section-title">{{ $seccion->datos->titulo }}</h2>@endif
        @if($seccion->datos->descripcion)<p class="carrusel-historia">{{ $seccion->datos->descripcion }}</p>@endif
    </div>
    @endif
    <div class="carrusel-track">
        @foreach($seccion->datos->fotos as $i => $foto)
        <div class="carrusel-slide {{ $i === 0 ? 'activa' : '' }}">
            <img src="{{ $foto->url }}" alt="{{ $foto->titulo ?: $seccion->datos->titulo }}" loading="lazy">
            @if($foto->titulo)
                <div class="carrusel-caption">{{ $foto->titulo }}</div>
            @endif
        </div>
        @endforeach

        @if($seccion->datos->fotos->count() > 1)
        <button type="button" class="carrusel-nav prev" aria-label="Anterior"><i class="bi bi-chevron-left"></i></button>
        <button type="button" class="carrusel-nav next" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></button>
        @endif
    </div>

    @if($seccion->datos->fotos->count() > 1)
    <div class="carrusel-dots">
        @foreach($seccion->datos->fotos as $i => $foto)
            <button type="button" class="{{ $i === 0 ? 'activa' : '' }}" aria-label="Foto {{ $i + 1 }}"></button>
        @endforeach
    </div>
    @endif
</div>
