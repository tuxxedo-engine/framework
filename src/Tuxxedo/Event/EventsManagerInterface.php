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

#[DefaultImplementation(class: EventsManager::class, lifecycle: Lifecycle::PERSISTENT)]
interface EventsManagerInterface
{
    /**
     * @template TSubscriber of object
     *
     * @param class-string<TSubscriber>|(\Closure(): TSubscriber)|TSubscriber $subscriber
     */
    public function registerSubscriber(
        string|object $subscriber,
    ): void;

    public function fire(
        object $event,
    ): void;
}
