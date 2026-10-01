import { createServer, IncomingMessage, Server } from 'node:http';
import type { AddressInfo } from 'node:net';
import { loadEnv } from '../config/env';
import { canalPrivado, firmarPeticion, ReverbPublisher } from './reverb.publisher';

describe('firmarPeticion', () => {
  // Vector del ejemplo de la documentación oficial de Pusher (REST API, "Authentication").
  const cred = { appId: '3', appKey: '278d425bdf160c739803', appSecret: '7ad3773142a6692b25b8' };
  const cuerpo = '{"name":"foo","channels":["project-3"],"data":"{\\"some\\":\\"data\\"}"}';

  it('reproduce el vector oficial de Pusher (md5 del cuerpo y firma HMAC-SHA256)', () => {
    const p = firmarPeticion(cred, '/apps/3/events', cuerpo, 1353088179);
    expect(p.body_md5).toBe('ec365a775a4cd0599faeb73354201b6f');
    expect(p.auth_signature).toBe('da454824c97ba181a32ccc17a72625ba02771f50b50e1e7430e47a1f3f457e6c');
    expect(p.auth_version).toBe('1.0');
  });

  it('la firma depende del secreto, del cuerpo, de la ruta y de la hora', () => {
    const base = firmarPeticion(cred, '/apps/3/events', cuerpo, 1353088179).auth_signature;
    expect(firmarPeticion({ ...cred, appSecret: 'otro' }, '/apps/3/events', cuerpo, 1353088179).auth_signature).not.toBe(base);
    expect(firmarPeticion(cred, '/apps/3/events', cuerpo + ' ', 1353088179).auth_signature).not.toBe(base);
    expect(firmarPeticion(cred, '/apps/4/events', cuerpo, 1353088179).auth_signature).not.toBe(base);
    expect(firmarPeticion(cred, '/apps/3/events', cuerpo, 1353088180).auth_signature).not.toBe(base);
  });
});

describe('canalPrivado', () => {
  it('antepone private- como Laravel (PrivateChannel("tenant.1") -> "private-tenant.1")', () => {
    expect(canalPrivado('tenant.1')).toBe('private-tenant.1');
    expect(canalPrivado('docente.7')).toBe('private-docente.7');
  });
});

describe('ReverbPublisher (contra un servidor HTTP de prueba)', () => {
  let servidor: Server;
  let puerto: number;
  let recibida: { url: string; cuerpo: string } | null = null;
  let respuesta = 200;

  beforeAll(async () => {
    servidor = createServer((req: IncomingMessage, res) => {
      let cuerpo = '';
      req.on('data', (d) => (cuerpo += d));
      req.on('end', () => {
        recibida = { url: req.url ?? '', cuerpo };
        res.statusCode = respuesta;
        res.end('{}');
      });
    });
    await new Promise<void>((ok) => servidor.listen(0, '127.0.0.1', ok));
    puerto = (servidor.address() as AddressInfo).port;
  });
  afterAll(() => new Promise<void>((ok) => servidor.close(() => ok())));
  beforeEach(() => {
    recibida = null;
    respuesta = 200;
  });

  const publicador = (extra: Record<string, string> = {}) =>
    new ReverbPublisher(
      loadEnv({
        DATABASE_URL: 'mysql://u@h/db',
        REVERB_APP_ID: '374478',
        REVERB_APP_KEY: 'clave',
        REVERB_APP_SECRET: 'secreto',
        REVERB_HOST: '127.0.0.1',
        REVERB_PORT: String(puerto),
        ...extra,
      }),
    );

  it('envía POST /apps/{id}/events con la firma que corresponde al cuerpo enviado y los canales con prefijo', async () => {
    await publicador().publicar(['tenant.5'], 'dashboard.updated', { tipo: 'nueva_matricula', datos: { grupo_id: 9 } }, () => 1_700_000_000_000);

    const u = new URL(recibida!.url, 'http://x');
    expect(u.pathname).toBe('/apps/374478/events');
    const cuerpo = JSON.parse(recibida!.cuerpo);
    expect(cuerpo.name).toBe('dashboard.updated');
    expect(cuerpo.channels).toEqual(['private-tenant.5']);
    expect(typeof cuerpo.data).toBe('string'); // protocolo Pusher: data es TEXTO JSON
    expect(JSON.parse(cuerpo.data)).toEqual({ tipo: 'nueva_matricula', datos: { grupo_id: 9 } });

    const esperada = firmarPeticion({ appId: '374478', appKey: 'clave', appSecret: 'secreto' }, '/apps/374478/events', recibida!.cuerpo, 1_700_000_000);
    expect(u.searchParams.get('auth_key')).toBe('clave');
    expect(u.searchParams.get('auth_timestamp')).toBe('1700000000');
    expect(u.searchParams.get('body_md5')).toBe(esperada.body_md5);
    expect(u.searchParams.get('auth_signature')).toBe(esperada.auth_signature);
  });

  it('si Reverb responde con error, publicar lanza y publicarMejorEsfuerzo devuelve false sin lanzar', async () => {
    respuesta = 401;
    await expect(publicador().publicar(['tenant.1'], 'x', {})).rejects.toThrow(/401/);
    await expect(publicador().publicarMejorEsfuerzo(['tenant.1'], 'x', {})).resolves.toBe(false);
  });

  it('sin credenciales no está habilitado: publicar lanza y mejor esfuerzo devuelve false sin llamar a la red', async () => {
    const sin = new ReverbPublisher(loadEnv({ DATABASE_URL: 'mysql://u@h/db' }));
    expect(sin.habilitado).toBe(false);
    await expect(sin.publicar(['tenant.1'], 'x', {})).rejects.toThrow(/no está configurado/);
    await expect(sin.publicarMejorEsfuerzo(['tenant.1'], 'x', {})).resolves.toBe(false);
    expect(recibida).toBeNull();
  });
});

describe('loadEnv · Reverb', () => {
  const base = { DATABASE_URL: 'mysql://u@h/db' };
  it('es opcional y tiene valores por defecto como Laravel', () => {
    const env = loadEnv(base);
    expect(env.REVERB_APP_ID).toBeUndefined();
    expect(env.REVERB_HOST).toBe('localhost');
    expect(env.REVERB_PORT).toBe(8080);
    expect(env.REVERB_SCHEME).toBe('http');
  });
  it('exige las tres credenciales juntas', () => {
    expect(() => loadEnv({ ...base, REVERB_APP_ID: '1' })).toThrow(/REVERB_APP_KEY/);
    expect(() => loadEnv({ ...base, REVERB_APP_ID: '1', REVERB_APP_KEY: 'k' })).toThrow(/REVERB_APP_SECRET/);
    expect(loadEnv({ ...base, REVERB_APP_ID: '1', REVERB_APP_KEY: 'k', REVERB_APP_SECRET: 's' }).REVERB_APP_ID).toBe('1');
  });
  it('rechaza un esquema distinto de http/https', () => {
    expect(() => loadEnv({ ...base, REVERB_SCHEME: 'ws' })).toThrow(/REVERB_SCHEME/);
  });
});
