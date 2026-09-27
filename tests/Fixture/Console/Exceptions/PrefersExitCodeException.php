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

namespace Fixture\Console\Exceptions;

use Tuxxedo\Console\ExitCode;
use Tuxxedo\Console\PrefersExitCodeInterface;

class PrefersExitCodeException extends \RuntimeException implements PrefersExitCodeInterface
{
    public function __construct(
        public readonly ?ExitCode $exitCode = ExitCode::CONFIG_ERROR,
        string $message = 'preferred exit code',
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            previous: $previous,
        );
    }
}
