<?php

declare(strict_types=1);

namespace App\Transformer;

use App\Model\ContentEntry;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.content_transformer')]
class ImageUrlRewriter implements TransformerInterface
{
    public function __construct(private readonly string $baseUrl = '')
    {
    }

    public function getName(): string
    {
        return 'image_url_rewriter';
    }

    public function transform(ContentEntry $entry): ContentEntry
    {
        if (empty($this->baseUrl)) {
            return $entry;
        }
        $fields = $entry->fields;
        if (isset($fields['body']) && is_string($fields['body'])) {
            $fields['body'] = preg_replace(
                '/(src=["\'])\//',
                '$1'.rtrim($this->baseUrl, '/').'/',
                $fields['body'],
            );
        }

        return new ContentEntry(
            $entry->id, $entry->contentType, $entry->slug,
            $entry->locale, $entry->publishedAt, $fields,
        );
    }
}
