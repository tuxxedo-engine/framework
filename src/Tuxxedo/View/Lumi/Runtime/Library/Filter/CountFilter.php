<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\View\Lumi\Runtime\Library\Filter;

use Tuxxedo\View\Lumi\Runtime\Directive\DirectivesInterface;
use Tuxxedo\View\Lumi\Runtime\Filter\FilterInterface;

class CountFilter implements FilterInterface
{
    public private(set) string $name = 'count';
    public private(set) array $aliases = [
        'sizeof',
    ];

    public function call(
        mixed $value,
        DirectivesInterface $directives,
    ): int {
        /** @var array<mixed> $value */

        return \sizeof($value);
    }
}
