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

namespace Tuxxedo\Serializer;

use Tuxxedo\Exception;

class SerializerException extends Exception
{
    /**
     * @param class-string $serializerClass
     */
    public static function fromValidatorRequired(
        string $serializerClass,
    ): self {
        return new self(
            message: \sprintf(
                'Serializer "%s" validates by default and needs a validator; pass one or set skipValidation to true',
                self::formatShortClass($serializerClass),
            ),
        );
    }

    public static function fromUnencodableValue(
        string $format,
        \Throwable $previous,
    ): self {
        return new self(
            message: \sprintf(
                'Value cannot be encoded as %s: %s',
                $format,
                $previous->getMessage(),
            ),
            previous: $previous,
        );
    }

    /**
     * @param class-string $className
     */
    public static function fromUninstantiableClass(
        string $className,
    ): self {
        return new self(
            message: \sprintf(
                'Class "%s" cannot be instantiated by the serializer',
                $className,
            ),
        );
    }

    /**
     * @param class-string $className
     */
    public static function fromInstantiationFailure(
        string $className,
        \Throwable $previous,
    ): self {
        return new self(
            message: \sprintf(
                'Class "%s" could not be created: %s',
                $className,
                $previous->getMessage(),
            ),
            previous: $previous,
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
                'Circular reference detected while encoding "%s"',
                $className,
            ),
        );
    }

    /**
     * @param class-string $className
     */
    public static function fromInvalidConverter(
        string $className,
        string $property,
        string $converter,
    ): self {
        return new self(
            message: \sprintf(
                'Converter "%s" on "%s::$%s" must implement ValueConverterInterface',
                $converter,
                $className,
                $property,
            ),
        );
    }

    public static function fromCsvCharacter(
        string $option,
        string $value,
    ): self {
        return new self(
            message: \sprintf(
                'CSV %s must be a single character, got "%s"',
                $option,
                $value,
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromCsvBuffer(): self
    {
        return new self(
            message: 'Could not open a memory buffer for CSV',
        );
    }

    /**
     * @param class-string $className
     */
    public static function fromCsvNestedValue(
        string $className,
        string $property,
    ): self {
        return new self(
            message: \sprintf(
                'Property "%s::$%s" holds a list or map, which cannot be written to a CSV cell',
                $className,
                $property,
            ),
        );
    }

    public static function fromInvalidValue(
        string $expected,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid value, expected %s',
                $expected,
            ),
        );
    }
}
