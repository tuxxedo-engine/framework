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

use Fixture\Event\StoppableSignal;
use Tuxxedo\Event\Attribute\Event;
use Tuxxedo\Event\Attribute\Listener;
use Tuxxedo\Event\ListenerPriority;

class StoppingSubscriber
{
    /**
     * @var string[]
     */
    public array $calls = [];

    #[Listener(priority: ListenerPriority::HIGH)]
    public function stopFirst(
        #[Event] StoppableSignal $event,
    ): void {
        $this->calls[] = 'stop';

        $event->stopPropagation();
    }

    #[Listener(priority: ListenerPriority::LOW)]
    public function shouldNotRun(
        #[Event] StoppableSignal $event,
    ): void {
        $this->calls[] = 'should-not-run';
    }
}
