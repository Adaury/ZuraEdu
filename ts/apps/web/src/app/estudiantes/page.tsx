import { ESTUDIANTE_ESTADOS } from '@zuraedu/shared';
import { redirect } from 'next/navigation';
import { listarEstudiantes } from '@/lib/api';
import { config, ruta } from '@/lib/config';
import { requerirSesion } from '@/lib/sesion';
import { codigoPorEstado, consultaParaApi, enlaceListado, leerConsulta, MENSAJES } from '@/lib/validacion';

export const dynamic = 'force-dynamic';
export const metadata = { title: 'Estudiantes · ZuraEdu' };

const ETIQUETA_ESTADO: Record<string, string> = { activo: 'Activo', inactivo: 'Inactivo', egresado: 'Egresado', transferido: 'Transferido' };

export default async function Estudiantes({ searchParams }: { searchParams: Promise<Record<string, string | string[] | undefined>> }) {
  const ctx = await requerirSesion();
  const consulta = leerConsulta(await searchParams, ESTUDIANTE_ESTADOS);
  const r = await listarEstudiantes(ctx, consultaParaApi(consulta, config.porPagina));

  if (!r.ok && r.status === 401) redirect('/sesion/expirada');

  return (
    <>
      <h1>Estudiantes</h1>

      <form method="get" action={ruta('/estudiantes')} className="filtros" role="search">
        <div>
          <label htmlFor="q">Buscar</label>
          <input id="q" name="q" type="search" defaultValue={consulta.q} maxLength={100} placeholder="Nombre, apellido, matrícula o cédula" />
        </div>
        <div>
          <label htmlFor="estado">Estado</label>
          <select id="estado" name="estado" defaultValue={consulta.estado}>
            <option value="">Todos</option>
            {ESTUDIANTE_ESTADOS.map((e) => (
              <option key={e} value={e}>
                {ETIQUETA_ESTADO[e] ?? e}
              </option>
            ))}
          </select>
        </div>
        <button type="submit" className="boton">
          Filtrar
        </button>
      </form>

      {!r.ok ? (
        <p role="alert" className="aviso error">
          {MENSAJES[codigoPorEstado(r.status)]}
          {r.reintentarEnSegundos ? ` (reintenta en ${r.reintentarEnSegundos} s)` : ''}
        </p>
      ) : r.data.data.length === 0 ? (
        <div className="tarjeta vacio">
          <p>No hay estudiantes con esos criterios.</p>
          {consulta.page > 1 && <a href={ruta(enlaceListado(consulta, 1))}>Volver a la primera página</a>}
        </div>
      ) : (
        <>
          <div className="tabla-contenedor">
            <table>
              <caption>
                {r.data.meta.total} {r.data.meta.total === 1 ? 'estudiante' : 'estudiantes'}
              </caption>
              <thead>
                <tr>
                  <th scope="col">Matrícula</th>
                  <th scope="col">Cédula</th>
                  <th scope="col">Apellidos</th>
                  <th scope="col">Nombres</th>
                  <th scope="col">Estado</th>
                  <th scope="col">
                    <span className="sr-only">Acciones</span>
                  </th>
                </tr>
              </thead>
              <tbody>
                {r.data.data.map((e) => (
                  <tr key={e.id}>
                    <td>{e.numeroMatricula}</td>
                    <td>{e.cedula ?? '—'}</td>
                    <td>{e.apellidos}</td>
                    <td>{e.nombres}</td>
                    <td>{ETIQUETA_ESTADO[e.estado] ?? e.estado}</td>
                    <td>
                      <a href={ruta(`/estudiantes/${e.id}/editar`)} aria-label={`Editar a ${e.nombres} ${e.apellidos}`}>
                        Editar
                      </a>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <nav className="paginacion" aria-label="Paginación">
            {r.data.meta.page > 1 ? <a href={ruta(enlaceListado(consulta, r.data.meta.page - 1))}>← Anterior</a> : <span />}
            <span>
              Página {r.data.meta.page} de {r.data.meta.lastPage}
            </span>
            {r.data.meta.page < r.data.meta.lastPage ? <a href={ruta(enlaceListado(consulta, r.data.meta.page + 1))}>Siguiente →</a> : <span />}
          </nav>
        </>
      )}
    </>
  );
}
