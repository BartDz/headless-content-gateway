<?php

declare(strict_types=1);

namespace App\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Adapter\AdapterRegistry;
use App\Adapter\Exception\ContentNotFoundException;
use App\Cache\ContentCacheManager;
use App\Config\CmsConfigLoader;
use App\Locale\LocaleFallbackResolver;
use App\Model\ContentEntry;
use App\Transformer\ContentTransformerPipeline;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<ContentEntry> */
class ContentItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly AdapterRegistry $registry,
        private readonly ContentCacheManager $cache,
        private readonly CmsConfigLoader $configLoader,
        private readonly LocaleFallbackResolver $localeResolver,
        private readonly ContentTransformerPipeline $pipeline,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?ContentEntry
    {
        $contentType = $uriVariables['contentType'];
        $slug = $uriVariables['slug'];

        $typeConfig = $this->configLoader->getContentType($contentType);
        $adapter = $this->registry->get($typeConfig->adapter);

        $request = $context['request'] ?? null;
        $locales = $this->localeResolver->resolve($request?->headers->get('Accept-Language') ?? 'en');

        foreach ($locales as $locale) {
            try {
                $entry = $this->cache->get(
                    $typeConfig->adapter,
                    $contentType,
                    $slug,
                    $locale,
                    fn () => $adapter->fetchEntry($contentType, $slug, $locale),
                    $typeConfig->cacheTtl,
                );
                $request?->attributes->set('_cache_hit', $this->cache->wasLastRequestCacheHit());

                return $this->pipeline->transform($entry, $typeConfig->transformers);
            } catch (ContentNotFoundException) {
                continue;
            }
        }

        throw new NotFoundHttpException("Content not found: $contentType/$slug");
    }
}
