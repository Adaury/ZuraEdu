(function() {
    const CONTEO_URL = ZURA_CFG.alertasConteoUrl;
    let lastCount    = ZURA_CFG.alertasInicial;

    async function pollAlertas() {
        try {
            const res  = await fetch(CONTEO_URL, { headers: { 'Accept': 'application/json' } });
            if (! res.ok) return;
            const { total } = await res.json();
            const badge = document.getElementById('adminBellBadge');
            const bell  = document.getElementById('adminBell');
            if (! badge) return;

            if (total > 0) {
                badge.textContent = total > 9 ? '9+' : total;
                badge.style.display = 'flex';
                if (total > lastCount) {
                    bell.style.color = '#ef4444';
                    setTimeout(() => { bell.style.color = ''; }, 3000);
                }
            } else {
                badge.style.display = 'none';
            }
            lastCount = total;
        } catch (_) {}
    }

    setInterval(pollAlertas, 60000);
})();
