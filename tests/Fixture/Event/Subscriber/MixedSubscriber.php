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

class MixedSubscriber
{
    /**
     * @var string[]
     */
    public array $calls = [];

    #[Listener]
    public function tagged(
        #[Event] OrderPlaced $event,
    ): void {
        $this->calls[] = 'tagged';
    }

    public function untagged(
        OrderPlaced $event,
    ): void {
        $this->calls[] = 'untagged';
    }

    #[Listener]
    public function listenerWithoutEventParameter(
        OrderPlaced $event,
    ): void {
        $this->calls[] = 'no-event-attribute';
    }
}
