<?php

declare(strict_types=1);

namespace DevExtreme\Data\Laravel\Tests\Support;

/**
 * The option matrix of the core package's SQL-vs-memory parity test.
 */
final class ParityOptions
{
    /**
     * String ordering follows the database collation (case-sensitive in SQLite by default) while the
     * in-memory source compares case-insensitively, so cases here sort on numeric/date columns.
     *
     * @return iterable<string, array{0: array<string, mixed>}>
     */
    public static function matrix(): iterable
    {
        $sum = ['selector' => 'amount', 'summaryType' => 'sum'];
        $avg = ['selector' => 'amount', 'summaryType' => 'avg'];
        $min = ['selector' => 'amount', 'summaryType' => 'min'];
        $max = ['selector' => 'qty', 'summaryType' => 'max'];
        $count = ['summaryType' => 'count'];
        $all = [$sum, $avg, $min, $max, $count];

        yield 'plain' => [[]];
        yield 'paging' => [['skip' => 3, 'take' => 2, 'requireTotalCount' => true]];
        yield 'filter + sort + paging' => [[
            'filter' => [['amount', '>', 15], 'and', [['category', 'Books'], 'or', ['category', 'Games']]],
            'sort' => [['selector' => 'qty', 'desc' => true], ['selector' => 'id']],
            'skip' => 1,
            'take' => 3,
            'requireTotalCount' => true,
        ]];
        yield 'not / null filters' => [['filter' => [['!', ['note', 'contains', 'gift']], 'and', ['amount', '<>', 30]]]];
        yield 'select' => [['select' => ['id', 'amount'], 'sort' => [['selector' => 'amount']], 'take' => 4]];
        yield 'total summary + paging' => [['totalSummary' => $all, 'take' => 3, 'skip' => 1, 'requireTotalCount' => true]];
        yield 'total summary + filter' => [['totalSummary' => $all, 'filter' => ['shipped', 1]]];
        yield 'summary query' => [['isSummaryQuery' => true, 'totalSummary' => $all]];
        yield 'count query' => [['isCountQuery' => true, 'filter' => ['qty', '>=', 2]]];
        yield 'expanded 1 level' => [['group' => [['selector' => 'category']], 'groupSummary' => $all, 'totalSummary' => $all, 'requireTotalCount' => true, 'requireGroupCount' => true]];
        yield 'expanded 2 levels desc' => [['group' => [['selector' => 'category', 'desc' => true], ['selector' => 'shipped']], 'groupSummary' => [$sum, $count]]];
        yield 'expanded paging' => [['group' => [['selector' => 'category']], 'skip' => 1, 'take' => 2, 'requireGroupCount' => true, 'requireTotalCount' => true]];
        yield 'collapsed 1 level' => [['group' => [['selector' => 'category', 'isExpanded' => false]], 'groupSummary' => $all, 'totalSummary' => $all, 'requireTotalCount' => true, 'requireGroupCount' => true]];
        yield 'collapsed 2 levels' => [['group' => [['selector' => 'category'], ['selector' => 'shipped', 'isExpanded' => false]], 'groupSummary' => $all, 'totalSummary' => [$sum]]];
        yield 'collapsed 3 levels' => [['group' => [['selector' => 'category'], ['selector' => 'shipped'], ['selector' => 'qty', 'isExpanded' => false]], 'groupSummary' => [$sum]]];
        yield 'collapsed + filter' => [['filter' => ['amount', '>', 15], 'group' => [['selector' => 'shipped', 'isExpanded' => false]], 'groupSummary' => [$avg]]];
        yield 'collapsed remote paging' => [['group' => [['selector' => 'category', 'isExpanded' => false]], 'skip' => 1, 'take' => 1, 'requireTotalCount' => true, 'requireGroupCount' => true, 'totalSummary' => [$sum]]];
        yield 'collapsed paging no summary' => [['group' => [['selector' => 'category', 'isExpanded' => false]], 'take' => 2, 'requireTotalCount' => true, 'requireGroupCount' => true]];
        yield 'collapsed 2 levels paging' => [['group' => [['selector' => 'category'], ['selector' => 'shipped', 'isExpanded' => false]], 'skip' => 1, 'take' => 1]];
        yield 'year/month' => [['group' => [['selector' => 'ordered_at', 'groupInterval' => 'year'], ['selector' => 'ordered_at', 'groupInterval' => 'quarter', 'isExpanded' => false]], 'groupSummary' => [$sum]]];
        yield 'month expanded' => [['group' => [['selector' => 'ordered_at', 'groupInterval' => 'month']]]];
        yield 'day of week' => [['group' => [['selector' => 'ordered_at', 'groupInterval' => 'dayOfWeek', 'desc' => true, 'isExpanded' => false]]]];
        yield 'numeric interval' => [['group' => [['selector' => 'amount', 'groupInterval' => 25, 'isExpanded' => false]], 'groupSummary' => [$count]]];
        yield 'numeric interval expanded' => [['group' => [['selector' => 'qty', 'groupInterval' => 2]]]];
        yield 'group by nullable' => [['group' => [['selector' => 'amount', 'desc' => true, 'isExpanded' => false]]]];
        yield 'group sorted by other field' => [['group' => [['selector' => 'category']], 'sort' => [['selector' => 'amount', 'desc' => true]]]];
        yield 'summary only count with group' => [['group' => [['selector' => 'category', 'isExpanded' => false]], 'totalSummary' => [$count]]];
        yield 'paginate via key' => [['paginateViaPrimaryKey' => true, 'sort' => [['selector' => 'qty', 'desc' => true]], 'skip' => 2, 'take' => 3, 'requireTotalCount' => true]];
    }
}
