<?php
declare(strict_types=1);

use App\Cache\ContentCacheManager;
use App\Model\ContentEntry;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

function cacheEntry(string $slug = 'test'): ContentEntry
{
    return new ContentEntry('1', 'article', $slug, 'en', null, ['title' => 'Test']);
}

it('calls fetcher on cache miss', function () {
    $cache = new TagAwareAdapter(new ArrayAdapter());
    $manager = new ContentCacheManager($cache);

    $callCount = 0;
    $fetcher = function () use (&$callCount) {
        $callCount++;
        return cacheEntry();
    };

    $result = $manager->get('wordpress', 'article', 'test', 'en', $fetcher, 3600);

    expect($callCount)->toBe(1)
        ->and($result->slug)->toBe('test');
});

it('returns cached entry on second call (cache hit)', function () {
    $cache = new TagAwareAdapter(new ArrayAdapter());
    $manager = new ContentCacheManager($cache);

    $callCount = 0;
    $fetcher = function () use (&$callCount) {
        $callCount++;
        return cacheEntry();
    };

    $manager->get('wordpress', 'article', 'test', 'en', $fetcher, 3600);
    $manager->get('wordpress', 'article', 'test', 'en', $fetcher, 3600);

    expect($callCount)->toBe(1);
});

it('detects cache hit on second call', function () {
    $cache = new TagAwareAdapter(new ArrayAdapter());
    $manager = new ContentCacheManager($cache);

    $manager->get('wordpress', 'article', 'slug-x', 'en', fn() => cacheEntry('slug-x'), 3600);
    $manager->get('wordpress', 'article', 'slug-x', 'en', fn() => cacheEntry('slug-x'), 3600);

    expect($manager->wasLastRequestCacheHit())->toBeTrue();
});

it('detects cache miss on first call', function () {
    $cache = new TagAwareAdapter(new ArrayAdapter());
    $manager = new ContentCacheManager($cache);

    $manager->get('wordpress', 'article', 'slug-y', 'en', fn() => cacheEntry('slug-y'), 3600);

    expect($manager->wasLastRequestCacheHit())->toBeFalse();
});

it('invalidates cache by tag and forces fetcher call again', function () {
    $cache = new TagAwareAdapter(new ArrayAdapter());
    $manager = new ContentCacheManager($cache);

    $callCount = 0;
    $fetcher = function () use (&$callCount) {
        $callCount++;
        return cacheEntry();
    };

    $manager->get('wordpress', 'article', 'test', 'en', $fetcher, 3600);
    $manager->invalidateByTag('wordpress', 'article');
    $manager->get('wordpress', 'article', 'test', 'en', $fetcher, 3600);

    expect($callCount)->toBe(2);
});
