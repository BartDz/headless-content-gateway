<?php

declare(strict_types=1);

namespace App\Model;

class ContentCollection
{
    /** @param ContentEntry[] $entries */
    public function __construct(
        public readonly array $entries,
        public readonly int $total,
        public readonly int $page,
        public readonly int $limit,
    ) {
    }
}
