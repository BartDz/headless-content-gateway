<?php

declare(strict_types=1);

namespace App\Adapter\Strapi;

use App\Adapter\CmsAdapterInterface;
use App\Adapter\Exception\AdapterException;
use App\Adapter\Exception\ContentNotFoundException;
use App\Model\ContentCollection;
use App\Model\ContentEntry;
use App\Model\ContentQuery;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class StrapiAdapter implements CmsAdapterInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $baseUrl,
    ) {
    }

    public function supports(string $adapterName): bool
    {
        return 'strapi' === $adapterName;
    }

    public function fetchEntry(string $type, string $slug, string $locale): ContentEntry
    {
        try {
            $response = $this->client->request('GET', "$this->baseUrl/api/{$type}s", [
                'query' => [
                    'filters[slug][$eq]' => $slug,
                    'locale' => $locale,
                ],
            ]);
            $data = $response->toArray();
        } catch (\Throwable $e) {
            throw new AdapterException('Strapi request failed: '.$e->getMessage(), 0, $e);
        }

        $items = $data['data'] ?? [];
        if (empty($items)) {
            throw new ContentNotFoundException("Strapi entry not found: $slug");
        }

        return $this->normalize($items[0], $type, $locale);
    }

    public function fetchCollection(string $type, ContentQuery $query): ContentCollection
    {
        try {
            $response = $this->client->request('GET', "$this->baseUrl/api/{$type}s", [
                'query' => [
                    'pagination[page]' => $query->page,
                    'pagination[pageSize]' => $query->limit,
                    'locale' => $query->locale,
                ],
            ]);
            $data = $response->toArray();
        } catch (\Throwable $e) {
            throw new AdapterException('Strapi request failed: '.$e->getMessage(), 0, $e);
        }

        $items = $data['data'] ?? [];
        $total = $data['meta']['pagination']['total'] ?? count($items);

        return new ContentCollection(
            entries: array_map(fn (array $item) => $this->normalize($item, $type, $query->locale), $items),
            total: $total,
            page: $query->page,
            limit: $query->limit,
        );
    }

    private function normalize(array $data, string $type, string $locale): ContentEntry
    {
        $attrs = $data['attributes'];
        $publishedAt = isset($attrs['publishedAt'])
            ? new \DateTimeImmutable($attrs['publishedAt'])
            : null;

        return new ContentEntry(
            id: (string) $data['id'],
            contentType: $type,
            slug: $attrs['slug'],
            locale: $locale,
            publishedAt: $publishedAt,
            fields: [
                'title' => $attrs['title'] ?? '',
                'body' => $attrs['content'] ?? '',
                'slug' => $attrs['slug'],
            ],
        );
    }
}
