import { ESTUDIANTE_ESTADOS } from '@zuraedu/shared';
import { actualizarEstudiante } from '@/lib/api';
import { contextoDe, redirigir, rechazarOtroOrigen } from '@/lib/respuestas';
import { camposInvalidos, codigoPorEstado, idValido } from '@/lib/validacion';

export const dynamic = 'force-dynamic';

/**
 * Guarda los cambios de un estudiante. El navegador solo manda datos de formulario: aquí se vuelven a validar y se envían a la API
 * (que es la que decide: reglas de negocio, permisos, colegio y auditoría). Los errores vuelven como CÓDIGOS fijos en la URL, nunca
 * como texto libre.
 */
export async function POST(request: Request, { params }: { params: Promise<{ id: string }> }): Promise<Response> {
  const rechazo = rechazarOtroOrigen(request);
  if (rechazo) return rechazo;

  const id = idValido((await params).id);
  if (id === null) return new Response('No encontrado.', { status: 404 });
  const volver = (consulta: string) => redirigir(`/estudiantes/${id}/editar?${consulta}`);

  const ctx = contextoDe(request);
  if (!ctx.token) return redirigir('/sesion/expirada');

  const form = await request.formData().catch(() => null);
  const texto = (campo: string, max: number) => String(form?.get(campo) ?? '').trim().slice(0, max);
  const sexo = texto('sexo', 1);
  const estado = texto('estado', 20);
  if (!['M', 'F'].includes(sexo)) return volver('e=invalido&c=sexo');
  if (!(ESTUDIANTE_ESTADOS as readonly string[]).includes(estado)) return volver('e=invalido&c=estado');

  const cedula = texto('cedula', 50);
  const r = await actualizarEstudiante(ctx, id, {
    nombres: texto('nombres', 100),
    apellidos: texto('apellidos', 100),
    cedula: cedula === '' ? null : cedula, // vaciar la cédula la deja en NULL
    sexo,
    estado,
  });

  if (r.ok) return volver('ok=1');
  if (r.status === 401) return redirigir('/sesion/expirada');
  const campos = r.status === 400 ? camposInvalidos(r.cuerpo) : [];
  return volver(`e=${codigoPorEstado(r.status)}${campos.length ? `&c=${campos.join(',')}` : ''}`);
}
