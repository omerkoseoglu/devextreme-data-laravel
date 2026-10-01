# DevExtreme Data for Laravel

> **Unofficial.** This is an independent, community-maintained port. It is not affiliated with, endorsed by or supported by Developer Express Inc. "DevExtreme" and "DevExpress" are trademarks of Developer Express Inc.

Laravel integration for `omerkoseoglu/devextreme-data`: answer DevExtreme widget requests
(`DataGrid`, `PivotGrid`, `SelectBox`, ... with `remoteOperations`) straight from Eloquent models, relations,
query builders, collections or arrays. Filtering, sorting, paging, grouping and summaries run **in the database**.

Requires PHP 8.2+, Laravel 12 or 13. SQLite, MySQL/MariaDB and PostgreSQL connections.

## Install

```bash
composer require omerkoseoglu/devextreme-data-laravel
```

The service provider and the `DevExtreme` facade are auto-discovered. Optional config:

```bash
php artisan vendor:publish --tag=devextreme-data-config
```

## Usage

```php
use DevExtreme\Data\Laravel\Facades\DevExtreme;

Route::get('/api/orders', fn () => DevExtreme::response(Order::class));

// any builder, relation or constraint
Route::get('/api/my-orders', fn (Request $r) =>
    DevExtreme::response($r->user()->orders()->where('status', 'open'))
);
```

```js
const store = DevExpress.data.AspNet.createStore({ key: 'id', loadUrl: '/api/orders' });
new DevExpress.ui.dxDataGrid(el, { dataSource: store, remoteOperations: true });
```

`DevExtreme::load($source)` returns the `LoadResult` object (JSON-serializable) when you need to post-process it.
Sources: model class-string, `Eloquent\Builder`, `Relation`, `Query\Builder`, `Collection`, array, or any
`DevExtreme\Data\Contracts\DataSourceInterface`. Parameters are read from the query string, form or JSON body.

Malformed requests (bad JSON, mixed `and`/`or`, unknown field, ...) become **HTTP 400** automatically.

### How `EloquentSource` works

The builder is compiled to SQL — global scopes (soft deletes, tenancy), constraints and bindings included — and used
as a derived table. DevExtreme operations are applied on top of it by the database. Consequences:

- Your scopes and constraints **always** apply; clients cannot escape them.
- Rows are returned as plain arrays: no model hydration, casts, accessors or `$appends`/`$hidden`.
  Select what you expose (`->select('id', 'total')`).
- By default the model key is used as the stable-sort tie-breaker.

### Exposing only some fields / joins

Pass a whitelist mapping client field names to output columns of your query. Dotted names become nested JSON:

```php
use DevExtreme\Data\Laravel\EloquentSource;

$source = EloquentSource::for(
    Order::query()
        ->join('customers', 'customers.id', '=', 'orders.customer_id')
        ->select('orders.id', 'orders.total', 'customers.name as customer_name'),
    columns: ['id' => 'id', 'total' => 'total', 'customer.name' => 'customer_name'],
);

Route::get('/api/orders', fn () => DevExtreme::response($source));
```

Without `columns`, any plain column name of the query is accepted (identifier-checked, values are always bound).
Use `columns` in production. Values may also be `DB::raw()` expressions: `'total' => DB::raw('amount * qty')`.

### Configuration (`config/devextreme-data.php`)

| Key | Default | Meaning |
|---|---|---|
| `normalize_dates` | `true` | Compare ISO-8601 filter dates as wall-clock time |
| `max_take` | `null` | Cap rows per request (protects against "load everything"). Leave `null` for PivotGrid endpoints |

### Request helper

```php
$options = $request->devExtremeOptions(); // DevExtreme\Data\LoadOptions
```

## Development

```bash
composer install     # uses ../devextreme-php-data as a path repository
composer test
```

Tests run the core package's SQL-vs-memory parity matrix against `EloquentSource`, plus soft deletes, relations,
joins, bindings and HTTP-level behaviour (Orchestra Testbench, SQLite in memory).

MIT licensed.
