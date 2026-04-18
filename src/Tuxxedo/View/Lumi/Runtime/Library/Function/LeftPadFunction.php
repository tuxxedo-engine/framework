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

namespace Tuxxedo\View\Lumi\Runtime\Library\Function;

use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeFrameInterface;

class LeftPadFunction implements FunctionInterface
{
    public private(set) string $name = 'leftPad';
    public private(set) array $aliases = [];

    /**
     * @param \Closure(): RuntimeFrameInterface $frame
     */
    public function call(
        array $arguments,
        \Closure $frame,
    ): string {
        /** @var string $string */
        $string = $arguments[0];

        /** @var int $length */
        $length = $arguments[1];

        /** @var string $padString */
        $padString = $arguments[2] ?? ' ';

        return \mb_str_pad($string, $length, $padString, \STR_PAD_LEFT);
    }
}
