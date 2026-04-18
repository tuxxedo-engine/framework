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

class IsOddFunction implements FunctionInterface
{
    public private(set) string $name = 'isOdd';
    public private(set) array $aliases = [];

    /**
     * @param \Closure(): RuntimeFrameInterface $frame
     */
    public function call(
        array $arguments,
        \Closure $frame,
    ): bool {
        /** @var int $input */
        $input = $arguments[0];

        return \intval($input) % 2 !== 0;
    }
}
