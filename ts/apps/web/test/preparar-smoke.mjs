// Prepara los datos de la prueba de humo en una base con el esquema de Laravel y los roles ya sembrados (RolesSeeder):
// un colegio con un administrador (contraseña conocida), tres estudiantes, y OTRO colegio con un estudiante (para el aislamiento).
// Imprime variables `CLAVE=valor` para que el CI las añada a $GITHUB_ENV.
//
// Uso: DATABASE_URL=mysql://root:root@127.0.0.1:3306/sge_e2e SMOKE_PASSWORD_HASH='<bcrypt>' node test/preparar-smoke.mjs
import { randomBytes } from 'node:crypto';
import { createPool } from 'mysql2/promise';

const url = process.env.DATABASE_URL;
const hash = process.env.SMOKE_PASSWORD_HASH;
if (!url || !hash) {
  console.error('Faltan DATABASE_URL y SMOKE_PASSWORD_HASH.');
  process.exit(2);
}

const sufijo = randomBytes(3).toString('hex');
const pool = createPool(url);
try {
  const [[rol]] = await pool.query("select id from roles where name = 'Administrador' limit 1");
  if (!rol) throw new Error('No existe el rol Administrador: ejecuta RolesSeeder antes.');

  const colegio = async (dominio, nombre) => {
    const [r] = await pool.query('insert into tenants (nombre_institucion, dominio, estado) values (?, ?, ?)', [nombre, dominio, 'activo']);
    return r.insertId;
  };
  const estudiante = async (tenantId, n, cedula, apellidos) => {
    const [r] = await pool.query(
      "insert into estudiantes (tenant_id, numero_matricula, cedula, nombres, apellidos, sexo, estado) values (?, ?, ?, ?, ?, 'F', 'activo')",
      [tenantId, `SMK-${sufijo}-${n}`, cedula, `Nombre${n}`, apellidos],
    );
    return r.insertId;
  };

  const dominio = `smoke${sufijo}`;
  const propio = await colegio(dominio, `Colegio Smoke ${sufijo}`);
  const ajeno = await colegio(`ajeno${sufijo}`, `Colegio Ajeno ${sufijo}`);

  const email = `smoke-admin-${sufijo}@example.test`;
  const [u] = await pool.query('insert into users (name, email, password, tenant_id, activo) values (?, ?, ?, ?, 1)', ['Admin Smoke', email, hash, propio]);
  await pool.query("insert into model_has_roles (role_id, model_type, model_id) values (?, 'App\\\\Models\\\\User', ?)", [rol.id, u.insertId]);

  for (let i = 1; i <= 3; i++) await estudiante(propio, i, `900-${sufijo}-${i}`, `Smoke${i}`);
  const idAjeno = await estudiante(ajeno, 99, `901-${sufijo}-1`, 'Ajeno');

  console.log(`SMOKE_HOST=${dominio}.zuraedu.com`);
  console.log(`SMOKE_EMAIL=${email}`);
  console.log(`SMOKE_AJENO_ID=${idAjeno}`);
} finally {
  await pool.end();
}
