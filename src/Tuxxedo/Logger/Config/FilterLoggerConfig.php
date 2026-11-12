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
use Tuxxedo\Logger\LogFailureMode;
use Tuxxedo\Logger\LogLevel;
use Tuxxedo\Logger\LoggerInterface;
use Tuxxedo\Logger\Processor\LogProcessorInterface;
use Tuxxedo\Temporal\ClockInterface;

class FilterLoggerConfig extends AbstractLoggerConfig implements FilterLoggerConfigInterface
{
    /**
     * @param list<class-string<LogProcessorInterface>|LogProcessorInterface> $processors
     */
    public function __construct(
        public readonly LogLevel $minimum,
        public readonly LoggerConfigInterface $logger,
        array $processors = [],
        LogFailureMode $processorFailure = LogFailureMode::MARKER,
    ) {
        parent::__construct(
            processors: $processors,
            processorFailure: $processorFailure,
        );
    }

    public function createLogger(
        ContainerInterface $container,
    ): LoggerInterface {
        return new FilterLogger(
            container: $container,
            minimum: $this->minimum,
            logger: $this->logger->createLogger($container),
            clock: $container->resolve(ClockInterface::class),
            processors: $this->createProcessors($container),
            processorFailure: $this->processorFailure,
        );
    }
}
