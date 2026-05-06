<?php

declare(strict_types=1);

namespace App\Controller;

use App\Cache\ContentCacheManager;
use App\Config\CmsConfigLoader;
use App\Webhook\WebhookValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class WebhookController extends AbstractController
{
    public function __construct(
        private readonly WebhookValidator $validator,
        private readonly ContentCacheManager $cache,
        private readonly CmsConfigLoader $configLoader,
    ) {
    }

    #[Route('/webhooks/{adapter}', name: 'webhook', methods: ['POST'])]
    public function __invoke(Request $request, string $adapter): JsonResponse
    {
        $secret = getenv('WEBHOOK_SECRET_'.strtoupper($adapter)) ?: '';

        if (!$this->validator->validate($request, $adapter, $secret)) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $body = json_decode($request->getContent(), true) ?? [];
        $contentType = $body['contentType'] ?? $body['model'] ?? null;

        if (null !== $contentType) {
            $this->cache->invalidateByTag($adapter, $contentType);

            return $this->json(['invalidated' => "$adapter.$contentType"]);
        }

        foreach ($this->configLoader->getContentTypeNames() as $type) {
            $this->cache->invalidateByTag($adapter, $type);
        }

        return $this->json(['invalidated' => $adapter]);
    }
}
