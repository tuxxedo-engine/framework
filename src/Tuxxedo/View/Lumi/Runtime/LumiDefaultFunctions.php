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

namespace Tuxxedo\View\Lumi\Runtime;

use Tuxxedo\View\View;
use Tuxxedo\View\ViewRenderInterface;

class LumiDefaultFunctions
{
    /**
     * @param array<mixed> $arguments
     */
    private function includeImplementation(
        array $arguments,
        ViewRenderInterface $render,
        LumiDirectivesInterface $directives,
    ): mixed {
        /** @var string $view */
        $view = $arguments[0];

        /** @var array<string, mixed> $scope */
        $scope = $arguments[1] ?? [];

        return $render->render(
            view: new View(
                name: $view,
                scope: $scope,
            ),
            directives: $directives->directives,
        );
    }

    /**
     * @return \Generator<array{0: string, 1: \Closure(array<mixed> $arguments, ViewRenderInterface $render, LumiDirectivesInterface $directives): mixed}>
     */
    public function export(): \Generator
    {
        yield [
            'include',
            $this->includeImplementation(...),
        ];
    }
}
