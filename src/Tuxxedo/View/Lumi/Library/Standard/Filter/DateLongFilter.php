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

use Tuxxedo\Temporal\TemporalCoercion;
use Tuxxedo\View\Lumi\Library\Filter\FilterInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class DateLongFilter implements FilterInterface
{
    public private(set) string $name = 'date_long';
    public private(set) array $aliases = [];

    /**
     * @param \Closure(): RuntimeContextInterface $context
     */
    public function call(
        mixed $value,
        \Closure $context,
    ): string {
        return TemporalCoercion::format(
            value: $value,
            pattern: 'l, F j, Y g:i A',
        );
    }
}
