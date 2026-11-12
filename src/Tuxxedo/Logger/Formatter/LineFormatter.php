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

namespace Tuxxedo\Logger\Formatter;

use Tuxxedo\Logger\LogRecordInterface;

class LineFormatter implements LogFormatterInterface
{
    private readonly LogMessageInterpolatorInterface $interpolator;

    public function __construct(
        ?LogMessageInterpolatorInterface $interpolator = null,
    ) {
        $this->interpolator = $interpolator ?? new LogMessageInterpolator();
    }

    public function format(
        LogRecordInterface $record,
    ): string {
        return \sprintf(
            '[%s] [%s] %s',
            $record->timestamp->format('Y-m-d\TH:i:s.uP'),
            $record->level->name,
            $this->interpolator->interpolate($record),
        ) . \PHP_EOL;
    }
}
