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

class JsonPrettyFunction implements FunctionInterface
{
    public private(set) string $name = 'jsonPretty';
    public private(set) array $aliases = [];

    /**
     * @param \Closure(): RuntimeContextInterface $context
     */
    public function call(
        array $arguments,
        \Closure $context,
    ): string {
        /** @var string $value */
        $value = $arguments[0];

        return \json_encode($value, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
    }
}
