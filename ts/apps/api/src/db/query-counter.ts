/**
 * Cuenta las consultas ejecutadas por las conexiones de Kysely. Sirve para observabilidad básica
 * y para que las pruebas afirmen "esta petición hizo N consultas" (p. ej. que la caché funciona).
 */
export class QueryCounter {
  total = 0;
}

export const QUERY_COUNTER = Symbol('QUERY_COUNTER');
