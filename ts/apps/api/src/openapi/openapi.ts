import { PER_PAGE_DEFAULT, PER_PAGE_MAX } from '@zuraedu/shared';
import { z } from 'zod';
import { ESTUDIANTE_ESTADOS } from '@zuraedu/shared';
import { actualizarEstudianteSchema } from '../estudiantes/estudiantes.update';
import { errorSchema, estudianteDtoSchema, estudiantesPageSchema, healthSchema } from './respuestas';

type Json = Record<string, unknown>;

/** JSON Schema (2020-12, el que usa OpenAPI 3.1) de un esquema zod, sin la cabecera `$schema`. */
function jsonSchema(schema: z.ZodType, io: 'input' | 'output' = 'output'): Json {
  const { $schema: _omitido, ...resto } = z.toJSONSchema(schema, { io, unrepresentable: 'any' }) as Json;
  return resto;
}

const ref = (nombre: string) => ({ $ref: `#/components/schemas/${nombre}` });

function respuestaError(descripcion: string) {
  return { description: descripcion, content: { 'application/json': { schema: ref('Error') } } };
}

/**
 * Documento OpenAPI 3.1 de la API. Los esquemas salen de los mismos esquemas zod que validan las
 * peticiones (entrada) y las respuestas (salida); las pruebas verifican que cada ruta registrada en Nest
 * esté documentada aquí y que las respuestas reales cumplan el esquema.
 */
export function construirOpenApi(version = '0.1.0'): Json {
  const cuerpoActualizar = {
    ...jsonSchema(actualizarEstudianteSchema, 'input'),
    // El "al menos un campo" es un refinamiento de zod y no se traduce solo.
    minProperties: 1,
  } as unknown as Json & { properties: Record<string, Json> };
  cuerpoActualizar.properties.fechaNacimiento = {
    ...cuerpoActualizar.properties.fechaNacimiento,
    format: 'date',
    description: 'AAAA-MM-DD; debe ser anterior a hoy.',
  };
  cuerpoActualizar.properties.cedula = {
    ...cuerpoActualizar.properties.cedula,
    description: 'null o cadena vacía la quita. Única por colegio.',
  };

  return {
    openapi: '3.1.0',
    info: {
      title: 'ZuraEdu API',
      version,
      description:
        'API TypeScript de ZuraEdu (migración gradual desde Laravel). Lee la misma base de datos y reconoce los ' +
        'tokens de Laravel Sanctum. El colegio (tenant) sale SIEMPRE del usuario del token, nunca de la URL ni del cuerpo.',
    },
    servers: [{ url: '/' }],
    tags: [
      { name: 'Sistema', description: 'Estado del servicio.' },
      { name: 'Estudiantes', description: 'Consulta y edición de estudiantes del colegio del usuario autenticado.' },
    ],
    paths: {
      '/health': {
        get: {
          tags: ['Sistema'],
          summary: 'Estado del servicio',
          description: 'Público. Misma forma que el /health de Laravel para reutilizar el monitoreo.',
          operationId: 'health',
          security: [],
          responses: { '200': { description: 'Servicio operativo o degradado.', content: { 'application/json': { schema: ref('Health') } } } },
        },
      },
      '/api/v1/estudiantes': {
        get: {
          tags: ['Estudiantes'],
          summary: 'Listar estudiantes del colegio',
          description:
            'Requiere el permiso `ver-estudiantes`. Excluye borrados lógicos y ordena por apellidos y nombres. ' +
            'Solo devuelve estudiantes del colegio del usuario autenticado.',
          operationId: 'listarEstudiantes',
          parameters: [
            { name: 'page', in: 'query', schema: { type: 'integer', minimum: 1, default: 1 }, description: 'Página (desde 1).' },
            {
              name: 'perPage',
              in: 'query',
              schema: { type: 'integer', minimum: 1, maximum: PER_PAGE_MAX, default: PER_PAGE_DEFAULT },
              description: 'Elementos por página.',
            },
            {
              name: 'q',
              in: 'query',
              schema: { type: 'string', maxLength: 100 },
              description: 'Busca (texto literal, sin comodines) en nombres, apellidos, número de matrícula y cédula.',
            },
            { name: 'estado', in: 'query', schema: { type: 'string', enum: [...ESTUDIANTE_ESTADOS] }, description: 'Filtra por estado.' },
          ],
          responses: {
            '200': { description: 'Página de estudiantes.', content: { 'application/json': { schema: ref('EstudiantesPage') } } },
            '400': respuestaError('Parámetros inválidos.'),
            '401': respuestaError('Sin token, token inválido, usuario inactivo o token vencido.'),
            '403': respuestaError('Sin permiso, institución suspendida o token usado en el dominio de otro colegio.'),
          },
        },
      },
      '/api/v1/estudiantes/{id}': {
        patch: {
          tags: ['Estudiantes'],
          summary: 'Editar los datos de identidad de un estudiante',
          description:
            'Requiere el permiso `gestionar-estudiantes`. Edición parcial (al menos un campo). Todo ocurre en una ' +
            'transacción con la fila bloqueada y deja en `activity_logs` los mismos dos registros que Laravel ' +
            '(`estudiante.actualizado` y `estudiante.editado`). Si no cambia ningún campo no se escribe ni se audita. ' +
            'Los campos desconocidos (p. ej. `tenant_id`) se rechazan con 400.',
          operationId: 'actualizarEstudiante',
          parameters: [
            { name: 'id', in: 'path', required: true, schema: { type: 'integer', minimum: 1 }, description: 'Id del estudiante.' },
          ],
          requestBody: { required: true, content: { 'application/json': { schema: cuerpoActualizar } } },
          responses: {
            '200': { description: 'Estudiante tras la edición.', content: { 'application/json': { schema: ref('Estudiante') } } },
            '400': respuestaError('Cuerpo o id inválidos, o campo desconocido.'),
            '401': respuestaError('No autenticado.'),
            '403': respuestaError('Sin el permiso `gestionar-estudiantes`.'),
            '404': respuestaError('No existe, está borrado lógicamente o es de otro colegio (no se distingue).'),
            '409': respuestaError('La cédula ya existe en el colegio.'),
          },
        },
      },
    },
    components: {
      securitySchemes: {
        bearerAuth: {
          type: 'http',
          scheme: 'bearer',
          description: 'Token de Laravel Sanctum con el formato `id|texto` (el mismo que emite POST /api/v1/login de Laravel).',
        },
      },
      schemas: {
        Estudiante: jsonSchema(estudianteDtoSchema),
        EstudiantesPage: jsonSchema(estudiantesPageSchema),
        Health: jsonSchema(healthSchema),
        Error: jsonSchema(errorSchema),
      },
    },
    // Todo es privado por defecto; las rutas públicas lo declaran con `security: []`.
    security: [{ bearerAuth: [] }],
  };
}
