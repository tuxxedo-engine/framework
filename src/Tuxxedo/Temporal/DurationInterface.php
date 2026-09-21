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

interface DurationInterface
{
    public int $seconds {
        get;
    }

    public int $nanoseconds {
        get;
    }

    public bool $negative {
        get;
    }

    public function add(
        DurationInterface $other,
    ): self;

    public function sub(
        DurationInterface $other,
    ): self;

    /**
     * @throws TemporalException
     */
    public function multiplyBy(
        int $factor,
    ): self;

    /**
     * @throws TemporalException
     */
    public function divideBy(
        int $divisor,
    ): self;

    public function negate(): self;

    public function absolute(): self;

    public function equals(
        DurationInterface $other,
    ): bool;

    public function isZero(): bool;

    public function isPositive(): bool;

    public function isNegative(): bool;
}
