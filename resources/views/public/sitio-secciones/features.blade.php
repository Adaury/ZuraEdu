<section>
    <div class="section-inner">
        @if($seccion->dato('titulo'))<h2 class="section-title">{{ $seccion->dato('titulo') }}</h2>@endif
        <div class="features-grid">
            @foreach($seccion->dato('items', []) as $item)
            <div class="feature-card">
                @if($item['icono'] ?? null)<i class="bi {{ $item['icono'] }}"></i>@endif
                @if($item['titulo'] ?? null)<h3>{{ $item['titulo'] }}</h3>@endif
                @if($item['texto'] ?? null)<p>{{ $item['texto'] }}</p>@endif
            </div>
            @endforeach
        </div>
    </div>
</section>
