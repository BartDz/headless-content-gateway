<?php

declare(strict_types=1);

use App\Adapter\WordPress\WordPressAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

it('returns single article item via WordPress adapter', function () {
    $client = static::createClient();

    $fixture = file_get_contents(__DIR__ . '/../Fixtures/WordPress/article-hello.json');
    $mockClient = new MockHttpClient([
        new MockResponse($fixture, [
            'http_code' => 200,
            'response_headers' => ['Content-Type: application/json'],
        ]),
    ]);

    static::getContainer()->set(WordPressAdapter::class, new WordPressAdapter($mockClient, 'http://mock-wp'));

    $client->request('GET', '/content/article/hello-world', [], [], ['HTTP_ACCEPT' => 'application/json']);

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($client->getResponse()->headers->get('X-Cache'))->toBe('MISS');

    $data = json_decode($client->getResponse()->getContent(), true);
    expect($data)->toHaveKey('slug')
        ->and($data['slug'])->toBe('hello-world')
        ->and($data)->toHaveKey('fields')
        ->and($data['fields'])->toHaveKey('title');
});

it('returns 404 for unknown article slug', function () {
    $client = static::createClient();

    $mockClient = new MockHttpClient(
        fn () => new MockResponse('[]', ['http_code' => 200, 'response_headers' => ['Content-Type: application/json']])
    );

    static::getContainer()->set(WordPressAdapter::class, new WordPressAdapter($mockClient, 'http://mock-wp'));

    $client->request('GET', '/content/article/nonexistent', [], [], ['HTTP_ACCEPT' => 'application/json']);

    expect($client->getResponse()->getStatusCode())->toBe(404);
});

it('cache manager returns same entry on second call without hitting adapter', function () {
    $fixture = json_decode(
        file_get_contents(__DIR__ . '/../Fixtures/WordPress/article-hello.json'),
        true,
    );

    $callCount = 0;
    $fetcher = function () use (&$callCount, $fixture): \App\Model\ContentEntry {
        ++$callCount;

        return new \App\Model\ContentEntry(
            id: '1',
            contentType: 'article',
            slug: 'hello-world',
            locale: 'en',
            publishedAt: null,
            fields: ['title' => $fixture[0]['title']['rendered']],
        );
    };

    $cache = static::createClient();
    $manager = static::getContainer()->get(\App\Cache\ContentCacheManager::class);

    $entry1 = $manager->get('wordpress', 'article', 'hello-world', 'en', $fetcher, 3600);
    $entry2 = $manager->get('wordpress', 'article', 'hello-world', 'en', $fetcher, 3600);

    expect($callCount)->toBe(1)
        ->and($entry1->slug)->toBe('hello-world')
        ->and($entry2->slug)->toBe('hello-world');
});
