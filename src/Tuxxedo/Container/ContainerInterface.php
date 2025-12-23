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

namespace Tuxxedo\Container;

// @todo Consider renaming methods like bind() to be more descriptive
// @todo Consider whether sealing should be emitting a readonly copy? Or maybe there needs to be some strategies here
interface ContainerInterface
{
    public bool $sealed {
        get;
    }

    public function seal(): void;

    /**
     * @param class-string|object $class
     */
    public function bind(
        string|object $class,
        bool $bindInterfaces = true,
        bool $bindParent = true,
    ): static;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $class
     * @param (\Closure(self): TClassName) $initializer
     */
    public function lazy(
        string $class,
        \Closure $initializer,
        bool $bindInterfaces = true,
        bool $bindParent = true,
    ): static;

    /**
     * @param class-string|class-string[] $aliasClassName
     * @param class-string $resolvedClassName
     */
    public function alias(
        string|array $aliasClassName,
        string $resolvedClassName,
    ): static;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $className
     * @return TClassName
     *
     * @throws ContainerException
     */
    public function resolve(
        string $className,
    ): object;

    /**
     * @param array<array-key, mixed> $arguments
     */
    public function call(
        \Closure $callable,
        array $arguments = [],
    ): mixed;

    /**
     * @param class-string $className
     */
    public function isBound(
        string $className,
    ): bool;

    /**
     * @param class-string $className
     */
    public function isInitialized(
        string $className,
    ): bool;

    /**
     * @param class-string $className
     */
    public function isAlias(
        string $className,
    ): bool;

    /**
     * @param class-string $alias
     * @param class-string $className
     */
    public function isAliasOf(
        string $alias,
        string $className,
    ): bool;
}
