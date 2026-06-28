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

use Tuxxedo\Container\ContainerException;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;
use Tuxxedo\Env\EnvException;
use Tuxxedo\Env\EnvInterface;
use Tuxxedo\Reflection\ParameterReflectorInterface;

/**
 * @implements DependencyResolverInterface<mixed>
 */
#[\Attribute(flags: \Attribute::TARGET_PARAMETER)]
class Env implements DependencyResolverInterface
{
    public function __construct(
        private readonly string $key,
        private readonly mixed $default = null,
    ) {
    }

    public function resolve(
        ContainerInterface $container,
        ParameterReflectorInterface $parameter,
    ): mixed {
        try {
            $env = $container->resolve(EnvInterface::class);
        } catch (ContainerException $exception) {
            throw EnvException::fromUnboundEnv(
                previous: $exception,
            );
        }

        if (!$env->has($this->key)) {
            if ($this->default !== null) {
                return $this->default;
            }

            if ($parameter->isNullable()) {
                return null;
            }

            throw EnvException::fromMissingKey(
                key: $this->key,
            );
        }

        $builtinType = $parameter->getBuiltinType();
        $classType = $parameter->getDefaultType();

        return match (true) {
            $builtinType === 'string' => $env->string($this->key),
            $builtinType === 'int' => $env->int($this->key),
            $builtinType === 'bool' => $env->bool($this->key),
            $builtinType === 'float' => $env->float($this->key),
            $classType !== null && \is_subclass_of($classType, \UnitEnum::class) => $env->enum($this->key, $classType),
            default => throw EnvException::fromUnsupportedParameterType(
                key: $this->key,
                type: $builtinType ?? $classType,
            ),
        };
    }
}
