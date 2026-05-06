<?php

declare(strict_types=1);

use App\Adapter\WordPress\WordPressAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

it('returns article collection via WordPress adapter with fixture data', function () {
    $client = static::createClient();

    $fixture = file_get_contents(__DIR__ . '/../Fixtures/WordPress/articles-collection.json');
    $mockClient = new MockHttpClient([
        new MockResponse($fixture, [
            'http_code' => 200,
            'response_headers' => ['X-WP-Total: 2', 'Content-Type: application/json'],
        ]),
    ]);

    static::getContainer()->set(WordPressAdapter::class, new WordPressAdapter($mockClient, 'http://mock-wp'));

    $client->request('GET', '/content/article', [], [], ['HTTP_ACCEPT' => 'application/json']);

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($client->getResponse()->headers->get('X-Cache'))->toBe('MISS');

    $data = json_decode($client->getResponse()->getContent(), true);
    expect($data)->toHaveKey('entries')
        ->and($data['entries'])->toHaveCount(2)
        ->and($data['entries'][0]['slug'])->toBe('hello-world');
});
