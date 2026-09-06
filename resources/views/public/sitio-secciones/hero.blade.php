<section class="hero">
    <h1>{{ $config['hero_titulo'] ?: $config['nombre'] }}</h1>
    @if($config['hero_subtitulo'])
        <p>{{ $config['hero_subtitulo'] }}</p>
    @endif
    @if($config['hero_btn_texto'] || $config['hero_btn2_texto'])
    <div class="actions">
        @if($config['hero_btn_texto'])
            <a href="{{ $config['hero_btn_url'] }}" class="btn btn-light">{{ $config['hero_btn_texto'] }}</a>
        @endif
        @if($config['hero_btn2_texto'])
            <a href="{{ $config['hero_btn2_url'] }}" class="btn btn-outline">{{ $config['hero_btn2_texto'] }}</a>
        @endif
    </div>
    @endif
</section>
