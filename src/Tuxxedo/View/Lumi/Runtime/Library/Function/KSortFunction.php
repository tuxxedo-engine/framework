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

use Tuxxedo\View\Lumi\Library\Directive\DirectivesInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\ViewRenderInterface;

class KSortFunction implements FunctionInterface
{
    public private(set) string $name = 'ksort';
    public private(set) array $aliases = [];

    /**
     * @param array<mixed> $arguments
     * @return array<mixed>
     */
    public function call(
        array $arguments,
        ViewRenderInterface $render,
        DirectivesInterface $directives,
    ): array {
        /** @var array<mixed> $array */
        $array = $arguments[0];

        \ksort($array);

        return $array;
    }
}
