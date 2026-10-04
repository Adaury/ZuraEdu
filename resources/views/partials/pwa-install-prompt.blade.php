{{--
    PWA Install Prompt
    ──────────────────
    • Chrome/Edge/Samsung (Android): captura beforeinstallprompt y muestra banner.
    • iOS Safari: detecta el UA y muestra instrucciones del botón Compartir.
    • Standalone: oculto (ya instalado).
    • Dismissal: guardado en localStorage 30 días; no vuelve a aparecer.
--}}
@php
    $pwaColor = app()->bound('tenant') ? (app('tenant')->color_primario ?? '#1d4ed8') : '#1d4ed8';
    $pwaName  = app()->bound('tenant') ? (app('tenant')->nombre_institucion ?? config('app.name')) : config('app.name');
    $pwaTid   = app()->bound('tenant') ? (app('tenant')->id ?? 0) : 0;
@endphp

{{-- ── Banner principal (Android / Desktop) ────────────────────────────── --}}
<div id="pwa-install-banner"
     role="banner"
     aria-label="Instalar aplicación"
     style="
        display:none;
        position:fixed;
        bottom:0;left:0;right:0;
        z-index:10000;
        background:#fff;
        border-top:3px solid {{ $pwaColor }};
        box-shadow:0 -4px 24px rgba(0,0,0,.12);
        padding:.875rem 1rem;
        font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
        animation:pwaSlideUp .28s cubic-bezier(.4,0,.2,1);
     ">
    <div style="max-width:640px;margin:0 auto;display:flex;align-items:center;gap:.875rem;">
        <img src="/pwa/icon/96?tid={{ $pwaTid }}"
             width="48" height="48"
             alt="Icono"
             style="border-radius:.625rem;flex-shrink:0;">
        <div style="flex:1;min-width:0;">
            <div style="font-weight:700;font-size:.9375rem;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                Instalar {{ $pwaName }}
            </div>
            <div style="font-size:.8125rem;color:#64748b;margin-top:.125rem;">
                Accede más rápido desde tu pantalla de inicio
            </div>
        </div>
        <button id="pwa-install-btn"
                style="
                    background:{{ $pwaColor }};
                    color:#fff;
                    border:none;
                    border-radius:.5rem;
                    padding:.5rem 1.125rem;
                    font-size:.875rem;
                    font-weight:600;
                    cursor:pointer;
                    white-space:nowrap;
                    flex-shrink:0;
                    transition:opacity .15s;
                "
                onmouseenter="this.style.opacity='.85'"
                onmouseleave="this.style.opacity='1'">
            Instalar
        </button>
        <button id="pwa-install-dismiss"
                aria-label="Cerrar"
                style="
                    background:none;
                    border:none;
                    cursor:pointer;
                    color:#94a3b8;
                    font-size:1.375rem;
                    line-height:1;
                    padding:.25rem;
                    flex-shrink:0;
                    transition:color .15s;
                "
                onmouseenter="this.style.color='#475569'"
                onmouseleave="this.style.color='#94a3b8'">
            &times;
        </button>
    </div>
</div>

{{-- ── Guía de instalación (iOS, y manual en otros navegadores). Safari en iPhone no tiene un «botón instalar» que una web pueda
     pulsar: la única vía es Compartir → Añadir a pantalla de inicio, así que se explica paso a paso y con un botón de cierre claro. ── --}}
