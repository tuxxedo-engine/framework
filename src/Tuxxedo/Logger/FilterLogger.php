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

class FilterLogger extends AbstractLogger
{
    public function __construct(
        public readonly LogLevel $minimum,
        private readonly LoggerInterface $logger,
        ?ClockInterface $clock = null,
    ) {
        parent::__construct(
            clock: $clock,
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
