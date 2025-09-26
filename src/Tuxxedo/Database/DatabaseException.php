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

namespace Tuxxedo\Database;

class DatabaseException extends \Exception
{
    public static function fromUnknownNamedConnection(
        string $name,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to find connection: Named connection "%s" was not registered',
                $name,
            ),
        );
    }

    public static function fromNoDefaultConnectionAvailable(): self
    {
        return new self(
            message: 'Unable to find connection: No default connection is available',
        );
    }

    public static function fromNoDefaultReadConnectionAvailable(): self
    {
        return new self(
            message: 'Unable to find connection: No default read connection is available',
        );
    }

    public static function fromNoDefaultWriteConnectionAvailable(): self
    {
        return new self(
            message: 'Unable to find connection: No default write connection is available',
        );
    }
}
