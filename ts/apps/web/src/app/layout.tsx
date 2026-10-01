import type { ReactNode } from 'react';

export const metadata = {
  title: 'ZuraEdu',
  description: 'Sistema de gestión escolar — esqueleto TypeScript',
};

export default function RootLayout({ children }: { children: ReactNode }) {
  return (
    <html lang="es">
      <body style={{ fontFamily: 'system-ui, sans-serif', margin: 0, padding: '2rem', background: '#f8fafc', color: '#0f172a' }}>
        {children}
      </body>
    </html>
  );
}
