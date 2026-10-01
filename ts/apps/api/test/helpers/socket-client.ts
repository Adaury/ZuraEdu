import { createHmac } from 'node:crypto';

export interface Mensaje {
  event: string;
  channel?: string;
  data?: string;
}

export interface ConfigSocket {
  host: string;
  port: string;
  key: string;
  secret: string;
}

/** Cliente mínimo del protocolo Pusher para pruebas: conecta, se suscribe a un canal privado y acumula los mensajes. */
export class ClienteSocket {
  readonly recibidos: Mensaje[] = [];
  private ws!: WebSocket;

  constructor(private readonly cfg: ConfigSocket) {}

  async conectarYSuscribir(canal: string): Promise<void> {
    const { host, port, key, secret } = this.cfg;
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
    const auth = `${key}:${createHmac('sha256', secret).update(`${socketId}:${canal}`).digest('hex')}`;
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
