<?php

declare(strict_types=1);

namespace DevExtreme\Data\Laravel\Tests\Feature;

use DevExtreme\Data\ArraySource;
use DevExtreme\Data\Laravel\EloquentSource;
use DevExtreme\Data\Laravel\Tests\Models\Customer;
use DevExtreme\Data\Laravel\Tests\Models\Order;
use DevExtreme\Data\Laravel\Tests\Support\Fixtures;
use DevExtreme\Data\Laravel\Tests\Support\ParityOptions;
use DevExtreme\Data\Laravel\Tests\TestCase;
use DevExtreme\Data\LoadOptions;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;

final class EloquentSourceTest extends TestCase
{
    private const COLUMNS = ['id', 'customer', 'category', 'amount', 'qty', 'ordered_at', 'shipped', 'note'];

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function load(EloquentSource $source, array $options = []): array
    {
        return $source->load(LoadOptions::fromArray($options))->toArray();
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>}>
     */
    public static function parityProvider(): iterable
    {
        yield from ParityOptions::matrix();
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('parityProvider')]
    public function testMatchesTheInMemorySource(array $options): void
    {
        $options += ['primaryKey' => ['id']];

        $expected = (new ArraySource(Fixtures::orders()))->load(LoadOptions::fromArray($options))->toArray();
        $actual = EloquentSource::for(Order::query()->select(self::COLUMNS))->load(LoadOptions::fromArray($options))->toArray();

        self::assertEquals($expected, $actual);
    }

    public function testSoftDeleteScopeIsApplied(): void
    {
        Order::find(3)?->delete();

        $source = EloquentSource::for(Order::class);
        $result = $this->load($source, ['requireTotalCount' => true, 'select' => ['id']]);

        self::assertSame(7, $result['totalCount']);
        self::assertNotContains(['id' => 3], $result['data']);

        $withTrashed = $this->load(EloquentSource::for(Order::withTrashed()), ['requireTotalCount' => true]);
        self::assertSame(8, $withTrashed['totalCount']);
    }

    public function testBuilderConstraintsAndBindingsAreKept(): void
    {
        $source = EloquentSource::for(
            Order::query()->where('category', 'Games')->where('amount', '>', 30)->whereIn('shipped', [0, 1]),
        );

        $result = $this->load($source, ['select' => ['id'], 'sort' => [['selector' => 'id']]]);

        self::assertSame([['id' => 4], ['id' => 8]], $result['data']);
    }

    public function testBindingsSurviveGroupingAndSummaries(): void
    {
        $source = EloquentSource::for(Order::query()->where('shipped', 1)->where('qty', '>=', 2));

        $result = $this->load($source, [
            'group' => [['selector' => 'category', 'isExpanded' => false]],
            'groupSummary' => [['selector' => 'amount', 'summaryType' => 'sum']],
            'totalSummary' => [['selector' => 'amount', 'summaryType' => 'sum']],
            'requireTotalCount' => true,
        ]);

        // shipped=1 and qty>=2: ids 1 (Books 10), 6 (Music, null), 8 (Games 70)
        self::assertSame(3, $result['totalCount']);
        self::assertEquals([80], $result['summary']);
        self::assertSame(['Books', 'Games', 'Music'], array_column($result['data'], 'key'));
    }

    public function testDateBindingsArePrepared(): void
    {
        $source = EloquentSource::for(Order::query()->where('ordered_at', '>=', new \DateTimeImmutable('2025-07-01 00:00:00')));

        $result = $this->load($source, ['select' => ['id'], 'sort' => [['selector' => 'id']]]);

        self::assertSame([['id' => 7], ['id' => 8]], $result['data']);
    }

    public function testRelationSource(): void
    {
        $customer = Customer::findOrFail(2); // even order ids
        $source = EloquentSource::for($customer->orders());

        $result = $this->load($source, ['select' => ['id'], 'requireTotalCount' => true]);

        self::assertSame(4, $result['totalCount']);
        self::assertSame([2, 4, 6, 8], array_column($result['data'], 'id'));
    }

    public function testJoinedColumnsThroughAWhitelist(): void
    {
        $source = EloquentSource::for(
            Order::query()
                ->join('customers', 'customers.id', '=', 'orders.customer_id')
                ->select('orders.id', 'orders.amount', 'customers.name as customer_name'),
            columns: ['id' => 'id', 'amount' => 'amount', 'customer.name' => 'customer_name'],
            primaryKey: ['id'],
        );

        $result = $this->load($source, [
            'filter' => ['customer.name', 'Linus'],
            'select' => ['id', 'customer.name'],
            'take' => 2,
        ]);

        self::assertSame(
            [['id' => 2, 'customer' => ['name' => 'Linus']], ['id' => 4, 'customer' => ['name' => 'Linus']]],
            $result['data'],
        );

        $grouped = $this->load($source, [
            'group' => [['selector' => 'customer.name', 'isExpanded' => false]],
            'groupSummary' => [['selector' => 'amount', 'summaryType' => 'sum']],
        ]);
        self::assertSame(['Ada', 'Linus'], array_column($grouped['data'], 'key'));
        self::assertEquals([[150], [130]], array_column($grouped['data'], 'summary'));

        $this->expectException(\InvalidArgumentException::class);
        $this->load($source, ['filter' => ['customer_name', 'Ada']]); // only whitelisted field names
    }

    public function testRawExpressionColumns(): void
    {
        $source = EloquentSource::for(
            Order::query()->select('id', 'amount', 'qty'),
            columns: ['id' => 'id', 'total' => new Expression('amount * qty')],
            primaryKey: ['id'],
        );

        $result = $this->load($source, ['filter' => ['total', '>=', 300], 'select' => ['id']]);

        // 30*3, 40*1, 50*5, 60*4, 70*6 -> >= 300: 50*5=250 no; 60*4=240 no; 70*6=420 yes
        self::assertSame([['id' => 8]], $result['data']);
    }

    public function testQueryBuilderSource(): void
    {
        $source = EloquentSource::for(DB::table('orders')->select('id', 'qty')->where('qty', '>', 4));

        $result = $this->load($source, ['sort' => [['selector' => 'id']], 'totalSummary' => [['selector' => 'qty', 'summaryType' => 'sum']]]);

        self::assertSame([['id' => 5, 'qty' => 5], ['id' => 8, 'qty' => 6]], $result['data']);
        self::assertEquals([11], $result['summary']);
    }

    public function testInjectionAttemptsAreRejected(): void
    {
        $source = EloquentSource::for(Order::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->load($source, ['filter' => ['id; DROP TABLE orders', 1]]);
    }

    public function testRejectsNonModelClassStrings(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        EloquentSource::for(\stdClass::class);
    }
}
