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

class PriorityOrderedSubscriber
{
    /**
     * @var string[]
     */
    public array $calls = [];

    #[Listener(priority: ListenerPriority::LOW)]
    public function onLow(
        #[Event] OrderPlaced $event,
    ): void {
        $this->calls[] = 'low';
    }

    #[Listener(priority: ListenerPriority::HIGH)]
    public function onHigh(
        #[Event] OrderPlaced $event,
    ): void {
        $this->calls[] = 'high';
    }

    #[Listener(priority: ListenerPriority::NORMAL)]
    public function onNormal(
        #[Event] OrderPlaced $event,
    ): void {
        $this->calls[] = 'normal';
    }
}
