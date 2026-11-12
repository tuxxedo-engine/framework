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

use Tuxxedo\Temporal\ClockInterface;

class GroupLogger extends AbstractLogger
{
    /**
     * @param list<LoggerInterface> $loggers
     */
    public function __construct(
        private readonly array $loggers,
        ?ClockInterface $clock = null,
    ) {
        parent::__construct(
            clock: $clock,
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
