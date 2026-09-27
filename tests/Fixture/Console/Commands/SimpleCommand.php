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
use Tuxxedo\Console\ExitCode;
use Tuxxedo\Console\Output\OutputInterface;

class SimpleCommand
{
    #[Command(
        name: 'demo:simple',
        description: 'A simple demo command',
    )]
    public function run(
        OutputInterface $output,
    ): ExitCode {
        $output->line('simple');

        return ExitCode::SUCCESS;
    }
}
