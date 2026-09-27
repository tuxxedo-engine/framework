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

use Tuxxedo\Console\Attribute\Command;
use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\ExitCode;

class WrappedExitCodeThrowingCommand
{
    #[Command('demo:wrapped-exit-code')]
    public function run(): ExitCode
    {
        throw new \RuntimeException(
            message: 'outer wrap',
            previous: new ConsoleException(
                exitCode: ExitCode::CONFIG_ERROR,
                message: 'inner console exception',
            ),
        );
    }
}
