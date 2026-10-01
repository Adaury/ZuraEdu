import { Inject, Injectable } from '@nestjs/common';
import { ENV, Env } from '../config/env';
import { Database, SYSTEM_DB } from '../db/db.module';

const TTL_MS = 5 * 60 * 1000; // igual que Cache::remember(..., 300) en ResolveTenant de Laravel

/**
 * Resuelve el tenant que corresponde a un Host, con las mismas reglas que
 * `ResolveTenant::resolve()` de Laravel:
 *   1) dominio_personalizado == host
 *   2) host con 3+ partes: dominio == primer segmento (subdominio)
 *   3) dominio == host completo
 *
 * En la API NO decide a qué tenant se accede: eso lo fija el usuario del token. El host solo
 * se usa para detectar incoherencias (token del colegio A usado en el dominio del colegio B).
 */
@Injectable()
export class HostTenantResolver {
  private readonly cache = new Map<string, { id: number | null; expira: number }>();

  constructor(
    @Inject(SYSTEM_DB) private readonly db: Database,
    @Inject(ENV) private readonly env: Env,
  ) {}

  async resolve(hostHeader: string | undefined): Promise<number | undefined> {
    const host = (hostHeader ?? '').split(':')[0].trim().toLowerCase();
    if (!host) return undefined;

    const enCache = this.cache.get(host);
    if (enCache && enCache.expira > Date.now()) return enCache.id ?? undefined;

    const id = await this.buscar(host);
    this.cache.set(host, { id: id ?? null, expira: Date.now() + TTL_MS });
    return id;
  }

  private async buscar(host: string): Promise<number | undefined> {
    const porDominioPropio = await this.db
      .selectFrom('tenants')
      .select('id')
      .where('dominio_personalizado', '=', host)
      .where('deleted_at', 'is', null)
      .executeTakeFirst();
    if (porDominioPropio) return Number(porDominioPropio.id);

    const partes = host.split('.');
    if (partes.length >= 3) {
      const porSubdominio = await this.db
        .selectFrom('tenants')
        .select('id')
        .where('dominio', '=', partes[0])
        .where('deleted_at', 'is', null)
        .executeTakeFirst();
      if (porSubdominio) return Number(porSubdominio.id);
    }

    const porHost = await this.db
      .selectFrom('tenants')
      .select('id')
      .where('dominio', '=', host)
      .where('deleted_at', 'is', null)
      .executeTakeFirst();
    return porHost ? Number(porHost.id) : undefined;
  }
}
