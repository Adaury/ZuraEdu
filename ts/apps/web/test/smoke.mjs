// Prueba de humo de la web contra servicios REALES (web + API TypeScript + Laravel + MySQL). Recorre el flujo completo por HTTP:
// login, listado, búsqueda, edición, errores, CSRF, XSS por URL, ids raros, aislamiento entre colegios y cierre de sesión.
//
// Uso:  WEB_URL=http://127.0.0.1:3101 API_URL=http://127.0.0.1:3100 SMOKE_HOST=demo.zuraedu.com \
//       SMOKE_EMAIL=admin@demo.com SMOKE_PASSWORD=... [SMOKE_AJENO_ID=<id de un estudiante de OTRO colegio>] node test/smoke.mjs
// Sale con código 1 si falla algo.
import { request } from 'node:http';

const WEB = new URL(process.env.WEB_URL ?? 'http://127.0.0.1:3101');
const API = new URL(process.env.API_URL ?? 'http://127.0.0.1:3100');
const HOST = process.env.SMOKE_HOST ?? 'demo.zuraedu.com';
const EMAIL = process.env.SMOKE_EMAIL;
const PASSWORD = process.env.SMOKE_PASSWORD;
const AJENO = process.env.SMOKE_AJENO_ID;
if (!EMAIL || !PASSWORD) {
  console.error('Faltan SMOKE_EMAIL y SMOKE_PASSWORD.');
  process.exit(2);
}

/** Petición con Host y Origin controlados (el fetch de Node no deja cambiar Host). */
function http(base, { path, method = 'GET', headers = {}, cuerpo }) {
  return new Promise((resolver, rechazar) => {
    const datos = cuerpo === undefined ? undefined : typeof cuerpo === 'string' ? cuerpo : JSON.stringify(cuerpo);
    const h = { Host: HOST, ...headers };
    if (datos !== undefined) h['Content-Length'] = String(Buffer.byteLength(datos));
    const req = request({ host: base.hostname, port: base.port, path, method, headers: h }, (res) => {
      const trozos = [];
      res.on('data', (t) => trozos.push(t));
      res.on('end', () => resolver({ status: res.statusCode, headers: res.headers, texto: Buffer.concat(trozos).toString('utf8') }));
    });
    req.on('error', rechazar);
    if (datos !== undefined) req.write(datos);
    req.end();
  });
}
const web = (o) => http(WEB, o);
const ORIGEN = `http://${HOST}`;
const formulario = (obj) => new URLSearchParams(obj).toString();
const post = (path, obj, { cookie, origin = ORIGEN } = {}) =>
  web({
    path,
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', ...(origin ? { Origin: origin } : {}), ...(cookie ? { Cookie: cookie } : {}) },
    cuerpo: formulario(obj),
  });
const get = (path, cookie) => web({ path, headers: cookie ? { Cookie: cookie } : {} });

const sinComentarios = (html) => html.replace(/<!-- -->/g, ''); // React separa los textos con comentarios
let fallos = 0;
let pruebas = 0;
function verificar(descripcion, condicion, detalle = '') {
  pruebas++;
  if (condicion) console.log(`  ✓ ${descripcion}`);
  else {
    fallos++;
    console.log(`  ✗ ${descripcion}${detalle ? `\n      ${detalle}` : ''}`);
  }
}
const ubicacion = (r) => r.headers.location ?? '';
const esRedireccion = (r, destino) => [301, 302, 303, 307, 308].includes(r.status) && ubicacion(r).startsWith(destino);
const cookieDe = (r) => (r.headers['set-cookie'] ?? []).find((c) => c.startsWith('zura_token='));

console.log(`Web ${WEB.origin} · API ${API.origin} · Host ${HOST} · usuario ${EMAIL}`);

