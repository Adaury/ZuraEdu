import type { NextConfig } from 'next';

const config: NextConfig = {
  // Paquete del workspace con tipos/constantes compartidos con la API.
  transpilePackages: ['@zuraedu/shared'],
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
