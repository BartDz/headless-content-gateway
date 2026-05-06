<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use App\Config\CmsBridgeConfiguration;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Yaml\Yaml;

class CmsBridgeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $rawFile = $container->getParameter('kernel.project_dir').'/config/cms_bridge.yaml';
        if (file_exists($rawFile)) {
            $configs[] = Yaml::parseFile($rawFile);
        }

        $configuration = new CmsBridgeConfiguration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('cms_bridge.config', $config);
    }
}