console.log('\n1) Sin sesión');
verificar('GET / redirige al login', esRedireccion(await get('/'), '/login'));
verificar('GET /estudiantes redirige al login', esRedireccion(await get('/estudiantes'), '/login'));
verificar('GET /estudiantes/1/editar redirige al login', esRedireccion(await get('/estudiantes/1/editar'), '/login'));
const login = await get('/login');
verificar('GET /login → 200 con el formulario', login.status === 200 && login.texto.includes('name="email"') && login.texto.includes('name="password"'));
verificar('cabeceras de seguridad (X-Frame-Options, nosniff, sin x-powered-by)', login.headers['x-frame-options'] === 'DENY' && login.headers['x-content-type-options'] === 'nosniff' && !login.headers['x-powered-by']);
const xss = await get('/login?error=%3Cscript%3Ealert(1)%3C/script%3E');
// Next serializa los parámetros de la URL en su propio JSON interno (escapado como <script>): eso no es HTML. Lo que importa es
// que no haya un <script> inyectado ni un mensaje de error visible construido con el texto de la URL.
verificar('?error=<script> no se refleja: sin <script> inyectado y sin aviso visible (solo códigos conocidos)', xss.status === 200 && !xss.texto.includes('<script>alert') && !xss.texto.includes('role="alert"'));

console.log('\n2) CSRF en el login');
verificar('POST sin Origin → 403', (await post('/sesion/entrar', { email: EMAIL, password: PASSWORD }, { origin: null })).status === 403);
verificar('POST con Origin de otro sitio → 403', (await post('/sesion/entrar', { email: EMAIL, password: PASSWORD }, { origin: 'https://evil.example' })).status === 403);
verificar('POST con Origin parecido (host.evil) → 403', (await post('/sesion/entrar', { email: EMAIL, password: PASSWORD }, { origin: `http://${HOST}.evil.example` })).status === 403);

