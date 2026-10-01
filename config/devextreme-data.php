<?php

declare(strict_types=1);

return [
    /*
     | Rewrite ISO-8601 date-time filter values ("2024-05-01T10:00:00.000Z") to "2024-05-01 10:00:00"
     | before they are bound to SQL (wall-clock comparison, no timezone conversion).
     */
    'normalize_dates' => true,

    /*
     | Upper bound for "take". A request without "take" (or with a larger one) is limited to this many rows,
     | which protects the server from "load the whole table" requests. null = unlimited.
     |
     | Count/summary queries are not affected. Note that grouped requests page over top-level groups, so a
     | cap that is too small also truncates a PivotGrid; leave it null for endpoints that feed one.
     */
    'max_take' => null,
];
