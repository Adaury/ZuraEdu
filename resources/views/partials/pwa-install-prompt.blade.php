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

{{-- ── Tooltip iOS (Safari no soporta beforeinstallprompt) ─────────────── --}}
<div id="pwa-ios-prompt"
     role="dialog"
     aria-modal="true"
     aria-label="Cómo instalar en iOS"
     style="
        display:none;
        position:fixed;
        bottom:1.25rem;left:50%;
        transform:translateX(-50%);
        z-index:10000;
        background:#1e293b;
        color:#f8fafc;
        border-radius:1rem;
        padding:1rem 1.25rem;
        max-width:calc(100vw - 2.5rem);
        width:340px;
        box-shadow:0 8px 32px rgba(0,0,0,.28);
        font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
        font-size:.875rem;
        line-height:1.55;
        animation:pwaSlideUp .28s cubic-bezier(.4,0,.2,1);
     ">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;margin-bottom:.625rem;">
        <strong style="font-size:.9375rem;">Instalar {{ $pwaName }}</strong>
        <button id="pwa-ios-dismiss"
                aria-label="Cerrar"
                style="background:none;border:none;color:#94a3b8;font-size:1.25rem;line-height:1;cursor:pointer;padding:0;flex-shrink:0;">
            &times;
        </button>
    </div>
    <ol style="margin:0;padding-left:1.125rem;color:#cbd5e1;">
        <li>Toca el botón <strong style="color:#f8fafc;">Compartir</strong>
            <span style="font-size:1rem;">&#10514;</span> en Safari</li>
        <li>Selecciona <strong style="color:#f8fafc;">Añadir a pantalla de inicio</strong></li>
        <li>Toca <strong style="color:#f8fafc;">Añadir</strong> para confirmar</li>
    </ol>
    {{-- Triángulo apuntando abajo --}}
    <div style="
        position:absolute;
        bottom:-8px;left:50%;
        transform:translateX(-50%);
        width:0;height:0;
        border-left:9px solid transparent;
        border-right:9px solid transparent;
        border-top:9px solid #1e293b;
    "></div>
</div>

<style>
@keyframes pwaSlideUp {
    from { transform: translateY(100%); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}
#pwa-ios-prompt { animation: pwaSlideUp .28s cubic-bezier(.4,0,.2,1); }
</style>

<script>
(function () {
    'use strict';

    const STORAGE_KEY  = 'pwa_prompt_dismissed';
    const DISMISS_DAYS = 30;

    // Ya está instalado como standalone → no mostrar nada
    if (window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true) return;

    // Fue descartado recientemente
    const dismissed = localStorage.getItem(STORAGE_KEY);
    if (dismissed && Date.now() < parseInt(dismissed, 10)) return;

    const banner      = document.getElementById('pwa-install-banner');
    const installBtn  = document.getElementById('pwa-install-btn');
    const dismissBtn  = document.getElementById('pwa-install-dismiss');
    const iosPrompt   = document.getElementById('pwa-ios-prompt');
    const iosDismiss  = document.getElementById('pwa-ios-dismiss');

    function snooze() {
        const until = Date.now() + DISMISS_DAYS * 86400 * 1000;
        localStorage.setItem(STORAGE_KEY, String(until));
    }

    function isIos() {
        return /iphone|ipad|ipod/i.test(navigator.userAgent)
            && !window.MSStream;
    }

    function isSafari() {
        return /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    }

    // ── iOS Safari ────────────────────────────────────────────────────────
    if (isIos() && isSafari()) {
        window.addEventListener('load', function () {
            setTimeout(function () {
                iosPrompt.style.display = 'block';
            }, 3000);
        });

        iosDismiss.addEventListener('click', function () {
            iosPrompt.style.display = 'none';
            snooze();
        });
        return;
    }

    // ── Chrome / Edge / Samsung (beforeinstallprompt) ─────────────────────
    var deferredPrompt = null;

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;

        // Pequeño retraso para no interrumpir la carga inicial
        setTimeout(function () {
            banner.style.display = 'block';
        }, 2500);
    });

    installBtn.addEventListener('click', function () {
        if (!deferredPrompt) return;
        banner.style.display = 'none';
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(function (result) {
            if (result.outcome === 'accepted') {
                snooze();
            }
            deferredPrompt = null;
        });
    });

    dismissBtn.addEventListener('click', function () {
        banner.style.display = 'none';
        snooze();
    });

    // Si el usuario instala desde el menú del navegador, ocultamos el banner
    window.addEventListener('appinstalled', function () {
        banner.style.display = 'none';
        deferredPrompt = null;
        snooze();
    });
})();
</script>
