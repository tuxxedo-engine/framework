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

namespace Tuxxedo\Validator;

class ValidatorException extends \Exception
{
    public static function fromRecursionDepthExceeded(
        string $path,
        int $limit,
    ): self {
        return new self(
            message: \sprintf(
                'Validation cascade exceeded the maximum recursion depth of %d at path "%s"',
                $limit,
                $path,
            ),
        );
    }
}
