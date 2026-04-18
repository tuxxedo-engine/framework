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

class ReplaceFunction implements FunctionInterface
{
    public private(set) string $name = 'replace';
    public private(set) array $aliases = [];

    /**
     * @param \Closure(): RuntimeContextInterface $context
     */
    public function call(
        array $arguments,
        \Closure $context,
    ): string {
        /** @var string $subject */
        $subject = $arguments[0];

        /** @var string|string[] $search */
        $search = $arguments[1];

        /** @var string|string[] $replace */
        $replace = $arguments[2];

        return \str_replace($search, $replace, $subject);
    }
}
