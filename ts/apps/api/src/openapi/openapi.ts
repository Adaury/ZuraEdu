import { PER_PAGE_DEFAULT, PER_PAGE_MAX } from '@zuraedu/shared';
import { z } from 'zod';
import { ESTUDIANTE_ESTADOS } from '@zuraedu/shared';
import { actualizarEstudianteSchema, crearEstudianteSchema } from '../estudiantes/estudiantes.update';
import { crearMatriculaSchema } from '../matriculas/matriculas.input';
import { errorSchema, estudianteDtoSchema, estudiantesPageSchema, healthSchema, matriculaDtoSchema } from './respuestas';

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
/** Detalles que zod no traduce solo a JSON Schema (formatos y descripciones). Se aplican igual a crear y editar. */
function refinarCamposEstudiante<T extends Json>(esquema: T): T & { properties: Record<string, Json> } {
  const e = esquema as unknown as T & { properties: Record<string, Json> };
  e.properties.fechaNacimiento = {
    ...e.properties.fechaNacimiento,
    format: 'date',
    description: 'AAAA-MM-DD; debe ser anterior a hoy.',
  };
  e.properties.email = { ...e.properties.email, format: 'email', description: 'null o cadena vacía lo quita.' };
  e.properties.cedula = { ...e.properties.cedula, description: 'null o cadena vacía la quita. Única por colegio.' };
  e.properties.numeroMatricula = {
    ...e.properties.numeroMatricula,
    description: 'Único por colegio. Al crear, si no se envía se genera (AAAA-NNNNN).',
  };
  e.properties.nacionalidad = {
    ...e.properties.nacionalidad,
    description: 'No admite null. Al crear, si no se envía toma el valor por defecto de la columna (Dominicana).',
  };
  return e;
}

