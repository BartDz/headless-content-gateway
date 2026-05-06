<?php

declare(strict_types=1);

namespace App\Adapter\Contentful;

use App\Adapter\CmsAdapterInterface;
use App\Adapter\Exception\AdapterException;
use App\Adapter\Exception\ContentNotFoundException;
use App\Model\ContentCollection;
use App\Model\ContentEntry;
use App\Model\ContentQuery;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ContentfulAdapter implements CmsAdapterInterface
{
    private const BASE_URL = 'https://cdn.contentful.com';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $spaceId,
        private readonly string $accessToken,
    ) {
    }

    public function supports(string $adapterName): bool
    {
        return 'contentful' === $adapterName;
    }

    public function fetchEntry(string $type, string $slug, string $locale): ContentEntry
    {
        $data = $this->request("/spaces/$this->spaceId/entries", [
            'content_type' => $type,
            'fields.slug' => $slug,
            'locale' => $locale,
            'limit' => 1,
        ]);

        if (empty($data['items'])) {
            throw new ContentNotFoundException("Contentful entry not found: $slug");
        }

        return $this->normalize($data['items'][0], $type, $locale);
    }

    public function fetchCollection(string $type, ContentQuery $query): ContentCollection
    {
        $data = $this->request("/spaces/$this->spaceId/entries", [
            'content_type' => $type,
            'locale' => $query->locale,
            'skip' => ($query->page - 1) * $query->limit,
            'limit' => $query->limit,
        ]);

        $items = array_map(
            fn (array $item) => $this->normalize($item, $type, $query->locale),
            $data['items'] ?? [],
        );

        return new ContentCollection(
            entries: $items,
            total: $data['total'] ?? count($items),
            page: $query->page,
            limit: $query->limit,
        );
    }

    private function request(string $path, array $query = []): array
    {
        try {
            $response = $this->client->request('GET', self::BASE_URL.$path, [
                'query' => array_merge($query, ['access_token' => $this->accessToken]),
            ]);

            return $response->toArray();
        } catch (\Throwable $e) {
            throw new AdapterException('Contentful request failed: '.$e->getMessage(), 0, $e);
        }
    }

    private function normalize(array $item, string $type, string $locale): ContentEntry
    {
        $fields = $item['fields'] ?? [];
        $localizedField = fn (string $key): mixed => $fields[$key][$locale] ?? $fields[$key]['en-US'] ?? null;

        $publishedAt = $localizedField('publishedAt');

        return new ContentEntry(
            id: $item['sys']['id'],
            contentType: $type,
            slug: (string) ($localizedField('slug') ?? ''),
            locale: $locale,
            publishedAt: $publishedAt ? new \DateTimeImmutable($publishedAt) : null,
            fields: [
                'title' => (string) ($localizedField('title') ?? ''),
                'body' => (string) ($localizedField('body') ?? ''),
                'slug' => (string) ($localizedField('slug') ?? ''),
            ],
        );
    }
}
