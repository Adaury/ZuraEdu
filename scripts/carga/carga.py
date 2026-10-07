#!/usr/bin/env python3
"""Generador de carga HTTP para ZuraEdu (solo librería estándar, asyncio). Pensado para el flujo .github/workflows/carga.yml.

Cada "usuario virtual" usa la sesión de una persona distinta (scripts/carga/preparar.php las fabrica) y navega las páginas reales de su portal.

Modos:
  cerrado   cada usuario pide otra página en cuanto recibe la respuesta (mide CAPACIDAD: cuántas páginas/s aguanta el servidor)
  usuarios  cada usuario espera entre 'pensar-min' y 'pensar-max' segundos entre páginas (se parece a personas reales)
  rafaga    todos los usuarios piden UNA página en el mismo instante (mide cómo reacciona a una avalancha)

Uso: carga.py --etiqueta X --modo usuarios --usuarios 1500 --seg 90 --rampa 30 --pensar-min 10 --pensar-max 30 \
              --sesiones sesiones.json --url http://127.0.0.1 --host carga.local --salida resultado.json [--log-nginx access.log] [--md resumen.md]
"""
import argparse, asyncio, collections, json, os, random, sys, time

MEZCLA = [('estudiante', 50), ('padre', 30), ('docente', 15), ('admin', 5)]
RUTAS = {
    'estudiante': ['/portal/estudiante'] * 4 + ['/portal/estudiante/asistencia', '/portal/estudiante/horario',
                                                '/portal/estudiante/boletin', '/portal/estudiante/comunicados'],
    'padre': ['/portal/padre'] * 3 + ['/portal/padre/hijo/{h}', '/portal/padre/hijo/{h}/asistencia', '/portal/padre/hijo/{h}/horario'],
    'docente': ['/portal/docente'] * 3 + ['/portal/docente/horario', '/portal/docente/asistencia-rapida'],
    'admin': ['/admin/dashboard', '/admin/estudiantes', '/admin/asistencia', '/admin/calificaciones'],
}


def pct(valores, p):
    a = sorted(valores)
    return round(a[min(len(a) - 1, int(p / 100 * len(a)))], 1) if a else None


def clasificar(estado, cuerpo_final):
    if 200 <= estado < 300:
        # HTML completo (termina en </html>) o JSON completo (termina en } o ]): la petición llegó entera
        fin = cuerpo_final.rstrip()
        if fin.endswith(b'\n0'):          # fin de una respuesta «chunked» (…\r\n0\r\n\r\n): el contenido real termina antes
            fin = fin[:-1].rstrip()
        return 'ok' if (b'</html>' in cuerpo_final.lower() or fin[-1:] in (b'}', b']')) else 'incompleta'
    if 300 <= estado < 400:
        return 'redireccion'          # normalmente: la sesión no valió y mandó al login
    if estado == 429:
        return '429'
    if 400 <= estado < 500:
        return '4xx'
    return '5xx' if estado >= 500 else 'respuesta_vacia'


