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

use Tuxxedo\View\Lumi\Library\Attribute\LumiFilter;

class CollectionFilters
{
    /**
     * @param array<mixed> $value
     */
    #[LumiFilter('count', aliases: ['sizeof'])]
    public function count(
        array $value,
    ): int {
        return \sizeof($value);
    }

    /**
     * @param string|array<mixed>|\Countable $value
     */
    #[LumiFilter('length')]
    public function length(
        string|array|\Countable $value,
    ): int {
        if ($value instanceof \Countable) {
            return $value->count();
        }

        if (\is_array($value)) {
            return \sizeof($value);
        }

        return \mb_strlen($value);
    }
}
