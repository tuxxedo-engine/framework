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

namespace Tuxxedo\Router;

class RouterException extends \Exception
{
    public static function fromInvalidClassLikeStructure(
        string $className,
    ): self {
        return new self(
            message: \sprintf(
                '%s must be a class',
                $className,
            ),
        );
    }

    public static function fromNonInstantiableMethod(
        string $className,
        string $method,
    ): self {
        return new self(
            message: \sprintf(
                '%s::%s() must not be static or abstract',
                $className,
                $method,
            ),
        );
    }

    public static function fromEmptyUri(
        string $className,
        string $method,
    ): self {
        return new self(
            message: \sprintf(
                '%s::%s() has an empty URI without an #[Controller] attribute',
                $className,
                $method,
            ),
        );
    }

    /**
     * @param string[] $names
     */
    public static function fromNotAllArgumentNamesAreUnique(
        string $className,
        string $method,
        array $names,
    ): self {
        return new self(
            message: \sprintf(
                '%s::%s() has URI with non-unique arguments: %s',
                $className,
                $method,
                \join(', ', $names),
            ),
        );
    }

    public static function fromNoArgumentAttributeFound(
        string $className,
        string $method,
        string $parameter,
    ): self {
        return new self(
            message: \sprintf(
                '%s::%s() with parameter $%s does not have an argument attribute',
                $className,
                $method,
                $parameter,
            ),
        );
    }

    public static function fromHasNoType(
        string $className,
        string $method,
        string $parameter,
    ): self {
        return new self(
            message: \sprintf(
                '%s::%s() with parameter $%s has no type',
                $className,
                $method,
                $parameter,
            ),
        );
    }

    public static function fromUnsupportedNativeType(
        string $className,
        string $method,
        string $parameter,
        string $type,
    ): self {
        return new self(
            message: \sprintf(
                '%s::%s() with parameter $%s (%s) is not supported',
                $className,
                $method,
                $parameter,
                $type,
            ),
        );
    }

    public static function fromUnsupportedType(
        string $className,
        string $method,
        string $parameter,
    ): self {
        return new self(
            message: \sprintf(
                '%s::%s() with parameter $%s has an unsupported type',
                $className,
                $method,
                $parameter,
            ),
        );
    }

    public static function fromOptionalArgumentHasNoDefaultValue(
        string $className,
        string $method,
        string $parameter,
    ): self {
        return new self(
            message: \sprintf(
                '%s::%s() with parameter $%s has no default value',
                $className,
                $method,
                $parameter,
            ),
        );
    }

    public static function fromInvalidNamedRoute(
        string $name,
    ): self {
        return new self(
            message: \sprintf(
                'Named route "%s" not found',
                $name,
            ),
        );
    }

    public static function fromDuplicateRouteName(
        string $className,
        string $method,
        string $name,
    ): self {
        return new self(
            message: \sprintf(
                '%s::%s() has a route with a non-unique name "%s"',
                $className,
                $method,
                $name,
            ),
        );
    }
}
