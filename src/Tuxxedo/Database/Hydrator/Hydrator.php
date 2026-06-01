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

namespace Tuxxedo\Database\Hydrator;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Reflection\PropertyReflector;

class Hydrator implements HydratorInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $className
     * @param array<string, mixed> $values
     * @return TClassName
     *
     * @throws HydrationException
     */
    public function hydrate(
        string $className,
        array $values,
    ): object {
        $className = $this->container->resolveName($className);

        try {
            $reflection = new \ReflectionClass($className);

            if (
                $reflection->isAbstract() ||
                $reflection->isInterface() ||
                $reflection->isEnum() ||
                $reflection->isTrait()
            ) {
                throw new \ReflectionException();
            }
        } catch (\ReflectionException) {
            throw HydrationException::fromInvalidClass(
                className: $className,
            );
        }

        if (\is_a($className, HydratableInterface::class, true)) {
            return $this->instantiateFromFactory($className, $values);
        }

        if ($reflection->isReadOnly()) {
            return $this->instantiateFromReflection($className, $values);
        }

        return $this->instantiateFromContainer($className, $values);
    }

    /**
     * @template TClassName of HydratableInterface
     *
     * @param class-string<TClassName> $className
     * @param array<string, mixed> $values
     * @return TClassName
     */
    private function instantiateFromFactory(
        string $className,
        array $values,
    ): object {
        return $className::create($values);
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $className
     * @param array<string, mixed> $values
     * @return TClassName
     *
     * @throws HydrationException
     */
    private function instantiateFromReflection(
        string $className,
        array $values,
    ): object {
        $instance = (new \ReflectionClass($className))->newInstanceWithoutConstructor();

        foreach ($values as $property => $value) {
            try {
                PropertyReflector::createFromObject($instance, $property)->setValue($instance, $value);
            } catch (\ReflectionException) {
                throw HydrationException::fromMissingProperty(
                    className: $className,
                    property: $property,
                );
            }
        }

        return $instance;
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $className
     * @param array<string, mixed> $values
     * @return TClassName
     *
     * @throws HydrationException
     */
    private function instantiateFromContainer(
        string $className,
        array $values,
    ): object {
        $constructorParameters = [];
        $constructor = (new \ReflectionClass($className))->getConstructor();

        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $parameter) {
                $constructorParameters[$parameter->getName()] = true;
            }
        }

        $constructorValues = \array_intersect_key($values, $constructorParameters);
        $leftoverValues = \array_diff_key($values, $constructorParameters);

        $instance = $this->container->resolve($className, $constructorValues);

        foreach ($leftoverValues as $property => $value) {
            try {
                PropertyReflector::createFromObject($instance, $property)->setValue($instance, $value);
            } catch (\ReflectionException) {
                throw HydrationException::fromMissingProperty(
                    className: $className,
                    property: $property,
                );
            }
        }

        return $instance;
    }
}
