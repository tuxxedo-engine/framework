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
use Tuxxedo\Logger\GroupLogger;
use Tuxxedo\Logger\LoggerInterface;
use Tuxxedo\Temporal\ClockInterface;

class GroupLoggerConfig implements GroupLoggerConfigInterface
{
    /**
     * @param list<LoggerConfigInterface> $loggers
     */
    public function __construct(
        public readonly array $loggers,
    ) {
    }

    public function createLogger(
        ContainerInterface $container,
    ): LoggerInterface {
        $loggers = [];

        foreach ($this->loggers as $logger) {
            $loggers[] = $logger->createLogger($container);
        }

        return new GroupLogger(
            loggers: $loggers,
            clock: $container->resolve(ClockInterface::class),
        );
    }
}
