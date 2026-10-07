// Biblioteca para grabar los videos de ZuraEdu con un navegador real (Playwright) y convertirlos a MP4 con ffmpeg.
// Sin audio: cada paso lleva un texto explicativo en pantalla, un cursor visible y movimientos suaves.
// Las credenciales NUNCA van aquí: se leen de VIDEO_CLAVE o del archivo indicado en VIDEO_CLAVE_FILE.
const { chromium } = require(process.env.PLAYWRIGHT_PATH || 'playwright-core');
const fs = require('fs');
const os = require('os');
const path = require('path');
const { execFileSync } = require('child_process');

const RAIZ = path.resolve(__dirname, '..', '..');
const BASE = process.env.VIDEO_BASE || 'http://127.0.0.1:8091';
const SALIDA = process.env.VIDEO_OUT || path.join(RAIZ, 'public', 'videos');
const CHROME = process.env.CHROME_PATH || path.join(os.homedir(), 'AppData', 'Local', 'ms-playwright', 'chromium-1228', 'chrome-win64', 'chrome.exe');
const FFMPEG = process.env.FFMPEG || 'ffmpeg';

function clave() {
    if (process.env.VIDEO_CLAVE) return process.env.VIDEO_CLAVE.trim();
    if (process.env.VIDEO_CLAVE_FILE) return fs.readFileSync(process.env.VIDEO_CLAVE_FILE, 'utf8').trim();
    throw new Error('Falta VIDEO_CLAVE o VIDEO_CLAVE_FILE (contraseña de las cuentas de demostración).');
}

const logoBlanco = () => 'data:image/png;base64,' + fs.readFileSync(path.join(RAIZ, 'public', 'brand', 'zuraedu-logo-blanco.png')).toString('base64');

// Cursor y barra de texto, inyectados en cada página
const INYECCION = `
(() => {
  if (window.__videoListo) return; window.__videoListo = true;
  const css = document.createElement('style');
  css.textContent = \`
    #__cursor{position:fixed;z-index:2147483647;width:26px;height:26px;margin:-13px 0 0 -13px;border-radius:50%;pointer-events:none;
      background:rgba(99,102,241,.35);border:3px solid #fff;box-shadow:0 2px 10px rgba(0,0,0,.45);transition:transform .12s ease;left:-50px;top:-50px}
    #__cursor.clic{transform:scale(.7)}
    .__onda{position:fixed;z-index:2147483646;width:14px;height:14px;margin:-7px 0 0 -7px;border-radius:50%;pointer-events:none;border:3px solid #6366f1;animation:__onda .6s ease-out forwards}
    @keyframes __onda{to{transform:scale(4.5);opacity:0}}
    #__cap{position:fixed;z-index:2147483647;left:50%;bottom:34px;transform:translateX(-50%);max-width:82%;padding:14px 26px;border-radius:14px;
      background:rgba(15,23,42,.92);color:#fff;font:600 24px/1.35 system-ui,Segoe UI,Arial,sans-serif;text-align:center;
      border-left:6px solid #6366f1;box-shadow:0 10px 30px rgba(0,0,0,.35);opacity:0;transition:opacity .35s ease;pointer-events:none}
    #__cap.on{opacity:1}
    #__cap.movil{font-size:19px;bottom:22px;max-width:92%;padding:11px 16px}
  \`;
  const c = document.createElement('div'); c.id = '__cursor';
  const k = document.createElement('div'); k.id = '__cap';
  const montar = () => { document.documentElement.appendChild(css); document.body.appendChild(c); document.body.appendChild(k); try { const t = sessionStorage.getItem('__cap'); if (t) { k.textContent = t; k.classList.add('on'); } } catch (e) {} };
  if (document.body) montar(); else document.addEventListener('DOMContentLoaded', montar);
  window.addEventListener('mousemove', (e) => { c.style.left = e.clientX + 'px'; c.style.top = e.clientY + 'px'; }, true);
  window.addEventListener('mousedown', (e) => { c.classList.add('clic'); const o = document.createElement('div'); o.className = '__onda'; o.style.left = e.clientX + 'px'; o.style.top = e.clientY + 'px'; document.body.appendChild(o); setTimeout(() => o.remove(), 700); }, true);
  window.addEventListener('mouseup', () => c.classList.remove('clic'), true);
  window.__cap = (t, movil) => { try { sessionStorage.setItem('__cap', t || ''); } catch (e) {} k.textContent = t || ''; k.classList.toggle('movil', !!movil); k.classList.toggle('on', !!t); };
})();
`;

