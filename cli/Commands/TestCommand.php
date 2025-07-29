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

namespace Cli\Commands;

use Tuxxedo\Console\Attributes\Command;
use Tuxxedo\Console\Attributes\DefaultCommand;
use Tuxxedo\Console\Io\Output;
use Tuxxedo\Console\Io\OutputInterface;

#[Command(name: 'test')]
class TestCommand
{
    #[DefaultCommand]
    public function default(/** @todo InputInterface */): OutputInterface
    {
        return new Output('Hello World');
    }
}
