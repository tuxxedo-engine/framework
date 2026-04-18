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

namespace Tuxxedo\View\Lumi\Runtime\Library\Filter;

use Tuxxedo\View\Lumi\Library\Filter\FilterInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeFrameInterface;

class CountFilter implements FilterInterface
{
    public private(set) string $name = 'count';
    public private(set) array $aliases = [
        'sizeof',
    ];

    /**
     * @param \Closure(): RuntimeFrameInterface $frame
     */
    public function call(
        mixed $value,
        \Closure $frame,
    ): int {
        /** @var array<mixed> $value */

        return \sizeof($value);
    }
}
