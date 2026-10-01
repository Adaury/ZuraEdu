import { redirect } from 'next/navigation';
import { ruta } from '@/lib/config';
import { contextoActual } from '@/lib/sesion';
import { esCodigoError, MENSAJES } from '@/lib/validacion';

export const dynamic = 'force-dynamic';
export const metadata = { title: 'Iniciar sesión · ZuraEdu' };

export default async function Login({ searchParams }: { searchParams: Promise<Record<string, string | string[] | undefined>> }) {
  if ((await contextoActual()).token) redirect('/estudiantes');

  const crudo = (await searchParams).error;
  const codigo = typeof crudo === 'string' && esCodigoError(crudo) ? crudo : null; // solo códigos conocidos, nunca texto de la URL

  return (
    <section className="tarjeta estrecha">
      <h1>Iniciar sesión</h1>
      {codigo && (
        <p role="alert" className="aviso error">
          {MENSAJES[codigo]}
        </p>
      )}
      <form method="post" action={ruta('/sesion/entrar')}>
        <label htmlFor="email">Correo electrónico</label>
        <input id="email" name="email" type="email" autoComplete="username" required maxLength={150} />
        <label htmlFor="password">Contraseña</label>
        <input id="password" name="password" type="password" autoComplete="current-password" required maxLength={200} />
        <div className="acciones">
          <button type="submit" className="boton">
            Entrar
          </button>
        </div>
      </form>
    </section>
  );
}
