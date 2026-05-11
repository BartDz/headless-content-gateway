<?php

declare(strict_types=1);

use App\Adapter\Sanity\SanityAdapter;
use App\Adapter\Exception\ContentNotFoundException;
use App\Model\ContentQuery;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

function sanityFixture(string $file): string
{
    return file_get_contents(dirname(__DIR__, 2) . "/Fixtures/Sanity/$file");
}

it('fetches single entry from Sanity by slug', function () {
    $client = new MockHttpClient([
        new MockResponse(sanityFixture('entry-hello.json'), ['http_code' => 200]),
    ]);
    $adapter = new SanityAdapter($client, 'project123', 'production');

    $entry = $adapter->fetchEntry('article', 'hello-sanity', 'en');

    expect($entry->slug)->toBe('hello-sanity')
        ->and($entry->contentType)->toBe('article')
        ->and($entry->fields['title'])->toBe('Hello Sanity')
        ->and($entry->fields['body'])->toBe('Sanity body text.');
});

it('fetches collection from Sanity', function () {
    $client = new MockHttpClient([
        new MockResponse(sanityFixture('entries-collection.json'), ['http_code' => 200]),
        new MockResponse(sanityFixture('count.json'), ['http_code' => 200]),
    ]);
    $adapter = new SanityAdapter($client, 'project123', 'production');

    $col = $adapter->fetchCollection('article', new ContentQuery());

    expect($col->entries)->toHaveCount(2)
        ->and($col->total)->toBe(2)
        ->and($col->entries[0]->slug)->toBe('hello-sanity');
});

it('throws ContentNotFoundException when result is null', function () {
    $empty = '{"result": null}';
    $client = new MockHttpClient([
        new MockResponse($empty, ['http_code' => 200]),
    ]);
    $adapter = new SanityAdapter($client, 'project123', 'production');
    $adapter->fetchEntry('article', 'nonexistent', 'en');
})->throws(ContentNotFoundException::class);

it('supports sanity adapter name', function () {
    $adapter = new SanityAdapter(new MockHttpClient(), 'project', 'production');
    expect($adapter->supports('sanity'))->toBeTrue()
        ->and($adapter->supports('wordpress'))->toBeFalse();
});
