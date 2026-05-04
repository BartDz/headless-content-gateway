<?php
declare(strict_types=1);
namespace App\Adapter;

use App\Model\ContentCollection;
use App\Model\ContentEntry;
use App\Model\ContentQuery;

interface CmsAdapterInterface
{
    public function fetchEntry(string $type, string $slug, string $locale): ContentEntry;
    public function fetchCollection(string $type, ContentQuery $query): ContentCollection;
    public function supports(string $adapterName): bool;
}
