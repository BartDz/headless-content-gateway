<?php

declare(strict_types=1);

namespace App\Cache;

use App\Model\ContentEntry;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

class ContentCacheManager
{
    private bool $lastHit = false;

    public function __construct(
        private readonly TagAwareAdapterInterface $cache,
    ) {
    }

    /**
     * @param callable(): ContentEntry $fetcher
     */
    public function get(
        string $adapter,
        string $type,
        string $slug,
        string $locale,
        callable $fetcher,
        int $ttl,
    ): ContentEntry {
        $key = "content.$adapter.$type.$slug.$locale";
        $tag = "content.$adapter.$type";

        // Use getItem() to detect hit vs miss
        $item = $this->cache->getItem($key);
        $this->lastHit = $item->isHit();

        if (!$item->isHit()) {
            $entry = $fetcher();
            $item->set($entry);
            $item->expiresAfter($ttl);
            $item->tag([$tag]);
            $this->cache->save($item);

            return $entry;
        }

        return $item->get();
    }

    public function invalidateByTag(string $adapter, string $type): void
    {
        $this->cache->invalidateTags(["content.$adapter.$type"]);
    }

    public function wasLastRequestCacheHit(): bool
    {
        return $this->lastHit;
    }
}
