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

namespace Tuxxedo\View\Lumi\Runtime\Function;

use Tuxxedo\View\Lumi\Runtime\Directive\DirectivesInterface;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewException;
use Tuxxedo\View\ViewRenderInterface;

class DefaultFunctions implements FunctionProviderInterface
{
    /**
     * @param array<mixed> $arguments
     *
     * @throws ViewException
     */
    private function includeImplementation(
        array $arguments,
        ViewRenderInterface $render,
        DirectivesInterface $directives,
    ): string {
        /** @var string $view */
        $view = $arguments[0];

        /** @var array<string, mixed> $scope */
        $scope = $arguments[1] ?? [];

        echo $render->render(
            view: new View(
                name: $view,
                scope: $scope,
            ),
            directives: $directives->directives,
        );

        return '';
    }

    /**
     * @param array<mixed> $arguments
     *
     * @throws ViewException
     */
    private function directiveImplementation(
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

    /**
     * @param array<mixed> $arguments
     */
    private function hasDirectiveImplementation(
        array $arguments,
        ViewRenderInterface $render,
        DirectivesInterface $directives,
    ): bool {
        /** @var string $directive */
        $directive = $arguments[0];

        return $directives->has($directive);
    }

    public function export(): \Generator
    {
        yield [
            'include',
            $this->includeImplementation(...),
        ];

        yield [
            'directive',
            $this->directiveImplementation(...),
        ];

        yield [
            'hasDirective',
            $this->hasDirectiveImplementation(...),
        ];
    }
}
