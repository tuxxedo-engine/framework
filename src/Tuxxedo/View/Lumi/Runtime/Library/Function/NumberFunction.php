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

namespace Tuxxedo\View\Lumi\Runtime\Library\Function;

use Tuxxedo\View\Lumi\Library\Directive\DirectivesInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\ViewRenderInterface;

class NumberFunction implements FunctionInterface
{
    public private(set) string $name = 'number';
    public private(set) array $aliases = [];

    public function call(
        array $arguments,
        ViewRenderInterface $render,
        DirectivesInterface $directives,
    ): string {
        /** @var int|float $number */
        $number = $arguments[0];

        /** @var int $decimals */
        $decimals = $arguments[1] ?? 0;

        /** @var string $decimalSeparator */
        $decimalSeparator = $arguments[2] ?? '.';

        /** @var string $thousandsSeparator */
        $thousandsSeparator = $arguments[3] ?? ',';

        return \number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);
    }
}
