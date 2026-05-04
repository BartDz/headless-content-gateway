<?php
declare(strict_types=1);
namespace App\Adapter\WordPress;

use App\Adapter\CmsAdapterInterface;
use App\Adapter\Exception\AdapterException;
use App\Adapter\Exception\ContentNotFoundException;
use App\Model\ContentCollection;
use App\Model\ContentEntry;
use App\Model\ContentQuery;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WordPressAdapter implements CmsAdapterInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $baseUrl,
    ) {}

    public function supports(string $adapterName): bool
    {
        return $adapterName === 'wordpress';
    }

    public function fetchEntry(string $type, string $slug, string $locale): ContentEntry
    {
        try {
            $response = $this->client->request('GET', "$this->baseUrl/wp-json/wp/v2/posts", [
                'query' => ['slug' => $slug],
            ]);
            $data = $response->toArray();
        } catch (\Throwable $e) {
            throw new AdapterException("WordPress request failed: " . $e->getMessage(), 0, $e);
        }

        if (empty($data)) {
            throw new ContentNotFoundException("WordPress post not found: $slug");
        }

        return $this->normalize($data[0], $type);
    }

    public function fetchCollection(string $type, ContentQuery $query): ContentCollection
    {
        try {
            $response = $this->client->request('GET', "$this->baseUrl/wp-json/wp/v2/posts", [
                'query' => [
                    'page' => $query->page,
                    'per_page' => $query->limit,
                ],
            ]);
            $data = $response->toArray();
            $headers = $response->getHeaders();
            $total = (int) ($headers['x-wp-total'][0] ?? count($data));
        } catch (\Throwable $e) {
            throw new AdapterException("WordPress request failed: " . $e->getMessage(), 0, $e);
        }

        return new ContentCollection(
            entries: array_map(fn(array $item) => $this->normalize($item, $type), $data),
            total: $total,
            page: $query->page,
            limit: $query->limit,
        );
    }

    private function normalize(array $data, string $type): ContentEntry
    {
        $publishedAt = isset($data['date'])
            ? new \DateTimeImmutable($data['date'])
            : null;

        return new ContentEntry(
            id: (string) $data['id'],
            contentType: $type,
            slug: $data['slug'],
            locale: 'en',
            publishedAt: $publishedAt,
            fields: [
                'title' => $data['title']['rendered'],
                'body' => $data['content']['rendered'],
                'slug' => $data['slug'],
            ],
        );
    }
}
