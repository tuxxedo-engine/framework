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
     * @throws UnresolvableDependencyException
     */
    public function resolve(
        string $className,
    ): object;
}
