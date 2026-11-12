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
use Tuxxedo\Logger\LoggerInterface;
use Tuxxedo\Logger\NullLogger;
use Tuxxedo\Temporal\ClockInterface;

class NullLoggerConfig extends AbstractLoggerConfig implements NullLoggerConfigInterface
{
    public function createLogger(
        ContainerInterface $container,
    ): LoggerInterface {
        return new NullLogger(
            container: $container,
            clock: $container->resolve(ClockInterface::class),
            processors: $this->createProcessors($container),
            processorFailure: $this->processorFailure,
        );
    }
}