const pausa = (ms) => new Promise((r) => setTimeout(r, ms));

function tarjetaHtml({ titulo, subtitulo = '', lineas = [], pie = '', tono = 'azul' }) {
    const fondos = {
        azul: 'linear-gradient(135deg,#1e3a6e 0%,#312e81 55%,#4f46e5 100%)',
        oscuro: 'linear-gradient(135deg,#0f172a 0%,#1e293b 100%)',
        claro: 'linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%)',
    };
    return `<!doctype html><html><head><meta charset="utf-8"><style>
      *{box-sizing:border-box;margin:0}
      body{height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:22px;text-align:center;color:#fff;
        background:${fondos[tono] || fondos.azul};font-family:system-ui,Segoe UI,Arial,sans-serif;padding:6vw;overflow:hidden}
      img{height:min(11vh,90px);animation:subir .9s ease both}
      h1{font-size:min(7vw,64px);line-height:1.12;font-weight:800;letter-spacing:-.02em;max-width:90%;animation:subir .9s .15s ease both}
      h2{font-size:min(3.4vw,28px);font-weight:500;opacity:.9;max-width:80%;line-height:1.4;animation:subir .9s .35s ease both}
      ul{list-style:none;padding:0;display:flex;flex-wrap:wrap;gap:12px;justify-content:center;max-width:90%}
      li{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.28);padding:10px 20px;border-radius:999px;font-size:min(2.6vw,22px);font-weight:600;animation:subir .8s ease both}
      ${lineas.map((_, i) => `li:nth-child(${i + 1}){animation-delay:${0.5 + i * 0.28}s}`).join('\n')}
      p{position:absolute;bottom:4vh;font-size:min(2.2vw,19px);opacity:.75;animation:subir .9s .9s ease both}
      @keyframes subir{from{opacity:0;transform:translateY(22px)}to{opacity:1;transform:none}}
    </style></head><body>
      <img src="${logoBlanco()}" alt="ZuraEdu">
      <h1>${titulo}</h1>
      ${subtitulo ? `<h2>${subtitulo}</h2>` : ''}
      ${lineas.length ? `<ul>${lineas.map((l) => `<li>${l}</li>`).join('')}</ul>` : ''}
      ${pie ? `<p>${pie}</p>` : ''}
    </body></html>`;
}