console.log('\n3) Inicio de sesión');
const mala = await post('/sesion/entrar', { email: EMAIL, password: `${PASSWORD}-mala` });
verificar('contraseña incorrecta → /login?error=credenciales y SIN cookie', esRedireccion(mala, '/login?error=credenciales') && !cookieDe(mala), `status ${mala.status} → ${ubicacion(mala)}`);
const vacia = await post('/sesion/entrar', { email: '', password: '' });
verificar('campos vacíos → credenciales', esRedireccion(vacia, '/login?error=credenciales'));
const ok = await post('/sesion/entrar', { email: EMAIL, password: PASSWORD });
const set = cookieDe(ok) ?? '';
verificar('credenciales correctas → 303 a /estudiantes', ok.status === 303 && ubicacion(ok) === '/estudiantes', `status ${ok.status} → ${ubicacion(ok)} ${ok.texto.slice(0, 100)}`);
verificar('cookie HttpOnly + SameSite=Lax + Path=/ + Max-Age', /HttpOnly/i.test(set) && /SameSite=Lax/i.test(set) && /Path=\//.test(set) && /Max-Age=\d+/.test(set), set);
const valorCookie = decodeURIComponent((set.split(';')[0] ?? '').replace('zura_token=', ''));
verificar('el token tiene el formato de Sanctum (id|texto)', /^\d+\|[A-Za-z0-9]{20,}$/.test(valorCookie));
const COOKIE = `zura_token=${encodeURIComponent(valorCookie)}`;
verificar('con sesión, /login redirige al listado', esRedireccion(await get('/login', COOKIE), '/estudiantes'));

console.log('\n4) Listado');
const lista = await get('/estudiantes', COOKIE);
verificar('GET /estudiantes → 200 con tabla y paginación', lista.status === 200 && lista.texto.includes('<table') && sinComentarios(lista.texto).includes('Página 1 de'), `status ${lista.status}`);
verificar('la página NO contiene el token (la cookie es HttpOnly y no se refleja)', !lista.texto.includes(valorCookie.split('|')[1] ?? 'xx'));
const ids = [...lista.texto.matchAll(/href="\/estudiantes\/(\d+)\/editar"/g)].map((m) => Number(m[1]));
verificar('hay estudiantes con enlace de edición', ids.length > 0, `ids: ${ids.length}`);
const filas = (html) => (html.match(/<tr>/g) ?? []).length;
verificar('el listado trae como máximo 20 filas por página', filas(lista.texto) <= 20 + 1 && filas(lista.texto) >= 2);

const apellido = (lista.texto.match(/<td>([^<]+)<\/td><td>[^<]*<\/td><td>[^<]*<\/td><td>/) ?? [])[1];
const buscar = await get(`/estudiantes?q=${encodeURIComponent(ids.length ? 'a' : 'x')}&estado=activo`, COOKIE);
verificar('búsqueda con filtro de estado → 200', buscar.status === 200);
const ignorados = await get('/estudiantes?estado=hacker&page=abc&q=%3Cscript%3Ealert(1)%3C/script%3E', COOKIE);
verificar('estado inválido, page inválida y q con <script> → 200 y q escapada', ignorados.status === 200 && !ignorados.texto.includes('<script>alert') && ignorados.texto.includes('&lt;script&gt;'));
const lejos = await get('/estudiantes?page=99999', COOKIE);
verificar('página fuera de rango → 200 con "No hay estudiantes" (no un error)', lejos.status === 200 && lejos.texto.includes('No hay estudiantes'));

console.log('\n5) Edición');
const id = ids[0];
const editar = await get(`/estudiantes/${id}/editar`, COOKIE);
verificar('GET editar → 200 con el formulario y el id correcto', editar.status === 200 && editar.texto.includes(`action="/estudiantes/${id}/guardar"`), `status ${editar.status}`);
verificar('?e=<script> no se refleja; ?e=invalido&c=nombres marca el campo', (await get(`/estudiantes/${id}/editar?e=%3Cscript%3Ealert(1)%3C/script%3E`, COOKIE)).texto.includes('<script>alert') === false
  && (await get(`/estudiantes/${id}/editar?e=invalido&c=nombres`, COOKIE)).texto.includes('aria-invalid="true"'));
verificar('id con formato raro (abc, 1e3, 0x10, 007) → 404', (await Promise.all(['abc', '1e3', '0x10', '007', '-1'].map((x) => get(`/estudiantes/${x}/editar`, COOKIE)))).every((r) => r.status === 404));
verificar('id inexistente → 404', (await get('/estudiantes/999999999/editar', COOKIE)).status === 404);
if (AJENO) verificar(`estudiante de OTRO colegio (${AJENO}) → 404, no se distingue de inexistente`, (await get(`/estudiantes/${AJENO}/editar`, COOKIE)).status === 404);
else console.log('  - (omitida) estudiante de otro colegio: define SMOKE_AJENO_ID');

const campo = (html, nombre) => (html.match(new RegExp(`id="${nombre}"[^>]*value="([^"]*)"`)) ?? [])[1] ?? '';
const original = { nombres: campo(editar.texto, 'nombres'), apellidos: campo(editar.texto, 'apellidos'), cedula: campo(editar.texto, 'cedula') };
const sexoOriginal = (editar.texto.match(/<option value="([MF])" selected/) ?? [])[1] ?? 'M';
const estadoOriginal = (editar.texto.match(/<option value="(activo|inactivo|egresado|transferido)" selected/) ?? [])[1] ?? 'activo';
const cuerpoBase = { ...original, sexo: sexoOriginal, estado: estadoOriginal };
verificar('se leyeron los valores actuales del formulario', original.nombres !== '' && original.apellidos !== '', JSON.stringify(original));

verificar('guardar sin Origin → 403', (await post(`/estudiantes/${id}/guardar`, cuerpoBase, { cookie: COOKIE, origin: null })).status === 403);
verificar('guardar con Origin de otro sitio → 403', (await post(`/estudiantes/${id}/guardar`, cuerpoBase, { cookie: COOKIE, origin: 'https://evil.example' })).status === 403);
verificar('guardar sin sesión → expirada', esRedireccion(await post(`/estudiantes/${id}/guardar`, cuerpoBase), '/sesion/expirada'));
const nuevoApellido = `${original.apellidos.replace(/ SMK\d+$/, '')} SMK${Date.now() % 100000}`;
const guardar = await post(`/estudiantes/${id}/guardar`, { ...cuerpoBase, apellidos: nuevoApellido }, { cookie: COOKIE });
verificar('guardar un cambio válido → 303 a ?ok=1', guardar.status === 303 && ubicacion(guardar) === `/estudiantes/${id}/editar?ok=1`, `${guardar.status} → ${ubicacion(guardar)}`);
const trasGuardar = await get(`/estudiantes/${id}/editar?ok=1`, COOKIE);
verificar('el formulario muestra el apellido nuevo y el aviso "Cambios guardados"', campo(trasGuardar.texto, 'apellidos') === nuevoApellido && trasGuardar.texto.includes('Cambios guardados'));
const enApi = await http(API, { path: `/api/v1/estudiantes/${id}`, headers: { Authorization: `Bearer ${valorCookie}` } });
verificar('la API TypeScript confirma el cambio (misma BD)', enApi.status === 200 && JSON.parse(enApi.texto).apellidos === nuevoApellido, enApi.texto.slice(0, 120));

const sexoMalo = await post(`/estudiantes/${id}/guardar`, { ...cuerpoBase, sexo: 'X' }, { cookie: COOKIE });
verificar('sexo inválido → ?e=invalido&c=sexo', ubicacion(sexoMalo) === `/estudiantes/${id}/editar?e=invalido&c=sexo`, ubicacion(sexoMalo));
const estadoMalo = await post(`/estudiantes/${id}/guardar`, { ...cuerpoBase, estado: 'hackeado' }, { cookie: COOKIE });
verificar('estado inválido → ?e=invalido&c=estado', ubicacion(estadoMalo) === `/estudiantes/${id}/editar?e=invalido&c=estado`, ubicacion(estadoMalo));
const sinNombre = await post(`/estudiantes/${id}/guardar`, { ...cuerpoBase, nombres: '' }, { cookie: COOKIE });
verificar('nombres vacío (la API responde 400) → ?e=invalido&c=nombres', ubicacion(sinNombre).startsWith(`/estudiantes/${id}/editar?e=invalido`) && ubicacion(sinNombre).includes('nombres'), ubicacion(sinNombre));
if (ids.length > 1) {
  const otraLista = await get(`/estudiantes/${ids[1]}/editar`, COOKIE);
  const cedulaAjena = campo(otraLista.texto, 'cedula');
  if (cedulaAjena) {
    const dup = await post(`/estudiantes/${id}/guardar`, { ...cuerpoBase, cedula: cedulaAjena }, { cookie: COOKIE });
    verificar('cédula repetida en el colegio → ?e=duplicado', ubicacion(dup) === `/estudiantes/${id}/editar?e=duplicado`, ubicacion(dup));
  }
}
const inyeccion = await post(`/estudiantes/${id}/guardar`, { ...cuerpoBase, tenantId: '999', estado: estadoOriginal, tenant_id: '999' }, { cookie: COOKIE });
verificar('campos extra del formulario (tenantId) se ignoran: no hay error ni cambio de colegio', ubicacion(inyeccion) === `/estudiantes/${id}/editar?ok=1`, ubicacion(inyeccion));

// restaurar el dato original
await post(`/estudiantes/${id}/guardar`, cuerpoBase, { cookie: COOKIE });
const restaurado = await http(API, { path: `/api/v1/estudiantes/${id}`, headers: { Authorization: `Bearer ${valorCookie}` } });
verificar('el dato original quedó restaurado', JSON.parse(restaurado.texto).apellidos === original.apellidos);

console.log('\n6) Cierre de sesión');
verificar('POST /sesion/salir sin Origin → 403', (await post('/sesion/salir', {}, { cookie: COOKIE, origin: null })).status === 403);
const salir = await post('/sesion/salir', {}, { cookie: COOKIE });
const borrada = (salir.headers['set-cookie'] ?? []).find((c) => c.startsWith('zura_token='));
verificar('salir → 303 a /login y borra la cookie (Max-Age=0)', salir.status === 303 && ubicacion(salir) === '/login' && /Max-Age=0/.test(borrada ?? ''), `${salir.status} ${ubicacion(salir)} ${borrada}`);
const tokenRevocado = await http(API, { path: '/api/v1/estudiantes?perPage=1', headers: { Authorization: `Bearer ${valorCookie}` } });
verificar('el token quedó revocado en Laravel: la API responde 401 (con caché de auth = 0)', tokenRevocado.status === 401, `status ${tokenRevocado.status}`);
const conTokenMuerto = await get('/estudiantes', COOKIE);
verificar('con un token ya revocado, /estudiantes manda a /sesion/expirada', esRedireccion(conTokenMuerto, '/sesion/expirada'), `${conTokenMuerto.status} → ${ubicacion(conTokenMuerto)}`);
const expirada = await get('/sesion/expirada', COOKIE);
verificar('/sesion/expirada borra la cookie y vuelve al login con aviso', esRedireccion(expirada, '/login?error=expirada') && /Max-Age=0/.test((expirada.headers['set-cookie'] ?? []).join(';')));

console.log(`\n${pruebas - fallos}/${pruebas} comprobaciones correctas${fallos ? ` — ${fallos} FALLARON` : ''}.`);
process.exit(fallos ? 1 : 0);
