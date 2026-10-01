import { ASISTENCIA_ESTADOS, type HealthResponse } from '@zuraedu/shared';

// Siempre dinámico: el estado de la API cambia en cada petición.
export const dynamic = 'force-dynamic';

async function obtenerSalud(): Promise<HealthResponse | null> {
  const base = process.env.API_URL ?? 'http://127.0.0.1:3100';
  try {
    const res = await fetch(`${base}/health`, { cache: 'no-store' });
    return res.ok ? ((await res.json()) as HealthResponse) : null;
  } catch {
    return null;
  }
}

export default async function Inicio() {
  const salud = await obtenerSalud();

  return (
    <main style={{ maxWidth: 640 }}>
      <h1 style={{ marginTop: 0 }}>ZuraEdu — esqueleto TypeScript</h1>
      <p>
        Este es el punto de partida de la migración gradual desde Laravel. La web consume la API NestJS, que lee la
        misma base MySQL.
      </p>

      <section aria-label="Estado de la API" style={{ padding: '1rem', background: '#fff', borderRadius: 8, border: '1px solid #e2e8f0' }}>
        <strong>API:</strong>{' '}
        {salud ? (
          <span style={{ color: salud.status === 'ok' ? '#15803d' : '#b45309' }}>
            {salud.status} (base de datos: {salud.checks.database})
          </span>
        ) : (
          <span style={{ color: '#b91c1c' }}>sin conexión — arranca la API con «npm run dev:api»</span>
        )}
      </section>

      <p style={{ fontSize: '.85rem', color: '#475569' }}>
        Tipos compartidos con la API: estados de asistencia = {ASISTENCIA_ESTADOS.join(', ')}.
      </p>
    </main>
  );
}
