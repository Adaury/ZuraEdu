import { RateLimiter } from './rate-limiter';

describe('RateLimiter (memoria, reloj controlable)', () => {
  let ahora: number;
  let limiter: RateLimiter;
  beforeEach(() => {
    ahora = 1_000_000;
    limiter = new RateLimiter(null, () => ahora);
  });

  it('permite hasta el límite y rechaza la siguiente, con el restante bien contado', async () => {
    const r1 = await limiter.registrar('u:1:1', 3);
    const r2 = await limiter.registrar('u:1:1', 3);
    const r3 = await limiter.registrar('u:1:1', 3);
    const r4 = await limiter.registrar('u:1:1', 3);
    expect([r1.permitido, r2.permitido, r3.permitido, r4.permitido]).toEqual([true, true, true, false]);
    expect([r1.restante, r2.restante, r3.restante, r4.restante]).toEqual([2, 1, 0, 0]);
    expect(r4.limite).toBe(3);
  });

  it('cada clave tiene su propio contador (un usuario no gasta el cupo de otro, ni de otro colegio)', async () => {
    for (let i = 0; i < 3; i++) await limiter.registrar('u:1:1', 3);
    expect((await limiter.registrar('u:1:1', 3)).permitido).toBe(false);
    expect((await limiter.registrar('u:1:2', 3)).permitido).toBe(true); // otro usuario, mismo colegio
    expect((await limiter.registrar('u:2:1', 3)).permitido).toBe(true); // mismo id de usuario, otro colegio
  });

  it('la ventana se reinicia al vencer (ventana fija, como Limit::perMinute de Laravel)', async () => {
    for (let i = 0; i < 4; i++) await limiter.registrar('k', 3);
    ahora += 59_000;
    expect((await limiter.registrar('k', 3)).permitido).toBe(false); // aún dentro del minuto
    ahora += 1_000;
    const r = await limiter.registrar('k', 3);
    expect(r.permitido).toBe(true); // venció: ventana nueva
    expect(r.restante).toBe(2);
  });

  it('reiniciaEnSegundos baja con el tiempo y nunca es menor que 1', async () => {
    await limiter.registrar('k', 1);
    ahora += 20_000;
    expect((await limiter.registrar('k', 1)).reiniciaEnSegundos).toBe(40);
    ahora += 39_900;
    expect((await limiter.registrar('k', 1)).reiniciaEnSegundos).toBe(1);
  });

  it('purga las ventanas vencidas para que el mapa no crezca sin límite', async () => {
    for (let i = 0; i < 10_001; i++) await limiter.registrar(`u:${i}`, 5);
    ahora += 61_000;
    await limiter.registrar('nueva', 5); // al crear una ventana con el mapa lleno se purgan las vencidas
    // @ts-expect-error acceso interno solo para la prueba
    expect(limiter.memoria.size).toBeLessThan(10);
  });
});
