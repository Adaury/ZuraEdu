<section class="contacto" id="contacto">
    <div class="section-inner">
        <h2 class="section-title">Contacto</h2>
        <div class="contacto-grid">
            @if($config['contacto_direccion'])
            <div class="contacto-item"><i class="bi bi-geo-alt-fill"></i>{{ $config['contacto_direccion'] }}</div>
            @endif
            @if($config['contacto_telefono'])
            <div class="contacto-item"><i class="bi bi-telephone-fill"></i>{{ $config['contacto_telefono'] }}</div>
            @endif
            @if($config['contacto_email'])
            <div class="contacto-item"><i class="bi bi-envelope-fill"></i>{{ $config['contacto_email'] }}</div>
            @endif
        </div>
        @if($config['social_facebook'] || $config['social_instagram'] || $config['social_twitter'])
        <div class="socials">
            @if($config['social_facebook'])<a href="{{ $config['social_facebook'] }}" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a>@endif
            @if($config['social_instagram'])<a href="{{ $config['social_instagram'] }}" target="_blank" rel="noopener"><i class="bi bi-instagram"></i></a>@endif
            @if($config['social_twitter'])<a href="{{ $config['social_twitter'] }}" target="_blank" rel="noopener"><i class="bi bi-twitter-x"></i></a>@endif
        </div>
        @endif
    </div>
</section>