async def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--etiqueta', required=True)
    ap.add_argument('--modo', choices=['cerrado', 'usuarios', 'rafaga'], required=True)
    ap.add_argument('--usuarios', type=int, required=True)
    ap.add_argument('--seg', type=float, default=30)
    ap.add_argument('--rampa', type=float, default=0)
    ap.add_argument('--pensar-min', type=float, default=10)
    ap.add_argument('--pensar-max', type=float, default=30)
    ap.add_argument('--sesiones', required=True)
    ap.add_argument('--url', default='http://127.0.0.1')
    ap.add_argument('--host', default='carga.local')
    ap.add_argument('--timeout', type=float, default=60)
    ap.add_argument('--salida', required=True)
    ap.add_argument('--solo-ruta', help='todos los usuarios piden SOLO esta ruta (p. ej. /portal/estudiante)')
    ap.add_argument('--solo-rol', choices=[r for r, _ in MEZCLA], help='todos los usuarios son de este rol')
    ap.add_argument('--log-nginx')
    ap.add_argument('--md')
    a = ap.parse_args()

    host, _, puerto = a.url.replace('http://', '').partition(':')
    puerto = int(puerto or 80)
    sesiones = json.load(open(a.sesiones, encoding='utf-8'))
    por_rol = collections.defaultdict(list)
    for s in sesiones:
        por_rol[s['rol']].append(s)
    faltan = [r for r, _ in MEZCLA if not por_rol[r] and (not a.solo_rol or r == a.solo_rol)]
    if faltan:
        sys.exit(f"Faltan sesiones de: {faltan}")

    reg = []                      # (t_rel, rol, ruta, clase, estado, lat_ms)
    t0 = time.monotonic()
    parar = False

    async def una(s, ruta, rol):
        ini = time.monotonic()
        clase, estado = 'red', 0
        try:
            async def todo():
                r, w = await asyncio.open_connection(host, puerto)
                w.write(f"GET {ruta} HTTP/1.1\r\nHost: {a.host}\r\nCookie: {s['cookie']}\r\nConnection: close\r\nAccept: text/html\r\n\r\n".encode())
                await w.drain()
                datos = bytearray()
                while True:
                    b = await r.read(65536)
                    if not b:
                        break
                    datos += b
                w.close()
                return bytes(datos)
            d = await asyncio.wait_for(todo(), a.timeout)
            partes = d[:20].split(b' ')
            estado = int(partes[1]) if len(partes) > 1 and partes[1].isdigit() else 0
            clase = clasificar(estado, d[-400:])
        except asyncio.TimeoutError:
            clase = 'timeout'
        except ConnectionRefusedError:
            clase = 'rechazada'
        except (ConnectionResetError, BrokenPipeError):
            clase = 'reset'
        except OSError as e:
            clase = 'puertos_cliente' if e.errno in (98, 99, 24) else f'red_{e.errno}'
        reg.append((time.monotonic() - t0, rol, ruta, clase, estado, (time.monotonic() - ini) * 1000))

    roles = [r for r, p in MEZCLA for _ in range(p)]      # 100 posiciones con la mezcla pedida
    if a.solo_rol:
        roles = [a.solo_rol] * 100

    async def usuario(i):
        rol = roles[i % 100] if i % 100 < len(roles) else roles[0]
        s = por_rol[rol][i % len(por_rol[rol])]
        if a.rampa > 0:
            await asyncio.sleep(a.rampa * i / a.usuarios)
        fin = a.rampa + a.seg
        while not parar and time.monotonic() - t0 < fin:
            ruta = a.solo_ruta or random.choice(RUTAS[rol]).replace('{h}', str(s.get('hijoId', 0)))
            await una(s, ruta, rol)
            if a.modo == 'rafaga':
                return
            if a.modo == 'usuarios':
                await asyncio.sleep(random.uniform(a.pensar_min, a.pensar_max))

    async def progreso():
        while not parar:
            await asyncio.sleep(10)
            t = time.monotonic() - t0
            ult = [r for r in reg if r[0] > t - 10]
            ok = [r[5] for r in ult if r[3] == 'ok']
            print(f"[{a.etiqueta}] t={t:5.0f}s últimos10s={len(ult):5d} {dict(collections.Counter(r[3] for r in ult))} p50={pct(ok, 50)} p95={pct(ok, 95)}", flush=True)

    print(f"[{a.etiqueta}] modo={a.modo} usuarios={a.usuarios} seg={a.seg} rampa={a.rampa}", flush=True)
    tareas = [asyncio.create_task(usuario(i)) for i in range(a.usuarios)]
    prog = asyncio.create_task(progreso())
    try:
        await asyncio.wait_for(asyncio.gather(*tareas, return_exceptions=True), a.rampa + a.seg + a.timeout + 15)
    except asyncio.TimeoutError:
        print('PLAZO ALCANZADO: se cierra con lo medido', flush=True)
    parar = True
    for t in tareas:
        t.cancel()
    prog.cancel()

    dur = max(0.001, max((r[0] for r in reg), default=1))
    ok = [r[5] for r in reg if r[3] == 'ok']
    clases = collections.Counter(r[3] for r in reg)
    por_ruta = {}
    for ruta in sorted({r[2] for r in reg}):
        l = [r[5] for r in reg if r[2] == ruta and r[3] == 'ok']
        por_ruta[ruta] = {'n': sum(1 for r in reg if r[2] == ruta), 'ok': len(l), 'p50': pct(l, 50), 'p95': pct(l, 95)}
    res = {'etiqueta': a.etiqueta, 'modo': a.modo, 'usuarios': a.usuarios, 'duracion_s': round(dur, 1), 'total': len(reg), 'clases': dict(clases),
           'ok_pct': round(100 * len(ok) / max(1, len(reg)), 1), 'ok_por_s': round(len(ok) / dur, 1),
           'ok_p50_ms': pct(ok, 50), 'ok_p95_ms': pct(ok, 95), 'ok_p99_ms': pct(ok, 99), 'ok_max_ms': round(max(ok), 1) if ok else None, 'por_ruta': por_ruta}

    # Lo que midió el SERVIDOR (nginx: estado, request_time, ruta), independiente de lo que vio el generador.
    if a.log_nginx and os.path.exists(a.log_nginx):
        est, lat = collections.Counter(), []
        for linea in open(a.log_nginx, encoding='utf-8', errors='replace'):
            p = linea.split()
            if len(p) >= 2:
                est[p[0]] += 1
                if p[0] == '200':
                    try:
                        lat.append(float(p[1]) * 1000)
                    except ValueError:
                        pass
        res['servidor'] = {'estados': dict(est), 'p50_ms': pct(lat, 50), 'p95_ms': pct(lat, 95), 'max_ms': round(max(lat), 1) if lat else None}

    json.dump(res, open(a.salida, 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
    print(json.dumps({k: v for k, v in res.items() if k != 'por_ruta'}, ensure_ascii=False, indent=1))

    if a.md:
        c = res['clases']
        srv = res.get('servidor', {})
        # Todo lo que no es ok ni una de las columnas anteriores (incompleta, 4xx, reset…): antes quedaba sin columna y un 0 % OK parecía misterioso.
        otras = res['total'] - sum(c.get(k, 0) for k in ('ok', 'rechazada', 'timeout', '5xx', 'redireccion'))
        with open(a.md, 'a', encoding='utf-8') as f:
            f.write(f"| {a.etiqueta} | {a.usuarios} | {res['total']} | {res['ok_pct']}% | {res['ok_por_s']} | {res['ok_p50_ms']} | {res['ok_p95_ms']} | "
                    f"{res['ok_max_ms']} | {c.get('rechazada', 0)} | {c.get('timeout', 0)} | {c.get('5xx', 0)} | {c.get('redireccion', 0)} | {otras} | "
                    f"{srv.get('p50_ms', '-')} / {srv.get('p95_ms', '-')} |\n")


if __name__ == '__main__':
    asyncio.run(main())
