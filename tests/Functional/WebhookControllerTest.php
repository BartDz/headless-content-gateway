<?php
declare(strict_types=1);

it('returns 401 for missing webhook secret', function () {
    $client = static::createClient();
    $client->request('POST', '/webhooks/wordpress', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([]));

    expect($client->getResponse()->getStatusCode())->toBe(401);
});

it('returns 401 for wrong webhook secret', function () {
    putenv('WEBHOOK_SECRET_WORDPRESS=correct-secret');
    $client = static::createClient();
    $client->request('POST', '/webhooks/wordpress', [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_WEBHOOK_SECRET' => 'wrong-secret',
    ], json_encode([]));

    expect($client->getResponse()->getStatusCode())->toBe(401);
});

it('invalidates specific content type on valid webhook', function () {
    putenv('WEBHOOK_SECRET_WORDPRESS=testsecret');
    $client = static::createClient();
    $client->request('POST', '/webhooks/wordpress', [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_WEBHOOK_SECRET' => 'testsecret',
    ], json_encode(['contentType' => 'article']));

    expect($client->getResponse()->getStatusCode())->toBe(200);
    $body = json_decode($client->getResponse()->getContent(), true);
    expect($body['invalidated'])->toBe('wordpress.article');
});

it('invalidates all types when no contentType in body', function () {
    putenv('WEBHOOK_SECRET_WORDPRESS=testsecret');
    $client = static::createClient();
    $client->request('POST', '/webhooks/wordpress', [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_WEBHOOK_SECRET' => 'testsecret',
    ], json_encode([]));

    expect($client->getResponse()->getStatusCode())->toBe(200);
    $body = json_decode($client->getResponse()->getContent(), true);
    expect($body['invalidated'])->toBe('wordpress');
});
