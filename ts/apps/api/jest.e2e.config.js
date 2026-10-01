/**
 * Pruebas e2e: usan MySQL real (por defecto sge_bench, la copia con volumen).
 * TEST_DATABASE_URL=mysql://root@127.0.0.1:3306/sge_bench
 * Crean sus propios fixtures (tenant/usuario/token temporales) y los borran al terminar.
 */
module.exports = {
  rootDir: '.',
  testEnvironment: 'node',
  moduleFileExtensions: ['js', 'json', 'ts'],
  testRegex: 'test/.*\\.e2e-spec\\.ts$',
  transform: { '^.+\\.ts$': ['ts-jest', { tsconfig: 'tsconfig.json', diagnostics: { ignoreCodes: [151002] } }] },
  // Los e2e hacen decenas de peticiones con el mismo usuario: sin límite salvo en la prueba del limitador, que lo activa.
  setupFiles: ['<rootDir>/test/setup-env.js'],
  testTimeout: 30000,
};
