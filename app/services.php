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

use App\Subscribers\UserSubscriber;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Event\EventsManagerInterface;
use Tuxxedo\Logger\LoggerInterface;
use Tuxxedo\Logger\StreamLogger;

return static function (
    ContainerInterface $container,
    EventsManagerInterface $eventsManager,
): void {
    $container->singletonLazy(
        StreamLogger::class,
        static fn (): LoggerInterface => StreamLogger::createFromFile(
            file: __DIR__ . '/file.log',
        ),
    );

    $eventsManager->registerSubscriber(UserSubscriber::class);
};
