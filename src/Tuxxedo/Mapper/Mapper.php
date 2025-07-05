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
                throw MapperException::fromInvalidProperty(
                    property: $property,
                    className: $instance::class,
                );
            }

            try {
                $reflector->getProperty($property)->setValue(
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
    ): object {
        return $this->mapArrayTo(
            input: \get_object_vars($input),
            className: $className,
        );
    }

    public function mapToArrayOf(
        array $input,
        string|object $className,
    ): array {
        $mapped = [];

        foreach ($input as $value) {
            if (\is_object($value)) {
                $mapped[] = $this->mapObjectTo(
                    input: $value,
                    className: $className,
                );
            } elseif (\is_array($value)) {
                $mapped[] = $this->mapArrayTo(
                    input: $value,
                    className: $className,
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
