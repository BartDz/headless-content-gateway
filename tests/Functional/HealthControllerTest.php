<?php
declare(strict_types=1);

it('returns 200 with health status structure', function () {
    $client = static::createClient();
    $client->request('GET', '/health');

    expect($client->getResponse()->getStatusCode())->toBe(200);
    $body = json_decode($client->getResponse()->getContent(), true);
    expect($body)->toHaveKey('status')
        ->and($body)->toHaveKey('adapters')
        ->and($body['status'])->toBeIn(['ok', 'degraded', 'down']);
});

it('returns all four adapters in response', function () {
    $client = static::createClient();
    $client->request('GET', '/health');

    $body = json_decode($client->getResponse()->getContent(), true);
    expect($body['adapters'])->toHaveKey('wordpress')
        ->and($body['adapters'])->toHaveKey('strapi')
        ->and($body['adapters'])->toHaveKey('contentful')
        ->and($body['adapters'])->toHaveKey('storyblok');
});

it('adapter statuses are valid strings', function () {
    $client = static::createClient();
    $client->request('GET', '/health');

    $body = json_decode($client->getResponse()->getContent(), true);
    foreach ($body['adapters'] as $status) {
        expect($status)->toBeIn(['up', 'down']);
    }
});
