import { DummyDriver, Kysely, MysqlAdapter, MysqlIntrospector, MysqlQueryCompiler } from 'kysely';
import type { DB } from '../db/db';
import { TenantContextMissingError, TenantScopePlugin } from '../db/tenant-scope.plugin';
import { consultaPagina, consultaTotal, ESTUDIANTES_LISTADO_IDX } from './estudiantes.queries';
import { EstudiantesQuery } from './estudiantes.query';

let tenant: number | undefined = 7;
const db = new Kysely<DB>({
  dialect: {
    createAdapter: () => new MysqlAdapter(),
    createDriver: () => new DummyDriver(),
    createIntrospector: (d) => new MysqlIntrospector(d),
    createQueryCompiler: () => new MysqlQueryCompiler(),
  },
  plugins: [new TenantScopePlugin(new Set(['estudiantes']), () => tenant)],
});

const base: EstudiantesQuery = { page: 1, perPage: 30, q: undefined, estado: undefined };
const HINT = `/*+ INDEX(estudiantes ${ESTUDIANTES_LISTADO_IDX}) */`;

describe('consultaTotal', () => {
  beforeEach(() => (tenant = 7));

  it('sin filtros usa el hint del índice del listado, justo después del SELECT', () => {
    const { sql } = consultaTotal(db as never, base).compile();
    expect(sql).toContain(`select ${HINT} count(*) as \`total\``);
  });

  it('el hint NO rompe el filtro de tenant ni el de borrado lógico', () => {
    const { sql, parameters } = consultaTotal(db as never, base).compile();
    expect(sql).toContain('`estudiantes`.`tenant_id` = ?');
    expect(sql).toContain('`estudiantes`.`deleted_at` is null');
    expect(parameters).toEqual([7]);
  });

  it('con búsqueda de texto NO usa el hint (el índice no cubre ese filtro)', () => {
    const { sql } = consultaTotal(db as never, { ...base, q: 'perez' }).compile();
    expect(sql).not.toContain('/*+');
    expect(sql).toContain('like');
  });

  it('con filtro de estado NO usa el hint', () => {
    expect(consultaTotal(db as never, { ...base, estado: 'activo' }).compile().sql).not.toContain('/*+');
  });

  it('el nombre del índice es una constante, nunca algo que controle el cliente', () => {
    // Una búsqueda con texto de hint no debe colarse en el SQL como hint.
    const { sql, parameters } = consultaTotal(db as never, { ...base, q: '*/ INDEX(x y) /*' }).compile();
    expect(sql).not.toContain('INDEX(');
    expect(parameters).toContain('%*/ INDEX(x y) /*%');
  });

  it('sigue fallando cerrado sin tenant en contexto', () => {
    tenant = undefined;
    expect(() => consultaTotal(db as never, base).compile()).toThrow(TenantContextMissingError);
  });
});

describe('consultaPagina', () => {
  beforeEach(() => (tenant = 7));

  it('filtra por tenant, excluye borrados y ordena por apellidos, nombres, id', () => {
    const { sql, parameters } = consultaPagina(db as never, base).compile();
    expect(sql).toContain('`estudiantes`.`tenant_id` = ?');
    expect(sql).toContain('`estudiantes`.`deleted_at` is null');
    expect(sql).toMatch(/order by `estudiantes`\.`apellidos`, `estudiantes`\.`nombres`, `estudiantes`\.`id`/);
    expect(parameters[0]).toBe(7);
  });

  it('calcula el offset de la página', () => {
    const { sql, parameters } = consultaPagina(db as never, { ...base, page: 3, perPage: 20 }).compile();
    expect(sql).toMatch(/limit \? offset \?/);
    expect(parameters.slice(-2)).toEqual([20, 40]);
  });

  it('no lleva hint (el listado ya usa el índice por su orden)', () => {
    expect(consultaPagina(db as never, base).compile().sql).not.toContain('/*+');
  });
});
