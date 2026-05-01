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
        bool $deepMap = false,
    ): object {
        $visited = [];

        return $this->doMapArrayTo(
            input: $input,
            className: $className,
            skipInvalidProperties: $skipInvalidProperties,
            castType: $castType,
            deepMap: $deepMap,
            visited: $visited,
        );
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $className
     * @return TClassName
     *
     * @throws MapperException
     */
    public function mapObjectTo(
        object $input,
        string|object $className,
        bool $skipInvalidProperties = false,
        bool $castType = false,
        bool $deepMap = false,
    ): object {
        $visited = [];

        return $this->doMapObjectTo(
            input: $input,
            className: $className,
            skipInvalidProperties: $skipInvalidProperties,
            castType: $castType,
            deepMap: $deepMap,
            visited: $visited,
        );
    }

    /**
     * @template TClassName of object
     *
     * @param array<mixed> $input
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $className
     * @return TClassName[]
     *
     * @throws MapperException
     */
    public function mapToArrayOf(
        array $input,
        string|object $className,
        bool $skipInvalidProperties = false,
        bool $castType = false,
        bool $deepMap = false,
    ): array {
        $mapped = [];

        foreach ($input as $value) {
            $visited = [];

            if (\is_object($value)) {
                $mapped[] = $this->doMapObjectTo(
                    input: $value,
                    className: $className,
                    skipInvalidProperties: $skipInvalidProperties,
                    castType: $castType,
                    deepMap: $deepMap,
                    visited: $visited,
                );
            } elseif (\is_array($value)) {
                $mapped[] = $this->doMapArrayTo(
                    input: $value,
                    className: $className,
                    skipInvalidProperties: $skipInvalidProperties,
                    castType: $castType,
                    deepMap: $deepMap,
                    visited: $visited,
                );
            } else {
                throw MapperException::fromInvalidIterable(
                    type: \gettype($value),
                );
            }
        }

        return $mapped;
    }

    /**
     * @template TClassName of object
     *
     * @param array<mixed> $input
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $className
     * @param int[] $visited
     * @return TClassName
     */
    private function doMapArrayTo(
        array $input,
        string|object $className,
        bool $skipInvalidProperties,
        bool $castType,
        bool $deepMap,
        array &$visited,
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
                $type = $inputProperty->getType();

                if (
                    $castType &&
                    $type instanceof \ReflectionNamedType &&
                    $type->isBuiltin() &&
                    $type->getName() !== 'object' &&
                    $type->getName() !== 'array'
                ) {
                    \settype($value, $type->getName());
                }

                if (
                    $deepMap &&
                    $type instanceof \ReflectionNamedType &&
                    !$type->isBuiltin()
                ) {
                    /** @var class-string $valueClassName */
                    $valueClassName = $type->getName();

                    if (\is_array($value)) {
                        $value = $this->doMapArrayTo(
                            input: $value,
                            className: $valueClassName,
                            skipInvalidProperties: $skipInvalidProperties,
                            castType: $castType,
                            deepMap: true,
                            visited: $visited,
                        );
                    } elseif (\is_object($value)) {
                        $value = $this->doMapObjectTo(
                            input: $value,
                            className: $valueClassName,
                            skipInvalidProperties: $skipInvalidProperties,
                            castType: $castType,
                            deepMap: true,
                            visited: $visited,
                        );
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

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $className
     * @param int[] $visited
     * @return TClassName
     */
    private function doMapObjectTo(
        object $input,
        string|object $className,
        bool $skipInvalidProperties,
        bool $castType,
        bool $deepMap,
        array &$visited,
    ): object {
        $objectId = \spl_object_id($input);

        if (\in_array($objectId, $visited, true)) {
            throw MapperException::fromCircularReference(
                className: $input::class,
            );
        }

        $visited[] = $objectId;

        return $this->doMapArrayTo(
            input: \get_object_vars($input),
            className: $className,
            skipInvalidProperties: $skipInvalidProperties,
            castType: $castType,
            deepMap: $deepMap,
            visited: $visited,
        );
    }
}
