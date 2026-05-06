<?php

declare(strict_types=1);

namespace App\Model;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Api\Provider\ContentCollectionProvider;
use App\Api\Provider\ContentItemProvider;

#[ApiResource(
    shortName: 'Content',
    operations: [
        new Get(
            uriTemplate: '/content/{contentType}/{slug}',
            uriVariables: [
                'contentType' => new Link(fromClass: ContentEntry::class, identifiers: ['contentType']),
                'slug' => new Link(fromClass: ContentEntry::class, identifiers: ['slug']),
            ],
            provider: ContentItemProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/content/{contentType}',
            uriVariables: [
                'contentType' => new Link(fromClass: ContentEntry::class, identifiers: ['contentType']),
            ],
            provider: ContentCollectionProvider::class,
        ),
    ]
)]
class ContentEntry
{
    public function __construct(
        #[ApiProperty(identifier: false)]
        public readonly string $id,
        #[ApiProperty(identifier: true)]
        public readonly string $contentType,
        #[ApiProperty(identifier: true)]
        public readonly string $slug,
        public readonly string $locale,
        public readonly ?\DateTimeImmutable $publishedAt,
        public readonly array $fields,
    ) {
    }
}
