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
use Tuxxedo\Logger\LogFailureMode;
use Tuxxedo\Logger\LoggerInterface;
use Tuxxedo\Logger\Processor\LogProcessorInterface;
use Tuxxedo\Temporal\ClockInterface;

class GroupLoggerConfig extends AbstractLoggerConfig implements GroupLoggerConfigInterface
{
    /**
     * @param list<LoggerConfigInterface> $loggers
     * @param list<class-string<LogProcessorInterface>|LogProcessorInterface> $processors
     */
    public function __construct(
        public readonly array $loggers,
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
        $loggers = [];

        foreach ($this->loggers as $logger) {
            $loggers[] = $logger->createLogger($container);
        }

        return new GroupLogger(
            container: $container,
            loggers: $loggers,
            clock: $container->resolve(ClockInterface::class),
            processors: $this->createProcessors($container),
            processorFailure: $this->processorFailure,
        );
    }
}
