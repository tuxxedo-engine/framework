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

class FilterLogger extends AbstractLogger
{
    /**
     * @param list<LogProcessorInterface> $processors
     */
    public function __construct(
        ContainerInterface $container,
        public readonly LogLevel $minimum,
        private readonly LoggerInterface $logger,
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

    public function write(
        LogRecordInterface $record,
    ): void {
        if (!$record->level->isAtLeast($this->minimum)) {
            return;
        }

        parent::write($record);
    }

    protected function writeRecord(
        LogRecordInterface $record,
    ): void {
        $this->logger->write($record);
    }
}
