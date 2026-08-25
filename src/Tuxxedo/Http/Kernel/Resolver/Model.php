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

namespace Tuxxedo\Http\Kernel\Resolver;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\ModelsManagerInterface;
use Tuxxedo\Reflection\ParameterReflectorInterface;
use Tuxxedo\Router\Attribute\ArgumentConsumerInterface;

/**
 * @implements DependencyResolverInterface<object|null>
 */
#[\Attribute(flags: \Attribute::TARGET_PARAMETER)]
class Model implements DependencyResolverInterface, ArgumentConsumerInterface
{
    public readonly array $routeArguments;

    public function __construct(
        private readonly string $argumentName,
    ) {
        $this->routeArguments = [
            $this->argumentName,
        ];
    }

    /**
     * @throws HttpException
     * @throws ModelException
     */
    public function resolve(
        ContainerInterface $container,
        ParameterReflectorInterface $parameter,
    ): ?object {
        $modelClass = $parameter->getDefaultType();

        if ($modelClass === null) {
            if ($parameter->isNullable()) {
                return null;
            }

            throw ModelException::fromInvalidModelClassViaResolver();
        }

        $identifier = $container->resolve(RequestInterface::class)->route->arguments[$this->argumentName] ?? null;

        if ($identifier === null) {
            if ($parameter->isNullable()) {
                return null;
            }

            throw HttpException::fromNotFound();
        }

        $model = $container->resolve(ModelsManagerInterface::class)->findById($modelClass, $identifier);

        if ($model === null && !$parameter->isNullable()) {
            throw HttpException::fromNotFound();
        }

        return $model;
    }
}
