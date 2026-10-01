import 'reflect-metadata';
import { RequestMethod } from '@nestjs/common';
import { METHOD_METADATA, MODULE_METADATA, PATH_METADATA } from '@nestjs/common/constants';
import { ESTUDIANTE_ESTADOS, PER_PAGE_DEFAULT, PER_PAGE_MAX } from '@zuraedu/shared';
import { AppModule } from '../app.module';
import { estudiantesQuerySchema } from '../estudiantes/estudiantes.query';
import { actualizarEstudianteSchema } from '../estudiantes/estudiantes.update';
import { construirOpenApi } from './openapi';
import { errorSchema, estudianteDtoSchema, estudiantesPageSchema, healthSchema } from './respuestas';

type Json = Record<string, any>; // eslint-disable-line @typescript-eslint/no-explicit-any
const doc = construirOpenApi('9.9.9') as Json;

/** "GET /api/v1/estudiantes", "PATCH /api/v1/estudiantes/{id}"... de TODAS las rutas registradas en Nest. */
function rutasRegistradas(): string[] {
  const controladores: Array<new (...args: never[]) => object> = Reflect.getMetadata(MODULE_METADATA.CONTROLLERS, AppModule);
  const rutas: string[] = [];
  for (const c of controladores) {
    const base = (Reflect.getMetadata(PATH_METADATA, c) as string) ?? '';
    for (const nombre of Object.getOwnPropertyNames(c.prototype)) {
      const handler = (c.prototype as Record<string, unknown>)[nombre] as object;
      const metodo = Reflect.getMetadata(METHOD_METADATA, handler) as RequestMethod | undefined;
      if (metodo === undefined) continue; // GET vale 0: comparar con undefined, no con falsy
      const sub = (Reflect.getMetadata(PATH_METADATA, handler) as string) ?? '';
      const ruta = ('/' + [base, sub].filter((x) => x && x !== '/').join('/'))
        .replace(/\/+/g, '/')
        .replace(/:([A-Za-z]+)/g, '{$1}');
      rutas.push(`${RequestMethod[metodo]} ${ruta}`);
    }
  }
  return rutas;
}

const documentadas = () =>
  Object.entries(doc.paths).flatMap(([ruta, ops]) => Object.keys(ops as Json).map((m) => `${m.toUpperCase()} ${ruta}`));

