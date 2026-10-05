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

interface RuntimeInterface
{
    /**
     * @var array<string, string|int|float|bool|null>
     */
    public array $directives {
        get;
    }

    /**
     * @var array<string, PhpFunctionInterface>
     */
    public array $phpFunctions {
        get;
    }

    /**
     * @var array<string, \Closure(mixed[], RuntimeInterface): mixed>
     */
    public array $filterDispatchers {
        get;
    }

    /**
     * @var array<string, \Closure(mixed[], RuntimeInterface): mixed>
     */
    public array $functionDispatchers {
        get;
    }

    /**
     * @var array<class-string, object>
     */
    public array $instances {
        get;
    }

    /**
     * @var \Closure(class-string): object
     */
    public \Closure $instanceResolver {
        get;
    }

    public RuntimeFunctionPolicy $functionPolicy {
        get;
    }

    public LumiEngineInterface $engine {
        get;
    }

    /**
     * @var array<class-string>
     */
    public array $instanceCallClasses {
        get;
    }

    /**
     * @var array<string, \Closure(array<string, mixed>): void>
     */
    public array $blocks {
        get;
    }

    /**
     * @param class-string $className
     */
    public function resolveInstance(
        string $className,
    ): object;

    public function renderer(
        LumiViewRenderInterface $render,
    ): void;

    /**
     * @param array<string, string|int|float|bool|null>|null $directives
     * @param array<string, \Closure(array<string, mixed>): void>|null $blocks
     */
    public function pushState(
        ?array $directives = null,
        ?array $blocks = null,
    ): void;

    /**
     * @throws RuntimeException
     */
    public function popState(): void;

    public function directive(
        string $directive,
        string|int|float|bool|null $value,
    ): void;

    /**
     * @param mixed[] $arguments
     *
     * @throws RuntimeException
     */
    public function functionCall(
        string $function,
        array $arguments = [],
    ): mixed;

    /**
     * @throws RuntimeException
     */
    public function instanceCall(
        mixed $instance,
        bool $nullSafe = false,
    ): ?object;

    /**
     * @throws RuntimeException
     */
    public function filter(
        mixed $value,
        string $filter,
    ): mixed;

    /**
     * @throws RuntimeException
     */
    public function propertyAccess(
        mixed $instance,
        bool $nullSafe = false,
    ): ?object;

    /**
     * @throws RuntimeException
     */
    public function assertThis(
        mixed $value,
    ): void;

    public function hasBlock(
        string $name,
    ): bool;

    /**
     * @param array<string, mixed> $scope
     *
     * @throws RuntimeException
     */
    public function executeBlock(
        string $name,
        array &$scope,
    ): void;

    /**
     * @param \Closure(array<string, mixed>): void $block
     */
    public function block(
        string $name,
        \Closure $block,
    ): void;

    /**
     * @param array<string, mixed> $scope
     *
     * @throws RuntimeException
     */
    public function layout(
        string $file,
        array $scope = [],
    ): void;

    /**
     * @param array<string, mixed> $scope
     *
     * @throws RuntimeException
     */
    public function include(
        mixed $file,
        array $scope = [],
    ): void;
}
