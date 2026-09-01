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

namespace Tuxxedo\Console\Kernel;

use Tuxxedo\Console\ExitCode;
use Tuxxedo\Console\Output\ConsoleOutputInterface;

interface ConsoleErrorHandlerInterface
{
    public function handle(
        \Throwable $exception,
        ConsoleOutputInterface $output,
        ExitCode $exitCode,
    ): ExitCode;
}
