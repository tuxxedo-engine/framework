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

namespace Tuxxedo\View\Lumi\Runtime;

use Tuxxedo\View\Lumi\Library\Function\PhpFunctionInterface;
use Tuxxedo\View\Lumi\LumiEngineInterface;
use Tuxxedo\View\Lumi\LumiViewRenderInterface;
use Tuxxedo\View\View;

class Runtime implements RuntimeInterface
{
    /**
     * @var array<string, PhpFunctionInterface>
     */
    public readonly array $phpFunctions;

    /**
     * @var array<string, \Closure(mixed[], RuntimeInterface): mixed>
     */
    public readonly array $filterDispatchers;

    /**
     * @var array<string, \Closure(mixed[], RuntimeInterface): mixed>
     */
    public readonly array $functionDispatchers;

    /**
     * @var array<array<string, string|int|float|bool|null>>
     */
    public private(set) array $directivesStack = [];

    /**
     * @var array<array<string, \Closure(array<string, mixed>): void>>
     */
    public private(set) array $blocksStack = [];

    public private(set) LumiViewRenderInterface $renderer;
    public private(set) array $blocks = [];

    /**
     * @param \Closure(class-string): object $instanceResolver
     * @param array<string, string|int|float|bool|null> $directives
     * @param array<string, PhpFunctionInterface> $phpFunctions
     * @param array<string, \Closure(mixed[], RuntimeInterface): mixed> $filterDispatchers
     * @param array<string, \Closure(mixed[], RuntimeInterface): mixed> $functionDispatchers
     * @param array<class-string, object> $instances
     * @param array<class-string> $instanceCallClasses
     */
    public function __construct(
        public readonly LumiEngineInterface $engine,
        public readonly \Closure $instanceResolver,
        public private(set) array $directives = [],
        array $phpFunctions = [],
        public readonly RuntimeFunctionPolicy $functionPolicy = RuntimeFunctionPolicy::CUSTOM_ONLY,
        public readonly array $instanceCallClasses = [],
        array $filterDispatchers = [],
        array $functionDispatchers = [],
        public private(set) array $instances = [],
    ) {
        $this->phpFunctions = \array_change_key_case($phpFunctions);
        $this->filterDispatchers = \array_change_key_case($filterDispatchers);
        $this->functionDispatchers = \array_change_key_case($functionDispatchers);
    }

    /**
     * @param class-string $className
     */
    public function resolveInstance(
        string $className,
    ): object {
        return $this->instances[$className] ??= ($this->instanceResolver)($className);
    }

    public function renderer(
        LumiViewRenderInterface $render,
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
            throw RuntimeException::fromUnableToPopStateStack();
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
            throw RuntimeException::fromFunctionCallsDisabled();
        }

        $key = \strtolower($function);
        $dispatcher = $this->functionDispatchers[$key] ?? null;

        if ($dispatcher !== null) {
            if (!isset($this->renderer)) {
                throw RuntimeException::fromCannotCallCustomFunctionWithRender();
            }

            return $dispatcher($arguments, $this);
        }

        if (\array_key_exists($key, $this->phpFunctions)) {
            /** @var callable-string $callable */
            $callable = $this->phpFunctions[$key]->mappedName ?? $this->phpFunctions[$key]->name;

            return \call_user_func_array($callable, $arguments);
        }

        if ($this->functionPolicy === RuntimeFunctionPolicy::CUSTOM_ONLY) {
            throw RuntimeException::fromCannotCallCustomFunction(
                function: $function,
            );
        }

        /** @var callable-string $function */

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

            throw RuntimeException::fromCannotAccessNonObject();
        } elseif (
            \sizeof($this->instanceCallClasses) > 0 &&
            !\in_array($instance::class, $this->instanceCallClasses, true)
        ) {
            throw RuntimeException::fromCannotCallInstance(
                class: $instance::class,
            );
        } elseif ($instance === $this) {
            throw RuntimeException::fromCannotAccessThis();
        }

        return $instance;
    }

    public function filter(
        mixed $value,
        string $filter,
    ): mixed {
        if (!isset($this->renderer)) {
            throw RuntimeException::fromCannotCallCustomFunctionWithRender();
        }

        $dispatcher = $this->filterDispatchers[\strtolower($filter)] ?? throw RuntimeException::fromUnknownFilterCall(
            filter: $filter,
        );

        return $dispatcher(
            [
                $value,
            ],
            $this,
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

            throw RuntimeException::fromCannotAccessNonObject();
        }

        if ($instance === $this) {
            throw RuntimeException::fromCannotAccessThis();
        }

        return $instance;
    }

    public function assertThis(
        mixed $value,
    ): void {
        if ($value === $this) {
            throw RuntimeException::fromCannotAccessThis();
        }
    }

    public function hasBlock(
        string $name,
    ): bool {
        return \array_key_exists($name, $this->blocks);
    }

    public function executeBlock(
        string $name,
        array &$scope,
    ): void {
        if (!\array_key_exists($name, $this->blocks)) {
            throw RuntimeException::fromInvalidBlock(
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
        array $scope = [],
    ): void {
        if (!isset($this->renderer)) {
            throw RuntimeException::fromCannotCallCustomFunctionWithRender();
        }

        echo ($this->renderer)->render(
            view: new View(
                name: $file,
                scope: $scope,
            ),
            directives: $this->directives,
            blocks: $this->blocks,
        );
    }

    public function include(
        mixed $file,
        array $scope = [],
    ): void {
        if (!\is_string($file)) {
            throw RuntimeException::fromInvalidIncludeFile();
        }

        $resolved = \realpath($this->renderer->getViewFileName($file));
        $base = \realpath($this->renderer->loader->directory);

        if ($resolved === false || $base === false || !\str_starts_with($resolved, $base)) {
            throw RuntimeException::fromInvalidIncludeFile();
        }

        echo $this->renderer->render(
            view: new View(
                name: $file,
                scope: $scope,
            ),
            directives: $this->directives,
        );
    }
}
