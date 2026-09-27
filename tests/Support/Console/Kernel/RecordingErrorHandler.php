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

namespace Support\Console\Kernel;

use Tuxxedo\Console\ExitCode;
use Tuxxedo\Console\Kernel\ConsoleErrorHandlerInterface;
use Tuxxedo\Console\Output\ConsoleOutputInterface;

class RecordingErrorHandler implements ConsoleErrorHandlerInterface
{
    public int $callCount = 0;
    public ?\Throwable $lastException = null;
    public ?ExitCode $lastExitCode = null;

    public function __construct(
        private readonly ExitCode $returnedExitCode = ExitCode::FAILURE,
    ) {
    }

    public function handle(
        \Throwable $exception,
        ConsoleOutputInterface $output,
        ExitCode $exitCode,
    ): ExitCode {
        $this->callCount++;
        $this->lastException = $exception;
        $this->lastExitCode = $exitCode;

        return $this->returnedExitCode;
    }
}
