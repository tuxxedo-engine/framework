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

namespace Tuxxedo\Http\Response\Stream;

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
}
