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
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\InputContext;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Mapper\MapperException;

/**
 * @implements DependencyResolverInterface<array<object>>
 */
abstract class AbstractMapToArrayOf implements DependencyResolverInterface
{
    abstract protected InputContext $context {
        get;
    }

    /**
     * @param class-string<object>|(\Closure(): object)|object|null $className
     */
    public function __construct(
        protected string $name,
        protected string|object|null $className = null,
    ) {
    }

    /**
     * @return class-string
     */
    private function getDefaultType(
        \ReflectionParameter $parameter,
    ): string {
        $type = $parameter->getType();

        if (
            $type instanceof \ReflectionNamedType &&
            !$type->isBuiltin()
        ) {
            /** @var class-string */
            return $type->getName();
        }

        throw HttpException::fromBadRequest();
    }

    /**
     * @return object[]
     *
     * @throws HttpException
     */
    public function resolve(
        ContainerInterface $container,
        \ReflectionParameter $parameter,
    ): array {
        $context = $container->resolve(RequestInterface::class)->input($this->context);

        try {
            return $context->mapToArrayOf(
                name: $this->name,
                className: $this->className ?? $this->getDefaultType($parameter),
            );
        } catch (MapperException) {
            throw HttpException::fromBadRequest();
        }
    }
}
