<?php

declare(strict_types=1);

namespace App\Config;

class ContentTypeConfig
{
    public function __construct(
        public readonly string $name,
        public readonly string $adapter,
        public readonly int $cacheTtl,
        public readonly array $transformers,
        public readonly array $fieldMap,
    ) {
    }
}
