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

namespace Tuxxedo\Logger;

use Tuxxedo\Exception;

class LoggerException extends Exception
{
    public static function fromUnableToOpenFile(
        string $file,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to initialize logger, the log file "%s" could not be opened or created',
                $file,
            ),
        );
    }
}
