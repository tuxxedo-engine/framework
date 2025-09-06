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

use Tuxxedo\View\Lumi\Runtime\Directive\Directives;
use Tuxxedo\View\Lumi\Runtime\Directive\DirectivesInterface;
use Tuxxedo\View\ViewException;
use Tuxxedo\View\ViewRenderInterface;

class Runtime implements RuntimeInterface
{
    public private(set) array $directives;

    /**
     * @var array<array<string, string|int|float|bool|null>>
     */
    public private(set) array $directivesStack = [];

    public private(set) ViewRenderInterface $renderer;

    /**
     * @param array<string, string|int|float|bool|null> $directives
     * @param string[] $functions
     * @param array<string, \Closure(array<mixed> $arguments, ViewRenderInterface $render, DirectivesInterface $directives): mixed> $customFunctions
     * @param array<class-string> $instanceCallClasses
     * @param array<string, \Closure(mixed $input, DirectivesInterface $directives): mixed> $filters
     */
    public function __construct(
        array $directives = [],
        public readonly array $functions = [],
        public readonly array $customFunctions = [],
        public readonly RuntimeFunctionPolicy $functionPolicy = RuntimeFunctionPolicy::CUSTOM_ONLY,
        public readonly array $instanceCallClasses = [],
        public readonly array $filters = [],
    ) {
        $this->directives = $directives;
    }

    public function renderer(
        ViewRenderInterface $render,
    ): void {
        $this->renderer = $render;
    }

    public function pushDirectives(
        ?array $directives = null,
    ): void {
        \array_push($this->directivesStack, $this->directives);

        $this->directives = $directives ?? $this->directives;
    }

    public function popDirectives(): void
    {
        $directives = \array_pop($this->directivesStack);

        if ($directives === null) {
            throw ViewException::fromUnableToPopDirectivesStack();
        }

        $this->directives = $directives;
    }

    public function directive(
        string $directive,
        float|bool|int|string|null $value,
    ): void {
        $this->directives[$directive] = $value;
    }

    public function functionCall(
        string $function,
        array $arguments = [],
    ): mixed {
        if ($this->functionPolicy === RuntimeFunctionPolicy::DISALLOW_ALL) {
            throw ViewException::fromFunctionCallsDisabled();
        } elseif (
            $this->functionPolicy === RuntimeFunctionPolicy::CUSTOM_ONLY &&
            !\array_key_exists($function, $this->customFunctions)
        ) {
            throw ViewException::fromCannotCallCustomFunction(
                function: $function,
            );
        }

        if (\array_key_exists($function, $this->customFunctions)) {
            if (!isset($this->renderer)) {
                throw ViewException::fromCannotCallCustomFunctionWithRender();
            }

            return ($this->customFunctions[$function])(
                $arguments,
                $this->renderer,
                new Directives(
                    directives: $this->directives,
                ),
            );
        }

        return $function(...$arguments);
    }

    public function instanceCall(
        object $instance,
    ): object {
        if (
            \sizeof($this->instanceCallClasses) > 0 &&
            !\array_key_exists($instance::class, $this->instanceCallClasses)
        ) {
            throw ViewException::fromCannotCallInstance(
                class: $instance::class,
            );
        }

        return $instance;
    }

    public function filter(
        mixed $value,
        string $filter,
    ): mixed {
        if (!\array_key_exists($filter, $this->filters)) {
            throw ViewException::fromUnknownFilterCall(
                filter: $filter,
            );
        } elseif (!isset($this->renderer)) {
            throw ViewException::fromCannotCallCustomFunctionWithRender();
        }

        return ($this->filters[$filter])(
            $value,
            new Directives(
                directives: $this->directives,
            ),
        );
    }

    public function filterOrBitwiseOr(
        mixed $left,
        mixed $right,
    ): mixed {
        if (\is_string($right) && \array_key_exists($right, $this->filters)) {
            return $this->filter($left, $right);
        }

        if (!\is_int($left) || !\is_int($right)) {
            throw ViewException::fromInvalidBitwiseOr(
                leftType: \get_debug_type($left),
                rightType: \get_debug_type($right),
            );
        }

        return $left | $right;
    }
}
