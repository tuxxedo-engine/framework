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

use Fixture\Event\InjectedDependency;
use Fixture\Event\OrderPlaced;
use Tuxxedo\Event\Attribute\Event;
use Tuxxedo\Event\Attribute\Listener;

class InjectedSubscriber
{
    /**
     * @var string[]
     */
    public array $calls = [];

    #[Listener]
    public function onOrderPlaced(
        #[Event] OrderPlaced $event,
        InjectedDependency $dependency,
    ): void {
        $this->calls[] = $event->orderId . ':' . $dependency->value;
    }
}
