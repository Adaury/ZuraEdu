<section class="contacto" id="contacto-{{ $seccion->id }}">
    <div class="section-inner">
        <h2 class="section-title">{{ $seccion->dato('titulo') ?: 'Contacto' }}</h2>
        <div class="contacto-grid">
            @if($seccion->dato('direccion'))
            <div class="contacto-item"><i class="bi bi-geo-alt-fill"></i>{{ $seccion->dato('direccion') }}</div>
            @endif
            @if($seccion->dato('telefono'))
            <div class="contacto-item"><i class="bi bi-telephone-fill"></i>{{ $seccion->dato('telefono') }}</div>
            @endif
            @if($seccion->dato('email'))
            <div class="contacto-item"><i class="bi bi-envelope-fill"></i>{{ $seccion->dato('email') }}</div>
            @endif
        </div>
        @if($seccion->dato('facebook') || $seccion->dato('instagram') || $seccion->dato('twitter'))
        <div class="socials">
            @if($seccion->dato('facebook'))<a href="{{ $seccion->dato('facebook') }}" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a>@endif
            @if($seccion->dato('instagram'))<a href="{{ $seccion->dato('instagram') }}" target="_blank" rel="noopener"><i class="bi bi-instagram"></i></a>@endif
            @if($seccion->dato('twitter'))<a href="{{ $seccion->dato('twitter') }}" target="_blank" rel="noopener"><i class="bi bi-twitter-x"></i></a>@endif
        </div>
        @endif
    </div>
</section>
