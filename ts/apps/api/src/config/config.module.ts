import { Global, Module } from '@nestjs/common';
import { ENV, loadEnv } from './env';

/** Expone la configuración validada (`ENV`) a todos los módulos. */
@Global()
@Module({
  providers: [{ provide: ENV, useFactory: () => loadEnv() }],
  exports: [ENV],
})
export class ConfigModule {}
