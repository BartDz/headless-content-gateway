<?php

declare(strict_types=1);

namespace App\Transformer;

use App\Model\ContentEntry;
use League\CommonMark\CommonMarkConverter;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.content_transformer')]
class MarkdownToHtmlTransformer implements TransformerInterface
{
    private readonly CommonMarkConverter $converter;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter();
    }

    public function getName(): string
    {
        return 'markdown_to_html';
    }

    public function transform(ContentEntry $entry): ContentEntry
    {
        $fields = $entry->fields;
        if (isset($fields['body']) && is_string($fields['body'])) {
            $fields['body'] = trim((string) $this->converter->convert($fields['body']));
        }

        return new ContentEntry(
            $entry->id, $entry->contentType, $entry->slug,
            $entry->locale, $entry->publishedAt, $fields,
        );
    }
}
