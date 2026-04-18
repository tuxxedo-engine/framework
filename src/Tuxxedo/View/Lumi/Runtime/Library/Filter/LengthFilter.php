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
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class LengthFilter implements FilterInterface
{
    public private(set) string $name = 'length';
    public private(set) array $aliases = [];

    /**
     * @param \Closure(): RuntimeContextInterface $context
     */
    public function call(
        mixed $value,
        \Closure $context,
    ): int {

        if ($value instanceof \Countable) {
            return $value->count();
        } elseif (\is_array($value)) {
            return \sizeof($value);
        }

        /** @var string $value */

        return \mb_strlen($value);
    }
}
