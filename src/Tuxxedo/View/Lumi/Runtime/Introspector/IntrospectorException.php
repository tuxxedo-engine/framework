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

namespace Tuxxedo\View\Lumi\Runtime\Introspector;

use Tuxxedo\View\Lumi\LumiException;

class IntrospectorException extends LumiException
{
    public static function fromUnknownFunction(
        string $name,
    ): self {
        return new self(
            message: \sprintf(
                'No function metadata registered for "%s"',
                $name,
            ),
        );
    }

    /**
     * @param class-string $className
     */
    public static function fromFilterWithoutValueParameter(
        string $name,
        string $className,
        string $methodName,
    ): self {
        return new self(
            message: \sprintf(
                'Filter "%s" (%s::%s()) has no parameter to receive the piped value',
                $name,
                self::formatShortClass($className),
                $methodName,
            ),
        );
    }

    public static function fromUnknownFilter(
        string $name,
    ): self {
        return new self(
            message: \sprintf(
                'No filter metadata registered for "%s"',
                $name,
            ),
        );
    }
}
