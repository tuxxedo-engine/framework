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

namespace Fixture\Console\Commands;

use Support\Console\Middleware\RecordingCommandMiddleware;
use Tuxxedo\Console\Attribute\Command;
use Tuxxedo\Console\ExitCode;
use Tuxxedo\Console\Middleware\Attribute\CommandMiddleware;

class WrapperMiddlewareCommand
{
    #[Command('demo:wrapper-mw')]
    #[CommandMiddleware(RecordingCommandMiddleware::class)]
    public function run(): ExitCode
    {
        return ExitCode::SUCCESS;
    }
}
