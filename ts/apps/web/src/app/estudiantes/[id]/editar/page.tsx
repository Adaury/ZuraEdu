import { ESTUDIANTE_ESTADOS } from '@zuraedu/shared';
import { notFound, redirect } from 'next/navigation';
import { obtenerEstudiante } from '@/lib/api';
import { ruta } from '@/lib/config';
import { requerirSesion } from '@/lib/sesion';
import { codigoPorEstado, esCodigoError, idValido, leerCampos, MENSAJES } from '@/lib/validacion';

export const dynamic = 'force-dynamic';
export const metadata = { title: 'Editar estudiante · ZuraEdu' };

const ETIQUETA_ESTADO: Record<string, string> = { activo: 'Activo', inactivo: 'Inactivo', egresado: 'Egresado', transferido: 'Transferido' };
const primero = (v: string | string[] | undefined): string => (Array.isArray(v) ? (v[0] ?? '') : (v ?? ''));

export default async function Editar({
  params,
  searchParams,
}: {
  params: Promise<{ id: string }>;
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const id = idValido((await params).id);
  if (id === null) notFound();

  const ctx = await requerirSesion();
  const r = await obtenerEstudiante(ctx, id);
  if (!r.ok) {
    if (r.status === 401) redirect('/sesion/expirada');
    if (r.status === 404 || r.status === 400) notFound(); // otro colegio, borrado o inexistente: no se distingue
    return (
      <p role="alert" className="aviso error">
        {MENSAJES[codigoPorEstado(r.status)]}
      </p>
    );
  }
  const e = r.data;

  const sp = await searchParams;
  const codigoCrudo = primero(sp.e);
  const codigo = esCodigoError(codigoCrudo) ? codigoCrudo : null; // solo códigos conocidos
  const marcados = leerCampos(primero(sp.c));
  const guardado = primero(sp.ok) === '1';
  const invalido = (c: string) => (marcados as string[]).includes(c);

  return (
    <>
      <h1>
        Editar estudiante <small>({e.numeroMatricula})</small>
      </h1>

      {guardado && (
        <p role="status" className="aviso ok">
          Cambios guardados.
        </p>
      )}
      {codigo && (
        <p role="alert" className="aviso error">
          {MENSAJES[codigo]}
        </p>
      )}

      <form method="post" action={ruta(`/estudiantes/${id}/guardar`)} className="tarjeta">
        <label htmlFor="nombres">Nombres</label>
        <input id="nombres" name="nombres" defaultValue={e.nombres} required maxLength={100} aria-invalid={invalido('nombres')} />

        <label htmlFor="apellidos">Apellidos</label>
        <input id="apellidos" name="apellidos" defaultValue={e.apellidos} required maxLength={100} aria-invalid={invalido('apellidos')} />

        <label htmlFor="cedula">Cédula</label>
        <input id="cedula" name="cedula" defaultValue={e.cedula ?? ''} maxLength={50} aria-invalid={invalido('cedula')} />

        <label htmlFor="sexo">Sexo</label>
        <select id="sexo" name="sexo" defaultValue={e.sexo} aria-invalid={invalido('sexo')}>
          <option value="M">Masculino</option>
          <option value="F">Femenino</option>
        </select>

        <label htmlFor="estado">Estado</label>
        <select id="estado" name="estado" defaultValue={e.estado} aria-invalid={invalido('estado')}>
          {ESTUDIANTE_ESTADOS.map((s) => (
            <option key={s} value={s}>
              {ETIQUETA_ESTADO[s] ?? s}
            </option>
          ))}
        </select>

        <div className="acciones">
          <button type="submit" className="boton">
            Guardar cambios
          </button>
          <a href={ruta('/estudiantes')}>Volver al listado</a>
        </div>
      </form>
    </>
  );
}
