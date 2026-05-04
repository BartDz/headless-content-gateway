<?php
declare(strict_types=1);
namespace App\Config;

use Symfony\Component\Yaml\Yaml;

class CmsConfigLoader
{
    private array $config;

    public function __construct(private readonly string $projectDir)
    {
        $this->config = Yaml::parseFile($projectDir . '/config/cms_bridge.yaml');
    }

    public function getAdapterConfig(string $adapterName): array
    {
        return $this->config['adapters'][$adapterName] ?? [];
    }

    public function getContentType(string $typeName): ContentTypeConfig
    {
        $ct = $this->config['content_types'][$typeName] ?? null;
        if ($ct === null) {
            throw new \InvalidArgumentException("Unknown content type: $typeName");
        }
        return new ContentTypeConfig(
            name: $typeName,
            adapter: $ct['adapter'],
            cacheTtl: $ct['cache_ttl'] ?? 3600,
            transformers: $ct['transformers'] ?? [],
            fieldMap: $ct['field_map'] ?? [],
        );
    }

    /** @return string[] */
    public function getContentTypeNames(): array
    {
        return array_keys($this->config['content_types'] ?? []);
    }
}
