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

use Tuxxedo\View\Lumi\Library\Directive\DirectivesInterface;
use Tuxxedo\View\Lumi\Library\Filter\FilterInterface;

class Nl2brFilter implements FilterInterface
{
    public private(set) string $name = 'nl2br';
    public private(set) array $aliases = [];

    public function call(
        mixed $value,
        DirectivesInterface $directives,
    ): string {
        /** @var string $value */

        return \nl2br($value, false);
    }
}
