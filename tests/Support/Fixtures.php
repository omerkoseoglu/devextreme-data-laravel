<?php

declare(strict_types=1);

namespace DevExtreme\Data\Laravel\Tests\Support;

/**
 * A small, hand-checkable dataset shared by all backends.
 */
final class Fixtures
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function orders(): array
    {
        return [
            ['id' => 1, 'customer' => 'Alice', 'category' => 'Books', 'amount' => 10.0, 'qty' => 2, 'ordered_at' => '2024-01-15 10:00:00', 'shipped' => 1, 'note' => null],
            ['id' => 2, 'customer' => 'bob', 'category' => 'Books', 'amount' => 20.0, 'qty' => 1, 'ordered_at' => '2024-02-20 11:30:00', 'shipped' => 1, 'note' => 'gift'],
            ['id' => 3, 'customer' => 'Carol', 'category' => 'Games', 'amount' => 30.0, 'qty' => 3, 'ordered_at' => '2024-02-25 09:15:00', 'shipped' => 0, 'note' => null],
            ['id' => 4, 'customer' => 'alice', 'category' => 'Games', 'amount' => 40.0, 'qty' => 1, 'ordered_at' => '2024-05-05 18:45:00', 'shipped' => 1, 'note' => 'rush'],
            ['id' => 5, 'customer' => 'Dave', 'category' => 'Music', 'amount' => 50.0, 'qty' => 5, 'ordered_at' => '2025-01-10 08:00:00', 'shipped' => 0, 'note' => 'Gift card'],
            ['id' => 6, 'customer' => 'Eve', 'category' => 'Music', 'amount' => null, 'qty' => 2, 'ordered_at' => '2025-03-01 12:00:00', 'shipped' => 1, 'note' => null],
            ['id' => 7, 'customer' => 'Frank', 'category' => 'Books', 'amount' => 60.0, 'qty' => 4, 'ordered_at' => '2025-07-04 14:20:00', 'shipped' => 0, 'note' => '50% off'],
            ['id' => 8, 'customer' => 'Grace', 'category' => 'Games', 'amount' => 70.0, 'qty' => 6, 'ordered_at' => '2025-11-30 23:59:59', 'shipped' => 1, 'note' => 'a_b'],
        ];
    }

    /**
     * @param list<mixed> $rows
     *
     * @return list<int>
     */
    public static function ids(array $rows): array
    {
        return array_map(static fn (mixed $row): int => (int) (is_array($row) ? $row['id'] : $row->id), $rows);
    }
}
