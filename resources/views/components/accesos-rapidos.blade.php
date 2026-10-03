{{-- Cuadrícula de accesos rápidos del rol para la portada de cada panel. Uso: <x-accesos-rapidos /> --}}
@props(['titulo' => 'Accesos rápidos'])
@php $items = \App\Support\AccesosRapidos::para(auth()->user()); @endphp
@if(count($items))
@once
<style>
    .arg-caja { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1rem 1.1rem; margin-bottom: 1.25rem; }
    .arg-titulo { font-size: .8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; margin-bottom: .75rem; }
    .arg-guia { display: inline-flex; align-items: center; gap: .4rem; margin-top: .8rem; font-size: .8rem; font-weight: 600; color: #4f46e5; text-decoration: none; }
    .arg-guia:hover { text-decoration: underline; }
    .arg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(104px, 1fr)); gap: .6rem; }
    .arg-tile { display: flex; flex-direction: column; align-items: center; gap: .45rem; padding: .8rem .4rem; border-radius: 14px; text-decoration: none; color: #1e293b; font-size: .8rem; font-weight: 600; text-align: center; line-height: 1.2; border: 1px solid transparent; transition: background .12s, transform .12s, border-color .12s; }
    .arg-tile:hover { background: #f8fafc; border-color: #e2e8f0; transform: translateY(-2px); color: #0f172a; }
    .arg-ico { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.3rem; box-shadow: 0 4px 12px rgba(15,23,42,.16); }
    [data-theme="dark"] .arg-caja { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .arg-tile { color: #e2e8f0; }
    [data-theme="dark"] .arg-tile:hover { background: #334155; border-color: #475569; }
</style>
@endonce
<section class="arg-caja" aria-label="{{ $titulo }}">
    <div class="arg-titulo"><i class="bi bi-lightning-charge-fill me-1" style="color:#f59e0b;"></i>{{ $titulo }}</div>
    <div class="arg-grid">
        @foreach($items as $a)
            <a href="{{ $a['url'] }}" class="arg-tile">
                <span class="arg-ico" style="background:{{ $a['color'] }};"><i class="bi {{ $a['icono'] }}"></i></span>
                <span>{{ $a['etiqueta'] }}</span>
            </a>
        @endforeach
    </div>
    <a href="{{ route('guias.mia') }}" class="arg-guia"><i class="bi bi-book-half"></i> ¿Primera vez? Mira la guía rápida de tu rol</a>
</section>
@endif
