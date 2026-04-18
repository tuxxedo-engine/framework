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

class JsonFunction implements FunctionInterface
{
    public private(set) string $name = 'json';
    public private(set) array $aliases = [];

    /**
     * @param \Closure(): RuntimeFrameInterface $frame
     */
    public function call(
        array $arguments,
        \Closure $frame,
    ): string {
        /** @var string $value */
        $value = $arguments[0];

        return \json_encode($value, \JSON_THROW_ON_ERROR);
    }
}
