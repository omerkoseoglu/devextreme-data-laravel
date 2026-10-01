<?php

declare(strict_types=1);

namespace DevExtreme\Data\Laravel\Tests\Feature;

use DevExtreme\Data\Laravel\EloquentSource;
use DevExtreme\Data\Laravel\Facades\DevExtreme;
use DevExtreme\Data\Laravel\Tests\Models\Order;
use DevExtreme\Data\Laravel\Tests\TestCase;
use DevExtreme\Data\LoadOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

final class HttpTest extends TestCase
{
    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function defineRoutes($app): void
    {
        Route::get('/orders', static fn () => DevExtreme::response(Order::class));
        Route::post('/orders/search', static fn () => DevExtreme::response(Order::query()->where('shipped', 1)));
        Route::get('/collection', static fn () => DevExtreme::response(collect([['id' => 1], ['id' => 2], ['id' => 3]])));
        Route::get('/array', static fn () => DevExtreme::response([['id' => 1], ['id' => 2]]));
        Route::get('/options', static fn (Request $request) => response()->json(['take' => $request->devExtremeOptions()->take]));
        Route::get('/custom', static fn () => DevExtreme::response(
            EloquentSource::for(Order::class, columns: ['id' => 'id', 'note' => 'note'], primaryKey: ['id']),
        ));
    }

    /**
     * @param array<string, mixed> $params
     */
    private function qs(array $params): string
    {
        return http_build_query(array_map(static fn (mixed $v): string => is_string($v) ? $v : json_encode($v, JSON_THROW_ON_ERROR), $params));
    }

    public function testLoadsLikeTheDevExtremeClientRequests(): void
    {
        $response = $this->getJson('/orders?' . $this->qs([
            'skip' => 2,
            'take' => 3,
            'requireTotalCount' => 'true',
            'sort' => [['selector' => 'id', 'desc' => true]],
            'filter' => [['amount', '>', 10], 'and', ['category', 'Books']],
            'select' => ['id', 'amount'],
        ]));

        $response->assertOk()->assertExactJson([
            'data' => [],
            'totalCount' => 2,
            'groupCount' => -1,
        ]);
    }

    public function testGroupedSummaryResponse(): void
    {
        $this->getJson('/orders?' . $this->qs([
            'group' => [['selector' => 'category', 'isExpanded' => false]],
            'groupSummary' => [['selector' => 'amount', 'summaryType' => 'sum']],
            'totalSummary' => [['selector' => 'amount', 'summaryType' => 'sum']],
            'requireGroupCount' => 'true',
        ]))->assertOk()->assertJson([
            'data' => [
                ['key' => 'Books', 'items' => null, 'count' => 3, 'summary' => [90]],
                ['key' => 'Games', 'items' => null, 'count' => 3, 'summary' => [140]],
                ['key' => 'Music', 'items' => null, 'count' => 2, 'summary' => [50]],
            ],
            'groupCount' => 3,
            'summary' => [280],
        ]);
    }

    public function testPostWithFormAndJsonBodies(): void
    {
        $this->post('/orders/search', ['take' => '2', 'requireTotalCount' => 'true'])
            ->assertOk()->assertJsonPath('totalCount', 5)->assertJsonCount(2, 'data');

        $this->postJson('/orders/search', ['take' => 1, 'filter' => ['category', 'Music'], 'requireTotalCount' => true])
            ->assertOk()->assertJsonPath('totalCount', 1);
    }

    public function testMalformedRequestsAreAnswered400(): void
    {
        $this->getJson('/orders?filter=' . urlencode('[["a"'))->assertStatus(400);
        $this->getJson('/orders?take=many')->assertStatus(400);
        $this->getJson('/orders?' . $this->qs(['filter' => [['qty', 1], 'and', ['qty', 2], 'or', ['qty', 3]]]))->assertStatus(400);
        $this->getJson('/orders?' . $this->qs(['sort' => [['selector' => 'id; DROP TABLE orders']]]))->assertStatus(400);

        self::assertSame(8, Order::count());
    }

    public function testWhitelistedSourceHidesOtherColumns(): void
    {
        $this->getJson('/custom?take=1')->assertOk()->assertExactJson([
            'data' => [['id' => 1, 'note' => null]],
            'totalCount' => -1,
            'groupCount' => -1,
        ]);

        $this->getJson('/custom?' . $this->qs(['filter' => ['amount', '>', 1]]))->assertStatus(400);
    }

    public function testCollectionsAndArrays(): void
    {
        $this->getJson('/collection?' . $this->qs(['filter' => ['id', '>', 1], 'requireTotalCount' => 'true']))
            ->assertOk()->assertJsonPath('totalCount', 2);

        $this->getJson('/array?take=1')->assertOk()->assertJsonCount(1, 'data');
    }

    public function testRequestMacro(): void
    {
        $this->getJson('/options?take=7')->assertOk()->assertJson(['take' => 7]);
    }

    public function testMaxTakeCapsTheResult(): void
    {
        $this->app['config']->set('devextreme-data.max_take', 3);
        $this->app->forgetInstance(\DevExtreme\Data\Laravel\DevExtremeLoader::class);

        $this->getJson('/orders')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/orders?take=100')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/orders?take=2')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/orders?isCountQuery=true')->assertOk()->assertJsonPath('totalCount', 8);
    }

    public function testFacadeLoadReturnsAResultObject(): void
    {
        $result = DevExtreme::load(Order::query(), Request::create('/x', 'GET', ['take' => 2, 'requireTotalCount' => 'true']));

        self::assertSame(8, $result->totalCount);
        self::assertCount(2, $result->data ?? []);
        self::assertInstanceOf(LoadOptions::class, DevExtreme::options(Request::create('/x', 'GET', ['skip' => 4])));
        self::assertInstanceOf(Collection::class, collect());
    }

    public function testUnsupportedSourceFailsLoudly(): void
    {
        $this->expectException(\LogicException::class);

        DevExtreme::source(42);
    }
}
