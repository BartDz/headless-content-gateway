<?php
declare(strict_types=1);

use App\Adapter\Contentful\ContentfulAdapter;
use App\Adapter\Exception\ContentNotFoundException;
use App\Model\ContentQuery;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

function contentfulFixture(string $file): string
{
    return file_get_contents(dirname(__DIR__, 2) . "/Fixtures/Contentful/$file");
}

it('fetches single entry from Contentful by slug', function () {
    $client = new MockHttpClient([
        new MockResponse(contentfulFixture('entry-hello.json'), ['http_code' => 200]),
    ]);
    $adapter = new ContentfulAdapter($client, 'space123', 'token456');

    $entry = $adapter->fetchEntry('article', 'hello-contentful', 'en-US');

    expect($entry->slug)->toBe('hello-contentful')
        ->and($entry->contentType)->toBe('article')
        ->and($entry->fields['title'])->toBe('Hello Contentful')
        ->and($entry->fields['body'])->toBe('Contentful body text.');
});

it('fetches collection from Contentful', function () {
    $client = new MockHttpClient([
        new MockResponse(contentfulFixture('entries-collection.json'), ['http_code' => 200]),
    ]);
    $adapter = new ContentfulAdapter($client, 'space123', 'token456');

    $col = $adapter->fetchCollection('article', new ContentQuery(locale: 'en-US'));

    expect($col->entries)->toHaveCount(2)
        ->and($col->total)->toBe(2)
        ->and($col->entries[0]->slug)->toBe('hello-contentful');
});

it('throws ContentNotFoundException when no items returned', function () {
    $empty = '{"sys":{"type":"Array"},"total":0,"skip":0,"limit":1,"items":[]}';
    $client = new MockHttpClient([
        new MockResponse($empty, ['http_code' => 200]),
    ]);
    $adapter = new ContentfulAdapter($client, 'space123', 'token456');
    $adapter->fetchEntry('article', 'nonexistent', 'en-US');
})->throws(ContentNotFoundException::class);

it('supports contentful adapter name', function () {
    $adapter = new ContentfulAdapter(new MockHttpClient(), 'space', 'token');
    expect($adapter->supports('contentful'))->toBeTrue()
        ->and($adapter->supports('wordpress'))->toBeFalse();
});
