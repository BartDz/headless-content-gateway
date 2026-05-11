<?php

declare(strict_types=1);

use App\Adapter\AdapterRegistry;
use App\Adapter\Exception\ContentNotFoundException;
use App\Adapter\CmsAdapterInterface;
use App\Model\ContentEntry;
use App\Model\ContentCollection;
use App\Model\ContentQuery;

function makeAdapter(string $name, bool $throws = false): CmsAdapterInterface
{
    return new class($name, $throws) implements CmsAdapterInterface {
        public function __construct(
            private string $adapterName,
            private bool $throws,
        ) {}

        public function supports(string $adapterName): bool
        {
            return $adapterName === $this->adapterName;
        }

        public function fetchEntry(string $type, string $slug, string $locale): ContentEntry
        {
            if ($this->throws) {
                throw new ContentNotFoundException("not found in $this->adapterName");
            }
            return new ContentEntry(
                id: '1',
                contentType: $type,
                slug: $slug,
                locale: $locale,
                publishedAt: null,
                fields: ['source' => $this->adapterName],
            );
        }

        public function fetchCollection(string $type, ContentQuery $query): ContentCollection
        {
            if ($this->throws) {
                throw new ContentNotFoundException("not found in $this->adapterName");
            }
            return new ContentCollection(entries: [], total: 0, page: 1, limit: 10);
        }
    };
}

it('returns entry from primary adapter when it succeeds', function () {
    $primary = makeAdapter('wordpress', throws: false);
    $fallback = makeAdapter('storyblok', throws: false);

    $registry = new AdapterRegistry([$primary, $fallback]);
    $entry = $registry->get('wordpress')->fetchEntry('article', 'hello', 'en');

    expect($entry->fields['source'])->toBe('wordpress');
});

it('registry throws AdapterNotFoundException for unknown adapter name', function () {
    $registry = new AdapterRegistry([makeAdapter('wordpress')]);
    $registry->get('nonexistent');
})->throws(\App\Adapter\Exception\AdapterNotFoundException::class);
