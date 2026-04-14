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
use Tuxxedo\Http\InputContext;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Reflection\ParameterInterface;

/**
 * @implements DependencyResolverInterface<array<object>>
 */
#[\Attribute(flags: \Attribute::TARGET_PARAMETER)]
class MapToArrayOf implements DependencyResolverInterface
{
    /**
     * @param class-string<object>|(\Closure(): object)|object $className
     */
    public function __construct(
        protected readonly string $name,
        protected readonly string|object $className,
        protected readonly ?InputContext $context = null,
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
        $request = $container->resolve(RequestInterface::class);
        $context = $request->input($this->context ?? InputContext::fromMethod($request->server->method));

        try {
            return $context->mapToArrayOf(
                name: $this->name,
                className: $this->className,
            );
        } catch (\Throwable $exception) {
            throw HttpException::fromBadRequest(
                exception: $exception,
            );
        }
    }
}
