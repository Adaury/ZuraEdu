import type { ActualizarEstudiante } from './estudiantes.update';

/**
 * Columnas editables de `estudiantes`: nombre en el cuerpo de la API → columna de la base.
 *
 * El ORDEN es el de las columnas de la tabla y es importante: Eloquent lista las columnas modificadas
 * (`getChanges()`, que usa EstudianteObserver::updated para escribir "Campos: a, b, updated_at") en el orden de los
 * atributos del modelo, que es el de la tabla. Mantenerlo igual hace que el registro de auditoría de TypeScript sea
 * idéntico al de Laravel.
 *
 * Quedan fuera a propósito: `foto` (subida de archivos: depende de dónde se guarde), `user_id`, `tenant_id`, `id` y
 * `deleted_at`, que no se pueden tocar desde el cuerpo (el esquema es estricto y los rechaza).
 */
export const COLUMNAS_EDITABLES = [
  ['numeroMatricula', 'numero_matricula'],
  ['cedula', 'cedula'],
  ['nombres', 'nombres'],
  ['apellidos', 'apellidos'],
  ['fechaNacimiento', 'fecha_nacimiento'],
  ['sexo', 'sexo'],
  ['nacionalidad', 'nacionalidad'],
  ['lugarNacimiento', 'lugar_nacimiento'],
  ['telefono', 'telefono'],
  ['email', 'email'],
  ['direccion', 'direccion'],
  ['sector', 'sector'],
  ['municipio', 'municipio'],
  ['provincia', 'provincia'],
  ['estado', 'estado'],
  ['tutorNombre', 'tutor_nombre'],
  ['tutorParentesco', 'tutor_parentesco'],
  ['tutorTelefono', 'tutor_telefono'],
  ['tutorTrabajo', 'tutor_trabajo'],
  ['notasMedicas', 'notas_medicas'],
] as const satisfies ReadonlyArray<readonly [keyof ActualizarEstudiante, string]>;

export type ColumnaEditable = (typeof COLUMNAS_EDITABLES)[number][1];

export const NOMBRES_COLUMNAS_EDITABLES: readonly ColumnaEditable[] = COLUMNAS_EDITABLES.map(([, col]) => col);
