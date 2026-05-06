<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE)]
class CacheHeaderListener
{
    public function __invoke(ResponseEvent $event): void
    {
        $hit = $event->getRequest()->attributes->get('_cache_hit', false);
        $event->getResponse()->headers->set('X-Cache', $hit ? 'HIT' : 'MISS');
    }
}
