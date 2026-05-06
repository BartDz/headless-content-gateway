<?php

declare(strict_types=1);

namespace App\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Adapter\AdapterRegistry;
use App\Config\CmsConfigLoader;
use App\Locale\LocaleFallbackResolver;
use App\Model\ContentCollection;
use App\Model\ContentQuery;
use App\Transformer\ContentTransformerPipeline;

/** @implements ProviderInterface<ContentCollection> */
class ContentCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly AdapterRegistry $registry,
        private readonly CmsConfigLoader $configLoader,
        private readonly LocaleFallbackResolver $localeResolver,
        private readonly ContentTransformerPipeline $pipeline,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ContentCollection
    {
        $contentType = $uriVariables['contentType'];
        $typeConfig = $this->configLoader->getContentType($contentType);
        $adapter = $this->registry->get($typeConfig->adapter);

        $request = $context['request'] ?? null;
        $locale = $this->localeResolver->resolve($request?->headers->get('Accept-Language') ?? 'en')[0];

        $query = new ContentQuery(
            locale: $locale,
            page: (int) ($request?->query->get('page') ?? 1),
            limit: (int) ($request?->query->get('limit') ?? 10),
        );

        $collection = $adapter->fetchCollection($contentType, $query);

        $request?->attributes->set('_cache_hit', false);

        $transformed = array_map(
            fn ($entry) => $this->pipeline->transform($entry, $typeConfig->transformers),
            $collection->entries,
        );

        return new ContentCollection(
            entries: $transformed,
            total: $collection->total,
            page: $collection->page,
            limit: $collection->limit,
        );
    }
}
