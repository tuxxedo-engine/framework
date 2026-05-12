<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

namespace App\Subscribers;

use App\Subscribers\Events\UserCreatedEvent;
use Tuxxedo\Event\Attribute\Event;
use Tuxxedo\Event\Attribute\Listener;
use Tuxxedo\Logger\LoggerInterface;

class UserSubscriber
{
    #[Listener]
    public function onUserCreated(
        #[Event] UserCreatedEvent $event,
        LoggerInterface $logger,
    ): void {
        $logger->info(
            'User created with id #{id}',
            [
                'id' => $event->model->id,
            ],
        );
    }
}
