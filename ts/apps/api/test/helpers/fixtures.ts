import { createHash, randomBytes } from 'node:crypto';
import { createPool, Pool, ResultSetHeader, RowDataPacket } from 'mysql2/promise';

export const DATABASE_URL = process.env.TEST_DATABASE_URL ?? 'mysql://root@127.0.0.1:3306/sge_bench';
export const USER_MODEL = 'App\\Models\\User';

/** Pausa real: la caché usa Date.now, y estas pruebas verifican plazos reales de vencimiento. */
export const dormir = (ms: number) => new Promise<void>((r) => setTimeout(r, ms));

export interface TenantCreado {
  id: number;
  dominio: string;
}

/**
 * Datos temporales para pruebas e2e contra MySQL real. Cada prueba crea sus PROPIOS colegios, usuarios y
 * tokens (no depende de que exista un tenant 1 ni de datos previos), y todo se borra en `limpiar()`.
 * Requiere solo el esquema de Laravel migrado y los roles/permisos (RolesSeeder).
 */
export class Fixtures {
  readonly pool: Pool = createPool(DATABASE_URL);
  readonly sufijo = randomBytes(4).toString('hex');
  private readonly tenants: number[] = [];
  private readonly estudiantes: number[] = [];
  private readonly usuarios: number[] = [];
  private readonly tokens: number[] = [];

  async rolId(nombre: string): Promise<number> {
    const [rows] = await this.pool.query<RowDataPacket[]>('select id from roles where name = ?', [nombre]);
    if (!rows[0]) throw new Error(`Rol "${nombre}" no existe: ejecuta RolesSeeder en la base de pruebas`);
    return Number(rows[0].id);
  }

  async crearTenant(etiqueta: string, estado: 'activo' | 'suspendido' = 'activo'): Promise<TenantCreado> {
    const dominio = `fx${etiqueta}${this.sufijo}`.toLowerCase().slice(0, 40);
    const [r] = await this.pool.query<ResultSetHeader>(
      'insert into tenants (nombre_institucion, dominio, estado) values (?, ?, ?)',
      [`FX ${etiqueta} ${this.sufijo}`, dominio, estado],
    );
    this.tenants.push(r.insertId);
    return { id: r.insertId, dominio };
  }

  async crearEstudiante(
    tenantId: number,
    nombres: string,
    apellidos: string,
    indice: number,
    cedula: string | null = null,
  ): Promise<number> {
    const [r] = await this.pool.query<ResultSetHeader>(
      "insert into estudiantes (tenant_id, numero_matricula, cedula, nombres, apellidos, sexo, estado) values (?, ?, ?, ?, ?, 'F', 'activo')",
      [tenantId, `FX-${this.sufijo}-${tenantId}-${indice}`, cedula, nombres, apellidos],
    );
    this.estudiantes.push(r.insertId);
    return r.insertId;
  }

  async crearUsuario(tenantId: number, etiqueta: string, rol: string, activo = 1): Promise<number> {
    const [r] = await this.pool.query<ResultSetHeader>(
      'insert into users (name, email, password, tenant_id, activo) values (?, ?, ?, ?, ?)',
      [`fx ${etiqueta}`, `fx-${etiqueta}-${this.sufijo}-${randomBytes(2).toString('hex')}@example.test`, 'x', tenantId, activo],
    );
    this.usuarios.push(r.insertId);
    await this.pool.query('insert into model_has_roles (role_id, model_type, model_id) values (?, ?, ?)', [
      await this.rolId(rol),
      USER_MODEL,
      r.insertId,
    ]);
    return r.insertId;
  }

  /** Devuelve el token en formato Sanctum ("id|texto") y los datos para manipularlo. */
  async crearToken(userId: number, expiraEnSegundos?: number): Promise<{ bearer: string; id: number; plano: string }> {
    const plano = randomBytes(20).toString('hex');
    const hash = createHash('sha256').update(plano).digest('hex');
    const [r] = await this.pool.query<ResultSetHeader>(
      expiraEnSegundos === undefined
        ? 'insert into personal_access_tokens (tokenable_type, tokenable_id, name, token, abilities) values (?, ?, ?, ?, ?)'
        : `insert into personal_access_tokens (tokenable_type, tokenable_id, name, token, abilities, expires_at)
           values (?, ?, ?, ?, ?, date_add(utc_timestamp(), interval ? second))`,
      expiraEnSegundos === undefined
        ? [USER_MODEL, userId, 'fx', hash, '["*"]']
        : [USER_MODEL, userId, 'fx', hash, '["*"]', expiraEnSegundos],
    );
    this.tokens.push(r.insertId);
    return { bearer: `${r.insertId}|${plano}`, id: r.insertId, plano };
  }

  async limpiar(): Promise<void> {
    // Orden: lo que referencia a otras tablas primero.
    if (this.tokens.length) await this.pool.query('delete from personal_access_tokens where id in (?)', [this.tokens]);
    if (this.tenants.length) await this.pool.query('delete from activity_logs where tenant_id in (?)', [this.tenants]);
    // Por colegio (no solo los ids creados aquí): la API también crea estudiantes durante las pruebas.
    if (this.tenants.length) await this.pool.query('delete from estudiantes where tenant_id in (?)', [this.tenants]);
    if (this.estudiantes.length) await this.pool.query('delete from estudiantes where id in (?)', [this.estudiantes]);
    if (this.usuarios.length) {
      await this.pool.query('delete from model_has_roles where model_type = ? and model_id in (?)', [USER_MODEL, this.usuarios]);
      await this.pool.query('delete from users where id in (?)', [this.usuarios]);
    }
    if (this.tenants.length) await this.pool.query('delete from tenants where id in (?)', [this.tenants]);
    await this.pool.end();
  }
}
