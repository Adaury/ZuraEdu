import { ASISTENCIA_ESTADOS, type HealthResponse } from '@zuraedu/shared';
import { config } from '@/lib/config';
import { jsonSeguro, peticion } from '@/lib/http';
import { contextoActual } from '@/lib/sesion';

// Siempre dinámico: el estado de la API cambia en cada petición.
export const dynamic = 'force-dynamic';
export const metadata = { title: 'Estado · ZuraEdu' };

async function obtenerSalud(host: string | null): Promise<HealthResponse | null> {
  try {
    const r = await peticion(`${config.apiUrl}/health`, { host });
    return r.status === 200 ? (jsonSeguro(r) as HealthResponse) : null;
  } catch {
    return null;
  }
}

export default async function Estado() {
  const salud = await obtenerSalud((await contextoActual()).host);

  return (
    <>
      <h1>Estado del sistema</h1>
      <section aria-label="Estado de la API" className="tarjeta">
        <strong>API:</strong>{' '}
        {salud ? (
          <span style={{ color: salud.status === 'ok' ? 'var(--ok)' : 'var(--error)' }}>
            {salud.status} (base de datos: {salud.checks.database}, redis: {salud.checks.redis ?? 'n/d'})
          </span>
        ) : (
          <span style={{ color: 'var(--error)' }}>sin conexión con la API</span>
        )}
      </section>
      <p className="pie">Tipos compartidos con la API: estados de asistencia = {ASISTENCIA_ESTADOS.join(', ')}.</p>
    </>
  );
}
