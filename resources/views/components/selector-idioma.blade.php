@props(['variante' => 'claro', 'fijo' => false])
{{--
    Selector de idioma de la interfaz. Sin JavaScript ni Bootstrap (usa <details>), para que funcione igual en el
    panel, los portales y las pantallas de acceso. Los nombres van como entidades HTML a propósito: así el
    traductor del servidor no los toca (el idioma se muestra siempre en su propio idioma).
--}}
@php
    $actual = app()->getLocale();
    $idiomas = config('idiomas.disponibles');
    $oscuro = $variante === 'oscuro';
    $uid = 'idm' . substr(md5(uniqid('', true)), 0, 6);
@endphp
<details class="selector-idioma {{ $uid }}" @if($fijo) style="position:fixed;top:12px;right:12px;z-index:2000;" @endif>
    <summary title="Idioma" aria-label="Idioma">
        <i class="bi bi-translate" aria-hidden="true"></i>
        <span>{{ $idiomas[$actual]['corto'] ?? 'ES' }}</span>
    </summary>
    <div class="selector-idioma-menu" role="menu">
        @foreach($idiomas as $codigo => $dato)
            <form method="POST" action="{{ route('idioma.cambiar', $codigo) }}">
                @csrf
                <button type="submit" role="menuitem" class="{{ $codigo === $actual ? 'activo' : '' }}">
                    {!! htmlentities($dato['nombre'], ENT_NOQUOTES, 'UTF-8') !!}
                    @if($codigo === $actual)<i class="bi bi-check2" aria-hidden="true"></i>@endif
                </button>
            </form>
        @endforeach
    </div>
</details>
<style>
    .selector-idioma { position: relative; display: inline-block; }
    .selector-idioma > summary { list-style: none; cursor: pointer; display: inline-flex; align-items: center; gap: .35rem;
        padding: .35rem .6rem; border-radius: 10px; font-size: .78rem; font-weight: 700; user-select: none;
        border: 1px solid {{ $oscuro ? 'rgba(255,255,255,.28)' : 'rgba(100,116,139,.35)' }};
        color: {{ $oscuro ? '#fff' : '#334155' }}; background: {{ $oscuro ? 'rgba(255,255,255,.1)' : 'rgba(255,255,255,.85)' }}; }
    .selector-idioma > summary::-webkit-details-marker { display: none; }
    .selector-idioma[open] > summary { box-shadow: 0 0 0 2px rgba(99,102,241,.35); }
    .selector-idioma-menu { position: absolute; right: 0; top: calc(100% + 6px); min-width: 150px; z-index: 3000;
        background: #fff; border-radius: 12px; padding: .3rem; box-shadow: 0 12px 32px rgba(15,23,42,.22); border: 1px solid #e2e8f0; }
    .selector-idioma-menu form { margin: 0; }
    .selector-idioma-menu button { width: 100%; display: flex; justify-content: space-between; align-items: center; gap: .5rem;
        background: none; border: 0; text-align: left; padding: .45rem .65rem; border-radius: 8px; font-size: .85rem; color: #1e293b; cursor: pointer; }
    .selector-idioma-menu button:hover { background: #eef2ff; }
    .selector-idioma-menu button.activo { font-weight: 800; color: #4338ca; }
</style>
