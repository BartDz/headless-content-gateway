<?php

declare(strict_types=1);

namespace App\Locale;

class LocaleFallbackResolver
{
    /** @return string[] Locales sorted by quality, highest first */
    public function resolve(string $acceptLanguage): array
    {
        if ('' === trim($acceptLanguage)) {
            return ['en'];
        }

        $parts = array_map('trim', explode(',', $acceptLanguage));
        $weighted = [];

        foreach ($parts as $part) {
            if (str_contains($part, ';q=')) {
                [$locale, $q] = explode(';q=', $part, 2);
                $weighted[] = [trim($locale), (float) $q];
            } else {
                $weighted[] = [trim($part), 1.0];
            }
        }

        usort($weighted, fn ($a, $b) => $b[1] <=> $a[1]);

        return array_column($weighted, 0);
    }
}
