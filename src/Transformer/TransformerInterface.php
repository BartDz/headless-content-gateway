<?php

declare(strict_types=1);

namespace App\Transformer;

use App\Model\ContentEntry;

interface TransformerInterface
{
    public function transform(ContentEntry $entry): ContentEntry;

    public function getName(): string;
}
