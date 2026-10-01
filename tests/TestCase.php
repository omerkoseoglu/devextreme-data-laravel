<?php

declare(strict_types=1);

namespace DevExtreme\Data\Laravel\Tests;

use DevExtreme\Data\Laravel\DevExtremeDataServiceProvider;
use DevExtreme\Data\Laravel\Tests\Models\Customer;
use DevExtreme\Data\Laravel\Tests\Models\Order;
use DevExtreme\Data\Laravel\Tests\Support\Fixtures;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [DevExtremeDataServiceProvider::class];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function defineEnvironment($app): void
    {
        // DEVEXTREME_TEST_DRIVER=sqlite (default) | mysql | pgsql. The latter two DROP and recreate the
        // "orders" and "customers" tables of the configured database: use a throwaway one.
        $driver = getenv('DEVEXTREME_TEST_DRIVER') ?: 'sqlite';

        $connection = match ($driver) {
            'mysql' => [
                'driver' => 'mysql',
                'host' => getenv('DEVEXTREME_TEST_HOST') ?: '127.0.0.1',
                'port' => getenv('DEVEXTREME_TEST_PORT') ?: '3306',
                'database' => getenv('DEVEXTREME_TEST_DATABASE') ?: 'test',
                'username' => getenv('DEVEXTREME_TEST_USER') ?: 'root',
                'password' => (string) getenv('DEVEXTREME_TEST_PASSWORD'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ],
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => getenv('DEVEXTREME_TEST_HOST') ?: '127.0.0.1',
                'port' => getenv('DEVEXTREME_TEST_PORT') ?: '5432',
                'database' => getenv('DEVEXTREME_TEST_DATABASE') ?: 'test',
                'username' => getenv('DEVEXTREME_TEST_USER') ?: 'postgres',
                'password' => (string) getenv('DEVEXTREME_TEST_PASSWORD'),
                'charset' => 'utf8',
                'prefix' => '',
                'search_path' => 'public',
            ],
            default => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        };

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', $connection);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('orders');
        Schema::dropIfExists('customers');

        Schema::create('customers', static function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('country');
        });

        Schema::create('orders', static function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('customer_id')->nullable();
            $table->string('customer');
            $table->string('category');
            $table->double('amount')->nullable();
            $table->integer('qty');
            $table->dateTime('ordered_at');
            $table->integer('shipped');
            $table->string('note')->nullable();
            $table->softDeletes();
        });

        Customer::insert([
            ['id' => 1, 'name' => 'Ada', 'country' => 'UK'],
            ['id' => 2, 'name' => 'Linus', 'country' => 'FI'],
        ]);

        foreach (Fixtures::orders() as $row) {
            Order::create([...$row, 'customer_id' => $row['id'] % 2 === 0 ? 2 : 1]);
        }
    }
}
