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
use Tuxxedo\View\View;
use Tuxxedo\View\ViewRenderInterface;

class IncludeFunction implements FunctionInterface
{
    public private(set) string $name = 'include';

    public function call(
        array $arguments,
        ViewRenderInterface $render,
        DirectivesInterface $directives,
    ): mixed {
        /** @var string $view */
        $view = $arguments[0];

        /** @var array<string, mixed> $scope */
        $scope = $arguments[1] ?? [];

        // @todo This is not a return statement due to autoescape mode, would be nice if $directives could read the
        //       the current state, but that can be mutated with {% declare ... %}, maybe function arguments needs a
        //       an execution frame to keep state?
        echo $render->render(
            view: new View(
                name: $view,
                scope: $scope,
            ),
            directives: $directives->directives,
        );

        return '';
    }
}
