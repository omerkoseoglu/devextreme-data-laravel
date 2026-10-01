<?php

declare(strict_types=1);

namespace DevExtreme\Data\Laravel;

use DevExtreme\Data\Contracts\DataSourceInterface;
use DevExtreme\Data\LoadOptions;
use DevExtreme\Data\LoadResult;
use DevExtreme\Data\PdoSource;
use DevExtreme\Data\Sql\Dialect;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use InvalidArgumentException;

/**
 * A DevExtreme data source over an Eloquent builder, query builder, relation or model class.
 *
 * The builder is compiled to SQL (global scopes such as soft deletes included, bindings preserved) and used as
 * a derived table; DevExtreme filtering, sorting, paging, grouping and summaries are then executed by the
 * database on top of it. Rows come back as plain arrays, not hydrated models (no casts/accessors/appends).
 *
 *     EloquentSource::for(Order::query()->where('tenant_id', 7)->select('id', 'total', 'created_at'));
 *
 * With joins, select the columns explicitly (derived tables need unique column names) and expose them through `$columns`:
 *
 *     EloquentSource::for(
 *         Order::query()->join('customers', 'customers.id', '=', 'orders.customer_id')
 *             ->select('orders.id', 'orders.total', 'customers.name as customer_name'),
 *         columns: ['id' => 'id', 'total' => 'total', 'customer.name' => 'customer_name'],
 *     );
 */
final class EloquentSource implements DataSourceInterface
{
    private function __construct(private readonly PdoSource $inner)
    {
    }

    /**
     * @param EloquentBuilder<Model>|QueryBuilder|Relation<Model, Model, mixed>|class-string<Model> $query
     * @param array<string, string|Expression>|null $columns Whitelist: client field => output column of the query
     *                                                        (or a raw Expression). Null accepts any plain column name.
     * @param list<string>|null $primaryKey                   Defaults to the model's key. Pass [] to disable the key tie-break.
     */
    public static function for(
        EloquentBuilder|QueryBuilder|Relation|string $query,
        ?array $columns = null,
        ?array $primaryKey = null,
        bool $normalizeDates = true,
    ): self {
        if (is_string($query)) {
            if (!is_subclass_of($query, Model::class)) {
                throw new InvalidArgumentException(sprintf('"%s" is not an Eloquent model class.', $query));
            }

            $query = $query::query();
        }

        if ($query instanceof Relation) {
            $query = $query->getQuery();
        }

        if ($query instanceof EloquentBuilder) {
            $primaryKey ??= [$query->getModel()->getKeyName()];
            $base = $query->toBase(); // applies global scopes on a clone
        } else {
            $primaryKey ??= [];
            $base = clone $query;
        }

        $connection = $base->getConnection();
        $pdo = $connection->getReadPdo();
        $grammar = $base->getGrammar();
        $dialect = Dialect::fromPdo($pdo);

        $map = null;
        if ($columns !== null) {
            $map = [];
            foreach ($columns as $field => $column) {
                $map[$field] = $column instanceof Expression
                    ? (string) $column->getValue($grammar)
                    : $dialect->quoteIdentifier($column);
            }
        }

        $from = sprintf('(%s) AS %s', $base->toSql(), $grammar->wrap('devextreme_source'));

        return new self(new PdoSource(
            $pdo,
            $from,
            columns: $map,
            primaryKey: $primaryKey,
            dialect: $dialect,
            rawFrom: true,
            normalizeDates: $normalizeDates,
            fromParams: array_values($connection->prepareBindings($base->getBindings())),
        ));
    }

    public function load(LoadOptions $options): LoadResult
    {
        return $this->inner->load($options);
    }
}
