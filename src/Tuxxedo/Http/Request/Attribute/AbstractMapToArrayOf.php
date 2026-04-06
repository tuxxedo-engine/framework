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

namespace Tuxxedo\Http\Request\Attribute;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;
use Tuxxedo\Http\InputContext;
use Tuxxedo\Http\Request\RequestInterface;

/**
 * @implements DependencyResolverInterface<array<object>>
 */
abstract class AbstractMapToArrayOf implements DependencyResolverInterface
{
    abstract protected InputContext $context {
        get;
    }

    /**
     * @param class-string<object>|(\Closure(): object)|object $className
     */
    public function __construct(
        protected string $name,
        protected string|object $className,
    ) {
    }

    /**
     * @return object[]
     */
    public function resolve(
        ContainerInterface $container,
        \ReflectionParameter $parameter,
    ): array {
        $context = $container->resolve(RequestInterface::class)->input($this->context);

        return $context->mapToArrayOf(
            name: $this->name,
            className: $this->className,
        );
    }
}
