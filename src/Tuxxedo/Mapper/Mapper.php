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

namespace Tuxxedo\Mapper;

class Mapper implements MapperInterface
{
    /**
     * @template TClassName of object
     *
     * @param array<mixed> $input
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $className
     * @return TClassName
     *
     * @throws MapperException
     */
    public function mapArrayTo(
        array $input,
        string|object $className,
        bool $skipInvalidProperties = false,
        bool $castType = false,
    ): object {
        if ($className instanceof \Closure) {
            /** @var TClassName $instance */
            $instance = $className();
        } elseif (\is_object($className)) {
            $instance = clone $className;
        } else {
            $instance = new $className();
        }

        $reflector = new \ReflectionObject($instance);

        foreach ($input as $property => $value) {
            if (!$reflector->hasProperty($property)) {
                if ($skipInvalidProperties) {
                    continue;
                }

                throw MapperException::fromInvalidProperty(
                    property: $property,
                    className: $instance::class,
                );
            }

            try {
                $inputProperty = $reflector->getProperty($property);

                if ($castType) {
                    $type = $inputProperty->getType();

                    if (
                        $type instanceof \ReflectionNamedType &&
                        $type->isBuiltin() &&
                        $type->getName() !== 'object' &&
                        $type->getName() !== 'array'
                    ) {
                        \settype($value, $type->getName());
                    }
                }

                $inputProperty->setValue(
                    objectOrValue: $instance,
                    value: $value,
                );
            } catch (\TypeError) {
                throw MapperException::fromInvalidType(
                    property: $property,
                    type: \gettype($value),
                    expectedType: (string) $reflector->getProperty($property)->getType(),
                    className: $instance::class,
                );
            }
        }

        return $instance;
    }

    public function mapObjectTo(
        object $input,
        string|object $className,
        bool $skipInvalidProperties = false,
        bool $castType = false,
    ): object {
        return $this->mapArrayTo(
            input: \get_object_vars($input),
            className: $className,
            skipInvalidProperties: $skipInvalidProperties,
            castType: $castType,
        );
    }

    public function mapToArrayOf(
        array $input,
        string|object $className,
        bool $skipInvalidProperties = false,
        bool $castType = false,
    ): array {
        $mapped = [];

        foreach ($input as $value) {
            if (\is_object($value)) {
                $mapped[] = $this->mapObjectTo(
                    input: $value,
                    className: $className,
                    skipInvalidProperties: $skipInvalidProperties,
                    castType: $castType,
                );
            } elseif (\is_array($value)) {
                $mapped[] = $this->mapArrayTo(
                    input: $value,
                    className: $className,
                    skipInvalidProperties: $skipInvalidProperties,
                    castType: $castType,
                );
            } else {
                throw MapperException::fromInvalidIterable(
                    type: \gettype($value),
                );
            }
        }

        return $mapped;
    }
}
