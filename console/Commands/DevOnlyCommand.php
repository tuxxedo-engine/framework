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

namespace Console\Commands;

use Console\Middleware\DevelopmentOnly;
use Tuxxedo\Console\Attribute\Command;
use Tuxxedo\Console\ExitCode;
use Tuxxedo\Console\Middleware\Attribute\CommandMiddleware;
use Tuxxedo\Console\Output\OutputInterface;

class DevOnlyCommand
{
    #[Command('demo:dev-only')]
    #[CommandMiddleware(DevelopmentOnly::class)]
    public function run(
        OutputInterface $output,
    ): ExitCode {
        $output->success('demo:dev-only ran successfully');
        $output->line();

        return ExitCode::SUCCESS;
    }
}
