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

namespace App\Commands;

use Tuxxedo\Console\Attributes\DefaultCommand;
use Tuxxedo\Console\Attributes\DefaultCommandAction;
use Tuxxedo\Console\Io\InputInterface;

#[DefaultCommand]
class TestCommand
{
    #[DefaultCommandAction]
    public function default(InputInterface $input): void
    {
        $input->stdout->writeLine('Hello World');

        // @todo Return OutputInterface?
    }
}
