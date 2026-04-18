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
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class SplitFunction implements FunctionInterface
{
    public private(set) string $name = 'split';
    public private(set) array $aliases = [];

    /**
     * @param mixed[] $arguments
     * @param \Closure(): RuntimeContextInterface $context
     * @return string[]
     */
    public function call(
        array $arguments,
        \Closure $context,
    ): array {
        /** @var string $value */
        $value = $arguments[0];

        /** @var non-empty-string $separator */
        $separator = $arguments[1];

        return \explode($separator, $value);
    }
}
