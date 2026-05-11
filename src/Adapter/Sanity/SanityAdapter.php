<?php

declare(strict_types=1);

namespace App\Adapter\Sanity;

use App\Adapter\CmsAdapterInterface;
use App\Adapter\Exception\AdapterException;
use App\Adapter\Exception\ContentNotFoundException;
use App\Model\ContentCollection;
use App\Model\ContentEntry;
use App\Model\ContentQuery;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SanityAdapter implements CmsAdapterInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $projectId,
        private readonly string $dataset,
        private readonly string $apiToken = '',
        private readonly string $apiVersion = '2024-01-01',
    ) {
    }

    public function supports(string $adapterName): bool
    {
        return 'sanity' === $adapterName;
    }

    public function fetchEntry(string $type, string $slug, string $locale): ContentEntry
    {
        $query = '*[_type == $type && slug.current == $slug][0]';
        $data = $this->query($query, ['type' => $type, 'slug' => $slug]);

        if (null === $data) {
            throw new ContentNotFoundException("Sanity document not found: $slug");
        }

        return $this->normalize($data, $type, $locale);
    }

    public function fetchCollection(string $type, ContentQuery $query): ContentCollection
    {
        $offset = ($query->page - 1) * $query->limit;
        $groq = '*[_type == $type] | order(_createdAt desc) ['.$offset.'..'.($offset + $query->limit - 1).']';
        $countGroq = 'count(*[_type == $type])';

        $items = $this->query($groq, ['type' => $type]);
        $total = $this->query($countGroq, ['type' => $type]);

        $entries = array_map(
            fn (array $item) => $this->normalize($item, $type, $query->locale),
            is_array($items) ? $items : [],
        );

        return new ContentCollection(
            entries: $entries,
            total: is_int($total) ? $total : count($entries),
            page: $query->page,
            limit: $query->limit,
        );
    }

    private function query(string $groq, array $params = []): mixed
    {
        $queryParams = ['query' => $groq];
        foreach ($params as $key => $value) {
            $queryParams['$'.$key] = json_encode($value);
        }

        $headers = [];
        if ('' !== $this->apiToken) {
            $headers['Authorization'] = 'Bearer '.$this->apiToken;
        }

        try {
            $response = $this->client->request(
                'GET',
                "https://{$this->projectId}.api.sanity.io/v{$this->apiVersion}/data/query/{$this->dataset}",
                ['query' => $queryParams, 'headers' => $headers],
            );

            return $response->toArray()['result'] ?? null;
        } catch (\Throwable $e) {
            throw new AdapterException('Sanity request failed: '.$e->getMessage(), 0, $e);
        }
    }

    private function normalize(array $data, string $type, string $locale): ContentEntry
    {
        $publishedAt = isset($data['_createdAt'])
            ? new \DateTimeImmutable($data['_createdAt'])
            : null;

        return new ContentEntry(
            id: $data['_id'] ?? '',
            contentType: $type,
            slug: $data['slug']['current'] ?? '',
            locale: $locale,
            publishedAt: $publishedAt,
            fields: [
                'title' => $data['title'] ?? '',
                'body' => $data['body'] ?? '',
                'slug' => $data['slug']['current'] ?? '',
            ],
        );
    }
}
