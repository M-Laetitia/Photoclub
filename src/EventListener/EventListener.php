<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class EventListener
{
    #[AsEventListener(event: 'UserListener')]
    public function onUserListener($event): void
    {
        // ...
    }
}
