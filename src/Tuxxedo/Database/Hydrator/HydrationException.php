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

class HydrationException extends \Exception
{
    /**
     * @param class-string $className
     */
    public static function fromInvalidClass(
        string $className,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot hydrate class "%s": Class does not exist or is not instantiable',
                $className,
            ),
        );
    }

    /**
     * @param class-string $className
     */
    public static function fromMissingProperty(
        string $className,
        string $property,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot hydrate class "%1$s": Property %1$s::\$%2$s does not exist',
                $className,
                $property,
            ),
        );
    }
}
