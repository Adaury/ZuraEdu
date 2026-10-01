import { createServer, Server } from 'node:http';
import type { AddressInfo } from 'node:net';
import type { INestApplication } from '@nestjs/common';
import { Test } from '@nestjs/testing';
import Redis from 'ioredis';
import { ClsService } from 'nestjs-cls';
import { RowDataPacket } from 'mysql2/promise';
import { DATABASE_URL, Fixtures, TenantCreado } from './helpers/fixtures';
import { ClienteSocket } from './helpers/socket-client';

// Debe fijarse ANTES de importar AppModule: ConfigModule lee process.env al compilar el módulo.
const REDIS_HOST = process.env.TEST_REDIS_HOST ?? '127.0.0.1';
const REDIS_PORT = process.env.TEST_REDIS_PORT ?? '6379';
const PREFIJO = 'tsprobe_database_tsprobe_cache_'; // REDIS_PREFIX + CACHE_PREFIX de la prueba
const RV = {
  id: process.env.TEST_REVERB_APP_ID,
  key: process.env.TEST_REVERB_APP_KEY,
  secret: process.env.TEST_REVERB_APP_SECRET,
  host: process.env.TEST_REVERB_HOST ?? '127.0.0.1',
  port: process.env.TEST_REVERB_PORT ?? '8080',
};
const hayReverb = Boolean(RV.id && RV.key && RV.secret);
process.env.DATABASE_URL = DATABASE_URL;
process.env.REDIS_HOST = REDIS_HOST;
process.env.REDIS_PORT = REDIS_PORT;
process.env.REDIS_CACHE_DB = '15'; // nunca la 1: ahí vive la caché real de Laravel
process.env.REDIS_PREFIX = 'tsprobe_database_';
process.env.CACHE_PREFIX = 'tsprobe_cache_';
if (hayReverb) {
  process.env.REVERB_APP_ID = RV.id;
  process.env.REVERB_APP_KEY = RV.key;
  process.env.REVERB_APP_SECRET = RV.secret;
  process.env.REVERB_HOST = RV.host;
  process.env.REVERB_PORT = RV.port;
}

import { AppModule } from '../src/app.module';
import { NotificacionesService } from '../src/notificaciones/notificaciones.service';

interface FilaNotif extends RowDataPacket {
  id: number;
  user_id: number;
  tipo: string;
  titulo: string;
  mensaje: string;
  datos: unknown;
  leida: number;
  tenant_id: number;
}

