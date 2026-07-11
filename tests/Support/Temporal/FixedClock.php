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

namespace Support\Temporal;

use Tuxxedo\Temporal\ClockInterface;

class FixedClock implements ClockInterface
{
    public function __construct(
        private \DateTimeImmutable $now,
    ) {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function setNow(
        \DateTimeImmutable $now,
    ): void {
        $this->now = $now;
    }
}
