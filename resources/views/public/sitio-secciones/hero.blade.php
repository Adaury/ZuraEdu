<section class="hero">
    <h1>{{ $seccion->dato('titulo') ?: $config['nombre'] }}</h1>
    @if($seccion->dato('subtitulo'))
        <p>{{ $seccion->dato('subtitulo') }}</p>
    @endif
    @if($seccion->dato('btn_texto') || $seccion->dato('btn2_texto'))
    <div class="actions">
        @if($seccion->dato('btn_texto'))
            <a href="{{ $seccion->dato('btn_url') ?: route('login') }}" class="btn btn-light">{{ $seccion->dato('btn_texto') }}</a>
        @endif
        @if($seccion->dato('btn2_texto'))
            <a href="{{ $seccion->dato('btn2_url') ?: route('inscripcion') }}" class="btn btn-outline">{{ $seccion->dato('btn2_texto') }}</a>
        @endif
    </div>
    @endif
</section>
