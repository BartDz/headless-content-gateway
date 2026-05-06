<?php

declare(strict_types=1);

namespace App\Adapter;

use App\Adapter\Exception\AdapterNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class AdapterRegistry
{
    /** @param iterable<CmsAdapterInterface> $adapters */
    public function __construct(
        #[AutowireIterator('app.cms_adapter')]
        private readonly iterable $adapters,
    ) {
    }

    public function get(string $adapterName): CmsAdapterInterface
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($adapterName)) {
                return $adapter;
            }
        }
        throw new AdapterNotFoundException("No adapter found for: $adapterName");
    }
}