<div id="pwa-ios-prompt"
     role="dialog"
     aria-modal="true"
     aria-labelledby="pwa-guia-titulo"
     style="display:none;position:fixed;left:0;right:0;bottom:0;z-index:10001;
            background:#fff;color:#1e293b;border-radius:1.25rem 1.25rem 0 0;
            padding:1.1rem 1.1rem calc(1.1rem + env(safe-area-inset-bottom));
            box-shadow:0 -10px 40px rgba(0,0,0,.28);max-height:88vh;overflow-y:auto;
            font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:.9375rem;line-height:1.5;">
    <div style="max-width:460px;margin:0 auto;">
        <div style="display:flex;align-items:center;gap:.8rem;margin-bottom:.9rem;">
            <img src="/pwa/icon/96?tid={{ $pwaTid }}" width="52" height="52" alt="" style="border-radius:.8rem;flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,.18);">
            <div style="flex:1;min-width:0;">
                <strong id="pwa-guia-titulo" style="display:block;font-size:1.05rem;">Instala {{ $pwaName }}</strong>
                <span style="font-size:.82rem;color:#64748b;">Ábrela como una app, a pantalla completa</span>
            </div>
            <button id="pwa-ios-dismiss" aria-label="Cerrar" type="button"
                    style="background:#f1f5f9;border:none;border-radius:50%;width:34px;height:34px;font-size:1.25rem;line-height:1;color:#475569;cursor:pointer;flex-shrink:0;">&times;</button>
        </div>

        <ol id="pwa-guia-pasos" style="list-style:none;margin:0 0 1rem;padding:0;display:flex;flex-direction:column;gap:.55rem;"></ol>

        <p id="pwa-guia-nota" style="margin:0 0 1rem;font-size:.8rem;color:#64748b;"></p>

        <div style="display:flex;gap:.6rem;">
            <button id="pwa-ios-ok" type="button"
                    style="flex:1;background:{{ $pwaColor }};color:#fff;border:none;border-radius:.75rem;padding:.8rem 1rem;font-size:.95rem;font-weight:700;cursor:pointer;">Entendido</button>
            <button id="pwa-ios-luego" type="button"
                    style="background:#f1f5f9;color:#475569;border:none;border-radius:.75rem;padding:.8rem 1rem;font-size:.9rem;font-weight:600;cursor:pointer;">Más tarde</button>
        </div>
    </div>
</div>

<div id="pwa-ios-flecha" aria-hidden="true"
     style="display:none;position:fixed;left:50%;bottom:6px;transform:translateX(-50%);z-index:10002;color:#fff;font-size:2rem;line-height:1;
            text-shadow:0 2px 6px rgba(0,0,0,.5);animation:pwaRebote 1s infinite;pointer-events:none;">&#9660;</div>

