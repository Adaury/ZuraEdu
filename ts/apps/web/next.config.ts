import type { NextConfig } from 'next';
import { normalizarBase } from './src/lib/base';

const config: NextConfig = {
  // Paquete del workspace con tipos/constantes compartidos con la API.
  transpilePackages: ['@zuraedu/shared'],
  // La web vive bajo un prefijo (WEB_BASE_PATH) porque Laravel ya usa /login y / en el mismo servidor. Vacío = raíz.
  basePath: normalizarBase(process.env.WEB_BASE_PATH) || undefined,
  // No anunciar el framework.
  poweredByHeader: false,
  async headers() {
    return [
      {
        source: '/:path*',
        headers: [
          { key: 'X-Frame-Options', value: 'DENY' }, // la web no se puede incrustar en otro sitio (clickjacking)
          { key: 'X-Content-Type-Options', value: 'nosniff' },
          { key: 'Referrer-Policy', value: 'same-origin' },
          { key: 'Permissions-Policy', value: 'camera=(), microphone=(), geolocation=()' },
        ],
      },
    ];
  },
};

export default config;
