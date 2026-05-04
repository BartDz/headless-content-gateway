<?php
declare(strict_types=1);

use App\Adapter\WordPress\WordPressAdapter;
use App\Model\ContentQuery;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

function wpFixture(string $file): string
{
    return file_get_contents(dirname(__DIR__, 2) . "/Fixtures/WordPress/$file");
}

it('fetches single entry from WordPress', function () {
    $client = new MockHttpClient([
        new MockResponse(wpFixture('article-hello.json'), ['http_code' => 200]),
    ]);
    $adapter = new WordPressAdapter($client, 'http://localhost:8081');

    $entry = $adapter->fetchEntry('article', 'hello-world', 'en');

    expect($entry->slug)->toBe('hello-world')
        ->and($entry->contentType)->toBe('article')
        ->and($entry->fields['title'])->toBe('Hello World')
        ->and($entry->fields['body'])->toBe('<p>This is the post content.</p>');
});

it('fetches collection from WordPress', function () {
    $client = new MockHttpClient([
        new MockResponse(wpFixture('articles-collection.json'), [
            'http_code' => 200,
            'response_headers' => ['X-WP-Total: 2', 'X-WP-TotalPages: 1'],
        ]),
    ]);
    $adapter = new WordPressAdapter($client, 'http://localhost:8081');

    $col = $adapter->fetchCollection('article', new ContentQuery());

    expect($col->entries)->toHaveCount(2)
        ->and($col->total)->toBe(2)
        ->and($col->entries[0]->slug)->toBe('hello-world')
        ->and($col->entries[1]->slug)->toBe('second-post');
});

it('throws ContentNotFoundException when WordPress returns empty array', function () {
    $client = new MockHttpClient([
        new MockResponse('[]', ['http_code' => 200]),
    ]);
    $adapter = new WordPressAdapter($client, 'http://localhost:8081');
    $adapter->fetchEntry('article', 'nonexistent', 'en');
})->throws(\App\Adapter\Exception\ContentNotFoundException::class);

it('supports wordpress adapter name', function () {
    $adapter = new WordPressAdapter(new MockHttpClient(), 'http://localhost:8081');
    expect($adapter->supports('wordpress'))->toBeTrue()
        ->and($adapter->supports('strapi'))->toBeFalse()
        ->and($adapter->supports('contentful'))->toBeFalse();
});
