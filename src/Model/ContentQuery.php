<?php

declare(strict_types=1);

namespace App\Model;

class ContentQuery
{
    public function __construct(
        public readonly string $locale = 'en',
        public readonly int $page = 1,
        public readonly int $limit = 10,
        public readonly array $filters = [],
    ) {}
}
