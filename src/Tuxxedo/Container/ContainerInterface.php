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

interface ContainerInterface
{
    /**
     * @param class-string|object $class
     *
     * @throws ContainerException
     */
    public function transient(
        string|object $class,
        bool $bindInterfaces = true,
        bool $bindParent = true,
    ): static;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $class
     * @param (\Closure(ContainerInterface $container, array<mixed> $arguments): TClassName) $initializer
     *
     * @throws ContainerException
     */
    public function transientLazy(
        string $class,
        \Closure $initializer,
        bool $bindInterfaces = true,
        bool $bindParent = true,
    ): static;

    /**
     * @param class-string|object $class
     *
     * @throws ContainerException
     */
    public function persistent(
        string|object $class,
        bool $bindInterfaces = true,
        bool $bindParent = true,
    ): static;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $class
     * @param (\Closure(ContainerInterface $container, array<mixed> $arguments): TClassName) $initializer
     *
     * @throws ContainerException
     */
    public function persistentLazy(
        string $class,
        \Closure $initializer,
        bool $bindInterfaces = true,
        bool $bindParent = true,
    ): static;

    /**
     * @param class-string|class-string[] $aliasClassName
     * @param class-string $resolvedClassName
     *
     * @throws ContainerException
     */
    public function alias(
        string|array $aliasClassName,
        string $resolvedClassName,
    ): static;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $className
     * @param array<array-key, mixed> $arguments
     * @return TClassName
     *
     * @throws ContainerException
     */
    public function resolve(
        string $className,
        array $arguments = [],
    ): object;

    /**
     * @template TReturnType
     *
     * @param \Closure(): TReturnType $callable
     * @param array<array-key, mixed> $arguments
     * @return TReturnType
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
    public function isTransient(
        string $className,
    ): bool;

    /**
     * @param class-string $className
     */
    public function isPersistent(
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
