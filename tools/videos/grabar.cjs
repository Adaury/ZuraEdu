// Uso:  node tools/videos/grabar.cjs <nombre|todos> [nombre2 …]
// Requisitos (ver README.md): servidor local con base de demostración, ffmpeg, playwright-core, VIDEO_CLAVE_FILE.
const { grabar } = require('./lib.cjs');
const escenas = require('./escenas.cjs');

(async () => {
    const pedidos = process.argv.slice(2);
    const nombres = !pedidos.length || pedidos[0] === 'todos' ? Object.keys(escenas) : pedidos;
    for (const n of nombres) {
        if (!escenas[n]) { console.error('No existe la escena', n, '→ disponibles:', Object.keys(escenas).join(', ')); process.exitCode = 1; continue; }
        try { await grabar(n, escenas[n].escena, escenas[n].opciones); }
        catch (e) { console.error('✗', n, '→', e.message); process.exitCode = 1; }
    }
})();
