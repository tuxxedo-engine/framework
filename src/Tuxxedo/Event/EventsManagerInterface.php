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

// @todo Request deferred event queuing?
#[DefaultImplementation(class: EventsManager::class, lifecycle: Lifecycle::PERSISTENT)]
interface EventsManagerInterface
{
    /**
     * @param class-string|(\Closure(): object)|object $subscriber
     */
    public function registerSubscriber(
        string|object $subscriber,
        ListenerPriority $priority = ListenerPriority::NORMAL,
    ): void;

    public function fire(
        object $event,
    ): void;

    /**
     * @template TEvent of object
     *
     * @param class-string<TEvent> $eventClass
     * @param \Closure(): TEvent $event
     */
    public function fireLazy(
        string $eventClass,
        \Closure $event,
    ): void;
}
