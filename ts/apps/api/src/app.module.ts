import { Module } from '@nestjs/common';
import { APP_GUARD } from '@nestjs/core';
import { ClsModule } from 'nestjs-cls';
import { AuditService } from './audit/audit.service';
import { AuthCache, RELOJ } from './auth/auth-cache';
import { PermissionGuard } from './auth/permission.guard';
import { PermissionsService } from './auth/permissions.service';
import { SanctumGuard } from './auth/sanctum.guard';
import { ConfigModule } from './config/config.module';
import { DbModule } from './db/db.module';
import { EstudiantesController } from './estudiantes/estudiantes.controller';
import { EstudiantesService } from './estudiantes/estudiantes.service';
import { HealthController } from './health/health.controller';
import { OpenApiController } from './openapi/openapi.controller';
import { RateLimitGuard } from './ratelimit/rate-limit.guard';
import { RateLimiter } from './ratelimit/rate-limiter';
import { RedisModule } from './redis/redis.module';
import { HostTenantResolver } from './tenancy/host-tenant.resolver';
import { TenantContext } from './tenancy/tenant-context';

@Module({
  imports: [
    // Contexto por petición (AsyncLocalStorage): aquí vive el tenant que fija SanctumGuard.
    ClsModule.forRoot({ global: true, middleware: { mount: true } }),
    ConfigModule,
    DbModule,
    RedisModule,
  ],
  controllers: [HealthController, OpenApiController, EstudiantesController],
  providers: [
    { provide: RELOJ, useValue: () => Date.now() },
    AuthCache,
    TenantContext,
    AuditService,
    HostTenantResolver,
    PermissionsService,
    EstudiantesService,
    RateLimiter,
    // Orden = orden de ejecución: primero autenticar y fijar tenant, luego permisos.
    // Todo es privado por defecto; solo lo marcado con @Public() (/health) no pide token.
    { provide: APP_GUARD, useClass: SanctumGuard },
    { provide: APP_GUARD, useClass: RateLimitGuard },
    { provide: APP_GUARD, useClass: PermissionGuard },
  ],
})
export class AppModule {}
