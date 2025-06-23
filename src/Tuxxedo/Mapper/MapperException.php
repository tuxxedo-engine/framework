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

class MapperException extends \Exception
{
    public static function fromInvalidProperty(
        string $property,
        string $className,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid property "%s", property does not exist on class "%s"',
                $property,
                $className,
            ),
        );
    }

    public static function fromInvalidType(
        string $property,
        string $type,
        string $expectedType,
        string $className,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid type for property "%s" (%s), expected "%s" on class "%s"',
                $property,
                $type,
                $expectedType,
                $className,
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
}