export function construirOpenApi(version = '0.1.0'): Json {
  // El "al menos un campo" es un refinamiento de zod y no se traduce solo.
  const cuerpoActualizar = refinarCamposEstudiante({ ...jsonSchema(actualizarEstudianteSchema, 'input'), minProperties: 1 });
  // En crear, `required` (nombres, apellidos, fechaNacimiento, sexo, estado) sí lo genera zod.
  const cuerpoCrear = refinarCamposEstudiante(jsonSchema(crearEstudianteSchema, 'input'));
  const cuerpoMatricula = jsonSchema(crearMatriculaSchema, 'input');

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
      { name: 'Estudiantes', description: 'Consulta, alta, edición y borrado de estudiantes del colegio del usuario autenticado.' },
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
            '429': respuestaError('Demasiadas solicitudes (límite por usuario y minuto). Ver cabeceras `Retry-After` y `X-RateLimit-*`.'),
          },
        },
        post: {
          tags: ['Estudiantes'],
          summary: 'Crear un estudiante',
          description:
            'Requiere el permiso `gestionar-estudiantes`. El colegio sale del token. Obligatorios: `nombres`, `apellidos`, ' +
            '`fechaNacimiento`, `sexo` y `estado`. Si no se envía `numeroMatricula` se genera (AAAA-NNNNN). Deja en ' +
            '`activity_logs` el registro `estudiante.creado`, igual que Laravel. No admite `grupo_id` (matricular es otro ' +
            'módulo) ni `foto`; los campos desconocidos se rechazan con 400.',
          operationId: 'crearEstudiante',
          requestBody: { required: true, content: { 'application/json': { schema: cuerpoCrear } } },
          responses: {
            '201': { description: 'Estudiante creado.', content: { 'application/json': { schema: ref('Estudiante') } } },
            '400': respuestaError('Cuerpo inválido, campos obligatorios ausentes o campo desconocido.'),
            '401': respuestaError('No autenticado.'),
            '403': respuestaError('Sin el permiso `gestionar-estudiantes`.'),
            '429': respuestaError('Demasiadas solicitudes (límite por usuario y minuto). Ver cabeceras `Retry-After` y `X-RateLimit-*`.'),
            '409': respuestaError('La cédula o el número de matrícula ya existen en el colegio.'),
          },
        },
      },
      '/api/v1/estudiantes/{id}': {
        get: {
          tags: ['Estudiantes'],
          summary: 'Obtener un estudiante',
          description:
            'Requiere el permiso `ver-estudiantes`. Un estudiante de otro colegio o borrado lógicamente responde 404 (no se ' +
            'distingue de uno que no existe).',
          operationId: 'obtenerEstudiante',
          parameters: [
            { name: 'id', in: 'path', required: true, schema: { type: 'integer', minimum: 1 }, description: 'Id del estudiante.' },
          ],
          responses: {
            '200': { description: 'El estudiante.', content: { 'application/json': { schema: ref('Estudiante') } } },
            '400': respuestaError('Id inválido.'),
            '401': respuestaError('No autenticado.'),
            '403': respuestaError('Sin el permiso `ver-estudiantes`.'),
            '404': respuestaError('No existe, está borrado o es de otro colegio (no se distingue).'),
            '429': respuestaError('Demasiadas solicitudes (límite por usuario y minuto). Ver cabeceras `Retry-After` y `X-RateLimit-*`.'),
          },
        },
        patch: {
          tags: ['Estudiantes'],
          summary: 'Editar los datos de un estudiante',
          description:
            'Requiere el permiso `gestionar-estudiantes`. Edición parcial (al menos un campo). Todo ocurre en una ' +
            'transacción con la fila bloqueada. Auditoría igual que Laravel: `estudiante.actualizado` siempre que cambie ' +
            'alguna columna y `estudiante.editado` solo si cambia un campo sensible (cédula, nombres, apellidos, fecha de ' +
            'nacimiento, estado). Si no cambia ningún campo no se escribe ni se audita. Los campos desconocidos (p. ej. ' +
            '`tenant_id`) se rechazan con 400.',
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
            '429': respuestaError('Demasiadas solicitudes (límite por usuario y minuto). Ver cabeceras `Retry-After` y `X-RateLimit-*`.'),
            '404': respuestaError('No existe, está borrado lógicamente o es de otro colegio (no se distingue).'),
            '409': respuestaError('La cédula o el número de matrícula ya existen en el colegio.'),
          },
        },
        delete: {
          tags: ['Estudiantes'],
          summary: 'Borrar un estudiante (borrado lógico)',
          description:
            'Requiere el permiso `gestionar-estudiantes`. Borrado lógico, como Laravel: el estudiante deja de aparecer ' +
            'pero se conserva. Deja en `activity_logs` el registro `estudiante.eliminado`. Como Laravel, ' +
            'además borra el archivo de la foto del disco público, pero solo si la API tiene configurada esa carpeta ' +
            '(`FOTOS_PUBLICAS_DIR`); sin ella el archivo queda en el disco de Laravel.',
          operationId: 'eliminarEstudiante',
          parameters: [
            { name: 'id', in: 'path', required: true, schema: { type: 'integer', minimum: 1 }, description: 'Id del estudiante.' },
          ],
          responses: {
            '204': { description: 'Borrado. Sin cuerpo.' },
            '400': respuestaError('Id inválido.'),
            '401': respuestaError('No autenticado.'),
            '403': respuestaError('Sin el permiso `gestionar-estudiantes`.'),
            '429': respuestaError('Demasiadas solicitudes (límite por usuario y minuto). Ver cabeceras `Retry-After` y `X-RateLimit-*`.'),
            '404': respuestaError('No existe, ya estaba borrado o es de otro colegio (no se distingue).'),
          },
        },
      },
      '/api/v1/matriculas': {
        post: {
          tags: ['Matrículas'],
          summary: 'Matricular a un estudiante en un grupo',
          description:
            'Requiere el permiso `gestionar-matriculas`. El colegio sale del token; el estado (`activa`) y el `numeroOrden` los ' +
            'decide el servidor. Todo ocurre en una transacción que bloquea el grupo: comprueba el cupo (solo cuentan las ' +
            'matrículas `activa`), calcula el orden (cuenta todas las del grupo) y crea la matrícula. Después de confirmar emite ' +
            '`dashboard.updated` por Reverb y notifica «Matrícula confirmada» al estudiante y a sus representantes (in-app, ' +
            'push y tiempo real); esos efectos nunca hacen fallar la matrícula. Deja en `activity_logs` el registro ' +
            '`matricula.creada` (Laravel no audita el alta). Más estricto que Laravel: año escolar, estudiante y grupo deben ' +
            'existir en este colegio (no borrados) y el grupo debe ser del año indicado.',
          operationId: 'crearMatricula',
          requestBody: { required: true, content: { 'application/json': { schema: cuerpoMatricula } } },
          responses: {
            '201': { description: 'Matrícula creada.', content: { 'application/json': { schema: ref('Matricula') } } },
            '400': respuestaError('Cuerpo inválido, campos obligatorios ausentes o campo desconocido.'),
            '401': respuestaError('No autenticado.'),
            '403': respuestaError('Sin el permiso `gestionar-matriculas`.'),
            '409': respuestaError('El estudiante ya está matriculado en ese año escolar, o el grupo no tiene cupo.'),
            '422': respuestaError('El año escolar, el estudiante o el grupo no existen en este colegio, o el grupo es de otro año.'),
            '429': respuestaError('Demasiadas solicitudes (límite por usuario y minuto). Ver cabeceras `Retry-After` y `X-RateLimit-*`.'),
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
        Matricula: jsonSchema(matriculaDtoSchema),
        EstudiantesPage: jsonSchema(estudiantesPageSchema),
        Health: jsonSchema(healthSchema),
        Error: jsonSchema(errorSchema),
      },
    },
    // Todo es privado por defecto; las rutas públicas lo declaran con `security: []`.
    security: [{ bearerAuth: [] }],
  };
}
