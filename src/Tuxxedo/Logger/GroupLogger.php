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

namespace Tuxxedo\Logger;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Logger\Processor\LogProcessorInterface;
use Tuxxedo\Temporal\ClockInterface;

class GroupLogger extends AbstractLogger
{
    /**
     * @param list<LoggerInterface> $loggers
     * @param list<LogProcessorInterface> $processors
     */
    public function __construct(
        ContainerInterface $container,
        private readonly array $loggers,
        ?ClockInterface $clock = null,
        array $processors = [],
        LogFailureMode $processorFailure = LogFailureMode::MARKER,
    ) {
        parent::__construct(
            container: $container,
            clock: $clock,
            processors: $processors,
            processorFailure: $processorFailure,
        );
    }

    protected function writeRecord(
        LogRecordInterface $record,
    ): void {
        foreach ($this->loggers as $logger) {
            $logger->write($record);
        }
    }
}
