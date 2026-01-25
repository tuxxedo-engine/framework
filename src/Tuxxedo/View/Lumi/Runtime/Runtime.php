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

use Tuxxedo\View\Lumi\LumiEngineInterface;
use Tuxxedo\View\Lumi\Runtime\Directive\Directives;
use Tuxxedo\View\Lumi\Runtime\Directive\DirectivesInterface;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewException;
use Tuxxedo\View\ViewRenderInterface;

class Runtime implements RuntimeInterface
{
    public private(set) array $directives;

    /**
     * @var array<array<string, string|int|float|bool|null>>
     */
    public private(set) array $directivesStack = [];

    /**
     * @var array<array<string, \Closure(array<string, mixed>): void>>
     */
    public private(set) array $blocksStack = [];

    public private(set) ViewRenderInterface $renderer;

    public array $blocks = [];

    /**
     * @param array<string, string|int|float|bool|null> $directives
     * @param string[] $functions
     * @param array<string, \Closure(array<mixed> $arguments, ViewRenderInterface $render, DirectivesInterface $directives): mixed> $customFunctions
     * @param array<class-string> $instanceCallClasses
     * @param array<string, \Closure(mixed $input, DirectivesInterface $directives): mixed> $filters
     */
    public function __construct(
        public readonly LumiEngineInterface $engine,
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

    public function pushState(
        ?array $directives = null,
        ?array $blocks = null,
    ): void {
        \array_push($this->directivesStack, $this->directives);
        \array_push($this->blocksStack, $this->blocks);

        $this->directives = $directives ?? $this->directives;
        $this->blocks = $blocks ?? [];
    }

    public function popState(): void
    {
        $directives = \array_pop($this->directivesStack);
        $blocks = \array_pop($this->blocksStack);

        if ($directives === null || $blocks === null) {
            throw ViewException::fromUnableToPopStateStack();
        }

        $this->directives = $directives;
        $this->blocks = $blocks;
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
        mixed $instance,
        bool $nullSafe = false,
    ): ?object {
        if (!\is_object($instance)) {
            if ($nullSafe) {
                return null;
            }

            throw ViewException::fromCannotAccessNonObject();
        } elseif (
            \sizeof($this->instanceCallClasses) > 0 &&
            !\in_array($instance::class, $this->instanceCallClasses, true)
        ) {
            throw ViewException::fromCannotCallInstance(
                class: $instance::class,
            );
        } elseif ($instance === $this) {
            throw ViewException::fromCannotAccessThis();
        }

        return $instance;
    }

    public function hasFilter(
        string $filter,
    ): bool {
        return \array_key_exists($filter, $this->filters);
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

    public function propertyAccess(
        mixed $instance,
        bool $nullSafe = false,
    ): ?object {
        if (!\is_object($instance)) {
            if ($nullSafe) {
                return null;
            }

            throw ViewException::fromCannotAccessNonObject();
        }

        if ($instance === $this) {
            throw ViewException::fromCannotAccessThis();
        }

        return $instance;
    }

    public function assertThis(
        mixed $value,
    ): void {
        if ($value === $this) {
            throw ViewException::fromCannotAccessThis();
        }
    }

    public function hasBlock(
        string $name,
    ): bool {
        return \array_key_exists($name, $this->blocks);
    }

    public function blockExecute(
        string $name,
        array &$scope,
    ): void {
        if (!\array_key_exists($name, $this->blocks)) {
            throw ViewException::fromInvalidBlock(
                name: $name,
            );
        }

        ($this->blocks[$name])($scope);
    }

    public function block(
        string $name,
        \Closure $block,
    ): void {
        $this->blocks[$name] = $block;
    }

    public function layout(
        string $file,
    ): void {
        if (!isset($this->renderer)) {
            throw ViewException::fromCannotCallCustomFunctionWithRender();
        }

        echo ($this->renderer)->render(
            view: new View(
                name: $file,
            ),
            directives: $this->directives,
            blocks: $this->blocks,
        );
    }

    public function highlight(
        string $theme,
        string $sourceCode,
    ): void {
        echo $this->engine->highlightString(
            source: $sourceCode,
            theme: $theme,
            optimized: false,
        );
    }
}
