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

class HasDirectiveFunction implements FunctionInterface
{
    public private(set) string $name = 'hasDirective';
    public private(set) array $aliases = [];

    public function call(
        array $arguments,
        ViewRenderInterface $render,
        DirectivesInterface $directives,
    ): bool {
        /** @var string $directive */
        $directive = $arguments[0];

        return $directives->has($directive);
    }
}
