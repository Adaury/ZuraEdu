// Generador de carga mínimo y neutral (mismo para PHP y Node). Uso: node loadgen.js URL CONEXIONES SEGUNDOS [Header: valor ...]
const http = require('http');
const [url, conns, secs, ...hdrs] = process.argv.slice(2);
const headers = {};
for (const h of hdrs) { const i = h.indexOf(':'); headers[h.slice(0, i).trim()] = h.slice(i + 1).trim(); }
const u = new URL(url);
const agent = new http.Agent({ keepAlive: true, maxSockets: Number(conns) });
const lat = []; let ok = 0, no2xx = 0, err = 0, bytes = 0;
const fin = Date.now() + Number(secs) * 1000;

function una() {
  return new Promise((resolve) => {
    const t0 = process.hrtime.bigint();
    const req = http.request({ host: u.hostname, port: u.port, path: u.pathname + u.search, headers, agent, method: 'GET' }, (res) => {
      res.on('data', (d) => { bytes += d.length; });
      res.on('end', () => {
        lat.push(Number(process.hrtime.bigint() - t0) / 1e6);
        if (res.statusCode >= 200 && res.statusCode < 300) ok++; else no2xx++;
        resolve();
      });
    });
    req.on('error', () => { err++; resolve(); });
    req.setTimeout(30000, () => req.destroy());
    req.end();
  });
}
async function worker() { while (Date.now() < fin) await una(); }

(async () => {
  const t0 = Date.now();
  await Promise.all(Array.from({ length: Number(conns) }, worker));
  const dur = (Date.now() - t0) / 1000;
  lat.sort((a, b) => a - b);
  const p = (q) => (lat.length ? +lat[Math.min(lat.length - 1, Math.floor(q * lat.length))].toFixed(1) : 0);
  console.log(JSON.stringify({ req_s: Math.round(ok / dur), p50_ms: p(0.5), p95_ms: p(0.95), p99_ms: p(0.99), max_ms: p(1), ok, non2xx: no2xx, errores: err, KB_por_resp: ok ? Math.round(bytes / ok / 1024) : 0 }));
  agent.destroy();
})();
