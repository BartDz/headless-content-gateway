<?php
declare(strict_types=1);

use App\Config\CmsConfigLoader;

it('loads content type config for article', function () {
    $loader = new CmsConfigLoader(dirname(__DIR__, 3));
    $ct = $loader->getContentType('article');
    expect($ct->name)->toBe('article')
        ->and($ct->adapter)->toBe('wordpress')
        ->and($ct->cacheTtl)->toBe(3600)
        ->and($ct->fieldMap['title'])->toBe('title.rendered')
        ->and($ct->transformers)->toContain('markdown_to_html');
});

it('loads content type config for post', function () {
    $loader = new CmsConfigLoader(dirname(__DIR__, 3));
    $ct = $loader->getContentType('post');
    expect($ct->adapter)->toBe('strapi')
        ->and($ct->cacheTtl)->toBe(1800);
});

it('throws InvalidArgumentException on unknown type', function () {
    $loader = new CmsConfigLoader(dirname(__DIR__, 3));
    $loader->getContentType('nonexistent');
})->throws(\InvalidArgumentException::class, 'Unknown content type: nonexistent');

it('returns all content type names', function () {
    $loader = new CmsConfigLoader(dirname(__DIR__, 3));
    $names = $loader->getContentTypeNames();
    expect($names)->toContain('article')
        ->and($names)->toContain('post');
});

it('returns adapter config', function () {
    $loader = new CmsConfigLoader(dirname(__DIR__, 3));
    $cfg = $loader->getAdapterConfig('wordpress');
    expect($cfg)->toHaveKey('base_url');
});
