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

namespace Tuxxedo\Http\Client;

use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\DurationInterface;

readonly class RetryPolicy implements RetryPolicyInterface
{
    public DurationInterface $delay;
    public DurationInterface $maxDelay;

    /**
     * @param list<int> $statuses
     */
    public function __construct(
        public int $times = 3,
        ?DurationInterface $delay = null,
        public float $multiplier = 2.0,
        ?DurationInterface $maxDelay = null,
        public array $statuses = [
            429,
            503,
        ],
        public bool $retryNonIdempotent = false,
    ) {
        $this->delay = $delay ?? Duration::fromMilliseconds(200);
        $this->maxDelay = $maxDelay ?? Duration::fromSeconds(5);
    }
}
