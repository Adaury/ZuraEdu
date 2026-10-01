import { request as httpRequest, type IncomingHttpHeaders } from 'node:http';
import { request as httpsRequest } from 'node:https';

export interface Respuesta {
  status: number;
  headers: IncomingHttpHeaders;
  texto: string;
}

export interface OpcionesPeticion {
  method?: 'GET' | 'POST' | 'PATCH' | 'DELETE';
  headers?: Record<string, string>;
  /** Valor de la cabecera `Host`. */
  host?: string | null;
  cuerpoJson?: unknown;
  timeoutMs?: number;
}

/**
 * Petición HTTP del servidor web hacia la API y hacia Laravel.
 *
 * Por qué NO `fetch`: el `fetch` de Node ignora la cabecera `Host` (se comprobó: el servidor recibe la dirección interna) y la API
 * decide el colegio POR ESA CABECERA (y responde 403 si el token es de otro colegio). `node:http` sí permite fijarla. La web reenvía
 * el `Host` que mandó el navegador; nunca uno que construya ella.
 */
export function peticion(url: string, o: OpcionesPeticion = {}): Promise<Respuesta> {
  return new Promise((resolver, rechazar) => {
    const destino = new URL(url);
    const cuerpo = o.cuerpoJson === undefined ? undefined : JSON.stringify(o.cuerpoJson);
    const headers: Record<string, string> = { Accept: 'application/json', ...o.headers };
    if (o.host) headers.Host = o.host;
    if (cuerpo !== undefined) {
      headers['Content-Type'] = 'application/json';
      headers['Content-Length'] = String(Buffer.byteLength(cuerpo));
    }

    const pedir = destino.protocol === 'https:' ? httpsRequest : httpRequest;
    const req = pedir(destino, { method: o.method ?? 'GET', headers }, (res) => {
      const trozos: Buffer[] = [];
      res.on('data', (t: Buffer) => trozos.push(t));
      res.on('end', () => resolver({ status: res.statusCode ?? 0, headers: res.headers, texto: Buffer.concat(trozos).toString('utf8') }));
      res.on('error', rechazar);
    });
    req.setTimeout(o.timeoutMs ?? 8000, () => req.destroy(new Error('Tiempo de espera agotado')));
    req.on('error', rechazar);
    if (cuerpo !== undefined) req.write(cuerpo);
    req.end();
  });
}

/** JSON del cuerpo, o `null` si no lo es (nunca lanza). */
export function jsonSeguro(r: Respuesta): unknown {
  try {
    return JSON.parse(r.texto);
  } catch {
    return null;
  }
}
