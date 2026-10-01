import { redirect } from 'next/navigation';
import { contextoActual } from '@/lib/sesion';

export const dynamic = 'force-dynamic';

/** Entrada: con sesión al listado, sin sesión al login. (El estado de la API está en /estado.) */
export default async function Inicio() {
  const ctx = await contextoActual();
  redirect(ctx.token ? '/estudiantes' : '/login');
}
