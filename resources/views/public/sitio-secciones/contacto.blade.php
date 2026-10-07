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
        {{-- Las URLs son texto libre del administrador: solo http/https llegan al href (Marca::urlSegura rechaza javascript:, data:…) --}}
        @php
            $redesSitio = [
                'facebook'  => \App\Support\Marca::urlSegura($seccion->dato('facebook')),
                'instagram' => \App\Support\Marca::urlSegura($seccion->dato('instagram')),
                'youtube'   => \App\Support\Marca::urlSegura($seccion->dato('youtube')),
                'twitter-x' => \App\Support\Marca::urlSegura($seccion->dato('twitter')),
            ];
        @endphp
        @if(array_filter($redesSitio))
        <div class="socials">
            @foreach(array_filter($redesSitio) as $icono => $url)
            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ ucfirst(str_replace('-x', '', $icono)) }}"><i class="bi bi-{{ $icono }}"></i></a>
            @endforeach
        </div>
        @endif
    </div>
</section>
