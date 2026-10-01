import 'reflect-metadata';
import { NestFactory } from '@nestjs/core';
import { AppModule } from './app.module';
import { loadEnv } from './config/env';

/** Carga el .env (de apps/api o de la raíz de ts/) sin dependencias extra. En producción vienen del entorno. */
function cargarDotenv(): void {
  for (const ruta of ['.env', '../../.env']) {
    try {
      process.loadEnvFile(ruta);
      return;
    } catch {
      /* no existe: se prueba el siguiente */
    }
  }
}

async function bootstrap(): Promise<void> {
  cargarDotenv();
  const env = loadEnv(); // falla aquí, con un mensaje claro, si la configuración es inválida
  const app = await NestFactory.create(AppModule, { logger: env.NODE_ENV === 'production' ? ['error', 'warn', 'log'] : undefined });
  app.enableShutdownHooks();
  await app.listen(env.API_PORT);
}

void bootstrap();
