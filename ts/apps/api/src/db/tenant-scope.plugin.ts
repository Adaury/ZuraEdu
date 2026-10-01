import {
  AliasNode,
  BinaryOperationNode,
  ColumnNode,
  DeleteQueryNode,
  IdentifierNode,
  InsertQueryNode,
  JoinNode,
  KyselyPlugin,
  OperationNodeTransformer,
  OperationNode,
  OperatorNode,
  PluginTransformQueryArgs,
  PluginTransformResultArgs,
  QueryResult,
  QueryId,
  ReferenceNode,
  RootOperationNode,
  SelectQueryNode,
  TableNode,
  UnknownRow,
  UpdateQueryNode,
  ValueNode,
  WhereNode,
} from 'kysely';

/** Se lanza cuando se consulta una tabla de tenant sin tenant en contexto (falla cerrado). */
export class TenantContextMissingError extends Error {
  constructor(tabla: string) {
    super(
      `Consulta a la tabla "${tabla}" (con tenant_id) fuera de un contexto de tenant. ` +
        'Usa la conexión de sistema (SYSTEM_DB) solo para autenticación y tareas de plataforma.',
    );
    this.name = 'TenantContextMissingError';
  }
}

/** Se lanza si un INSERT a una tabla de tenant no trae tenant_id: nunca se adivina. */
export class TenantInsertWithoutTenantError extends Error {
  constructor(tabla: string) {
    super(`INSERT en "${tabla}" sin la columna tenant_id.`);
    this.name = 'TenantInsertWithoutTenantError';
  }
}

/**
 * Equivalente en TypeScript de `BelongsToTenant` (global scope de Laravel), pero más estricto:
 *
 *  - SELECT / UPDATE / DELETE sobre una tabla con tenant_id reciben `AND <tabla>.tenant_id = ?`.
 *  - Se aplica también a JOINs (en el ON, para no convertir un LEFT JOIN en INNER) y a
 *    subconsultas, porque el transformador recorre todo el árbol de la consulta.
 *  - Sin tenant en contexto, cualquier consulta a una tabla de tenant LANZA (fail closed).
 *  - Un INSERT sin tenant_id en una tabla de tenant LANZA.
 *
 * El tenant sale de un getter (el contexto de la petición), nunca de un parámetro del cliente.
 */
export class TenantScopePlugin implements KyselyPlugin {
  private readonly transformer: TenantScopeTransformer;

  constructor(tenantTables: ReadonlySet<string>, getTenantId: () => number | undefined) {
    this.transformer = new TenantScopeTransformer(tenantTables, getTenantId);
  }

  transformQuery(args: PluginTransformQueryArgs): RootOperationNode {
    return this.transformer.transformNode(args.node, args.queryId);
  }

  async transformResult(args: PluginTransformResultArgs): Promise<QueryResult<UnknownRow>> {
    return args.result;
  }
}

class TenantScopeTransformer extends OperationNodeTransformer {
  /**
   * Nodos que ya salieron filtrados. Kysely aplica los plugins al convertir un builder en subconsulta
   * (toOperationNode) y luego otra vez al compilar la consulta externa: sin esto el filtro se duplicaría.
   */
  private readonly filtrados = new WeakSet<object>();

  constructor(
    private readonly tenantTables: ReadonlySet<string>,
    private readonly getTenantId: () => number | undefined,
  ) {
    super();
  }

  protected override transformSelectQuery(node: SelectQueryNode, queryId?: QueryId): SelectQueryNode {
    if (this.filtrados.has(node)) return node;
    // El orden importa: primero se transforman los hijos (subconsultas) y luego se agrega el filtro propio.
    const base = super.transformSelectQuery(node, queryId);

    let result = base;
    for (const ref of this.tablesIn(node.from?.froms ?? [])) {
      result = this.conWhere(result, this.condition(ref));
    }

    if (node.joins?.length) {
      result = {
        ...result,
        joins: result.joins?.map((join, i) => this.scopeJoin(join, node.joins![i])),
      };
    }
    this.filtrados.add(result);
    return result;
  }

