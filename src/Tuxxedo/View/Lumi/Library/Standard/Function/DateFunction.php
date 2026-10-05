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

namespace Tuxxedo\View\Lumi\Library\Standard\Function;

use Tuxxedo\Temporal\ClockInterface;
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Temporal\LocalDateInterface;
use Tuxxedo\Temporal\LocalTimeInterface;
use Tuxxedo\Temporal\SystemClock;
use Tuxxedo\Temporal\TemporalCoercion;
use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class DateFunction implements FunctionInterface
{
    public private(set) string $name = 'date';
    public private(set) array $aliases = [
        'time',
    ];

    public function __construct(
        private readonly ClockInterface $clock = new SystemClock(),
    ) {
    }

    /**
     * @param \Closure(): RuntimeContextInterface $context
     */
    public function call(
        array $arguments,
        \Closure $context,
    ): string {
        /** @var string $format */
        $format = $arguments[0];

        /** @var InstantInterface|LocalDateInterface|LocalTimeInterface|\DateTimeInterface|int|string $value */
        $value = $arguments[1] ?? $this->clock->now();

        return TemporalCoercion::format(
            value: $value,
            pattern: $format,
        );
    }
}
