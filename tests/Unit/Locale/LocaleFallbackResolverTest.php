<?php
declare(strict_types=1);

use App\Locale\LocaleFallbackResolver;

it('parses simple Accept-Language header', function () {
    $resolver = new LocaleFallbackResolver();
    expect($resolver->resolve('en'))->toBe(['en']);
});

it('parses multiple locales with quality values', function () {
    $resolver = new LocaleFallbackResolver();
    expect($resolver->resolve('pl,en;q=0.9,fr;q=0.8'))->toBe(['pl', 'en', 'fr']);
});

it('sorts locales by quality descending', function () {
    $resolver = new LocaleFallbackResolver();
    expect($resolver->resolve('fr;q=0.5,pl,en;q=0.9'))->toBe(['pl', 'en', 'fr']);
});

it('returns en as default when header is empty', function () {
    $resolver = new LocaleFallbackResolver();
    expect($resolver->resolve(''))->toBe(['en']);
});

it('returns en as default when header is whitespace only', function () {
    $resolver = new LocaleFallbackResolver();
    expect($resolver->resolve('   '))->toBe(['en']);
});

it('handles single locale with quality', function () {
    $resolver = new LocaleFallbackResolver();
    expect($resolver->resolve('de;q=0.7'))->toBe(['de']);
});
