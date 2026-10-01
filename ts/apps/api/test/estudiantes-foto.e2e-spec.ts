import { existsSync, mkdirSync, mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import type { INestApplication } from '@nestjs/common';
import { Test } from '@nestjs/testing';
import request from 'supertest';
import { DATABASE_URL, Fixtures, TenantCreado } from './helpers/fixtures';

// Debe fijarse ANTES de importar AppModule: ConfigModule lee process.env al compilar el módulo.
process.env.DATABASE_URL = DATABASE_URL;
const PUBLICA = mkdtempSync(join(tmpdir(), 'zura-public-'));
mkdirSync(join(PUBLICA, 'fotos', 'estudiantes'), { recursive: true });

import { AppModule } from '../src/app.module';

async function crearApp(carpeta: string | undefined): Promise<INestApplication> {
  if (carpeta === undefined) delete process.env.FOTOS_PUBLICAS_DIR;
  else process.env.FOTOS_PUBLICAS_DIR = carpeta;
  const app = (await Test.createTestingModule({ imports: [AppModule] }).compile()).createNestApplication();
  await app.listen(0);
  return app;
}

describe('API · DELETE /api/v1/estudiantes/:id borra la foto como Laravel (e2e, MySQL + disco)', () => {
  const fx = new Fixtures();
  let colegio: TenantCreado;
  let admin = '';
  const apps: INestApplication[] = [];
  let n = 0;

  const estudianteConFoto = async (foto: string | null): Promise<number> => {
    const id = await fx.crearEstudiante(colegio.id, 'Foto', `Prueba${++n}`, n);
    if (foto !== null) await fx.pool.query('update estudiantes set foto = ? where id = ?', [foto, id]);
    return id;
  };
  const archivo = (nombre: string): string => {
    const ruta = join(PUBLICA, 'fotos', 'estudiantes', nombre);
    writeFileSync(ruta, 'contenido');
    return ruta;
  };
  const eliminar = (app: INestApplication, id: number) =>
    request(app.getHttpServer()).delete(`/api/v1/estudiantes/${id}`).set('Authorization', `Bearer ${admin}`);

  beforeAll(async () => {
    colegio = await fx.crearTenant('fto');
    admin = (await fx.crearToken(await fx.crearUsuario(colegio.id, 'ftoadmin', 'Administrador'))).bearer;
    apps.push(await crearApp(PUBLICA)); // apps[0]: con la carpeta configurada
  }, 60000);

  afterAll(async () => {
    await Promise.all(apps.map((a) => a.close()));
    await fx.limpiar();
    rmSync(PUBLICA, { recursive: true, force: true });
  });

  it('con la carpeta configurada: elimina al estudiante Y borra el archivo; la fila conserva la ruta (borrado lógico)', async () => {
    const ruta = archivo('a.jpg');
    const id = await estudianteConFoto('fotos/estudiantes/a.jpg');

    await eliminar(apps[0], id).expect(204);

    expect(existsSync(ruta)).toBe(false);
    const [filas] = (await fx.pool.query('select deleted_at, foto from estudiantes where id = ?', [id])) as unknown as [Array<{ deleted_at: string | null; foto: string }>];
    expect(filas[0].deleted_at).not.toBeNull();
    expect(filas[0].foto).toBe('fotos/estudiantes/a.jpg');
  });

  it('un estudiante sin foto se elimina sin tocar el archivo de otro estudiante', async () => {
    const otra = archivo('otra.jpg');
    await eliminar(apps[0], await estudianteConFoto(null)).expect(204);
    expect(existsSync(otra)).toBe(true);
  });

  it('el archivo ya inexistente no impide eliminar al estudiante', async () => {
    await eliminar(apps[0], await estudianteConFoto('fotos/estudiantes/no-esta.jpg')).expect(204);
  });

  it('una ruta de foto que se sale de la carpeta (../) NO borra el archivo ajeno, aunque exista', async () => {
    const nombre = `secreto-${Date.now()}.txt`;
    const fuera = join(PUBLICA, '..', nombre);
    writeFileSync(fuera, 'no me borres');
    try {
      await eliminar(apps[0], await estudianteConFoto(`../${nombre}`)).expect(204);
      expect(existsSync(fuera)).toBe(true);
    } finally {
      rmSync(fuera, { force: true });
    }
  });

  it('un estudiante que NO se pudo eliminar (otro colegio) no borra ningún archivo', async () => {
    const ruta = archivo('b.jpg');
    const otroColegio = await fx.crearTenant('fto2');
    const idAjeno = await fx.crearEstudiante(otroColegio.id, 'Ajeno', 'Foto', 99);
    await fx.pool.query("update estudiantes set foto = 'fotos/estudiantes/b.jpg' where id = ?", [idAjeno]);

    await eliminar(apps[0], idAjeno).expect(404);
    expect(existsSync(ruta)).toBe(true);
  });

  it('sin FOTOS_PUBLICAS_DIR no se toca ningún archivo (queda huérfano, como se documenta)', async () => {
    const sinCarpeta = await crearApp(undefined);
    apps.push(sinCarpeta);
    const ruta = archivo('c.jpg');
    const id = await estudianteConFoto('fotos/estudiantes/c.jpg');
    await eliminar(sinCarpeta, id).expect(204);
    expect(existsSync(ruta)).toBe(true);
  });
});
