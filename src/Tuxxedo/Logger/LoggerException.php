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

namespace Tuxxedo\Logger;

class LoggerException extends \Exception
{
    public static function fromUnableToOpenFile(
        string $file,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to initial logger as the log file could not be opened or created: %s',
                $file,
            ),
        );
    }
}
