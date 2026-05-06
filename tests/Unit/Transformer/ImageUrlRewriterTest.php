<?php
declare(strict_types=1);

use App\Model\ContentEntry;
use App\Transformer\ImageUrlRewriter;

it('rewrites relative src to absolute', function () {
    $transformer = new ImageUrlRewriter('https://example.com');
    $entry = new ContentEntry('1', 'article', 'test', 'en', null, [
        'body' => '<img src="/uploads/photo.jpg">',
    ]);
    $result = $transformer->transform($entry);
    expect($result->fields['body'])->toContain('src="https://example.com/uploads/photo.jpg"');
});

it('skips rewrite when no base url', function () {
    $transformer = new ImageUrlRewriter('');
    $entry = new ContentEntry('1', 'article', 'test', 'en', null, ['body' => '<img src="/photo.jpg">']);
    $result = $transformer->transform($entry);
    expect($result->fields['body'])->toBe('<img src="/photo.jpg">');
});

it('does not rewrite absolute src', function () {
    $transformer = new ImageUrlRewriter('https://example.com');
    $entry = new ContentEntry('1', 'article', 'test', 'en', null, [
        'body' => '<img src="https://cdn.example.com/photo.jpg">',
    ]);
    $result = $transformer->transform($entry);
    expect($result->fields['body'])->toBe('<img src="https://cdn.example.com/photo.jpg">');
});

it('getName returns image_url_rewriter', function () {
    expect((new ImageUrlRewriter())->getName())->toBe('image_url_rewriter');
});
