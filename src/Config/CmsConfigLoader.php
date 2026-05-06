<?php

declare(strict_types=1);

namespace App\Config;

class CmsConfigLoader
{
    public function __construct(private readonly array $config)
    {
    }

    public function getAdapterConfig(string $adapterName): array
    {
        return $this->config['adapters'][$adapterName] ?? [];
    }

    public function getContentType(string $typeName): ContentTypeConfig
    {
        $ct = $this->config['content_types'][$typeName] ?? null;
        if (null === $ct) {
            throw new \InvalidArgumentException("Unknown content type: $typeName");
        }

        return new ContentTypeConfig(
            name: $typeName,
            adapter: $ct['adapter'],
            cacheTtl: $ct['cache_ttl'],
            transformers: $ct['transformers'],
            fieldMap: $ct['field_map'] ?? [],
        );
    }

    /** @return string[] */
    public function getContentTypeNames(): array
    {
        return array_keys($this->config['content_types'] ?? []);
    }
}
