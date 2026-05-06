<?php
declare(strict_types=1);

use App\Adapter\Strapi\StrapiAdapter;
use App\Adapter\Exception\ContentNotFoundException;
use App\Model\ContentQuery;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

function strapiFixture(string $file): string
{
    return file_get_contents(dirname(__DIR__, 2) . "/Fixtures/Strapi/$file");
}

it('fetches single entry from Strapi', function () {
    // Strapi slug filter returns a collection - wrap single item in data array
    $fixture = json_encode([
        'data' => [json_decode(strapiFixture('post-hello.json'), true)['data']],
        'meta' => ['pagination' => ['total' => 1]],
    ]);
    $client = new MockHttpClient([
        new MockResponse($fixture, ['http_code' => 200]),
    ]);
    $adapter = new StrapiAdapter($client, 'http://localhost:1337');

    $entry = $adapter->fetchEntry('post', 'hello-strapi', 'en');

    expect($entry->slug)->toBe('hello-strapi')
        ->and($entry->contentType)->toBe('post')
        ->and($entry->fields['title'])->toBe('Hello Strapi')
        ->and($entry->fields['body'])->toBe('<p>Strapi content here.</p>');
});

it('fetches collection from Strapi', function () {
    $client = new MockHttpClient([
        new MockResponse(strapiFixture('posts-collection.json'), ['http_code' => 200]),
    ]);
    $adapter = new StrapiAdapter($client, 'http://localhost:1337');

    $col = $adapter->fetchCollection('post', new ContentQuery());

    expect($col->entries)->toHaveCount(2)
        ->and($col->total)->toBe(2)
        ->and($col->entries[0]->slug)->toBe('hello-strapi');
});

it('throws ContentNotFoundException when Strapi data is empty', function () {
    $client = new MockHttpClient([
        new MockResponse('{"data":[],"meta":{"pagination":{"total":0}}}', ['http_code' => 200]),
    ]);
    $adapter = new StrapiAdapter($client, 'http://localhost:1337');
    $adapter->fetchEntry('post', 'nonexistent', 'en');
})->throws(ContentNotFoundException::class);

it('supports strapi adapter name', function () {
    $adapter = new StrapiAdapter(new MockHttpClient(), 'http://localhost:1337');
    expect($adapter->supports('strapi'))->toBeTrue()
        ->and($adapter->supports('wordpress'))->toBeFalse();
});
