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

namespace Tuxxedo\Http\Stream;

use Tuxxedo\Exception;

class StreamException extends Exception
{
    public static function fromLineBreakInSseField(
        string $field,
    ): self {
        return new self(
            message: \sprintf(
                'Server-sent event field "%s" cannot contain a line break',
                $field,
            ),
        );
    }

    public static function fromUnreadableFile(
        string $path,
    ): self {
        return new self(
            message: \sprintf(
                'File "%s" could not be opened for reading',
                $path,
            ),
        );
    }

    public static function fromUnwritableFile(
        string $path,
    ): self {
        return new self(
            message: \sprintf(
                'File "%s" could not be opened for writing',
                $path,
            ),
        );
    }

    public static function fromClosedStream(): self
    {
        return new self(
            message: 'The stream is closed',
        );
    }

    public static function fromWriteFailure(): self
    {
        return new self(
            message: 'Data could not be written to the stream',
        );
    }

    public static function fromUnseekableStream(): self
    {
        return new self(
            message: 'The stream cannot be rewound',
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromTemporaryStream(): self
    {
        return new self(
            message: 'A temporary stream could not be opened',
        );
    }
}
