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

namespace Tuxxedo\Event;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

// @todo fireDelayed? So an event can be wrapped in a Closure and only created if its actually used?
// @todo Event prioritization instead of first to come via registerSubscriber()
// @todo Auto discovery feature like Lumi?
// @todo Request deferred event queuing?
#[DefaultImplementation(class: EventsManager::class, lifecycle: Lifecycle::PERSISTENT)]
interface EventsManagerInterface
{
    /**
     * @param class-string|(\Closure(): object)|object $subscriber
     */
    public function registerSubscriber(
        string|object $subscriber,
    ): void;

    public function fire(
        object $event,
    ): void;
}
