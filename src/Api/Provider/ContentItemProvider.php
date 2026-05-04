<?php

declare(strict_types=1);

namespace App\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;

class ContentItemProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        return null;
    }
}
