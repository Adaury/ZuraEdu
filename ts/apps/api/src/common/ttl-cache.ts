export interface TtlCacheOptions {
  /** Vida por defecto de una entrada, en ms. 0 = caché desactivada (siempre se carga). */
  ttlMs: number;
  /** Tope de entradas; al superarlo se descarta la menos usada recientemente. */
  maxEntries: number;
  /** Reloj inyectable para pruebas. */
  now?: () => number;
}

export interface Loaded<V> {
  value: V;
  /** Vida máxima de ESTA entrada (nunca supera la del caché). Útil p. ej. para tokens con expires_at. */
  ttlMs?: number;
}

interface Entry<V> {
  value: V;
  expiresAt: number;
}

/**
 * Caché en memoria con vencimiento (TTL), tope de tamaño (LRU) y "single-flight".
 *
 *  - Solo se guardan resultados POSITIVOS: si el cargador devuelve `undefined` o lanza, no se
 *    cachea nada (un token inválido nunca queda registrado).
 *  - Single-flight: N peticiones concurrentes por la misma clave ejecutan el cargador UNA vez.
 *  - Es por proceso: con varias instancias de la API cada una tiene la suya. La inconsistencia
 *    máxima entre instancias y frente a Laravel está acotada por el TTL.
 */
export class TtlCache<V> {
  private readonly entradas = new Map<string, Entry<V>>();
  private readonly enVuelo = new Map<string, Promise<V | undefined>>();
  hits = 0;
  misses = 0;

  constructor(private readonly opciones: TtlCacheOptions) {}

  get enabled(): boolean {
    return this.opciones.ttlMs > 0;
  }

  get size(): number {
    return this.entradas.size;
  }

  private ahora(): number {
    return (this.opciones.now ?? Date.now)();
  }

  get(clave: string): V | undefined {
    const e = this.entradas.get(clave);
    if (!e) return undefined;
    if (e.expiresAt <= this.ahora()) {
      this.entradas.delete(clave);
      return undefined;
    }
    // LRU: lo usado recientemente pasa al final.
    this.entradas.delete(clave);
    this.entradas.set(clave, e);
    return e.value;
  }

  set(clave: string, value: V, ttlMs?: number): void {
    const vida = Math.min(ttlMs ?? this.opciones.ttlMs, this.opciones.ttlMs);
    if (!this.enabled || vida <= 0) return;

    this.entradas.delete(clave);
    this.entradas.set(clave, { value, expiresAt: this.ahora() + vida });
    while (this.entradas.size > this.opciones.maxEntries) {
      const masAntigua = this.entradas.keys().next().value as string;
      this.entradas.delete(masAntigua);
    }
  }

  async getOrLoad(clave: string, cargar: () => Promise<Loaded<V> | undefined>): Promise<V | undefined> {
    if (!this.enabled) {
      return (await cargar())?.value;
    }

    const guardado = this.get(clave);
    if (guardado !== undefined) {
      this.hits++;
      return guardado;
    }
    this.misses++;

    const pendiente = this.enVuelo.get(clave);
    if (pendiente) return pendiente;

    const promesa = (async () => {
      try {
        const cargado = await cargar();
        if (cargado) this.set(clave, cargado.value, cargado.ttlMs);
        return cargado?.value;
      } finally {
        this.enVuelo.delete(clave);
      }
    })();
    this.enVuelo.set(clave, promesa);
    return promesa;
  }

  delete(clave: string): void {
    this.entradas.delete(clave);
  }

  clear(): void {
    this.entradas.clear();
  }
}
