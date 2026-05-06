<?php
declare(strict_types=1);

use App\Model\ContentEntry;
use App\Transformer\RichTextNormalizer;

it('renders contentful rich text nodes to html', function () {
    $transformer = new RichTextNormalizer();
    $richText = [
        'content' => [
            ['nodeType' => 'paragraph', 'content' => [
                ['nodeType' => 'text', 'value' => 'Hello world'],
            ]],
        ],
    ];
    $entry = new ContentEntry('1', 'article', 'test', 'en', null, ['body' => $richText]);
    $result = $transformer->transform($entry);
    expect($result->fields['body'])->toBe('<p>Hello world</p>');
});

it('leaves string body unchanged', function () {
    $transformer = new RichTextNormalizer();
    $entry = new ContentEntry('1', 'article', 'test', 'en', null, ['body' => 'plain string']);
    $result = $transformer->transform($entry);
    expect($result->fields['body'])->toBe('plain string');
});

it('getName returns rich_text_normalizer', function () {
    expect((new RichTextNormalizer())->getName())->toBe('rich_text_normalizer');
});
