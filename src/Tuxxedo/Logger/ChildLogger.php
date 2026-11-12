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

class ChildLogger extends AbstractLogger
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private readonly LoggerInterface $parent,
        array $context = [],
        ?string $channel = null,
        ?ClockInterface $clock = null,
    ) {
        parent::__construct(
            clock: $clock,
        );

        $this->context = $context;
        $this->channel = $channel;
    }

    protected function writeRecord(
        LogRecordInterface $record,
    ): void {
        $this->parent->write($record);
    }
}
