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

namespace Tuxxedo\Temporal;

class AdvancingClock implements ClockInterface
{
    private InstantInterface $current;

    public function __construct(
        InstantInterface $start,
        public readonly DurationInterface $step,
    ) {
        $this->current = $start;
    }

    public function now(): InstantInterface
    {
        $current = $this->current;
        $this->current = $current->plus(
            duration: $this->step,
        );

        return $current;
    }
}
