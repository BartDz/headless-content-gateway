<?php
declare(strict_types=1);

use App\Adapter\Storyblok\StoryblokAdapter;
use App\Adapter\Exception\ContentNotFoundException;
use App\Model\ContentQuery;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

function storyblokFixture(string $file): string
{
    return file_get_contents(dirname(__DIR__, 2) . "/Fixtures/Storyblok/$file");
}

it('fetches story from Storyblok', function () {
    $client = new MockHttpClient([
        new MockResponse(storyblokFixture('story-hello.json'), ['http_code' => 200]),
    ]);
    $adapter = new StoryblokAdapter($client, 'mytoken');

    $entry = $adapter->fetchEntry('article', 'hello-storyblok', 'en');

    expect($entry->slug)->toBe('hello-storyblok')
        ->and($entry->contentType)->toBe('article')
        ->and($entry->fields['title'])->toBe('Hello Storyblok')
        ->and($entry->fields['body'])->toBe('Storyblok body content.');
});

it('fetches stories collection from Storyblok', function () {
    $client = new MockHttpClient([
        new MockResponse(storyblokFixture('stories-collection.json'), ['http_code' => 200]),
    ]);
    $adapter = new StoryblokAdapter($client, 'mytoken');

    $col = $adapter->fetchCollection('article', new ContentQuery());

    expect($col->entries)->toHaveCount(2)
        ->and($col->total)->toBe(2)
        ->and($col->entries[0]->slug)->toBe('hello-storyblok');
});

it('throws ContentNotFoundException when story key missing', function () {
    $client = new MockHttpClient([
        new MockResponse('{"story":null}', ['http_code' => 200]),
    ]);
    $adapter = new StoryblokAdapter($client, 'mytoken');
    $adapter->fetchEntry('article', 'nonexistent', 'en');
})->throws(ContentNotFoundException::class);

it('supports storyblok adapter name', function () {
    $adapter = new StoryblokAdapter(new MockHttpClient(), 'token');
    expect($adapter->supports('storyblok'))->toBeTrue()
        ->and($adapter->supports('wordpress'))->toBeFalse();
});