<style>
@keyframes pwaSlideUp {
    from { transform: translateY(100%); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}
#pwa-ios-prompt { animation: pwaSlideUp .28s cubic-bezier(.4,0,.2,1); }
@keyframes pwaRebote { 0%,100% { transform: translate(-50%,0); } 50% { transform: translate(-50%,8px); } }
</style>

<script>
(function () {
    'use strict';

    const STORAGE_KEY  = 'pwa_prompt_dismissed';
    const DISMISS_DAYS = 30;

    const estaInstalada = () => window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    const banner     = document.getElementById('pwa-install-banner');
    const installBtn = document.getElementById('pwa-install-btn');
    const dismissBtn = document.getElementById('pwa-install-dismiss');
    const guia       = document.getElementById('pwa-ios-prompt');
    const flecha     = document.getElementById('pwa-ios-flecha');
    const pasos      = document.getElementById('pwa-guia-pasos');
    const nota       = document.getElementById('pwa-guia-nota');

    function snooze(dias) {
        try { localStorage.setItem(STORAGE_KEY, String(Date.now() + (dias || DISMISS_DAYS) * 86400 * 1000)); } catch (e) {}
    }
    function descartada() {
        try { const d = localStorage.getItem(STORAGE_KEY); return !!(d && Date.now() < parseInt(d, 10)); } catch (e) { return false; }
    }

    // ── Plataforma ────────────────────────────────────────────────────────
    const ua = navigator.userAgent;
    const esIpad = /ipad/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);   // iPadOS se presenta como Mac
    const esIos  = (/iphone|ipod/i.test(ua) || esIpad) && !window.MSStream;
    const iosOtroNavegador = esIos && /CriOS|FxiOS|EdgiOS|OPiOS/i.test(ua);   // Chrome/Firefox/Edge en iOS también pueden añadir a inicio

    // Iconos reales de iOS, para que se reconozcan en el menú
    const ICONO_COMPARTIR = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0a84ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-4px"><path d="M12 3v12"/><path d="M8 7l4-4 4 4"/><path d="M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>';
    const ICONO_MAS = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1e293b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-4px"><rect x="3" y="3" width="18" height="18" rx="4"/><path d="M12 8v8M8 12h8"/></svg>';

    function pasosParaEstaPlataforma() {
        if (esIos) {
            const donde = iosOtroNavegador ? 'arriba a la derecha' : (esIpad ? 'arriba, junto a la barra de direcciones' : 'en la barra de abajo');
            return {
                pasos: [
                    'Toca el botón <b>Compartir</b> ' + ICONO_COMPARTIR + ' <span style="color:#64748b">(' + donde + ')</span>',
                    'Desliza el menú y elige <b>Añadir a pantalla de inicio</b> ' + ICONO_MAS,
                    'Toca <b>Añadir</b>. ¡Listo! Encontrarás el icono en tu pantalla de inicio.',
                ],
                nota: iosOtroNavegador
                    ? 'Si no ves esa opción, abre esta página en Safari y repite los pasos.'
                    : 'Apple no permite instalar con un solo toque desde una página web: por eso hay que hacerlo desde Compartir.',
                flecha: !iosOtroNavegador && !esIpad,
            };
        }
        const android = /android/i.test(ua);
        return {
            pasos: android
                ? ['Toca el menú <b>&#8942;</b> del navegador (arriba a la derecha)', 'Elige <b>Instalar aplicación</b> o <b>Añadir a la pantalla de inicio</b>', 'Confirma con <b>Instalar</b>']
                : ['Busca el icono de instalar <b>&#8853;</b> al final de la barra de direcciones', 'O abre el menú del navegador y elige <b>Instalar ZuraEdu</b>', 'Confirma con <b>Instalar</b>'],
            nota: 'Si no aparece la opción, tu navegador puede no permitir instalar apps desde esta dirección.',
            flecha: false,
        };
    }

    function pintarPasos(cfg) {
        pasos.innerHTML = cfg.pasos.map(function (t, n) {
            return '<li style="display:flex;gap:.7rem;align-items:flex-start;background:#f8fafc;border-radius:.8rem;padding:.65rem .8rem;">'
                 + '<span style="flex-shrink:0;width:26px;height:26px;border-radius:50%;background:{{ $pwaColor }};color:#fff;font-weight:700;font-size:.85rem;display:flex;align-items:center;justify-content:center;">' + (n + 1) + '</span>'
                 + '<span>' + t + '</span></li>';
        }).join('');
        nota.textContent = cfg.nota;
    }

    function abrirGuia() {
        const cfg = pasosParaEstaPlataforma();
        pintarPasos(cfg);
        guia.style.paddingBottom = cfg.flecha ? 'calc(2.9rem + env(safe-area-inset-bottom))' : '';   // deja sitio a la flecha que señala el botón Compartir
        guia.style.display = 'block';
        flecha.style.display = cfg.flecha ? 'block' : 'none';
    }
    function cerrarGuia() { guia.style.display = 'none'; flecha.style.display = 'none'; }

    document.getElementById('pwa-ios-ok').addEventListener('click', function () { cerrarGuia(); snooze(30); });
    document.getElementById('pwa-ios-luego').addEventListener('click', function () { cerrarGuia(); snooze(3); });
    document.getElementById('pwa-ios-dismiss').addEventListener('click', function () { cerrarGuia(); snooze(3); });

    // ── Chrome / Edge / Samsung (beforeinstallprompt) ─────────────────────
    let deferredPrompt = null;
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
        document.dispatchEvent(new CustomEvent('zura:instalable'));
        if (estaInstalada() || descartada()) return;
        setTimeout(function () { banner.style.display = 'block'; }, 2500);
    });

    installBtn.addEventListener('click', function () {
        if (!deferredPrompt) { banner.style.display = 'none'; abrirGuia(); return; }   // antes: no hacía nada
        banner.style.display = 'none';
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(function (result) {
            if (result.outcome === 'accepted') snooze();
            deferredPrompt = null;
        });
    });
    dismissBtn.addEventListener('click', function () { banner.style.display = 'none'; snooze(); });
    window.addEventListener('appinstalled', function () { banner.style.display = 'none'; cerrarGuia(); deferredPrompt = null; snooze(); });

    // ── Acción única para el menú «Instalar la app» (accesos rápidos) ─────
    window.zuraInstalarApp = function () {
        if (estaInstalada()) return;
        if (deferredPrompt) { installBtn.click(); return; }
        abrirGuia();
    };
    window.zuraAppInstalada = estaInstalada;

    // ── Aviso automático (una vez, sin molestar) ──────────────────────────
    if (estaInstalada() || descartada()) return;
    if (esIos) {
        window.addEventListener('load', function () { setTimeout(abrirGuia, 3000); });
    }
})();
</script>
