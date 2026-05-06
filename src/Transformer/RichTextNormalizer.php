<?php

declare(strict_types=1);

namespace App\Transformer;

use App\Model\ContentEntry;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.content_transformer')]
class RichTextNormalizer implements TransformerInterface
{
    public function getName(): string
    {
        return 'rich_text_normalizer';
    }

    public function transform(ContentEntry $entry): ContentEntry
    {
        $fields = $entry->fields;
        if (isset($fields['body']) && is_array($fields['body'])) {
            $fields['body'] = $this->renderNodes($fields['body']['content'] ?? []);
        }

        return new ContentEntry(
            $entry->id, $entry->contentType, $entry->slug,
            $entry->locale, $entry->publishedAt, $fields,
        );
    }

    private function renderNodes(array $nodes): string
    {
        $html = '';
        foreach ($nodes as $node) {
            $html .= match ($node['nodeType'] ?? '') {
                'paragraph' => '<p>'.$this->renderNodes($node['content'] ?? []).'</p>',
                'text' => htmlspecialchars($node['value'] ?? ''),
                'heading-1' => '<h1>'.$this->renderNodes($node['content'] ?? []).'</h1>',
                'heading-2' => '<h2>'.$this->renderNodes($node['content'] ?? []).'</h2>',
                default => '',
            };
        }

        return $html;
    }
}