  protected override transformUpdateQuery(node: UpdateQueryNode, queryId?: QueryId): UpdateQueryNode {
    if (this.filtrados.has(node)) return node;
    const base = super.transformUpdateQuery(node, queryId);
    let result = base;
    for (const ref of this.tablesIn(node.table ? [node.table] : [])) {
      result = this.conWhere(result, this.condition(ref));
    }
    this.filtrados.add(result);
    return result;
  }

  protected override transformDeleteQuery(node: DeleteQueryNode, queryId?: QueryId): DeleteQueryNode {
    if (this.filtrados.has(node)) return node;
    const base = super.transformDeleteQuery(node, queryId);
    let result = base;
    for (const ref of this.tablesIn(node.from.froms)) {
      result = this.conWhere(result, this.condition(ref));
    }
    this.filtrados.add(result);
    return result;
  }

  protected override transformInsertQuery(node: InsertQueryNode, queryId?: QueryId): InsertQueryNode {
    const tabla = this.tableName(node.into);
    if (tabla && this.tenantTables.has(tabla)) {
      const columnas = node.columns?.map((c) => c.column.name) ?? [];
      if (!columnas.includes('tenant_id')) {
        throw new TenantInsertWithoutTenantError(tabla);
      }
    }
    return super.transformInsertQuery(node, queryId);
  }

  // ── helpers ─────────────────────────────────────────────────────────────

  /** Devuelve {tabla, referencia} de cada TableNode (con o sin alias) que sea tabla de tenant. */
  private tablesIn(nodes: readonly unknown[]): Array<{ tabla: string; referencia: string }> {
    const salida: Array<{ tabla: string; referencia: string }> = [];
    for (const n of nodes) {
      const ref = this.resolveTable(n);
      if (ref && this.tenantTables.has(ref.tabla)) salida.push(ref);
    }
    return salida;
  }

  private resolveTable(n: unknown): { tabla: string; referencia: string } | undefined {
    if (TableNode.is(n as TableNode)) {
      const tabla = this.tableName(n as TableNode)!;
      return { tabla, referencia: tabla };
    }
    if (AliasNode.is(n as AliasNode)) {
      const alias = n as AliasNode;
      if (TableNode.is(alias.node as TableNode)) {
        const tabla = this.tableName(alias.node as TableNode)!;
        const referencia = IdentifierNode.is(alias.alias) ? alias.alias.name : tabla;
        return { tabla, referencia };
      }
    }
    return undefined;
  }

  private tableName(node: unknown): string | undefined {
    if (!node || !TableNode.is(node as TableNode)) return undefined;
    return (node as TableNode).table.identifier.name;
  }

  /** Agrega `AND cond` al WHERE existente (o lo crea). Solo API pública de Kysely. */
  private conWhere<T extends { readonly where?: WhereNode }>(node: T, cond: OperationNode): T {
    return { ...node, where: node.where ? WhereNode.cloneWithOperation(node.where, 'And', cond) : WhereNode.create(cond) };
  }

  private scopeJoin(join: JoinNode, original: JoinNode): JoinNode {
    const ref = this.resolveTable(original.table);
    if (!ref || !this.tenantTables.has(ref.tabla)) return join;

    const cond = this.condition(ref);
    return JoinNode.cloneWithOn(join, cond);
  }

  private condition(ref: { tabla: string; referencia: string }) {
    const tenantId = this.getTenantId();
    if (tenantId === undefined || tenantId === null) {
      throw new TenantContextMissingError(ref.tabla);
    }
    return BinaryOperationNode.create(
      ReferenceNode.create(ColumnNode.create('tenant_id'), TableNode.create(ref.referencia)),
      OperatorNode.create('='),
      ValueNode.create(tenantId),
    );
  }
}
