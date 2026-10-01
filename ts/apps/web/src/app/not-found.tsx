import { ruta } from '@/lib/config';

export default function NoEncontrado() {
  return (
    <section className="tarjeta estrecha">
      <h1>No encontrado</h1>
      <p>La página o el registro que buscas no existe, o no tienes acceso a él.</p>
      <p>
        <a href={ruta('/estudiantes')}>Ir al listado de estudiantes</a>
      </p>
    </section>
  );
}