describe('contrato OpenAPI', () => {
  describe('estructura', () => {
    it('es OpenAPI 3.1 con la versión indicada', () => {
      expect(doc.openapi).toBe('3.1.0');
      expect(doc.info.version).toBe('9.9.9');
      expect(doc.info.title).toBeTruthy();
    });

    it('todo es privado por defecto (bearer) y solo /health declara security: []', () => {
      expect(doc.security).toEqual([{ bearerAuth: [] }]);
      expect(doc.components.securitySchemes.bearerAuth).toMatchObject({ type: 'http', scheme: 'bearer' });
      expect(doc.paths['/health'].get.security).toEqual([]);
      for (const [ruta, ops] of Object.entries<Json>(doc.paths)) {
        if (ruta === '/health') continue;
        for (const op of Object.values<Json>(ops)) expect(op.security).toBeUndefined(); // heredan bearerAuth
      }
    });

    it('todos los $ref apuntan a un esquema que existe', () => {
      const refs = [...JSON.stringify(doc).matchAll(/"\$ref":"#\/components\/schemas\/([A-Za-z]+)"/g)].map((m) => m[1]);
      expect(refs.length).toBeGreaterThan(0);
      for (const nombre of refs) expect(doc.components.schemas[nombre]).toBeDefined();
    });

    it('cada operación tiene operationId único y al menos una respuesta de error documentada (salvo /health)', () => {
      const ids: string[] = [];
      for (const [ruta, ops] of Object.entries<Json>(doc.paths)) {
        for (const op of Object.values<Json>(ops)) {
          ids.push(op.operationId);
          if (ruta !== '/health') expect(Object.keys(op.responses)).toEqual(expect.arrayContaining(['401', '403']));
        }
      }
      expect(new Set(ids).size).toBe(ids.length);
    });
  });

  describe('cobertura: ninguna ruta sin documentar', () => {
    it('cada ruta registrada en Nest aparece en el documento (excepto el propio /openapi.json)', () => {
      const registradas = rutasRegistradas().filter((r) => r !== 'GET /openapi.json');
      expect(registradas.length).toBeGreaterThanOrEqual(3);
      expect(documentadas().sort()).toEqual(registradas.sort());
    });
  });

  describe('coherencia con los esquemas que validan de verdad', () => {
    const params = (nombre: string) => doc.paths['/api/v1/estudiantes'].get.parameters.find((p: Json) => p.name === nombre);

    it('perPage: el máximo y el valor por defecto del contrato son los que aplica la validación', () => {
      const p = params('perPage').schema;
      expect(p).toMatchObject({ maximum: PER_PAGE_MAX, default: PER_PAGE_DEFAULT, minimum: 1 });
      expect(estudiantesQuerySchema.safeParse({ perPage: String(p.maximum) }).success).toBe(true);
      expect(estudiantesQuerySchema.safeParse({ perPage: String(p.maximum + 1) }).success).toBe(false);
      expect(estudiantesQuerySchema.parse({}).perPage).toBe(p.default);
    });

    it('page: mínimo 1 y por defecto 1, igual que la validación', () => {
      const p = params('page').schema;
      expect(estudiantesQuerySchema.safeParse({ page: String(p.minimum) }).success).toBe(true);
      expect(estudiantesQuerySchema.safeParse({ page: String(p.minimum - 1) }).success).toBe(false);
      expect(estudiantesQuerySchema.parse({}).page).toBe(p.default);
    });

    it('q: el largo máximo del contrato es el que aplica la validación', () => {
      const p = params('q').schema;
      expect(estudiantesQuerySchema.safeParse({ q: 'a'.repeat(p.maxLength) }).success).toBe(true);
      expect(estudiantesQuerySchema.safeParse({ q: 'a'.repeat(p.maxLength + 1) }).success).toBe(false);
    });

    it('estado (filtro): los valores del contrato son exactamente los del ENUM', () => {
      expect(params('estado').schema.enum).toEqual([...ESTUDIANTE_ESTADOS]);
    });

    describe('cuerpo del PATCH', () => {
      // Diferido: si la ruta documentada no coincide con la real, falla la prueba de cobertura y no la carga del archivo.
      const cuerpo = () => doc.paths['/api/v1/estudiantes/{id}'].patch.requestBody.content['application/json'].schema;

      it('rechaza campos desconocidos y exige al menos uno', () => {
        expect(cuerpo().additionalProperties).toBe(false);
        expect(cuerpo().minProperties).toBe(1);
        expect(actualizarEstudianteSchema.safeParse({ tenant_id: 1, estado: 'activo' }).success).toBe(false);
        expect(actualizarEstudianteSchema.safeParse({}).success).toBe(false);
      });

      it('documenta exactamente los campos editables y ninguno más (nunca tenant_id, id ni deleted_at)', () => {
        expect(Object.keys(cuerpo().properties).sort()).toEqual(['apellidos', 'cedula', 'estado', 'fechaNacimiento', 'nombres']);
        for (const prohibido of ['tenant_id', 'tenantId', 'id', 'deleted_at', 'user_id']) {
          expect(cuerpo().properties[prohibido]).toBeUndefined();
        }
      });

      it('los límites del contrato coinciden con la validación', () => {
        expect(cuerpo().properties.nombres).toMatchObject({ minLength: 2, maxLength: 100 });
        expect(actualizarEstudianteSchema.safeParse({ nombres: 'a'.repeat(100) }).success).toBe(true);
        expect(actualizarEstudianteSchema.safeParse({ nombres: 'a'.repeat(101) }).success).toBe(false);
        expect(cuerpo().properties.estado.enum).toEqual([...ESTUDIANTE_ESTADOS]);
        expect(cuerpo().properties.fechaNacimiento.format).toBe('date');
      });

      it('la cédula admite null (para quitarla)', () => {
        expect(JSON.stringify(cuerpo().properties.cedula)).toContain('"null"');
        expect(actualizarEstudianteSchema.safeParse({ cedula: null }).success).toBe(true);
      });
    });
  });

  describe('respuestas', () => {
    it('Estudiante: propiedades obligatorias = las del DTO compartido', () => {
      const s = doc.components.schemas.Estudiante;
      expect([...s.required].sort()).toEqual(['apellidos', 'cedula', 'estado', 'id', 'nombres', 'numeroMatricula', 'sexo']);
    });

    it('las respuestas de error 404 y 409 de PATCH están documentadas', () => {
      expect(Object.keys(doc.paths['/api/v1/estudiantes/{id}'].patch.responses)).toEqual(
        expect.arrayContaining(['200', '400', '401', '403', '404', '409']),
      );
    });

    it('los esquemas de respuesta aceptan una respuesta válida y rechazan una malformada', () => {
      const est = { id: 1, numeroMatricula: 'M1', cedula: null, nombres: 'Ana', apellidos: 'Perez', sexo: 'F', estado: 'activo' };
      expect(estudianteDtoSchema.safeParse(est).success).toBe(true);
      expect(estudianteDtoSchema.safeParse({ ...est, estado: 'borrado' }).success).toBe(false);
      expect(estudianteDtoSchema.safeParse({ ...est, id: 'x' }).success).toBe(false);
      expect(estudiantesPageSchema.safeParse({ data: [est], meta: { page: 1, perPage: 30, total: 1, lastPage: 1 } }).success).toBe(true);
      expect(estudiantesPageSchema.safeParse({ data: [est] }).success).toBe(false);
      expect(healthSchema.safeParse({ status: 'ok', checks: { database: 'ok' }, version: '0.1', runtime: 'node' }).success).toBe(true);
      expect(errorSchema.safeParse({ message: 'No autenticado.', error: 'Unauthorized', statusCode: 401 }).success).toBe(true);
    });
  });
});
