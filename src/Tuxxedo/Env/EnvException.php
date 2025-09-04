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

namespace Tuxxedo\Env;

class EnvException extends \Exception
{
    public static function fromInvalidVariable(
        string $variable,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid environment variable "%s"',
                $variable,
            ),
        );
    }
}
