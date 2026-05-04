<?php

declare(strict_types=1);

namespace App\Model;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Api\Provider\ContentItemProvider;
use App\Api\Provider\ContentCollectionProvider;

#[ApiResource(
    shortName: 'Content',
    operations: [
        new Get(
            uriTemplate: '/content/{contentType}/{slug}',
            provider: ContentItemProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/content/{contentType}',
            provider: ContentCollectionProvider::class,
        ),
    ]
)]
class ContentEntry
{
    public function __construct(
        public readonly string $id,
        public readonly string $contentType,
        public readonly string $slug,
        public readonly string $locale,
        public readonly ?\DateTimeImmutable $publishedAt,
        public readonly array $fields,
    ) {}
}
