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

class LcfirstFilter implements FilterInterface
{
    public private(set) string $name = 'lcfirst';
    public private(set) array $aliases = [];

    public function call(
        mixed $value,
        DirectivesInterface $directives,
    ): string {
        /** @var string $value */

        return \mb_lcfirst($value);
    }
}
