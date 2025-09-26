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

namespace Tuxxedo\Container;

class UnresolvableDependencyException extends \Exception
{
    protected static function formatNamedType(
        \ReflectionNamedType $type,
    ): string {
        return $type->allowsNull()
            ? '?' . $type->getName()
            : $type->getName();
    }

    protected static function formatUnionType(
        \ReflectionUnionType $unionType,
    ): string {
        return \implode(
            '|',
            \array_map(
                static fn (\ReflectionIntersectionType|\ReflectionNamedType $type): string => match (true) {
                    $type instanceof \ReflectionIntersectionType => self::formatIntersectionType($type),
                    $type instanceof \ReflectionNamedType => self::formatNamedType($type),
                },
                $unionType->getTypes(),
            ),
        );
    }

    protected static function formatIntersectionType(
        \ReflectionIntersectionType $intersectionType,
    ): string {
        return '(' . \implode('&', \array_map(\strval(...), $intersectionType->getTypes())) . ')';
    }

    /**
     * @param class-string<DependencyResolverInterface<mixed>> $attributeClass
     */
    public static function fromAttributeException(
        string $attributeClass,
        \Exception $exception,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to resolve dependency via attribute "%s", exception thrown with message: "%s"',
                $attributeClass,
                $exception->getMessage(),
            ),
        );
    }

    public static function fromUnresolvableType(): self
    {
        return new self(
            message: 'Unable to resolve dependency by attribute or type information',
        );
    }

    public static function fromNamedType(
        \ReflectionNamedType $type,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to resolve the type: "%s"',
                self::formatNamedType($type),
            ),
        );
    }

    public static function fromUnionType(
        \ReflectionUnionType $unionType,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to resolve any of the union types: "%s"',
                self::formatUnionType($unionType),
            ),
        );
    }

    public static function fromIntersectionType(): self
    {
        return new self(
            message: 'Unable to resolve dependency; intersection types is not supported',
        );
    }
}
