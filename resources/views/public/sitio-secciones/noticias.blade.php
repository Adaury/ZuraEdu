<section class="noticias">
    <div class="section-inner">
        <h2 class="section-title">Noticias y Publicaciones</h2>
        <div class="noticias-grid">
            @foreach($config['noticias'] as $noticia)
            <a href="{{ route('sitio.noticias.show', $noticia) }}" class="noticia-card">
                @if($noticia->imagen_url)
                    <img src="{{ $noticia->imagen_url }}" alt="{{ $noticia->titulo }}" loading="lazy">
                @endif
                <div class="cuerpo">
                    <span class="noticia-tipo">{{ $noticia->tipo_label }}</span>
                    <div class="noticia-titulo">{{ $noticia->titulo }}</div>
                    <div class="noticia-fecha">{{ $noticia->fecha->format('d/m/Y') }}</div>
                </div>
            </a>
            @endforeach
        </div>
        <a href="{{ route('sitio.noticias') }}" class="noticias-vertodas btn" style="border:1.5px solid var(--g200);color:var(--g900);">
            Ver todas las noticias <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
</section>
