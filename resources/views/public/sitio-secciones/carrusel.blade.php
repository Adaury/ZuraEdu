<div class="carrusel">
    @if($config['carrusel']->titulo || $config['carrusel']->descripcion)
    <div class="carrusel-intro">
        @if($config['carrusel']->titulo)<h2 class="section-title">{{ $config['carrusel']->titulo }}</h2>@endif
        @if($config['carrusel']->descripcion)<p class="carrusel-historia">{{ $config['carrusel']->descripcion }}</p>@endif
    </div>
    @endif
    <div class="carrusel-track">
        @foreach($config['carrusel']->fotos as $i => $foto)
        <div class="carrusel-slide {{ $i === 0 ? 'activa' : '' }}">
            <img src="{{ $foto->url }}" alt="{{ $foto->titulo ?: $config['carrusel']->titulo }}" loading="lazy">
            @if($foto->titulo)
                <div class="carrusel-caption">{{ $foto->titulo }}</div>
            @endif
        </div>
        @endforeach

        @if($config['carrusel']->fotos->count() > 1)
        <button type="button" class="carrusel-nav prev" aria-label="Anterior"><i class="bi bi-chevron-left"></i></button>
        <button type="button" class="carrusel-nav next" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></button>
        @endif
    </div>

    @if($config['carrusel']->fotos->count() > 1)
    <div class="carrusel-dots">
        @foreach($config['carrusel']->fotos as $i => $foto)
            <button type="button" class="{{ $i === 0 ? 'activa' : '' }}" aria-label="Foto {{ $i + 1 }}"></button>
        @endforeach
    </div>
    @endif
</div>
