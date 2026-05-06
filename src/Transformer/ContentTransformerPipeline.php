<?php

declare(strict_types=1);

namespace App\Transformer;

use App\Model\ContentEntry;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class ContentTransformerPipeline
{
    /** @var array<string, TransformerInterface> */
    private array $transformers = [];

    /** @param iterable<TransformerInterface> $transformers */
    public function __construct(
        #[AutowireIterator('app.content_transformer')]
        iterable $transformers,
    ) {
        foreach ($transformers as $transformer) {
            $this->transformers[$transformer->getName()] = $transformer;
        }
    }

    /** @param string[] $names */
    public function transform(ContentEntry $entry, array $names): ContentEntry
    {
        foreach ($names as $name) {
            if (isset($this->transformers[$name])) {
                $entry = $this->transformers[$name]->transform($entry);
            }
        }

        return $entry;
    }
}
