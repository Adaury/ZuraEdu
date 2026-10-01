import type { NextConfig } from 'next';

const config: NextConfig = {
  // Paquete del workspace con tipos/constantes compartidos con la API.
  transpilePackages: ['@zuraedu/shared'],
};

export default config;
