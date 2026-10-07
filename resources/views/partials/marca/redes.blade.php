{{--
    Iconos de enlace a las redes sociales de ZuraEdu (Instagram, Facebook, YouTube). Solo dibuja las que tienen URL válida en
    config('brand.redes') (variables BRAND_*_URL del .env); sin ninguna no dibuja nada. Color = currentColor del contenedor.
    Los mismos iconos como archivo suelto están en public/brand/redes/*.svg (para correos, material impreso o publicidad).
--}}
@php($redes = \App\Support\Marca::redes())
@if($redes)
<ul class="flex items-center gap-3" aria-label="Redes sociales de {{ \App\Support\Marca::nombre() }}" style="list-style:none;margin:0;padding:0">
    @foreach($redes as $red => $url)
    <li>
        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ ucfirst($red) }} de {{ \App\Support\Marca::nombre() }}"
           title="{{ ucfirst($red) }}"
           style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:999px;border:1px solid rgba(148,163,184,.35);color:#94a3b8;transition:color .15s,border-color .15s,background .15s"
           onmouseover="this.style.color='#fff';this.style.borderColor='#fff'" onmouseout="this.style.color='#94a3b8';this.style.borderColor='rgba(148,163,184,.35)'">
            @if($red === 'instagram')
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5.5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.4" cy="6.6" r="1" fill="currentColor" stroke="none"/></svg>
            @elseif($red === 'facebook')
            <svg width="19" height="19" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.2 21v-8h2.7l.5-3.2h-3.2V7.9c0-.9.4-1.7 1.8-1.7h1.5V3.4C16.2 3.3 15.2 3.2 14.2 3.2c-2.6 0-4.2 1.5-4.2 4.3v2.3H7.4V13H10v8z"/></svg>
            @else
            <svg width="21" height="21" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4A2.5 2.5 0 0 0 2.4 7.2C2 8.8 2 12 2 12s0 3.2.4 4.8a2.5 2.5 0 0 0 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8c.4-1.6.4-4.8.4-4.8s0-3.2-.4-4.8zM10 15V9l5.2 3z"/></svg>
            @endif
        </a>
    </li>
    @endforeach
</ul>
@endif
