<?php
declare(strict_types=1);

use App\Adapter\AdapterRegistry;
use App\Adapter\CmsAdapterInterface;
use App\Adapter\Exception\AdapterNotFoundException;
use App\Model\ContentEntry;
use App\Model\ContentCollection;
use App\Model\ContentQuery;

function makeSimpleAdapter(string $name): CmsAdapterInterface
{
    return new class($name) implements CmsAdapterInterface {
        public function __construct(private string $name) {}
        public function supports(string $n): bool { return $n === $this->name; }
        public function fetchEntry(string $t, string $s, string $l): ContentEntry {
            return new ContentEntry('1', $t, $s, $l, null, []);
        }
        public function fetchCollection(string $t, ContentQuery $q): ContentCollection {
            return new ContentCollection([], 0, 1, 10);
        }
    };
}

it('resolves correct adapter by name', function () {
    $registry = new AdapterRegistry([makeSimpleAdapter('wordpress'), makeSimpleAdapter('strapi')]);
    $adapter = $registry->get('wordpress');
    expect($adapter->supports('wordpress'))->toBeTrue();
});

it('returns different adapters for different names', function () {
    $registry = new AdapterRegistry([makeSimpleAdapter('wordpress'), makeSimpleAdapter('strapi')]);
    expect($registry->get('strapi')->supports('strapi'))->toBeTrue();
});

it('throws AdapterNotFoundException for unknown adapter', function () {
    $registry = new AdapterRegistry([makeSimpleAdapter('wordpress')]);
    $registry->get('contentful');
})->throws(AdapterNotFoundException::class, 'No adapter found for: contentful');

it('returns first matching adapter', function () {
    $registry = new AdapterRegistry([makeSimpleAdapter('wordpress'), makeSimpleAdapter('wordpress')]);
    $adapter = $registry->get('wordpress');
    expect($adapter)->toBeInstanceOf(CmsAdapterInterface::class);
});
