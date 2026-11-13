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

namespace Unit\Http\Client;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Client\RetryPolicy;
use Tuxxedo\Temporal\Duration;

class RetryPolicyTest extends TestCase
{
    public function testDefaults(): void
    {
        $policy = new RetryPolicy();

        self::assertSame(3, $policy->times);
        self::assertSame(200_000_000, $policy->delay->nanoseconds);
        self::assertSame(2.0, $policy->multiplier);
        self::assertSame(5, $policy->maxDelay->seconds);
        self::assertSame(
            [
                429,
                503,
            ],
            $policy->statuses,
        );

        self::assertFalse($policy->retryNonIdempotent);
    }

    public function testValues(): void
    {
        $delay = Duration::fromSeconds(1);
        $maxDelay = Duration::fromSeconds(9);
        $policy = new RetryPolicy(
            times: 1,
            delay: $delay,
            multiplier: 1.5,
            maxDelay: $maxDelay,
            statuses: [
                502,
            ],
            retryNonIdempotent: true,
        );

        self::assertSame(1, $policy->times);
        self::assertSame($delay, $policy->delay);
        self::assertSame(1.5, $policy->multiplier);
        self::assertSame($maxDelay, $policy->maxDelay);
        self::assertSame(
            [
                502,
            ],
            $policy->statuses,
        );

        self::assertTrue($policy->retryNonIdempotent);
    }
}
