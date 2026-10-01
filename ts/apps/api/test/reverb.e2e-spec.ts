import { createHmac } from 'node:crypto';
import { loadEnv } from '../src/config/env';
import { ReverbPublisher } from '../src/realtime/reverb.publisher';

/**
 * Prueba contra un Reverb REAL: un cliente WebSocket (protocolo Pusher) se suscribe a un canal privado y la API TypeScript
 * publica por HTTP; debe llegar el evento con el nombre y el contenido que espera el frontend de Laravel.
 *
 * Se omite si no hay un Reverb de pruebas. Variables: TEST_REVERB_APP_ID, TEST_REVERB_APP_KEY, TEST_REVERB_APP_SECRET y,
 * opcionalmente, TEST_REVERB_HOST (127.0.0.1) y TEST_REVERB_PORT (8080). Localmente:
 *   php artisan reverb:start --host=127.0.0.1 --port=8080     (con las REVERB_* del .env de Laravel)
 * No pongas aquí secretos reales: en el CI se usan credenciales inventadas para el Reverb efímero del job.
 */
const id = process.env.TEST_REVERB_APP_ID;
const key = process.env.TEST_REVERB_APP_KEY;
const secret = process.env.TEST_REVERB_APP_SECRET;
const host = process.env.TEST_REVERB_HOST ?? '127.0.0.1';
const port = process.env.TEST_REVERB_PORT ?? '8080';
const hay = Boolean(id && key && secret);
const describeSiHay = hay ? describe : describe.skip;
if (!hay) console.warn('Prueba de Reverb OMITIDA: faltan TEST_REVERB_APP_ID/KEY/SECRET.');

interface Mensaje {
  event: string;
  channel?: string;
  data?: string;
}

/** Cliente mínimo del protocolo Pusher: conecta, se suscribe a un canal privado y acumula los mensajes que llegan. */
class ClienteSocket {
  readonly recibidos: Mensaje[] = [];
  private ws!: WebSocket;

  async conectarYSuscribir(canal: string): Promise<void> {
    this.ws = new WebSocket(`ws://${host}:${port}/app/${key}?protocol=7&client=prueba&version=1.0`);
    const abrir = new Promise<string>((ok, mal) => {
      const t = setTimeout(() => mal(new Error('Reverb no respondió al conectar')), 5000);
      this.ws.onmessage = (e) => {
        const m = JSON.parse(String(e.data)) as Mensaje;
        this.recibidos.push(m);
        if (m.event === 'pusher:connection_established') {
          clearTimeout(t);
          ok(JSON.parse(m.data!).socket_id as string);
        }
      };
      this.ws.onerror = () => mal(new Error('Error de WebSocket'));
    });
    const socketId = await abrir;
    // Autorización de canal privado: "clave:HMAC-SHA256(secreto, socket_id:canal)". En producción la firma Laravel en /broadcasting/auth.
    const auth = `${key}:${createHmac('sha256', secret!).update(`${socketId}:${canal}`).digest('hex')}`;
    this.ws.send(JSON.stringify({ event: 'pusher:subscribe', data: { channel: canal, auth } }));
    await this.esperar((m) => m.event === 'pusher_internal:subscription_succeeded' && m.channel === canal, 5000);
  }

  esperar(cond: (m: Mensaje) => boolean, ms: number): Promise<Mensaje> {
    return new Promise((ok, mal) => {
      const previo = this.recibidos.find(cond);
      if (previo) return ok(previo);
      const inicio = Date.now();
      const iv = setInterval(() => {
        const m = this.recibidos.find(cond);
        if (m) {
          clearInterval(iv);
          ok(m);
        } else if (Date.now() - inicio > ms) {
          clearInterval(iv);
          mal(new Error('No llegó el mensaje esperado'));
        }
      }, 25);
    });
  }

  cerrar(): void {
    this.ws?.close();
  }
}

describeSiHay('API · Reverb (e2e, Reverb real)', () => {
  const publicador = new ReverbPublisher(
    loadEnv({
      DATABASE_URL: 'mysql://u@h/db',
      REVERB_APP_ID: id,
      REVERB_APP_KEY: key,
      REVERB_APP_SECRET: secret,
      REVERB_HOST: host,
      REVERB_PORT: port,
    }),
  );
  const sufijo = Math.floor(Math.random() * 1_000_000); // canal propio: no choca con otros colegios ni ejecuciones
  const clientes: ClienteSocket[] = [];
  afterAll(() => clientes.forEach((c) => c.cerrar()));

  it('un evento publicado desde TypeScript llega al suscriptor del canal privado con el nombre y los datos de Laravel', async () => {
    const c = new ClienteSocket();
    clientes.push(c);
    await c.conectarYSuscribir(`private-tenant.${sufijo}`);

    await publicador.publicar([`tenant.${sufijo}`], 'dashboard.updated', { tipo: 'nueva_matricula', datos: { grupo_id: 9 } });

    const m = await c.esperar((x) => x.event === 'dashboard.updated', 5000);
    expect(m.channel).toBe(`private-tenant.${sufijo}`);
    expect(JSON.parse(m.data!)).toEqual({ tipo: 'nueva_matricula', datos: { grupo_id: 9 } });
  });

  it('aislamiento: un evento de otro colegio NO llega a este suscriptor', async () => {
    const c = new ClienteSocket();
    clientes.push(c);
    await c.conectarYSuscribir(`private-tenant.${sufijo + 1}`);

    await publicador.publicar([`tenant.${sufijo + 2}`], 'dashboard.updated', { tipo: 'ajeno' });
    await new Promise((r) => setTimeout(r, 600));

    expect(c.recibidos.some((x) => x.event === 'dashboard.updated')).toBe(false);
  });

  it('con un secreto equivocado Reverb rechaza la publicación (401) y mejor esfuerzo devuelve false', async () => {
    const mal = new ReverbPublisher(
      loadEnv({ DATABASE_URL: 'mysql://u@h/db', REVERB_APP_ID: id, REVERB_APP_KEY: key, REVERB_APP_SECRET: 'incorrecto', REVERB_HOST: host, REVERB_PORT: port }),
    );
    await expect(mal.publicar([`tenant.${sufijo}`], 'x', {})).rejects.toThrow(/401/);
    await expect(mal.publicarMejorEsfuerzo([`tenant.${sufijo}`], 'x', {})).resolves.toBe(false);
  });
});
