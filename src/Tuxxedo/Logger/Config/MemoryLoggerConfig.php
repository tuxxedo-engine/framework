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
use Tuxxedo\Logger\MemoryLogger;
use Tuxxedo\Temporal\ClockInterface;

class MemoryLoggerConfig extends AbstractLoggerConfig implements MemoryLoggerConfigInterface
{
    public function createLogger(
        ContainerInterface $container,
    ): LoggerInterface {
        return new MemoryLogger(
            container: $container,
            clock: $container->resolve(ClockInterface::class),
            processors: $this->createProcessors($container),
            processorFailure: $this->processorFailure,
        );
    }
}
