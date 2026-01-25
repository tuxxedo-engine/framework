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

use Tuxxedo\View\Lumi\Runtime\Directive\DirectivesInterface;
use Tuxxedo\View\Lumi\Runtime\Function\FunctionInterface;
use Tuxxedo\View\ViewException;
use Tuxxedo\View\ViewRenderInterface;

class DirectiveFunction implements FunctionInterface
{
    public private(set) string $name = 'directive';

    public function call(
        array $arguments,
        ViewRenderInterface $render,
        DirectivesInterface $directives,
    ): mixed {
        /** @var string $directive */
        $directive = $arguments[0];

        if (!$directives->has($directive)) {
            throw ViewException::fromInvalidDirective(
                directive: $directive,
            );
        }

        return $directives->directives[$directive];
    }
}
