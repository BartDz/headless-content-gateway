<?php

declare(strict_types=1);

use App\Model\ContentEntry;
use App\Model\ContentCollection;
use App\Model\ContentQuery;

it('constructs ContentEntry with all fields', function () {
    $entry = new ContentEntry('1', 'article', 'hello', 'en', null, ['title' => 'Hello']);
    expect($entry->id)->toBe('1')
        ->and($entry->contentType)->toBe('article')
        ->and($entry->slug)->toBe('hello')
        ->and($entry->locale)->toBe('en')
        ->and($entry->publishedAt)->toBeNull()
        ->and($entry->fields['title'])->toBe('Hello');
});

it('constructs ContentEntry with publishedAt', function () {
    $date = new \DateTimeImmutable('2024-01-15');
    $entry = new ContentEntry('2', 'post', 'world', 'pl', $date, []);
    expect($entry->publishedAt)->toBe($date);
});

it('constructs ContentCollection', function () {
    $entry = new ContentEntry('1', 'article', 'hello', 'en', null, []);
    $col = new ContentCollection([$entry], 1, 1, 10);
    expect($col->entries)->toHaveCount(1)
        ->and($col->total)->toBe(1)
        ->and($col->page)->toBe(1)
        ->and($col->limit)->toBe(10);
});

it('ContentQuery has sensible defaults', function () {
    $q = new ContentQuery();
    expect($q->locale)->toBe('en')
        ->and($q->page)->toBe(1)
        ->and($q->limit)->toBe(10)
        ->and($q->filters)->toBe([]);
});

it('ContentQuery accepts custom values', function () {
    $q = new ContentQuery(locale: 'pl', page: 2, limit: 5);
    expect($q->locale)->toBe('pl')
        ->and($q->page)->toBe(2)
        ->and($q->limit)->toBe(5);
});
