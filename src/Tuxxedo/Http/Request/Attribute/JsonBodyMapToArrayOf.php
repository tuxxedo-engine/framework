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

namespace Tuxxedo\Http\Request\Attribute;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Reflection\ParameterInterface;

/**
 * @implements DependencyResolverInterface<array<object>>
 */
#[\Attribute(flags: \Attribute::TARGET_PARAMETER)]
class JsonBodyMapToArrayOf implements DependencyResolverInterface
{
    /**
     * @param class-string<object>|(\Closure(): object)|object $className
     */
    public function __construct(
        protected readonly string|object $className,
        protected readonly int $flags = 0,
    ) {
    }

    /**
     * @return object[]
     *
     * @throws HttpException
     */
    public function resolve(
        ContainerInterface $container,
        ParameterInterface $parameter,
    ): array {
        try {
            return $container->resolve(RequestInterface::class)->body->jsonMapToArrayOf(
                className: $this->className,
                flags: $this->flags,
            );
        } catch (\Throwable $exception) {
            throw HttpException::fromBadRequest(
                exception: $exception,
            );
        }
    }
}
