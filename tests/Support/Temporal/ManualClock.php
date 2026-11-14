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
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\InstantInterface;

class ManualClock implements ClockInterface
{
    public function __construct(
        private InstantInterface $instant,
    ) {
    }

    public function now(): InstantInterface
    {
        return $this->instant;
    }

    public function advance(
        int $milliseconds,
    ): void {
        $this->instant = $this->instant->plus(Duration::fromMilliseconds($milliseconds));
    }
}
