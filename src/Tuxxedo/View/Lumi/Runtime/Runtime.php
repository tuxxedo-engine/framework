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

use Tuxxedo\View\ViewException;
use Tuxxedo\View\ViewRenderInterface;

class Runtime implements RuntimeInterface
{
    public readonly array $defaultDirectives;
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
     */
    public function __construct(
        array $directives = [],
        public private(set) array $functions = [],
        public private(set) array $customFunctions = [],
        public readonly RuntimeFunctionMode $functionMode = RuntimeFunctionMode::CUSTOM_ONLY,
    ) {
        $this->defaultDirectives = $directives;
        $this->directives = $directives;
    }

    public function renderer(
        ViewRenderInterface $render,
    ): void {
        $this->renderer = $render;
    }

    public function resetDirectives(): void
    {
        $this->directives = $this->defaultDirectives;
    }

    public function pushDirectives(
        array $directives,
    ): void {
        \array_push($this->directivesStack, $this->directives);

        $this->directives = $directives;
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
        if ($this->functionMode === RuntimeFunctionMode::DISALLOW_ALL) {
            throw ViewException::fromFunctionCallsDisabled();
        } elseif (
            $this->functionMode === RuntimeFunctionMode::CUSTOM_ONLY &&
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
}
