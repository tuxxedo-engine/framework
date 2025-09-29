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
    public function __construct(
        #[\SensitiveParameter] string $message,
        #[\SensitiveParameter] public readonly string $sqlState = '00000',
    ) {
        parent::__construct(
            message: $message,
        );
    }

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

    public static function fromNoReadConnectionAvailable(): self
    {
        return new self(
            message: 'Unable to find connection: No read connection is available',
        );
    }

    public static function fromNoWriteConnectionAvailable(): self
    {
        return new self(
            message: 'Unable to find connection: No write connection is available',
        );
    }

    public static function fromCannotInitializeNativeDriver(): self
    {
        return new self(
            message: 'Unable to initialize native driver from PHP',
        );
    }

    public static function fromCannotConnect(
        string|int $code,
        string $error,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot connect to database: %s (code: %s)',
                $error,
                $code,
            ),
        );
    }

    public static function fromError(
        string $sqlState,
        string|int $code,
        string $error,
    ): self {
        return new self(
            message: \sprintf(
                'Database error: %s (code: %s, sql state: %s)',
                $error,
                $code,
                $sqlState,
            ),
            sqlState: $sqlState,
        );
    }

    // @todo Handle this better
    public static function fromResultTooBig(): self
    {
        return new self(
            message: 'The returned result is too big to handle',
        );
    }

    public static function fromEmptyResultSet(): self
    {
        return new self(
            message: 'Cannot fetch from this result set as it is empty',
        );
    }

    // @todo Handle this better with a better error message
    public static function fromCannotFetch(): self
    {
        return new self(
            message: 'Cannot fetch row from result set',
        );
    }
}
