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

namespace Tuxxedo\Container\Resolver;

use Tuxxedo\Container\ContainerException;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;
use Tuxxedo\Reflection\ParameterReflectorInterface;

/**
 * @implements DependencyResolverInterface<object>
 */
#[\Attribute(flags: \Attribute::TARGET_PARAMETER)]
class Lazy implements DependencyResolverInterface
{
    /**
     * @throws ContainerException
     */
    public function resolve(
        ContainerInterface $container,
        ParameterReflectorInterface $parameter,
    ): object {
        $type = $parameter->getDefaultType();

        if ($type === null) {
            throw ContainerException::fromNonScalarLazyGhost(
                parameter: $parameter->name,
            );
        }

        /** @var \ReflectionClass<object> $class */
        $class = new \ReflectionClass($container->resolveName($type));

        return $class->newLazyGhost(
            static function (object $object) use ($container): void {
                if (\method_exists($object, '__construct')) {
                    $container->call($object->__construct(...));
                }
            },
        );
    }
}
