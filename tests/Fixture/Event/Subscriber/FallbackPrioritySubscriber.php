<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Fixture\Event\Subscriber;

use Fixture\Event\OrderPlaced;
use Tuxxedo\Event\Attribute\Event;
use Tuxxedo\Event\Attribute\Listener;
use Tuxxedo\Event\ListenerPriority;

class FallbackPrioritySubscriber
{
    /**
     * @var string[]
     */
    public array $calls = [];

    #[Listener]
    public function onFallback(
        #[Event] OrderPlaced $event,
    ): void {
        $this->calls[] = 'fallback';
    }

    #[Listener(priority: ListenerPriority::HIGH)]
    public function onAttributeHigh(
        #[Event] OrderPlaced $event,
    ): void {
        $this->calls[] = 'attribute-high';
    }
}
