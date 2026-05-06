<?php

use App\Adapter\WordPress\WordPressAdapter;
use App\Model\ContentQuery;
use Symfony\Component\HttpClient\HttpClient;

/**
 * @group integration
 */
it('fetches real WordPress collection', function () {
    $client = HttpClient::create();
    $adapter = new WordPressAdapter($client, 'http://localhost:8081');

    $col = $adapter->fetchCollection('article', new ContentQuery(limit: 5));

    expect($col->entries)->not->toBeEmpty()
        ->and($col->total)->toBeGreaterThan(0);
})->group('integration');
