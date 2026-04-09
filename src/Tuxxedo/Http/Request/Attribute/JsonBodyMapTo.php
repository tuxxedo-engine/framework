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
use Tuxxedo\Container\Reflection\ParameterInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\RequestInterface;

/**
 * @implements DependencyResolverInterface<object>
 */
#[\Attribute(flags: \Attribute::TARGET_PARAMETER)]
class JsonBodyMapTo implements DependencyResolverInterface
{
    /**
     * @param class-string<object>|(\Closure(): object)|object|null $className
     */
    public function __construct(
        protected readonly string|object|null $className = null,
        protected readonly int $flags = 0,
    ) {
    }

    /**
     * @throws HttpException
     */
    public function resolve(
        ContainerInterface $container,
        ParameterInterface $parameter,
    ): object {
        try {
            return $container->resolve(RequestInterface::class)->body->jsonMapTo(
                className: $this->className ?? $parameter->getDefaultType() ?? throw HttpException::fromInternalServerError(),
                flags: $this->flags,
            );
        } catch (\Throwable $exception) {
            throw HttpException::fromBadRequest(
                exception: $exception,
            );
        }
    }
}
