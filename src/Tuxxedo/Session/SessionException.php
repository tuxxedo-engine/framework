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

namespace Tuxxedo\Session;

class SessionException extends \Exception
{
    public static function fromNotStarted(): self
    {
        return new self(
            message: 'A session must be started to use this functionality',
        );
    }

    public static function fromCannotStart(): self
    {
        return new self(
            message: 'Unable to start session',
        );
    }

    public static function fromCannotStop(): self
    {
        return new self(
            message: 'Unable to stop session',
        );
    }

    public static function fromCannotFetchIdentifier(): self
    {
        return new self(
            message: 'Unable to fetch a valid session identifier',
        );
    }

    public static function fromCannotRegenerateIdentifier(): self
    {
        return new self(
            message: 'Unable to regenerate a valid session identifier',
        );
    }

    /**
     * @param class-string<\UnitEnum> $enum
     */
    public static function fromInvalidEnum(
        string $name,
        string $enum,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to fetch "%s", enum is invalid "%s" or not suitable for this value',
                $name,
                $enum,
            ),
        );
    }
}