/** Graba `escena(v)` en un MP4. opciones: { ancho, alto, movil, escalarA: [ancho, alto] (tamaño final del MP4, p. ej. [1080, 1920]) } */
async function grabar(nombre, escena, { ancho = 1280, alto = 720, movil = false, escalarA = null } = {}) {
    fs.mkdirSync(SALIDA, { recursive: true });
    const tmp = fs.mkdtempSync(path.join(os.tmpdir(), 'zuraedu-video-'));
    const browser = await chromium.launch({ executablePath: CHROME, headless: true });
    const ctx = await browser.newContext({
        viewport: { width: ancho, height: alto },
        recordVideo: { dir: tmp, size: { width: ancho, height: alto } },
        deviceScaleFactor: 1,
        locale: 'es-DO',
        bypassCSP: true,   // la aplicación tiene CSP y bloquearía el cursor y los textos inyectados
        isMobile: movil,
        hasTouch: movil,
    });
    await ctx.addInitScript(INYECCION);
    const page = await ctx.newPage();
    page.on('dialog', (d) => d.dismiss());
    const inicio = Date.now();
    const v = {
        page, base: BASE, movil,
        pausa,
        async ir(ruta, texto, espera = 1500) {
            await page.goto(ruta.startsWith('http') ? ruta : BASE + ruta, { waitUntil: 'load' });
            await pausa(450);
            // El cursor se desplaza un poco en cada pantalla para que se note que alguien la está usando
            await page.mouse.move(ancho * (movil ? 0.55 : 0.62), alto * 0.42, { steps: 30 });
            if (texto) await this.cap(texto, espera); else await pausa(espera);
        },
        async cap(texto, ms) {
            await page.evaluate(([t, m]) => window.__cap && window.__cap(t, m), [texto, movil]);
            await pausa(ms ?? Math.max(1800, 1000 + texto.length * 55));
        },
        async sinTexto() { await page.evaluate(() => window.__cap && window.__cap('')); },
        async mover(objetivo, pasos = 28) {
            const loc = typeof objetivo === 'string' ? page.locator(objetivo).first() : objetivo;
            await loc.scrollIntoViewIfNeeded({ timeout: 2500 }).catch(() => {});
            const caja = await loc.boundingBox({ timeout: 2500 }).catch(() => null);
            if (!caja) return false;
            await page.mouse.move(caja.x + caja.width / 2, caja.y + caja.height / 2, { steps: pasos });
            await pausa(250);
            return true;
        },
        async clic(objetivo, espera = 1200) {
            if (await this.mover(objetivo)) {
                await page.mouse.down(); await pausa(90); await page.mouse.up();
                await pausa(espera);
            }
        },
        async escribir(objetivo, texto, espera = 700) {
            await this.mover(objetivo);
            const loc = typeof objetivo === 'string' ? page.locator(objetivo).first() : objetivo;
            await loc.click();
            await loc.pressSequentially(texto, { delay: 90 });
            await pausa(espera);
        },
        async bajar(px = 500, ms = 1400) {
            const pasos = 28;
            for (let i = 0; i < pasos; i++) {
                await page.evaluate((d) => window.scrollBy(0, d), px / pasos);
                await pausa(ms / pasos);
            }
            await pausa(300);
        },
        async arriba(ms = 600) { await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'smooth' })); await pausa(ms); },
        async tarjeta(datos, ms = 3200) {
            await page.setContent(tarjetaHtml(datos), { waitUntil: 'load' });
            await pausa(ms);
        },
        /** href del primer enlace que cumpla el selector (para entrar a una clase o a un hijo sin conocer su id) */
        async primerEnlace(selector) {
            return page.locator(selector).first().getAttribute('href', { timeout: 4000 }).catch(() => null);
        },
        async intentar(fn) { try { await fn(); } catch (e) { console.log('  (paso omitido: ' + String(e.message).split(/\r?\n/)[0].slice(0, 90) + ')'); } },
        async login(correo) {
            await page.context().clearCookies();   // cambiar de cuenta a mitad del video
            await page.goto(BASE + '/login', { waitUntil: 'load' });
            await page.fill('input[name=email]', correo);
            await page.fill('input[name=password]', clave());
            await Promise.all([page.waitForLoadState('load'), page.click('button[type=submit]')]);
            await pausa(900);
            if (/\/login/.test(page.url())) throw new Error('No se pudo iniciar sesión con ' + correo);
        },
    };

    let error = null;
    try { await escena(v); } catch (e) { error = e; }
    const video = page.video();
    await ctx.close();
    const webm = await video.path();
    await browser.close();
    if (error) { fs.rmSync(tmp, { recursive: true, force: true }); throw error; }

    const mp4 = path.join(SALIDA, nombre + '.mp4');
    const poster = path.join(SALIDA, nombre + '.jpg');
    const filtro = escalarA ? ['-vf', `scale=${escalarA[0]}:${escalarA[1]}:flags=lanczos`] : [];
    const calidad = escalarA ? '24' : '29';   // al ampliar, más calidad para que el texto no se vea pastoso
    execFileSync(FFMPEG, ['-y', '-loglevel', 'error', '-i', webm, '-an', ...filtro, '-c:v', 'libx264', '-preset', 'slow', '-crf', calidad, '-pix_fmt', 'yuv420p', '-movflags', '+faststart', '-r', escalarA ? '30' : '25', mp4]);
    execFileSync(FFMPEG, ['-y', '-loglevel', 'error', '-ss', '2.5', '-i', mp4, '-frames:v', '1', '-q:v', '4', poster]);
    fs.rmSync(tmp, { recursive: true, force: true });
    const seg = ((Date.now() - inicio) / 1000).toFixed(0);
    const mb = (fs.statSync(mp4).size / 1048576).toFixed(1);
    console.log(`✓ ${nombre}.mp4  (${seg} s grabando, ${mb} MB)`);
    return mp4;
}

module.exports = { grabar, BASE, SALIDA, pausa };
