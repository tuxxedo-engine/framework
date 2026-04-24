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

namespace Tuxxedo\Model;

class ModelException extends \Exception
{
    /**
     * @param class-string $modelClass
     */
    public static function fromInvalidModelClass(
        string $modelClass,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Class does not exist or is not a non-abstract class',
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromMissingTableAttribute(
        string $modelClass,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Model is missing the #[Table] attribute',
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromHasNoColumns(
        string $modelClass,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Model does not have any #[Column] attributes',
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromPropertyMayOnlyHaveOneColumn(
        string $modelClass,
        string $property,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Property \$%s has more than one #[Column] attribute',
                $modelClass,
                $property,
            ),
        );
    }
}
