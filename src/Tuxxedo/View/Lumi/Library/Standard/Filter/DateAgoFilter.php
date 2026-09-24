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

namespace Tuxxedo\View\Lumi\Library\Standard\Filter;

use Tuxxedo\Temporal\ClockInterface;
use Tuxxedo\Temporal\Humanizer\Humanizer;
use Tuxxedo\Temporal\SystemClock;
use Tuxxedo\Temporal\TemporalCoercion;
use Tuxxedo\View\Lumi\Library\Filter\FilterInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class DateAgoFilter implements FilterInterface
{
    public private(set) string $name = 'date_ago';
    public private(set) array $aliases = [];

    private readonly Humanizer $humanizer;

    public function __construct(
        ?Humanizer $humanizer = null,
        ?ClockInterface $clock = null,
    ) {
        $this->humanizer = $humanizer ?? new Humanizer($clock ?? new SystemClock());
    }

    /**
     * @param \Closure(): RuntimeContextInterface $context
     */
    public function call(
        mixed $value,
        \Closure $context,
    ): string {
        return $this->humanizer->diffForHumans(TemporalCoercion::toInstant($value));
    }
}
