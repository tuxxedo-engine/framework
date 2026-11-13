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

use Tuxxedo\Temporal\DurationInterface;

interface RetryPolicyInterface
{
    public int $times {
        get;
    }

    public DurationInterface $delay {
        get;
    }

    public float $multiplier {
        get;
    }

    public DurationInterface $maxDelay {
        get;
    }

    /**
     * @var list<int>
     */
    public array $statuses {
        get;
    }

    public bool $retryNonIdempotent {
        get;
    }
}
