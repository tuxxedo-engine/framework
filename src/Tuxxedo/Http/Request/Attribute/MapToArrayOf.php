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

/**
 * @implements DependencyResolverInterface<array<object>>
 */
#[\Attribute(flags: \Attribute::TARGET_PARAMETER)]
class MapToArrayOf implements DependencyResolverInterface
{
    /**
     * @param class-string<object>|(\Closure(): object)|object|null $className
     */
    public function __construct(
        protected readonly string $name,
        protected readonly string|object|null $className = null,
        protected readonly ?InputContext $context = null,
    ) {
    }

    /**
     * @return class-string
     */
    // @todo Consider generalizing this, might be useful for other abstractions
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
        $request = $container->resolve(RequestInterface::class);
        $context = $request->input($this->context ?? InputContext::fromMethod($request->server->method));

        try {
            return $context->mapToArrayOf(
                name: $this->name,
                className: $this->className ?? $this->getDefaultType($parameter),
            );
        } catch (\Throwable $exception) {
            throw HttpException::fromBadRequest(
                exception: $exception,
            );
        }
    }
}
