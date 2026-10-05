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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\Lumi\LumiEngineInterface;
use Tuxxedo\View\Lumi\LumiViewRenderInterface;
use Tuxxedo\View\View;

class Runtime implements RuntimeInterface
{
    /**
     * @var array<string, FunctionInterface>
     */
    public readonly array $functions;

    /**
     * @var array<array<string, string|int|float|bool|null>>
     */
    public private(set) array $directivesStack = [];

    /**
     * @var array<array<string, \Closure(array<string, mixed>): void>>
     */
    public private(set) array $blocksStack = [];

    public private(set) LumiViewRenderInterface $renderer;

    public array $blocks = [];

    /**
     * @param array<string, string|int|float|bool|null> $directives
     * @param array<string, FunctionInterface> $functions
     * @param array<class-string> $instanceCallClasses
     */
    public function __construct(
        public readonly LumiEngineInterface $engine,
        public private(set) array $directives = [],
        array $functions = [],
        public readonly RuntimeFunctionPolicy $functionPolicy = RuntimeFunctionPolicy::CUSTOM_ONLY,
        public readonly array $instanceCallClasses = [],
        public readonly ?ContainerInterface $container = null,
    ) {
        $this->functions = \array_change_key_case($functions);
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
        } elseif (
            $this->functionPolicy === RuntimeFunctionPolicy::CUSTOM_ONLY &&
            !\array_key_exists($function, $this->functions)
        ) {
            throw RuntimeException::fromCannotCallCustomFunction(
                function: $function,
            );
        }

        if (\array_key_exists($function, $this->functions)) {
            if (!isset($this->renderer)) {
                throw RuntimeException::fromCannotCallCustomFunctionWithRender();
            }

            return ($this->functions[$function])->call(
                arguments: $arguments,
                context: fn (): RuntimeContextInterface => new RuntimeContext(
                    runtime: $this,
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

        $introspector = $this->engine->compiler->introspector;

        if (!$introspector->hasFilter($filter)) {
            throw RuntimeException::fromUnknownFilterCall(
                filter: $filter,
            );
        }

        $metadata = $introspector->getFilter($filter);

        return $this->dispatchTypedFilter(
            value: $value,
            className: $metadata->className,
            methodName: $metadata->methodName,
            contextParameterIndex: $metadata->contextParameterIndex,
        );
    }

    /**
     * @param class-string $className
     */
    private function dispatchTypedFilter(
        mixed $value,
        string $className,
        string $methodName,
        ?int $contextParameterIndex,
    ): mixed {
        if ($this->container === null) {
            throw RuntimeException::fromTypedCallableWithoutContainer();
        }

        $handler = $this->container->resolve($className);
        $arguments = $contextParameterIndex === 0
            ? [
                new RuntimeContext(
                    runtime: $this,
                ),
                $value,
            ]
            : (
                $contextParameterIndex === null
                    ? [
                        $value,
                    ]
                    : [
                        $value,
                        new RuntimeContext(
                            runtime: $this,
                        ),
                    ]
            );

        /** @var callable $callable */
        $callable = [
            $handler,
            $methodName,
        ];

        return \call_user_func_array($callable, $arguments);
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
