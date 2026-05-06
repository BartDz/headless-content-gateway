<?php
declare(strict_types=1);

use App\Model\ContentEntry;
use App\Transformer\MarkdownToHtmlTransformer;

function transformEntry(array $fields): ContentEntry
{
    return new ContentEntry('1', 'article', 'test', 'en', null, $fields);
}

it('converts markdown body to html', function () {
    $transformer = new MarkdownToHtmlTransformer();
    $result = $transformer->transform(transformEntry(['body' => '# Hello', 'title' => 'Test']));
    expect($result->fields['body'])->toContain('<h1>Hello</h1>')
        ->and($result->fields['title'])->toBe('Test');
});

it('leaves non-body fields unchanged', function () {
    $transformer = new MarkdownToHtmlTransformer();
    $result = $transformer->transform(transformEntry(['title' => 'My **Title**', 'body' => 'plain']));
    expect($result->fields['title'])->toBe('My **Title**');
});

it('skips non-string body', function () {
    $transformer = new MarkdownToHtmlTransformer();
    $result = $transformer->transform(transformEntry(['body' => ['nested' => 'array']]));
    expect($result->fields['body'])->toBe(['nested' => 'array']);
});

it('getName returns markdown_to_html', function () {
    expect((new MarkdownToHtmlTransformer())->getName())->toBe('markdown_to_html');
});
