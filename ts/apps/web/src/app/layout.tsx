import { cookies } from 'next/headers';
import type { ReactNode } from 'react';
import { config, ruta } from '@/lib/config';
import './estilos.css';

export const metadata = {
  title: 'ZuraEdu',
  description: 'Sistema de gestión escolar',
};

export default async function RootLayout({ children }: { children: ReactNode }) {
  const conSesion = (await cookies()).has(config.cookieSesion);

  return (
    <html lang="es">
      <body>
        <header className="barra">
          <a className="marca" href={ruta(conSesion ? '/estudiantes' : '/login')}>
            ZuraEdu
          </a>
          {conSesion && (
            <form method="post" action={ruta('/sesion/salir')}>
              <button type="submit" className="boton secundario">
                Cerrar sesión
              </button>
            </form>
          )}
        </header>
        <main className="contenido">{children}</main>
      </body>
    </html>
  );
}
