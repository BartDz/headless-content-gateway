<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\CmsConfigLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HealthController extends AbstractController
{
    private const PROBE_TIMEOUT = 2.0;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly CmsConfigLoader $configLoader,
    ) {
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $statuses = [];

        // Probe Docker-based adapters
        $probeMap = [
            'wordpress' => fn (array $cfg) => ($cfg['base_url'] ?? '').'/wp-json/',
            'strapi' => fn (array $cfg) => ($cfg['base_url'] ?? '').'/api/',
        ];

        foreach ($probeMap as $adapterName => $urlBuilder) {
            $cfg = $this->configLoader->getAdapterConfig($adapterName);
            if (empty($cfg['base_url'] ?? '')) {
                continue;
            }
            try {
                $response = $this->client->request('GET', $urlBuilder($cfg), [
                    'timeout' => self::PROBE_TIMEOUT,
                ]);
                $response->getStatusCode();
                $statuses[$adapterName] = 'up';
            } catch (\Throwable) {
                $statuses[$adapterName] = 'down';
            }
        }

        // SaaS adapters - assumed up (no local probe possible without real tokens)
        $statuses['contentful'] = 'up';
        $statuses['storyblok'] = 'up';
        $statuses['sanity'] = 'up';

        $upCount = count(array_filter($statuses, fn ($s) => 'up' === $s));
        $total = count($statuses);

        $overall = match (true) {
            $upCount === $total => 'ok',
            0 === $upCount => 'down',
            default => 'degraded',
        };

        return $this->json([
            'status' => $overall,
            'adapters' => $statuses,
        ]);
    }
}
