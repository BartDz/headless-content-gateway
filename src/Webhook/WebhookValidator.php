<?php

declare(strict_types=1);

namespace App\Webhook;

use Symfony\Component\HttpFoundation\Request;

class WebhookValidator
{
    public function validate(Request $request, string $adapterName, string $secret): bool
    {
        if ('' === $secret) {
            return false;
        }

        $body = $request->getContent();

        return match ($adapterName) {
            'contentful' => $this->validateHmac(
                $body,
                $secret,
                $request->headers->get('X-Contentful-Signature') ?? '',
            ),
            'storyblok' => $this->validateHmac(
                $body,
                $secret,
                $request->headers->get('X-Storyblok-Hmac') ?? '',
            ),
            default => hash_equals(
                $secret,
                $request->headers->get('X-Webhook-Secret') ?? '',
            ),
        };
    }

    private function validateHmac(string $body, string $secret, string $signature): bool
    {
        if (empty($secret)) {
            return false;
        }
        $expected = hash_hmac('sha256', $body, $secret);

        return hash_equals($expected, $signature);
    }
}
