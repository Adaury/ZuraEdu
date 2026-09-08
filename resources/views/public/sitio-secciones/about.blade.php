<section>
    <div class="section-inner">
        @if($seccion->dato('titulo'))<h2 class="section-title">{{ $seccion->dato('titulo') }}</h2>@endif
        {{-- 'texto' viene de Quill y ya pasó por SanitizeInput::stripDangerousTags()
             (clave en $allowedRichFields) -- mismo límite de confianza que Publicacion::contenido. --}}
        @if($seccion->dato('texto'))<div class="about-text">{!! $seccion->dato('texto') !!}</div>@endif
    </div>
</section>
