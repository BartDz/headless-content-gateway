<?php

declare(strict_types=1);

namespace App\Adapter\Storyblok;

use App\Adapter\CmsAdapterInterface;
use App\Adapter\Exception\AdapterException;
use App\Adapter\Exception\ContentNotFoundException;
use App\Model\ContentCollection;
use App\Model\ContentEntry;
use App\Model\ContentQuery;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class StoryblokAdapter implements CmsAdapterInterface
{
    private const BASE_URL = 'https://api.storyblok.com/v2/cdn';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $accessToken,
    ) {
    }

    public function supports(string $adapterName): bool
    {
        return 'storyblok' === $adapterName;
    }

    public function fetchEntry(string $type, string $slug, string $locale): ContentEntry
    {
        try {
            $response = $this->client->request('GET', self::BASE_URL."/stories/$slug", [
                'query' => ['token' => $this->accessToken, 'language' => $locale],
            ]);
            $data = $response->toArray();
        } catch (\Throwable $e) {
            throw new AdapterException('Storyblok request failed: '.$e->getMessage(), 0, $e);
        }

        if (empty($data['story'])) {
            throw new ContentNotFoundException("Storyblok story not found: $slug");
        }

        return $this->normalize($data['story'], $type, $locale);
    }

    public function fetchCollection(string $type, ContentQuery $query): ContentCollection
    {
        try {
            $response = $this->client->request('GET', self::BASE_URL.'/stories', [
                'query' => [
                    'token' => $this->accessToken,
                    'content_type' => $type,
                    'language' => $query->locale,
                    'page' => $query->page,
                    'per_page' => $query->limit,
                ],
            ]);
            $data = $response->toArray();
        } catch (\Throwable $e) {
            throw new AdapterException('Storyblok request failed: '.$e->getMessage(), 0, $e);
        }

        $stories = $data['stories'] ?? [];
        $total = (int) ($data['total'] ?? count($stories));

        return new ContentCollection(
            entries: array_map(fn (array $s) => $this->normalize($s, $type, $query->locale), $stories),
            total: $total,
            page: $query->page,
            limit: $query->limit,
        );
    }

    private function normalize(array $story, string $type, string $locale): ContentEntry
    {
        $content = $story['content'] ?? [];
        $publishedAt = isset($story['published_at'])
            ? new \DateTimeImmutable($story['published_at'])
            : null;

        return new ContentEntry(
            id: (string) $story['id'],
            contentType: $type,
            slug: $story['slug'],
            locale: $locale,
            publishedAt: $publishedAt,
            fields: [
                'title' => $content['title'] ?? '',
                'body' => $content['body'] ?? '',
                'slug' => $story['slug'],
            ],
        );
    }
}
