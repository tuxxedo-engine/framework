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

namespace Tuxxedo\Logger\Config;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Logger\FilterLogger;
use Tuxxedo\Logger\LogLevel;
use Tuxxedo\Logger\LoggerInterface;
use Tuxxedo\Temporal\ClockInterface;

class FilterLoggerConfig implements FilterLoggerConfigInterface
{
    public function __construct(
        public readonly LogLevel $minimum,
        public readonly LoggerConfigInterface $logger,
    ) {
    }

    public function createLogger(
        ContainerInterface $container,
    ): LoggerInterface {
        return new FilterLogger(
            minimum: $this->minimum,
            logger: $this->logger->createLogger($container),
            clock: $container->resolve(ClockInterface::class),
        );
    }
}
