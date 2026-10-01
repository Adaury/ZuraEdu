import { TtlCache } from './ttl-cache';

function crear(ttlMs = 1000, maxEntries = 100) {
  let t = 1_000_000;
  const cache = new TtlCache<string>({ ttlMs, maxEntries, now: () => t });
  return { cache, avanzar: (ms: number) => (t += ms) };
}

describe('TtlCache', () => {
  it('guarda y devuelve un valor dentro de su vida', () => {
    const { cache, avanzar } = crear();
    cache.set('a', 'uno');
    avanzar(999);
    expect(cache.get('a')).toBe('uno');
  });

  it('vence exactamente al cumplirse el TTL', () => {
    const { cache, avanzar } = crear();
    cache.set('a', 'uno');
    avanzar(1000);
    expect(cache.get('a')).toBeUndefined();
    expect(cache.size).toBe(0); // se limpia al detectar el vencimiento
  });

  it('el TTL de una entrada nunca supera el del caché', () => {
    const { cache, avanzar } = crear(1000);
    cache.set('a', 'uno', 60_000); // pide más de lo permitido
    avanzar(1000);
    expect(cache.get('a')).toBeUndefined();
  });

  it('una entrada puede vivir MENOS que el TTL (token con expires_at próximo)', () => {
    const { cache, avanzar } = crear(1000);
    cache.set('a', 'uno', 200);
    avanzar(200);
    expect(cache.get('a')).toBeUndefined();
  });

  it('un TTL por entrada de 0 o negativo no cachea', () => {
    const { cache } = crear();
    cache.set('a', 'uno', 0);
    cache.set('b', 'dos', -5);
    expect(cache.size).toBe(0);
  });

  describe('tope de tamaño (LRU)', () => {
    it('descarta la entrada menos usada al superar maxEntries', () => {
      const { cache } = crear(1000, 2);
      cache.set('a', '1');
      cache.set('b', '2');
      cache.set('c', '3');
      expect(cache.get('a')).toBeUndefined();
      expect(cache.get('b')).toBe('2');
      expect(cache.get('c')).toBe('3');
    });

    it('leer una entrada la protege del descarte', () => {
      const { cache } = crear(1000, 2);
      cache.set('a', '1');
      cache.set('b', '2');
      cache.get('a'); // a pasa a ser la más reciente
      cache.set('c', '3'); // sale b
      expect(cache.get('a')).toBe('1');
      expect(cache.get('b')).toBeUndefined();
    });

    it('nunca supera el tope', () => {
      const { cache } = crear(1000, 5);
      for (let i = 0; i < 100; i++) cache.set(`k${i}`, 'x');
      expect(cache.size).toBe(5);
    });
  });

  describe('desactivada (ttl 0)', () => {
    it('no guarda nada y siempre ejecuta el cargador', async () => {
      const { cache } = crear(0);
      const cargar = jest.fn(async () => ({ value: 'v' }));
      await cache.getOrLoad('a', cargar);
      await cache.getOrLoad('a', cargar);
      expect(cargar).toHaveBeenCalledTimes(2);
      expect(cache.size).toBe(0);
      expect(cache.enabled).toBe(false);
    });
  });

  describe('getOrLoad', () => {
    it('carga una vez y luego sirve desde la caché', async () => {
      const { cache } = crear();
      const cargar = jest.fn(async () => ({ value: 'v' }));
      expect(await cache.getOrLoad('a', cargar)).toBe('v');
      expect(await cache.getOrLoad('a', cargar)).toBe('v');
      expect(cargar).toHaveBeenCalledTimes(1);
      expect(cache.hits).toBe(1);
      expect(cache.misses).toBe(1);
    });

    it('vuelve a cargar cuando la entrada venció', async () => {
      const { cache, avanzar } = crear();
      const cargar = jest.fn(async () => ({ value: 'v' }));
      await cache.getOrLoad('a', cargar);
      avanzar(1000);
      await cache.getOrLoad('a', cargar);
      expect(cargar).toHaveBeenCalledTimes(2);
    });

    it('respeta el ttlMs que devuelve el cargador', async () => {
      const { cache, avanzar } = crear(1000);
      await cache.getOrLoad('a', async () => ({ value: 'v', ttlMs: 100 }));
      avanzar(100);
      expect(cache.get('a')).toBeUndefined();
    });

    it('NO cachea resultados negativos (undefined)', async () => {
      const { cache } = crear();
      const cargar = jest.fn(async () => undefined);
      expect(await cache.getOrLoad('a', cargar)).toBeUndefined();
      expect(await cache.getOrLoad('a', cargar)).toBeUndefined();
      expect(cargar).toHaveBeenCalledTimes(2);
      expect(cache.size).toBe(0);
    });

    it('NO cachea cuando el cargador lanza, y propaga el error', async () => {
      const { cache } = crear();
      const cargar = jest.fn().mockRejectedValue(new Error('boom'));
      await expect(cache.getOrLoad('a', cargar)).rejects.toThrow('boom');
      await expect(cache.getOrLoad('a', cargar)).rejects.toThrow('boom');
      expect(cargar).toHaveBeenCalledTimes(2);
      expect(cache.size).toBe(0);
    });

    it('single-flight: peticiones concurrentes comparten una sola carga', async () => {
      const { cache } = crear();
      let resolver!: (v: { value: string }) => void;
      const cargar = jest.fn(() => new Promise<{ value: string }>((r) => (resolver = r)));

      const a = cache.getOrLoad('a', cargar);
      const b = cache.getOrLoad('a', cargar);
      const c = cache.getOrLoad('a', cargar);
      resolver({ value: 'v' });

      expect(await Promise.all([a, b, c])).toEqual(['v', 'v', 'v']);
      expect(cargar).toHaveBeenCalledTimes(1);
    });

    it('single-flight: un fallo llega a todos los que esperaban y no deja la clave bloqueada', async () => {
      const { cache } = crear();
      let rechazar!: (e: Error) => void;
      const lenta = jest.fn(() => new Promise<{ value: string }>((_, rej) => (rechazar = rej)));

      const a = cache.getOrLoad('a', lenta);
      const b = cache.getOrLoad('a', lenta);
      rechazar(new Error('fallo'));
      await expect(a).rejects.toThrow('fallo');
      await expect(b).rejects.toThrow('fallo');

      // La clave quedó libre: un nuevo intento vuelve a cargar con normalidad.
      expect(await cache.getOrLoad('a', async () => ({ value: 'ok' }))).toBe('ok');
    });

    it('claves distintas no se mezclan', async () => {
      const { cache } = crear();
      await cache.getOrLoad('a', async () => ({ value: '1' }));
      await cache.getOrLoad('b', async () => ({ value: '2' }));
      expect(cache.get('a')).toBe('1');
      expect(cache.get('b')).toBe('2');
    });
  });

  it('delete y clear invalidan', () => {
    const { cache } = crear();
    cache.set('a', '1');
    cache.set('b', '2');
    cache.delete('a');
    expect(cache.get('a')).toBeUndefined();
    cache.clear();
    expect(cache.size).toBe(0);
  });
});