describe('API · notificaciones (e2e: MySQL, Redis y un Expo falso)', () => {
  const fx = new Fixtures();
  let colegioA: TenantCreado;
  let colegioB: TenantCreado;
  let app: INestApplication;
  let servicio: NotificacionesService;
  let cls: ClsService;
  let sonda: Redis;
  let expo: Server;
  let expoRecibido: Array<Array<{ to: string; title: string; body: string; data: Record<string, unknown>; sound: string }>> = [];
  let expoCaido = false;
  let idA: number; // usuario del colegio A (estudiante)
  let idB: number; // usuario del colegio B

  const enColegio = <T>(tenantId: number, fn: () => Promise<T>): Promise<T> =>
    cls.run(async () => {
      cls.set('tenantId', tenantId);
      cls.set('userId', 1);
      return fn();
    });
  const filas = async (tenantId: number): Promise<FilaNotif[]> =>
    (await fx.pool.query<FilaNotif[]>('select * from notificaciones where tenant_id = ? order by id', [tenantId]))[0];
  const ajuste = (tenantId: number, key: string, value: string) =>
    fx.pool.query('insert into system_settings (tenant_id, `key`, value, created_at, updated_at) values (?, ?, ?, now(), now())', [tenantId, key, value]);
  const limpiarEstado = async () => {
    await fx.pool.query('delete from notificaciones where tenant_id in (?)', [[colegioA.id, colegioB.id]]);
    await fx.pool.query('delete from system_settings where tenant_id in (?)', [[colegioA.id, colegioB.id]]);
    await fx.pool.query('update users set notif_push_prefs = null where id in (?)', [[idA, idB]]);
    expoRecibido = [];
    expoCaido = false;
  };

  beforeAll(async () => {
    expo = createServer((req, res) => {
      let cuerpo = '';
      req.on('data', (d) => (cuerpo += d));
      req.on('end', () => {
        if (expoCaido) return req.socket.destroy();
        expoRecibido.push(JSON.parse(cuerpo));
        res.end('{}');
      });
    });
    await new Promise<void>((ok) => expo.listen(0, '127.0.0.1', ok));
    process.env.EXPO_PUSH_URL = `http://127.0.0.1:${(expo.address() as AddressInfo).port}/push`;

    colegioA = await fx.crearTenant('nta');
    colegioB = await fx.crearTenant('ntb');
    idA = await fx.crearUsuario(colegioA.id, 'nt1', 'Estudiante');
    idB = await fx.crearUsuario(colegioB.id, 'nt2', 'Estudiante');
    await fx.pool.query("insert into device_tokens (user_id, token, platform, created_at, updated_at) values (?, 'ExponentPushToken[abc]', 'ios', now(), now()), (?, 'token-no-expo', 'android', now(), now())", [idA, idA]);
    await fx.pool.query("insert into device_tokens (user_id, token, platform, created_at, updated_at) values (?, 'ExponentPushToken[ajeno]', 'ios', now(), now())", [idB]);

    app = (await Test.createTestingModule({ imports: [AppModule] }).compile()).createNestApplication();
    await app.listen(0);
    servicio = app.get(NotificacionesService);
    cls = app.get(ClsService);
    sonda = new Redis({ host: REDIS_HOST, port: Number(REDIS_PORT), db: 15, lazyConnect: true, maxRetriesPerRequest: 1 });
    await sonda.connect();
  }, 60000);

  afterEach(limpiarEstado);
  afterAll(async () => {
    await servicio?.drenar();
    await app?.close();
    await new Promise<void>((ok) => expo.close(() => ok()));
    sonda?.disconnect();
    await fx.limpiar();
  });

  it('crea la fila, borra la caché de Laravel y manda push SOLO a tokens de Expo (en segundo plano)', async () => {
    await sonda.set(`${PREFIJO}user_${idA}_notif_unread`, '3', 'EX', 120);

    const r = await enColegio(colegioA.id, () => servicio.enviar(idA, 'ausencia', 'Ausencia registrada', 'Se registró una ausencia.', { estudiante_id: 5, fecha: '2026-10-01' }));
    await servicio.drenar();

    expect(r).toEqual({ creada: true, segundoPlano: true });
    const f = await filas(colegioA.id);
    expect(f).toHaveLength(1);
    expect(f[0]).toMatchObject({ user_id: idA, tipo: 'ausencia', titulo: 'Ausencia registrada', leida: 0, tenant_id: colegioA.id });
    expect(typeof f[0].datos === 'string' ? JSON.parse(f[0].datos) : f[0].datos).toEqual({ estudiante_id: 5, fecha: '2026-10-01' });
    expect(await sonda.exists(`${PREFIJO}user_${idA}_notif_unread`)).toBe(0); // la campanita de PHP se refresca

    expect(expoRecibido).toHaveLength(1);
    expect(expoRecibido[0]).toEqual([
      { to: 'ExponentPushToken[abc]', title: 'Ausencia registrada', body: 'Se registró una ausencia.', data: { estudiante_id: 5, fecha: '2026-10-01', tipo: 'ausencia' }, sound: 'default' },
    ]); // el token "token-no-expo" no se envía
  });

  it('sin datos se guarda NULL (como `$datos ?: null` en PHP)', async () => {
    await enColegio(colegioA.id, () => servicio.enviar(idA, 'general', 'Hola', 'Mensaje'));
    expect((await filas(colegioA.id))[0].datos).toBeNull();
  });

  it('si la institución apaga el in-app de la categoría: no hay fila, ni push, ni nada', async () => {
    await ajuste(colegioA.id, 'notif_inapp_alertas', '0');
    const r = await enColegio(colegioA.id, () => servicio.enviar(idA, 'ausencia', 't', 'm'));
    await servicio.drenar();
    expect(r).toEqual({ creada: false, segundoPlano: false });
    expect(await filas(colegioA.id)).toHaveLength(0);
    expect(expoRecibido).toHaveLength(0);
  });

  it('la categoría "sistema" no se puede apagar (inapp_bloqueado), aunque el setting diga 0', async () => {
    await ajuste(colegioA.id, 'notif_inapp_sistema', '0');
    const r = await enColegio(colegioA.id, () => servicio.enviar(idA, 'general', 't', 'm'));
    expect(r.creada).toBe(true);
    expect(await filas(colegioA.id)).toHaveLength(1);
  });

  it('un tipo desconocido cae en "sistema" y tampoco se puede apagar', async () => {
    await ajuste(colegioA.id, 'notif_inapp_sistema', '0');
    const r = await enColegio(colegioA.id, () => servicio.enviar(idA, 'tipo_inventado', 't', 'm'));
    expect(r.creada).toBe(true);
  });

  it('el setting de un colegio no afecta a otro', async () => {
    await ajuste(colegioB.id, 'notif_inapp_alertas', '0');
    const r = await enColegio(colegioA.id, () => servicio.enviar(idA, 'ausencia', 't', 'm'));
    expect(r.creada).toBe(true);
  });

  it('push apagado por la institución: hay fila pero no push', async () => {
    await ajuste(colegioA.id, 'notif_push_alertas', '0');
    const r = await enColegio(colegioA.id, () => servicio.enviar(idA, 'ausencia', 't', 'm'));
    await servicio.drenar();
    expect(r.creada).toBe(true);
    expect(await filas(colegioA.id)).toHaveLength(1);
    expect(expoRecibido).toHaveLength(0);
  });

  it('push apagado por el usuario en UNA categoría: no recibe esa, sí recibe las demás', async () => {
    await fx.pool.query('update users set notif_push_prefs = ? where id = ?', [JSON.stringify({ alertas: false }), idA]);
    await enColegio(colegioA.id, () => servicio.enviar(idA, 'ausencia', 't', 'm')); // categoría alertas
    await enColegio(colegioA.id, () => servicio.enviar(idA, 'pago', 't2', 'm2')); // categoría pagos
    await servicio.drenar();
    expect(await filas(colegioA.id)).toHaveLength(2);
    expect(expoRecibido).toHaveLength(1);
    expect(expoRecibido[0][0].title).toBe('t2');
  });

  it('un destinatario de OTRO colegio se rechaza: no hay fila, ni se toca su token', async () => {
    const r = await enColegio(colegioA.id, () => servicio.enviar(idB, 'ausencia', 't', 'm'));
    await servicio.drenar();
    expect(r).toEqual({ creada: false, segundoPlano: false });
    expect(await filas(colegioA.id)).toHaveLength(0);
    expect(await filas(colegioB.id)).toHaveLength(0);
    expect(expoRecibido).toHaveLength(0);
  });

  it('un usuario inexistente se rechaza sin lanzar', async () => {
    const r = await enColegio(colegioA.id, () => servicio.enviar(999_999_999, 'ausencia', 't', 'm'));
    expect(r.creada).toBe(false);
  });

  it('si Expo no responde, la fila existe y no se lanza nada', async () => {
    expoCaido = true;
    const r = await enColegio(colegioA.id, () => servicio.enviar(idA, 'ausencia', 't', 'm'));
    await servicio.drenar();
    expect(r.creada).toBe(true);
    expect(await filas(colegioA.id)).toHaveLength(1);
  });

  it('sin tenant en el contexto lanza (nunca "adivina" un colegio)', async () => {
    await expect(cls.run(() => servicio.enviar(idA, 'ausencia', 't', 'm'))).rejects.toThrow(/tenant/i);
  });

  (hayReverb ? it : it.skip)('emite notification.created al canal private-user.{id} con icono, url y hora', async () => {
    const c = new ClienteSocket({ host: RV.host, port: RV.port, key: RV.key!, secret: RV.secret! });
    try {
      await c.conectarYSuscribir(`private-user.${idA}`);
      await enColegio(colegioA.id, () => servicio.enviar(idA, 'ausencia', 'Ausencia registrada', 'Texto', { url: '/portal/estudiante/asistencia' }));
      const m = await c.esperar((x) => x.event === 'notification.created', 5000);
      expect(m.channel).toBe(`private-user.${idA}`);
      const d = JSON.parse(m.data!);
      expect(d).toMatchObject({ tipo: 'ausencia', titulo: 'Ausencia registrada', mensaje: 'Texto', url: '/portal/estudiante/asistencia', icono: 'bi-calendar-x' });
      expect(d.tiempo).toMatch(/^\d{2}:\d{2}$/);
    } finally {
      c.cerrar();
    }
  });
});
