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

use Tuxxedo\Exception;

class MapperException extends Exception
{
    /**
     * @param class-string $className
     */
    public static function fromInvalidProperty(
        string $property,
        string $className,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid property "%s": property does not exist on class "%s"',
                $property,
                $className,
            ),
        );
    }

    /**
     * @param class-string $className
     */
    public static function fromInvalidType(
        string $property,
        string $type,
        string $expectedType,
        string $className,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid type for property "%s" on class "%s": expected "%s", got "%s"',
                $property,
                $className,
                $expectedType,
                $type,
            ),
        );
    }

    public static function fromInvalidIterable(
        string $type,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid iterable type "%s" supplied to array of',
                $type,
            ),
        );
    }

    /**
     * @param class-string $className
     */
    public static function fromCircularReference(
        string $className,
    ): self {
        return new self(
            message: \sprintf(
                'Circular reference detected while deep mapping class "%s"',
                $className,
            ),
        );
    }
}
