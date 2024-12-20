<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2024 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Config;

class ConfigException extends \Exception
{
    public static function fromInvalidDirective(string $directive): self
    {
        return new self(
            message: \sprintf(
                'Invalid directive "%s"',
                $directive,
            ),
        );
    }

    public static function fromUnexpectedDirectiveType(
        string $directive,
        string $actualType,
        string $expectedType,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected type for directive "%s", got type "%s" but expected "%s"',
                $directive,
                $actualType,
                $expectedType,
            ),
        );
    }
}
