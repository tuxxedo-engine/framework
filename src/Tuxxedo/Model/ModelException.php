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
                'Invalid model class "%s": Property %1$s::\$%s has more than one #[Column] attribute',
                $modelClass,
                $property,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromModelMayOnlyHaveOneKey(
        string $modelClass,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Model defines both a #[PrimaryKey] and #[CompositeKey] but these are mutually exclusive',
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromDuplicatePrimaryKey(
        string $modelClass,
        string $property,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": Models may only define one #[PrimaryKey] (duplicate declared at %1$s::\$%s)',
                $modelClass,
                $property,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromNoPrimaryKeyOrCompositeKey(
        string $modelClass,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot perform action on model "%s", as the model does not have a #[PrimaryKey] or a #[CompositeKey]',
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromPropertyValueMustBeScalar(
        string $modelClass,
        string $property,
        string $actualType,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot perform action on model "%s": The property value for "%s" must be a scalar, but "%s" was given',
                $modelClass,
                $property,
                $actualType,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromPropertyValueMustBeIdentifierType(
        string $modelClass,
        string $property,
        string $actualType,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot perform action on model "%s": The property value for "%s" must be string|int, but "%s" was given',
                $modelClass,
                $property,
                $actualType,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromCantFetchWithoutPrimaryKey(
        string $modelClass,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot fetch model "%s", as the model does not have a #[PrimaryKey]',
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromCantFetchWithoutCompositeKey(
        string $modelClass,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot fetch model "%s", as the model does not have a #[CompositeKey]',
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromModelNoLongerExists(
        string $modelClass,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot fetch model "%s", as the record no longer exists',
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromModelNotFound(
        string $modelClass,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot fetch model "%s", as the record does not exist',
                $modelClass,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromCompositeKeyReferencesUnknownColumn(
        string $modelClass,
        string $column,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid model class "%s": #[CompositeKey] references column "%s" which does not exist on the model',
                $modelClass,
                $column,
            ),
        );
    }

    /**
     * @param class-string $modelClass
     */
    public static function fromNullValueOnNonNullableColumn(
        string $modelClass,
        string $property,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot perform action on model "%s": A NULL value was supplied to property "%s", but it is not marked as nullable',
                $modelClass,
                $property,
            ),
        );
    }
}
