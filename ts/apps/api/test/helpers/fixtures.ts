import { createHash, randomBytes } from 'node:crypto';
import { createPool, Pool, ResultSetHeader, RowDataPacket } from 'mysql2/promise';

export const DATABASE_URL = process.env.TEST_DATABASE_URL ?? 'mysql://root@127.0.0.1:3306/sge_bench';
export const USER_MODEL = 'App\\Models\\User';

/** Pausa real: la caché usa Date.now, y estas pruebas verifican plazos reales de vencimiento. */
export const dormir = (ms: number) => new Promise<void>((r) => setTimeout(r, ms));

/**
 * Datos temporales para pruebas e2e contra MySQL real. Todo lo creado se borra en `limpiar()`;
 * no toca datos existentes.
 */
export class Fixtures {
  readonly pool: Pool = createPool(DATABASE_URL);
  readonly sufijo = randomBytes(4).toString('hex');
  private readonly usuarios: number[] = [];
  private readonly tokens: number[] = [];

  async rolId(nombre: string): Promise<number> {
    const [rows] = await this.pool.query<RowDataPacket[]>('select id from roles where name = ?', [nombre]);
    if (!rows[0]) throw new Error(`Rol "${nombre}" no existe en la base de pruebas`);
    return Number(rows[0].id);
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
    const expira = expiraEnSegundos === undefined ? null : expiraEnSegundos;
    const [r] = await this.pool.query<ResultSetHeader>(
      `insert into personal_access_tokens (tokenable_type, tokenable_id, name, token, abilities, expires_at)
       values (?, ?, ?, ?, ?, ${expira === null ? 'null' : 'date_add(utc_timestamp(), interval ? second)'})`,
      expira === null
        ? [USER_MODEL, userId, 'fx', createHash('sha256').update(plano).digest('hex'), '["*"]']
        : [USER_MODEL, userId, 'fx', createHash('sha256').update(plano).digest('hex'), '["*"]', expira],
    );
    this.tokens.push(r.insertId);
    return { bearer: `${r.insertId}|${plano}`, id: r.insertId, plano };
  }

  async limpiar(): Promise<void> {
    if (this.tokens.length) await this.pool.query('delete from personal_access_tokens where id in (?)', [this.tokens]);
    if (this.usuarios.length) {
      await this.pool.query('delete from model_has_roles where model_type = ? and model_id in (?)', [USER_MODEL, this.usuarios]);
      await this.pool.query('delete from users where id in (?)', [this.usuarios]);
    }
    await this.pool.end();
  }
}
