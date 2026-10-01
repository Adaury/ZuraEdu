/** Misma forma que el /health de Laravel, para poder reutilizar el monitoreo existente. */
export interface HealthResponse {
  status: 'ok' | 'degraded';
  checks: Record<string, string>;
  version: string;
  runtime: 'node';
}
